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

final readonly class ApproveProvisioningRequest
{
    public function __construct(
        private ProvisioningActorGuardContract $actors,
        private ProvisioningEventRecorder $events,
    ) {}

    public function handle(ProvisioningRequest $request, Model $checker): ProvisioningRevision
    {
        $this->actors->assertEligible($checker);

        return DB::transaction(function () use ($request, $checker): ProvisioningRevision {
            $locked = ProvisioningRequest::query()->lockForUpdate()->findOrFail($request->getKey());
            $revision = $locked->revisions()->where('version', $locked->current_revision_number)->lockForUpdate()->firstOrFail();

            if ($revision->status !== ProvisioningRequestStatus::AwaitingApproval) {
                throw new DomainException('Only a submitted provisioning revision may be approved.');
            }

            if ($revision->maker_type === $checker->getMorphClass()
                && (string) $revision->maker_id === (string) $checker->getKey()) {
                throw new DomainException('The checker must be independent from the maker.');
            }

            $revision->forceFill([
                'status' => ProvisioningRequestStatus::Approved,
                'checker_type' => $checker->getMorphClass(),
                'checker_id' => (string) $checker->getKey(),
                'approved_at' => now(),
            ])->save();
            $locked->forceFill(['status' => ProvisioningRequestStatus::Approved])->save();
            $this->events->record($locked, 'provisioning.request.approved', $checker, [
                'version' => $revision->version,
                'snapshot_hash' => $revision->snapshot_hash,
            ]);

            return $revision->refresh();
        }, attempts: 3);
    }
}
