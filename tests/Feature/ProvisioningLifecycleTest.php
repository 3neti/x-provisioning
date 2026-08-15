<?php

declare(strict_types=1);

use LBHurtado\XProvisioning\Actions\AcceptProvisioningOffer;
use LBHurtado\XProvisioning\Actions\ApproveProvisioningRequest;
use LBHurtado\XProvisioning\Actions\CreateProvisioningRequest;
use LBHurtado\XProvisioning\Actions\IssueProvisioningOffer;
use LBHurtado\XProvisioning\Actions\SubmitProvisioningRequest;
use LBHurtado\XProvisioning\Contracts\ProvisioningActivatorContract;
use LBHurtado\XProvisioning\Enums\ProvisioningProfile;
use LBHurtado\XProvisioning\Enums\ProvisioningRequestStatus;
use LBHurtado\XProvisioning\Models\ProvisioningAcceptance;
use LBHurtado\XProvisioning\Models\ProvisioningEvent;
use LBHurtado\XProvisioning\Models\ProvisioningOffer;
use LBHurtado\XProvisioning\Models\ProvisioningRevision;
use LBHurtado\XProvisioning\Tests\Fixtures\User;

it('requires an independent checker and activates an unknown candidate after verified acceptance', function (): void {
    $maker = User::query()->create(['name' => 'Maker']);
    $checker = User::query()->create(['name' => 'Checker']);

    app()->bind(ProvisioningActivatorContract::class, fn (): ProvisioningActivatorContract => new class implements ProvisioningActivatorContract
    {
        public function activate(
            ProvisioningRevision $revision,
            ProvisioningAcceptance $acceptance,
        ): string {
            return 'authority:'.$revision->snapshot_hash.':'.$acceptance->candidate_reference;
        }
    });

    $request = app(CreateProvisioningRequest::class)->handle(
        profile: ProvisioningProfile::TreasuryMaker,
        snapshot: ['role' => 'Treasury Maker', 'scope' => ['account_grants.request']],
        maker: $maker,
        commissioning: true,
    );

    expect($request->subject_reference)->toBeNull();

    app(SubmitProvisioningRequest::class)->handle($request, $maker);

    expect(fn () => app(ApproveProvisioningRequest::class)->handle($request, $maker))
        ->toThrow(DomainException::class, 'independent');

    app(ApproveProvisioningRequest::class)->handle($request, $checker);
    $credential = app(IssueProvisioningOffer::class)->handle($request);

    expect($credential->claimToken)->toHaveLength(64)
        ->and(ProvisioningOffer::query()->firstOrFail()->getRawOriginal('claim_token_hash'))
        ->not->toBe($credential->claimToken);

    $offer = app(AcceptProvisioningOffer::class)->handle(
        claimToken: $credential->claimToken,
        candidateType: 'user',
        candidateReference: 'candidate-42',
        evidence: [
            'name' => 'Candidate',
            'email' => 'candidate@example.test',
            'mobile' => '639171234567',
            'otp' => 'verified',
            'responsibility_attestation' => 'accepted',
        ],
    );

    expect($offer->status)->toBe(ProvisioningRequestStatus::Activated)
        ->and($offer->request->refresh()->subject_reference)->toBe('candidate-42')
        ->and($offer->activation_reference)->toContain('candidate-42')
        ->and(ProvisioningEvent::query()->orderBy('id')->pluck('event_type')->all())->toBe([
            'provisioning.request.created',
            'provisioning.request.submitted',
            'provisioning.request.approved',
            'provisioning.offer.issued',
            'provisioning.offer.accepted',
            'provisioning.acceptance.activated',
        ]);
});

it('fails closed when required claim evidence is absent', function (): void {
    $maker = User::query()->create(['name' => 'Maker']);
    $checker = User::query()->create(['name' => 'Checker']);
    $request = app(CreateProvisioningRequest::class)->handle(
        ProvisioningProfile::AccountInvitation,
        ['role' => 'Account holder'],
        $maker,
    );
    app(SubmitProvisioningRequest::class)->handle($request, $maker);
    app(ApproveProvisioningRequest::class)->handle($request, $checker);
    $credential = app(IssueProvisioningOffer::class)->handle($request);

    expect(fn () => app(AcceptProvisioningOffer::class)->handle(
        $credential->claimToken,
        'user',
        'candidate-1',
        ['name' => 'Candidate'],
    ))->toThrow(DomainException::class, 'email, mobile, otp');

    expect($credential->offer->refresh()->status)->toBe(ProvisioningRequestStatus::Offered)
        ->and($credential->offer->acceptance()->exists())->toBeFalse();
});
