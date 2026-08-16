<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Contracts;

use LBHurtado\XProvisioning\Enums\ProvisioningProfile;

interface ProvisioningSnapshotValidatorContract
{
    /** @param array<string, mixed> $snapshot */
    public function validate(ProvisioningProfile $profile, array $snapshot): void;
}
