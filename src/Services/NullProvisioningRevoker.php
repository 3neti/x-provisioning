<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Services;

use DomainException;
use LBHurtado\XProvisioning\Contracts\ProvisioningRevokerContract;
use LBHurtado\XProvisioning\Models\ProvisioningAcceptance;
use LBHurtado\XProvisioning\Models\ProvisioningRevision;

final readonly class NullProvisioningRevoker implements ProvisioningRevokerContract
{
    public function revoke(
        ProvisioningRevision $revision,
        ProvisioningAcceptance $acceptance,
        string $reason,
    ): string {
        throw new DomainException('No provisioning revoker is configured for this profile.');
    }
}
