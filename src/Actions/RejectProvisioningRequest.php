<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Actions;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LBHurtado\XProvisioning\Contracts\ProvisioningActorGuardContract;
use LBHurtado\XProvisioning\Enums\ProvisioningRequestStatus;
use LBHurtado\XProvisioning\Models\ProvisioningRequest;
use LBHurtado\XProvisioning\Models\ProvisioningRevision;
use LBHurtado\XProvisioning\Services\ProvisioningEventRecorder;

final readonly class RejectProvisioningRequest
{
    public function __construct(
        private ProvisioningActorGuardContract $actors,
        private ProvisioningEventRecorder $events,
    ) {}

    public function handle(ProvisioningRequest $request, Model $checker, string $reason): ProvisioningRevision
    {
        $this->actors->assertEligible($checker);
        $reason = trim($reason);

        if ($reason === '') {
            throw new DomainException('A rejection reason is required.');
        }

        return DB::transaction(function () use ($request, $checker, $reason): ProvisioningRevision {
            $locked = ProvisioningRequest::query()->lockForUpdate()->findOrFail($request->getKey());
            $revision = $locked->revisions()->where('version', $locked->current_revision_number)->lockForUpdate()->firstOrFail();

            if ($revision->status !== ProvisioningRequestStatus::AwaitingApproval) {
                throw new DomainException('Only a submitted provisioning revision may be rejected.');
            }

            if ($revision->maker_type === $checker->getMorphClass()
                && (string) $revision->maker_id === (string) $checker->getKey()) {
                throw new DomainException('The checker must be independent from the maker.');
            }

            $revision->forceFill([
                'status' => ProvisioningRequestStatus::Rejected,
                'checker_type' => $checker->getMorphClass(),
                'checker_id' => (string) $checker->getKey(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();
            $locked->forceFill(['status' => ProvisioningRequestStatus::Rejected])->save();
            $this->events->record($locked, 'provisioning.request.rejected', $checker, [
                'version' => $revision->version,
                'snapshot_hash' => $revision->snapshot_hash,
                'reason' => $reason,
            ]);

            return $revision->refresh();
        }, attempts: 3);
    }
}
