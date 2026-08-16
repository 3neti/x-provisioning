<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Contracts;

use LBHurtado\XProvisioning\Data\CommercialRecipientDesignationAuthorityData;
use LBHurtado\XProvisioning\Models\ProvisioningOffer;

interface CommercialRecipientDesignationAuthorityFactoryContract
{
    public function fromActivatedOffer(ProvisioningOffer $offer): CommercialRecipientDesignationAuthorityData;
}
