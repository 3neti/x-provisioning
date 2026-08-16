<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use LBHurtado\XProvisioning\Actions\AcceptProvisioningOffer;
use LBHurtado\XProvisioning\Actions\ActivateProvisioningAcceptance;
use LBHurtado\XProvisioning\Actions\ApproveProvisioningRequest;
use LBHurtado\XProvisioning\Actions\CreateProvisioningRequest;
use LBHurtado\XProvisioning\Actions\IssueProvisioningOffer;
use LBHurtado\XProvisioning\Actions\SubmitProvisioningRequest;
use LBHurtado\XProvisioning\Contracts\CommercialRecipientDesignationAuthorityFactoryContract;
use LBHurtado\XProvisioning\Contracts\ProvisioningActivatorContract;
use LBHurtado\XProvisioning\Data\CommercialRecipientDesignationAuthorityData;
use LBHurtado\XProvisioning\Data\CommercialRecipientDesignationData;
use LBHurtado\XProvisioning\Enums\CommercialSettlementAccountBinding;
use LBHurtado\XProvisioning\Enums\CommercialSettlementDisposition;
use LBHurtado\XProvisioning\Enums\ProvisioningActivationMode;
use LBHurtado\XProvisioning\Enums\ProvisioningProfile;
use LBHurtado\XProvisioning\Models\ProvisioningAcceptance;
use LBHurtado\XProvisioning\Models\ProvisioningRequest;
use LBHurtado\XProvisioning\Models\ProvisioningRevision;
use LBHurtado\XProvisioning\Tests\Fixtures\User;

it('fails before persistence when a commercial recipient designation is partial', function (): void {
    $maker = User::query()->create(['name' => 'Maker']);

    expect(fn () => app(CreateProvisioningRequest::class)->handle(
        profile: ProvisioningProfile::CommercialRecipientDesignation,
        snapshot: [
            'counterparty_reference' => 'counterparty:3neti',
            'commercial_role' => 'service_aggregator',
        ],
        maker: $maker,
    ))->toThrow(DomainException::class, 'is required');

    expect(ProvisioningRequest::query()->count())->toBe(0);
});

it('produces an immutable typed authority envelope only after independent activation', function (): void {
    $maker = User::query()->create(['name' => 'Maker']);
    $approvalChecker = User::query()->create(['name' => 'Approval Checker']);
    $activationChecker = User::query()->create(['name' => 'Activation Checker']);
    $representative = User::query()->create(['name' => '3neti Representative']);
    app()->bind(ProvisioningActivatorContract::class, fn (): ProvisioningActivatorContract => new class implements ProvisioningActivatorContract
    {
        public function activate(
            ProvisioningRevision $revision,
            ProvisioningAcceptance $acceptance,
            ?Model $checker = null,
        ): string {
            return 'commercial-designation:'.$revision->snapshot_hash;
        }
    });

    $request = app(CreateProvisioningRequest::class)->handle(
        profile: ProvisioningProfile::CommercialRecipientDesignation,
        snapshot: designationSnapshot(),
        maker: $maker,
        activationMode: ProvisioningActivationMode::ReviewRequired,
    );
    app(SubmitProvisioningRequest::class)->handle($request, $maker);
    app(ApproveProvisioningRequest::class)->handle($request, $approvalChecker);
    $credential = app(IssueProvisioningOffer::class)->handle($request);
    $accepted = app(AcceptProvisioningOffer::class)->handle(
        claimToken: $credential->claimToken,
        candidateType: $representative->getMorphClass(),
        candidateReference: (string) $representative->getKey(),
        evidence: [
            'representative' => 'verified',
            'authority' => 'accepted',
            'agreement' => 'accepted',
            'private_tax_document' => 'must-not-appear',
        ],
    );
    $factory = app(CommercialRecipientDesignationAuthorityFactoryContract::class);

    expect(fn () => $factory->fromActivatedOffer($accepted))
        ->toThrow(DomainException::class, 'independently activated');

    $activated = app(ActivateProvisioningAcceptance::class)->handle($accepted, $activationChecker);
    $authority = $factory->fromActivatedOffer($activated);
    $payload = $authority->toArray();
    $encoded = json_encode($payload, JSON_THROW_ON_ERROR);

    expect($authority)->toBeInstanceOf(CommercialRecipientDesignationAuthorityData::class)
        ->and($authority->authorityHash())->toHaveLength(64)
        ->and($payload['schema'])->toBe(CommercialRecipientDesignationAuthorityData::Schema)
        ->and($payload['designation']['counterparty_reference'])->toBe('counterparty:3neti')
        ->and($payload['designation']['component_scope'])->toBe([
            'inputs.fields.kyc',
            'inputs.fields.otp',
        ])
        ->and($payload['designation']['agreement_reference'])->toBe('agreement:institution-3neti:v1')
        ->and($payload['designation']['tax_profile_reference'])->toBe('tax-profile:3neti:ph:v1')
        ->and($payload['designation']['settlement_disposition'])->toBe('retain_payable')
        ->and($payload['designation']['settlement_account_reference'])->toBeNull()
        ->and($payload['designation'])->not->toHaveKey('settlement_principal_reference')
        ->and($payload['accepted_snapshot_hash'])->toBe($activated->revision->snapshot_hash)
        ->and($payload['acceptance_evidence_hash'])->toBe($activated->acceptance->evidence_hash)
        ->and($payload['activated_by']['reference'])->toBe((string) $activationChecker->getKey())
        ->and($encoded)->not->toContain('private_tax_document')
        ->and($encoded)->not->toContain('must-not-appear');
});

