<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Actions;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LBHurtado\XProvisioning\Contracts\ProvisioningActorGuardContract;
use LBHurtado\XProvisioning\Contracts\ProvisioningRevokerContract;
use LBHurtado\XProvisioning\Enums\ProvisioningRequestStatus;
use LBHurtado\XProvisioning\Models\ProvisioningOffer;
use LBHurtado\XProvisioning\Services\ProvisioningEventRecorder;

final readonly class SupersedeProvisioningAcceptance
{
    public function __construct(
        private ProvisioningActorGuardContract $actors,
        private ProvisioningRevokerContract $revoker,
        private ProvisioningEventRecorder $events,
    ) {}

    public function handle(
        ProvisioningOffer $predecessor,
        ProvisioningOffer $replacement,
        Model $checker,
        string $reason,
    ): ProvisioningOffer {
        $this->actors->assertEligible($checker);
        $reason = trim($reason);

        if ($reason === '') {
            throw new DomainException('A supersession reason is required.');
        }

        return DB::transaction(function () use ($predecessor, $replacement, $checker, $reason): ProvisioningOffer {
            $ids = [(int) $predecessor->getKey(), (int) $replacement->getKey()];
            sort($ids);
            $offers = ProvisioningOffer::query()
                ->with(['request', 'revision', 'acceptance'])
                ->whereKey($ids)
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (ProvisioningOffer $offer): int => (int) $offer->getKey());
            $lockedPredecessor = $offers->get((int) $predecessor->getKey());
            $lockedReplacement = $offers->get((int) $replacement->getKey());

            if (! $lockedPredecessor instanceof ProvisioningOffer || ! $lockedReplacement instanceof ProvisioningOffer) {
                throw new DomainException('The supersession authority could not be resolved.');
            }

            if ($lockedPredecessor->status === ProvisioningRequestStatus::Superseded
                && $lockedPredecessor->superseded_by_offer_id === $lockedReplacement->getKey()) {
                return $lockedPredecessor;
            }

            if ($lockedPredecessor->status !== ProvisioningRequestStatus::Activated
                || $lockedReplacement->status !== ProvisioningRequestStatus::Activated) {
                throw new DomainException('Both predecessor and replacement authority must be active before supersession.');
            }

            if ($lockedPredecessor->getKey() === $lockedReplacement->getKey()
                || $lockedPredecessor->request->profile !== $lockedReplacement->request->profile
                || $lockedPredecessor->acceptance?->candidate_type !== $lockedReplacement->acceptance?->candidate_type
                || $lockedPredecessor->acceptance?->candidate_reference !== $lockedReplacement->acceptance?->candidate_reference) {
                throw new DomainException('Replacement authority must target the same verified subject and profile.');
            }

            if (($lockedPredecessor->revision->maker_type === $checker->getMorphClass()
                && (string) $lockedPredecessor->revision->maker_id === (string) $checker->getKey())
                || ($lockedPredecessor->acceptance?->candidate_type === $checker->getMorphClass()
                    && (string) $lockedPredecessor->acceptance?->candidate_reference === (string) $checker->getKey())) {
                throw new DomainException('Supersession requires a checker independent from the maker and recipient.');
            }

            $revocationReference = trim($this->revoker->revoke(
                $lockedPredecessor->revision,
                $lockedPredecessor->acceptance,
                $reason,
            ));

            if ($revocationReference === '') {
                throw new DomainException('Supersession did not return an authoritative revocation reference.');
            }

            $reference = 'supersession:'.$lockedPredecessor->reference.':'.$lockedReplacement->reference;
            $lockedPredecessor->forceFill([
                'status' => ProvisioningRequestStatus::Superseded,
                'superseded_by_offer_id' => $lockedReplacement->getKey(),
                'supersession_reference' => $reference,
                'superseded_at' => now(),
                'revocation_reference' => $revocationReference,
            ])->save();
            $lockedPredecessor->request->forceFill(['status' => ProvisioningRequestStatus::Superseded])->save();
            $this->events->record($lockedPredecessor->request, 'provisioning.acceptance.superseded', $checker, [
                'offer_reference' => $lockedPredecessor->reference,
                'replacement_offer_reference' => $lockedReplacement->reference,
                'supersession_reference' => $reference,
                'reason' => $reason,
                'snapshot_hash' => $lockedPredecessor->revision->snapshot_hash,
                'replacement_snapshot_hash' => $lockedReplacement->revision->snapshot_hash,
            ]);

            return $lockedPredecessor->refresh();
        }, attempts: 3);
    }
}
