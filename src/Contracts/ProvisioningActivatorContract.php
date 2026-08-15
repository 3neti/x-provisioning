<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Contracts;

use Illuminate\Database\Eloquent\Model;
use LBHurtado\XProvisioning\Models\ProvisioningAcceptance;
use LBHurtado\XProvisioning\Models\ProvisioningRevision;

interface ProvisioningActivatorContract
{
    public function activate(
        ProvisioningRevision $revision,
        ProvisioningAcceptance $acceptance,
        ?Model $checker = null,
    ): string;
}
