<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Actions;

use DomainException;
use Illuminate\Support\Facades\DB;
use LBHurtado\XProvisioning\Enums\ProvisioningSeatStatus;
use LBHurtado\XProvisioning\Models\ProvisioningRequest;
use LBHurtado\XProvisioning\Models\ProvisioningSeat;

final readonly class AttachCommissioningSeatRequest
{
    public function handle(ProvisioningSeat $seat, ProvisioningRequest $request): ProvisioningSeat
    {
        return DB::transaction(function () use ($seat, $request): ProvisioningSeat {
            $locked = ProvisioningSeat::query()->lockForUpdate()->findOrFail($seat->getKey());

            if ($locked->status !== ProvisioningSeatStatus::Vacant
                || $locked->profile !== $request->profile
                || ! $request->commissioning) {
                throw new DomainException('The commissioning request does not match the vacant seat.');
            }

            $locked->forceFill([
                'request_id' => $request->getKey(),
                'status' => ProvisioningSeatStatus::Offered,
            ])->save();

            return $locked->refresh();
        }, attempts: 3);
    }
}
