<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LBHurtado\XProvisioning\Enums\ProvisioningProfile;
use LBHurtado\XProvisioning\Enums\ProvisioningSeatStatus;
use LBHurtado\XProvisioning\Models\ProvisioningSeat;

final readonly class ProvisionCommissioningSeats
{
    /**
     * @param  list<array{key:string,label:string,profile:ProvisioningProfile|string,required?:bool}>  $seats
     * @return list<ProvisioningSeat>
     */
    public function handle(array $seats): array
    {
        return DB::transaction(function () use ($seats): array {
            $provisioned = [];

            foreach ($seats as $seat) {
                $profile = $seat['profile'] instanceof ProvisioningProfile
                    ? $seat['profile']
                    : ProvisioningProfile::from($seat['profile']);
                $provisioned[] = ProvisioningSeat::query()->firstOrCreate(
                    ['seat_key' => trim($seat['key'])],
                    [
                        'reference' => (string) Str::ulid(),
                        'label' => trim($seat['label']),
                        'profile' => $profile,
                        'required' => $seat['required'] ?? true,
                        'status' => ProvisioningSeatStatus::Vacant,
                    ],
                );
            }

            return $provisioned;
        }, attempts: 3);
    }
}
