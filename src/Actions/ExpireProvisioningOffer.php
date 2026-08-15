<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Actions;

use DomainException;
use Illuminate\Support\Facades\DB;
use LBHurtado\XProvisioning\Enums\ProvisioningRequestStatus;
use LBHurtado\XProvisioning\Models\ProvisioningOffer;
use LBHurtado\XProvisioning\Services\ProvisioningEventRecorder;

final readonly class ExpireProvisioningOffer
{
    public function __construct(private ProvisioningEventRecorder $events) {}

    public function handle(ProvisioningOffer $offer): ProvisioningOffer
    {
        return DB::transaction(function () use ($offer): ProvisioningOffer {
            $locked = ProvisioningOffer::query()->with('request')->lockForUpdate()->findOrFail($offer->getKey());

            if ($locked->status === ProvisioningRequestStatus::Expired) {
                return $locked;
            }

            if ($locked->status !== ProvisioningRequestStatus::Offered || ! $locked->expires_at?->isPast()) {
                throw new DomainException('Only an elapsed provisioning offer may be expired.');
            }

            $locked->forceFill(['status' => ProvisioningRequestStatus::Expired])->save();
            $locked->request->forceFill(['status' => ProvisioningRequestStatus::Expired])->save();
            $this->events->record($locked->request, 'provisioning.offer.expired', null, [
                'offer_reference' => $locked->reference,
                'expires_at' => $locked->expires_at?->toIso8601String(),
            ]);

            return $locked->refresh();
        }, attempts: 3);
    }
}
