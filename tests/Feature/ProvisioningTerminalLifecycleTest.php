<?php

declare(strict_types=1);

use LBHurtado\XProvisioning\Actions\AcceptProvisioningOffer;
use LBHurtado\XProvisioning\Actions\ActivateProvisioningAcceptance;
use LBHurtado\XProvisioning\Actions\ApproveProvisioningRequest;
use LBHurtado\XProvisioning\Actions\CreateProvisioningRequest;
use LBHurtado\XProvisioning\Actions\ExpireProvisioningOffer;
use LBHurtado\XProvisioning\Actions\IssueProvisioningOffer;
use LBHurtado\XProvisioning\Actions\RejectProvisioningRequest;
use LBHurtado\XProvisioning\Actions\RevokeProvisioningAcceptance;
use LBHurtado\XProvisioning\Actions\SubmitProvisioningRequest;
use LBHurtado\XProvisioning\Actions\WithdrawProvisioningRequest;
use LBHurtado\XProvisioning\Contracts\ProvisioningActivatorContract;
use LBHurtado\XProvisioning\Contracts\ProvisioningRevokerContract;
use LBHurtado\XProvisioning\Enums\ProvisioningActivationMode;
use LBHurtado\XProvisioning\Enums\ProvisioningProfile;
use LBHurtado\XProvisioning\Enums\ProvisioningRequestStatus;
use LBHurtado\XProvisioning\Models\ProvisioningAcceptance;
use LBHurtado\XProvisioning\Models\ProvisioningEvent;
use LBHurtado\XProvisioning\Models\ProvisioningRequest;
use LBHurtado\XProvisioning\Models\ProvisioningRevision;
use LBHurtado\XProvisioning\Tests\Fixtures\User;

function submittedProvisioning(User $maker): ProvisioningRequest
{
    $request = app(CreateProvisioningRequest::class)->handle(
        ProvisioningProfile::CommercialMaker,
        ['purpose' => 'Terminal lifecycle test'],
        $maker,
        activationMode: ProvisioningActivationMode::ReviewRequired,
    );
    app(SubmitProvisioningRequest::class)->handle($request, $maker);

    return $request;
}

it('allows an independent checker to reject and prevents the maker from rejecting', function (): void {
    $maker = User::query()->create(['name' => 'Maker']);
    $checker = User::query()->create(['name' => 'Checker']);
    $request = submittedProvisioning($maker);

    expect(fn () => app(RejectProvisioningRequest::class)->handle($request, $maker, 'Not independent.'))
        ->toThrow(DomainException::class, 'independent');

    $revision = app(RejectProvisioningRequest::class)->handle($request, $checker, 'Evidence scope is incomplete.');

    expect($revision->status)->toBe(ProvisioningRequestStatus::Rejected)
        ->and($request->refresh()->status)->toBe(ProvisioningRequestStatus::Rejected)
        ->and($revision->rejection_reason)->toBe('Evidence scope is incomplete.');
});

it('allows only the maker to withdraw before an offer is issued', function (): void {
    $maker = User::query()->create(['name' => 'Maker']);
    $other = User::query()->create(['name' => 'Other']);
    $request = submittedProvisioning($maker);

    expect(fn () => app(WithdrawProvisioningRequest::class)->handle($request, $other, 'Wrong actor.'))
        ->toThrow(DomainException::class, 'Only the maker');

    $withdrawn = app(WithdrawProvisioningRequest::class)->handle($request, $maker, 'Role no longer required.');

    expect($withdrawn->status)->toBe(ProvisioningRequestStatus::Withdrawn)
        ->and($withdrawn->revisions()->firstOrFail()->status)->toBe(ProvisioningRequestStatus::Withdrawn);
});

it('expires an elapsed offer idempotently without invoking an activator', function (): void {
    $maker = User::query()->create(['name' => 'Maker']);
    $checker = User::query()->create(['name' => 'Checker']);
    $request = submittedProvisioning($maker);
    app(ApproveProvisioningRequest::class)->handle($request, $checker);
    $credential = app(IssueProvisioningOffer::class)->handle($request, now()->subSecond());

    $expired = app(ExpireProvisioningOffer::class)->handle($credential->offer);
    $replayed = app(ExpireProvisioningOffer::class)->handle($expired);

    expect($expired->status)->toBe(ProvisioningRequestStatus::Expired)
        ->and($replayed->status)->toBe(ProvisioningRequestStatus::Expired)
        ->and(ProvisioningEvent::query()->where('event_type', 'provisioning.offer.expired')->count())->toBe(1);
});

it('revokes activated authority through the configured revoker exactly once', function (): void {
    $maker = User::query()->create(['name' => 'Maker']);
    $approver = User::query()->create(['name' => 'Approver']);
    $revoker = User::query()->create(['name' => 'Revoker']);
    $calls = new class
    {
        public int $activations = 0;

        public int $revocations = 0;
    };

    app()->bind(ProvisioningActivatorContract::class, fn (): ProvisioningActivatorContract => new class($calls) implements ProvisioningActivatorContract
    {
        public function __construct(private object $calls) {}

        public function activate(ProvisioningRevision $revision, ProvisioningAcceptance $acceptance): string
        {
            $this->calls->activations++;

            return 'activation:test';
        }
    });
    app()->bind(ProvisioningRevokerContract::class, fn (): ProvisioningRevokerContract => new class($calls) implements ProvisioningRevokerContract
    {
        public function __construct(private object $calls) {}

        public function revoke(ProvisioningRevision $revision, ProvisioningAcceptance $acceptance, string $reason): string
        {
            $this->calls->revocations++;

            return 'revocation:test';
        }
    });

    $request = app(CreateProvisioningRequest::class)->handle(
        ProvisioningProfile::CommercialMaker,
        ['purpose' => 'Revocation test'],
        $maker,
        activationMode: ProvisioningActivationMode::ReviewRequired,
    );
    app(SubmitProvisioningRequest::class)->handle($request, $maker);
    app(ApproveProvisioningRequest::class)->handle($request, $approver);
    $credential = app(IssueProvisioningOffer::class)->handle($request);
    $pending = app(AcceptProvisioningOffer::class)->handle(
        $credential->claimToken,
        'user',
        'candidate-1',
        [
            'name' => 'Candidate',
            'email' => 'candidate@example.test',
            'mobile' => '639171234567',
            'otp' => 'verified',
            'responsibility_attestation' => 'accepted',
        ],
    );
    $activated = app(ActivateProvisioningAcceptance::class)->handle($pending);
    $revoked = app(RevokeProvisioningAcceptance::class)->handle($activated, $revoker, 'Authority is no longer required.');
    $replayed = app(RevokeProvisioningAcceptance::class)->handle($revoked, $revoker, 'Authority is no longer required.');

    expect($revoked->status)->toBe(ProvisioningRequestStatus::Revoked)
        ->and($replayed->status)->toBe(ProvisioningRequestStatus::Revoked)
        ->and($revoked->revocation_reference)->toBe('revocation:test')
        ->and($calls->activations)->toBe(1)
        ->and($calls->revocations)->toBe(1)
        ->and(ProvisioningEvent::query()->where('event_type', 'provisioning.acceptance.revoked')->count())->toBe(1);
});
