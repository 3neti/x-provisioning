<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Actions;

use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LBHurtado\XProvisioning\Data\ProvisioningOfferCredentialData;
use LBHurtado\XProvisioning\Enums\ProvisioningRequestStatus;
use LBHurtado\XProvisioning\Models\ProvisioningRequest;
use LBHurtado\XProvisioning\Services\ProvisioningEventRecorder;

final readonly class IssueProvisioningOffer
{
    public function __construct(private ProvisioningEventRecorder $events) {}

    public function handle(
        ProvisioningRequest $request,
        ?Carbon $expiresAt = null,
    ): ProvisioningOfferCredentialData {
        return DB::transaction(function () use ($request, $expiresAt): ProvisioningOfferCredentialData {
            $locked = ProvisioningRequest::query()->lockForUpdate()->findOrFail($request->getKey());

            if ($locked->status !== ProvisioningRequestStatus::Approved || $locked->offer()->exists()) {
                throw new DomainException('Only an approved request without an existing offer may be issued.');
            }

            $revision = $locked->revisions()->where('version', $locked->current_revision_number)->firstOrFail();
            $token = Str::random(64);
            $offer = $locked->offer()->create([
                'revision_id' => $revision->getKey(),
                'reference' => (string) Str::ulid(),
                'claim_token_hash' => hash('sha256', $token),
                'status' => ProvisioningRequestStatus::Offered,
                'expires_at' => $expiresAt ?? now()->addSeconds((int) config('x-provisioning.offer_ttl_seconds', 604_800)),
            ]);
            $locked->forceFill(['status' => ProvisioningRequestStatus::Offered])->save();
            $this->events->record($locked, 'provisioning.offer.issued', null, [
                'offer_reference' => $offer->reference,
                'revision_version' => $revision->version,
                'snapshot_hash' => $revision->snapshot_hash,
                'expires_at' => $offer->expires_at?->toIso8601String(),
            ]);

            return new ProvisioningOfferCredentialData($offer, $token);
        }, attempts: 3);
    }
}
