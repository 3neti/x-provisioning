<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning;

use Illuminate\Support\ServiceProvider;
use LBHurtado\XProvisioning\Contracts\ProvisioningActivatorContract;
use LBHurtado\XProvisioning\Contracts\ProvisioningActorGuardContract;
use LBHurtado\XProvisioning\Contracts\ProvisioningEvidenceVerifierContract;
use LBHurtado\XProvisioning\Contracts\ProvisioningRevokerContract;
use LBHurtado\XProvisioning\Services\ConfiguredProvisioningEvidenceVerifier;
use LBHurtado\XProvisioning\Services\NullProvisioningActivator;
use LBHurtado\XProvisioning\Services\NullProvisioningRevoker;
use LBHurtado\XProvisioning\Services\PermissiveProvisioningActorGuard;

final class XProvisioningServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/x-provisioning.php', 'x-provisioning');
        $this->app->singleton(ProvisioningActorGuardContract::class, PermissiveProvisioningActorGuard::class);
        $this->app->singleton(ProvisioningEvidenceVerifierContract::class, ConfiguredProvisioningEvidenceVerifier::class);
        $this->app->singleton(ProvisioningActivatorContract::class, NullProvisioningActivator::class);
        $this->app->singleton(ProvisioningRevokerContract::class, NullProvisioningRevoker::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->publishes([
            __DIR__.'/../config/x-provisioning.php' => config_path('x-provisioning.php'),
        ], 'x-provisioning-config');
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'x-provisioning-migrations');
    }
}