it('requires explicit Account and principal bindings for automatic internal settlement', function (): void {
    expect(fn () => new CommercialRecipientDesignationData(
        counterpartyReference: 'counterparty:3neti',
        commercialRole: 'service_aggregator',
        componentScope: ['inputs.fields.otp'],
        agreementReference: 'agreement:institution-3neti:v1',
        settlementDesignationReference: 'settlement-designation:3neti:v1',
        taxProfileReference: null,
        effectiveFrom: '2026-08-16T00:00:00+08:00',
        settlementDisposition: CommercialSettlementDisposition::InternalAccountCredit,
        settlementAccountReference: 'wallet:account-uuid',
    ))->toThrow(DomainException::class, 'requires Account and principal references');

    $designation = new CommercialRecipientDesignationData(
        counterpartyReference: 'counterparty:3neti',
        commercialRole: 'service_aggregator',
        componentScope: ['inputs.fields.otp'],
        agreementReference: 'agreement:institution-3neti:v1',
        settlementDesignationReference: 'settlement-designation:3neti:v1',
        taxProfileReference: null,
        effectiveFrom: '2026-08-16T00:00:00+08:00',
        settlementDisposition: CommercialSettlementDisposition::InternalAccountCredit,
        settlementAccountReference: 'wallet:account-uuid',
        settlementPrincipalReference: 'principal:3neti',
    );

    expect(CommercialRecipientDesignationData::fromArray($designation->toArray())->toArray())
        ->toBe($designation->toArray());
});

it('can bind internal settlement to the independently accepted candidate Account', function (): void {
    $designation = new CommercialRecipientDesignationData(
        counterpartyReference: 'counterparty:3neti',
        commercialRole: 'service_aggregator',
        componentScope: ['inputs.fields.otp'],
        agreementReference: 'agreement:institution-3neti:v1',
        settlementDesignationReference: 'designation:commissioning:3neti:v2',
        taxProfileReference: null,
        effectiveFrom: '2026-08-16T00:00:00+08:00',
        settlementDisposition: CommercialSettlementDisposition::InternalAccountCredit,
        settlementAccountBinding: CommercialSettlementAccountBinding::AcceptedCandidateAccount,
        supersedesDesignationReference: 'designation:commissioning:3neti:v1',
    );

    expect($designation->toArray())
        ->toMatchArray([
            'settlement_account_binding' => 'accepted_candidate_account',
            'supersedes_designation_reference' => 'designation:commissioning:3neti:v1',
            'settlement_account_reference' => null,
        ])
        ->not->toHaveKey('settlement_principal_reference')
        ->and(CommercialRecipientDesignationData::fromArray($designation->toArray())->toArray())
        ->toBe($designation->toArray());
});

it('rejects preselected Account evidence for candidate-bound settlement', function (): void {
    expect(fn () => new CommercialRecipientDesignationData(
        counterpartyReference: 'counterparty:3neti',
        commercialRole: 'service_aggregator',
        componentScope: ['inputs.fields.otp'],
        agreementReference: 'agreement:institution-3neti:v1',
        settlementDesignationReference: 'designation:commissioning:3neti:v2',
        taxProfileReference: null,
        effectiveFrom: '2026-08-16T00:00:00+08:00',
        settlementDisposition: CommercialSettlementDisposition::InternalAccountCredit,
        settlementAccountReference: 'wallet:preselected',
        settlementAccountBinding: CommercialSettlementAccountBinding::AcceptedCandidateAccount,
    ))->toThrow(DomainException::class, 'cannot name an Account or principal before acceptance');
});

it('keeps ordinary provisioning profiles backward compatible', function (): void {
    $maker = User::query()->create(['name' => 'Maker']);

    $request = app(CreateProvisioningRequest::class)->handle(
        profile: ProvisioningProfile::AccountInvitation,
        snapshot: ['role' => 'Account holder'],
        maker: $maker,
    );

    expect($request->profile)->toBe(ProvisioningProfile::AccountInvitation)
        ->and($request->revisions->first()->snapshot['role'])->toBe('Account holder');
});

/** @return array<string, mixed> */
function designationSnapshot(): array
{
    return [
        'counterparty_reference' => 'counterparty:3neti',
        'commercial_role' => 'service_aggregator',
        'component_scope' => [
            'inputs.fields.otp',
            'inputs.fields.kyc',
        ],
        'agreement_reference' => 'agreement:institution-3neti:v1',
        'settlement_designation_reference' => 'settlement-designation:3neti:v1',
        'tax_profile_reference' => 'tax-profile:3neti:ph:v1',
        'settlement_disposition' => 'retain_payable',
        'settlement_account_reference' => null,
        'settlement_principal_reference' => null,
        'effective_from' => '2026-08-16T00:00:00+08:00',
        'effective_until' => null,
    ];
}
