<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LBHurtado\XProvisioning\Contracts\ProvisioningActorGuardContract;
use LBHurtado\XProvisioning\Enums\ProvisioningActivationMode;
use LBHurtado\XProvisioning\Enums\ProvisioningProfile;
use LBHurtado\XProvisioning\Enums\ProvisioningRequestStatus;
use LBHurtado\XProvisioning\Models\ProvisioningRequest;
use LBHurtado\XProvisioning\Services\ProvisioningEventRecorder;
use LBHurtado\XProvisioning\Support\CanonicalJson;

final readonly class CreateProvisioningRequest
{
    public function __construct(
        private ProvisioningActorGuardContract $actors,
        private ProvisioningEventRecorder $events,
    ) {}

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>  $metadata
     */
    public function handle(
        ProvisioningProfile $profile,
        array $snapshot,
        Model $maker,
        ?ProvisioningActivationMode $activationMode = null,
        bool $commissioning = false,
        ?string $subjectType = null,
        ?string $subjectReference = null,
        array $metadata = [],
    ): ProvisioningRequest {
        $this->actors->assertEligible($maker);
        $activationMode ??= ProvisioningActivationMode::from((string) config(
            "x-provisioning.profiles.{$profile->value}.activation_mode",
            ProvisioningActivationMode::ReviewRequired->value,
        ));
        $snapshot = [
            ...$snapshot,
            'profile' => $profile->value,
            'activation_mode' => $activationMode->value,
            'required_evidence' => data_get(
                $snapshot,
                'required_evidence',
                config("x-provisioning.profiles.{$profile->value}.required_evidence", []),
            ),
        ];

        return DB::transaction(function () use (
            $profile,
            $snapshot,
            $maker,
            $activationMode,
            $commissioning,
            $subjectType,
            $subjectReference,
            $metadata,
        ): ProvisioningRequest {
            $request = ProvisioningRequest::query()->create([
                'reference' => (string) Str::ulid(),
                'profile' => $profile,
                'status' => ProvisioningRequestStatus::Draft,
                'commissioning' => $commissioning,
                'subject_type' => $subjectType,
                'subject_reference' => $subjectReference,
                'metadata' => $metadata,
            ]);
            $request->revisions()->create([
                'version' => 1,
                'status' => ProvisioningRequestStatus::Draft,
                'activation_mode' => $activationMode,
                'snapshot' => $snapshot,
                'snapshot_hash' => hash('sha256', CanonicalJson::encode($snapshot)),
                'maker_type' => $maker->getMorphClass(),
                'maker_id' => (string) $maker->getKey(),
            ]);
            $this->events->record($request, 'provisioning.request.created', $maker, [
                'profile' => $profile->value,
                'snapshot_hash' => $request->revisions()->value('snapshot_hash'),
                'commissioning' => $commissioning,
            ]);

            return $request->load('revisions');
        }, attempts: 3);
    }
}
