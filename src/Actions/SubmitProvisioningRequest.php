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

final readonly class SubmitProvisioningRequest
{
    public function __construct(
        private ProvisioningActorGuardContract $actors,
        private ProvisioningEventRecorder $events,
    ) {}

    public function handle(ProvisioningRequest $request, Model $maker): ProvisioningRevision
    {
        $this->actors->assertEligible($maker);

        return DB::transaction(function () use ($request, $maker): ProvisioningRevision {
            $locked = ProvisioningRequest::query()->lockForUpdate()->findOrFail($request->getKey());
            $revision = $locked->revisions()->where('version', $locked->current_revision_number)->lockForUpdate()->firstOrFail();

            if ($revision->status !== ProvisioningRequestStatus::Draft
                || $revision->maker_type !== $maker->getMorphClass()
                || (string) $revision->maker_id !== (string) $maker->getKey()) {
                throw new DomainException('Only the eligible maker may submit the current draft.');
            }

            $revision->forceFill([
                'status' => ProvisioningRequestStatus::AwaitingApproval,
                'submitted_at' => now(),
            ])->save();
            $locked->forceFill(['status' => ProvisioningRequestStatus::AwaitingApproval])->save();
            $this->events->record($locked, 'provisioning.request.submitted', $maker, [
                'version' => $revision->version,
                'snapshot_hash' => $revision->snapshot_hash,
            ]);

            return $revision->refresh();
        }, attempts: 3);
    }
}
