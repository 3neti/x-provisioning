<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Actions;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LBHurtado\XProvisioning\Enums\ProvisioningRequestStatus;
use LBHurtado\XProvisioning\Models\ProvisioningRequest;
use LBHurtado\XProvisioning\Services\ProvisioningEventRecorder;

final readonly class WithdrawProvisioningRequest
{
    public function __construct(private ProvisioningEventRecorder $events) {}

    public function handle(ProvisioningRequest $request, Model $maker, string $reason): ProvisioningRequest
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new DomainException('A withdrawal reason is required.');
        }

        return DB::transaction(function () use ($request, $maker, $reason): ProvisioningRequest {
            $locked = ProvisioningRequest::query()->lockForUpdate()->findOrFail($request->getKey());
            $revision = $locked->revisions()->where('version', $locked->current_revision_number)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [
                ProvisioningRequestStatus::Draft,
                ProvisioningRequestStatus::AwaitingApproval,
                ProvisioningRequestStatus::Approved,
            ], true)) {
                throw new DomainException('This provisioning request can no longer be withdrawn.');
            }

            if ($revision->maker_type !== $maker->getMorphClass()
                || (string) $revision->maker_id !== (string) $maker->getKey()) {
                throw new DomainException('Only the maker may withdraw this provisioning request.');
            }

            $revision->forceFill(['status' => ProvisioningRequestStatus::Withdrawn])->save();
            $locked->forceFill(['status' => ProvisioningRequestStatus::Withdrawn])->save();
            $this->events->record($locked, 'provisioning.request.withdrawn', $maker, [
                'version' => $revision->version,
                'snapshot_hash' => $revision->snapshot_hash,
                'reason' => $reason,
            ]);

            return $locked->refresh();
        }, attempts: 3);
    }
}
