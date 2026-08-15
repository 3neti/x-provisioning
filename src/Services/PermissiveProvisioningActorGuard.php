<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Services;

use Illuminate\Database\Eloquent\Model;
use LBHurtado\XProvisioning\Contracts\ProvisioningActorGuardContract;

final readonly class PermissiveProvisioningActorGuard implements ProvisioningActorGuardContract
{
    public function assertEligible(Model $actor): void {}
}
