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

final readonly class RevokeProvisioningAcceptance
{
    public function __construct(
        private ProvisioningActorGuardContract $actors,
        private ProvisioningRevokerContract $revoker,
        private ProvisioningEventRecorder $events,
    ) {}

    public function handle(ProvisioningOffer $offer, Model $checker, string $reason): ProvisioningOffer
    {
        $this->actors->assertEligible($checker);
        $reason = trim($reason);

        if ($reason === '') {
            throw new DomainException('A revocation reason is required.');
        }

        return DB::transaction(function () use ($offer, $checker, $reason): ProvisioningOffer {
            $locked = ProvisioningOffer::query()->with(['request', 'revision', 'acceptance'])->lockForUpdate()->findOrFail($offer->getKey());

            if ($locked->status === ProvisioningRequestStatus::Revoked) {
                return $locked;
            }

            if ($locked->status !== ProvisioningRequestStatus::Activated || $locked->acceptance === null) {
                throw new DomainException('Only activated provisioning authority may be revoked.');
            }

            if ($locked->revision->checker_type === $checker->getMorphClass()
                && (string) $locked->revision->checker_id === (string) $checker->getKey()) {
                throw new DomainException('Revocation requires a checker independent from the original approval.');
            }

            $reference = trim($this->revoker->revoke($locked->revision, $locked->acceptance, $reason));

            if ($reference === '') {
                throw new DomainException('Provisioning revocation did not return an authoritative reference.');
            }

            $locked->forceFill([
                'status' => ProvisioningRequestStatus::Revoked,
                'revoked_at' => now(),
                'revocation_reference' => $reference,
            ])->save();
            $locked->request->forceFill(['status' => ProvisioningRequestStatus::Revoked])->save();
            $this->events->record($locked->request, 'provisioning.acceptance.revoked', $checker, [
                'offer_reference' => $locked->reference,
                'revocation_reference' => $reference,
                'reason' => $reason,
                'snapshot_hash' => $locked->revision->snapshot_hash,
                'evidence_hash' => $locked->acceptance->evidence_hash,
            ]);

            return $locked->refresh();
        }, attempts: 3);
    }
}
