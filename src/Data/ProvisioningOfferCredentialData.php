<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Data;

use LBHurtado\XProvisioning\Models\ProvisioningOffer;

final readonly class ProvisioningOfferCredentialData
{
    public function __construct(
        public ProvisioningOffer $offer,
        public string $claimToken,
    ) {}
}
