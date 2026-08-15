<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Actions;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LBHurtado\XProvisioning\Contracts\ProvisioningActivatorContract;
use LBHurtado\XProvisioning\Contracts\ProvisioningActorGuardContract;
use LBHurtado\XProvisioning\Enums\ProvisioningActivationMode;
use LBHurtado\XProvisioning\Enums\ProvisioningRequestStatus;
use LBHurtado\XProvisioning\Models\ProvisioningOffer;
use LBHurtado\XProvisioning\Services\ProvisioningEventRecorder;

final readonly class ActivateProvisioningAcceptance
{
    public function __construct(
        private ProvisioningActorGuardContract $actors,
        private ProvisioningActivatorContract $activator,
        private ProvisioningEventRecorder $events,
    ) {}

    public function handle(ProvisioningOffer $offer, ?Model $checker = null): ProvisioningOffer
    {
        if ($checker instanceof Model) {
            $this->actors->assertEligible($checker);
        }

        return DB::transaction(function () use ($offer, $checker): ProvisioningOffer {
            $locked = ProvisioningOffer::query()->with(['request', 'revision', 'acceptance'])->lockForUpdate()->findOrFail($offer->getKey());

            if ($locked->status === ProvisioningRequestStatus::Activated) {
                return $locked;
            }

            if ($locked->status !== ProvisioningRequestStatus::ActivationPending || $locked->acceptance === null) {
                throw new DomainException('Only an accepted provisioning offer may be activated.');
            }

            if ($locked->revision->activation_mode === ProvisioningActivationMode::ReviewRequired
                && ! $checker instanceof Model) {
                throw new DomainException('Review-required provisioning needs an activation checker.');
            }

            if ($checker instanceof Model
                && (($locked->revision->maker_type === $checker->getMorphClass()
                    && (string) $locked->revision->maker_id === (string) $checker->getKey())
                    || ($locked->acceptance->candidate_type === $checker->getMorphClass()
                        && (string) $locked->acceptance->candidate_reference === (string) $checker->getKey()))) {
                throw new DomainException('Activation requires a checker independent from the maker and recipient.');
            }

            $activationReference = $this->activator->activate($locked->revision, $locked->acceptance, $checker);

            if (trim($activationReference) === '') {
                throw new DomainException('Provisioning activation did not return an authoritative reference.');
            }

            $locked->forceFill([
                'status' => ProvisioningRequestStatus::Activated,
                'activation_reference' => $activationReference,
                'activated_at' => now(),
                'activated_by_type' => $checker?->getMorphClass(),
                'activated_by_id' => $checker?->getKey(),
            ])->save();
            $locked->request->forceFill(['status' => ProvisioningRequestStatus::Activated])->save();
            $this->events->record($locked->request, 'provisioning.acceptance.activated', $checker, [
                'offer_reference' => $locked->reference,
                'activation_reference' => $activationReference,
                'snapshot_hash' => $locked->revision->snapshot_hash,
                'evidence_hash' => $locked->acceptance->evidence_hash,
            ]);

            return $locked->refresh();
        }, attempts: 3);
    }
}
