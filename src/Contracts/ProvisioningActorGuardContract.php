<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ProvisioningActorGuardContract
{
    public function assertEligible(Model $actor): void;
}
