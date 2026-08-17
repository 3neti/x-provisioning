<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use LBHurtado\XProvisioning\Contracts\ProvisioningActivatorContract;
use LBHurtado\XProvisioning\Contracts\ProvisioningActorGuardContract;
use LBHurtado\XProvisioning\Contracts\ProvisioningEvidenceVerifierContract;
use LBHurtado\XProvisioning\Contracts\ProvisioningRevokerContract;
use LBHurtado\XProvisioning\Models\ProvisioningAcceptance;
use LBHurtado\XProvisioning\Models\ProvisioningRevision;
use LBHurtado\XProvisioning\Services\ConfiguredProvisioningEvidenceVerifier;
use LBHurtado\XProvisioning\Services\NullProvisioningActivator;
use LBHurtado\XProvisioning\Services\NullProvisioningRevoker;
use LBHurtado\XProvisioning\Services\PermissiveProvisioningActorGuard;
use LBHurtado\XProvisioning\XProvisioningServiceProvider;

it('provides safe standalone lifecycle defaults', function (): void {
    expect($this->app->make(ProvisioningActorGuardContract::class))->toBeInstanceOf(PermissiveProvisioningActorGuard::class)
        ->and($this->app->make(ProvisioningEvidenceVerifierContract::class))->toBeInstanceOf(ConfiguredProvisioningEvidenceVerifier::class)
        ->and($this->app->make(ProvisioningActivatorContract::class))->toBeInstanceOf(NullProvisioningActivator::class)
        ->and($this->app->make(ProvisioningRevokerContract::class))->toBeInstanceOf(NullProvisioningRevoker::class);
});

it('preserves integrator-owned provisioning lifecycle bindings', function (): void {
    $actorGuard = new class implements ProvisioningActorGuardContract
    {
        public function assertEligible(Model $actor): void {}
    };
    $evidenceVerifier = new class implements ProvisioningEvidenceVerifierContract
    {
        public function assertVerified(ProvisioningRevision $revision, array $evidence): void {}
    };
    $activator = new class implements ProvisioningActivatorContract
    {
        public function activate(
            ProvisioningRevision $revision,
            ProvisioningAcceptance $acceptance,
            ?Model $checker = null,
        ): string {
            return 'integrator-activation';
        }
    };
    $revoker = new class implements ProvisioningRevokerContract
    {
        public function revoke(
            ProvisioningRevision $revision,
            ProvisioningAcceptance $acceptance,
            string $reason,
        ): string {
            return 'integrator-revocation';
        }
    };

    $this->app->instance(ProvisioningActorGuardContract::class, $actorGuard);
    $this->app->instance(ProvisioningEvidenceVerifierContract::class, $evidenceVerifier);
    $this->app->instance(ProvisioningActivatorContract::class, $activator);
    $this->app->instance(ProvisioningRevokerContract::class, $revoker);

    (new XProvisioningServiceProvider($this->app))->register();

    expect($this->app->make(ProvisioningActorGuardContract::class))->toBe($actorGuard)
        ->and($this->app->make(ProvisioningEvidenceVerifierContract::class))->toBe($evidenceVerifier)
        ->and($this->app->make(ProvisioningActivatorContract::class))->toBe($activator)
        ->and($this->app->make(ProvisioningRevokerContract::class))->toBe($revoker);
});
