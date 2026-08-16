<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Services;

use LBHurtado\XProvisioning\Contracts\ProvisioningSnapshotValidatorContract;
use LBHurtado\XProvisioning\Data\CommercialRecipientDesignationData;
use LBHurtado\XProvisioning\Enums\ProvisioningProfile;

final readonly class TypedProvisioningSnapshotValidator implements ProvisioningSnapshotValidatorContract
{
    public function validate(ProvisioningProfile $profile, array $snapshot): void
    {
        if ($profile === ProvisioningProfile::CommercialRecipientDesignation) {
            CommercialRecipientDesignationData::fromArray($snapshot);
        }
    }
}
