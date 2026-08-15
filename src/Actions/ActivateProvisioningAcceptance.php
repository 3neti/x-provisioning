<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Actions;

use DomainException;
use Illuminate\Support\Facades\DB;
use LBHurtado\XProvisioning\Contracts\ProvisioningActivatorContract;
use LBHurtado\XProvisioning\Enums\ProvisioningRequestStatus;
use LBHurtado\XProvisioning\Models\ProvisioningOffer;
use LBHurtado\XProvisioning\Services\ProvisioningEventRecorder;

final readonly class ActivateProvisioningAcceptance
{
    public function __construct(
        private ProvisioningActivatorContract $activator,
        private ProvisioningEventRecorder $events,
    ) {}

    public function handle(ProvisioningOffer $offer): ProvisioningOffer
    {
        return DB::transaction(function () use ($offer): ProvisioningOffer {
            $locked = ProvisioningOffer::query()->with(['request', 'revision', 'acceptance'])->lockForUpdate()->findOrFail($offer->getKey());

            if ($locked->status === ProvisioningRequestStatus::Activated) {
                return $locked;
            }

            if ($locked->status !== ProvisioningRequestStatus::ActivationPending || $locked->acceptance === null) {
                throw new DomainException('Only an accepted provisioning offer may be activated.');
            }

            $activationReference = $this->activator->activate($locked->revision, $locked->acceptance);

            if (trim($activationReference) === '') {
                throw new DomainException('Provisioning activation did not return an authoritative reference.');
            }

            $locked->forceFill([
                'status' => ProvisioningRequestStatus::Activated,
                'activation_reference' => $activationReference,
                'activated_at' => now(),
            ])->save();
            $locked->request->forceFill(['status' => ProvisioningRequestStatus::Activated])->save();
            $this->events->record($locked->request, 'provisioning.acceptance.activated', null, [
                'offer_reference' => $locked->reference,
                'activation_reference' => $activationReference,
                'snapshot_hash' => $locked->revision->snapshot_hash,
                'evidence_hash' => $locked->acceptance->evidence_hash,
            ]);

            return $locked->refresh();
        }, attempts: 3);
    }
}
