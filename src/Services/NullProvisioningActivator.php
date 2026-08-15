<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Services;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use LBHurtado\XProvisioning\Contracts\ProvisioningActivatorContract;
use LBHurtado\XProvisioning\Models\ProvisioningAcceptance;
use LBHurtado\XProvisioning\Models\ProvisioningRevision;

final readonly class NullProvisioningActivator implements ProvisioningActivatorContract
{
    public function activate(
        ProvisioningRevision $revision,
        ProvisioningAcceptance $acceptance,
        ?Model $checker = null,
    ): string {
        throw new DomainException('No provisioning activator is configured for this profile.');
    }
}
