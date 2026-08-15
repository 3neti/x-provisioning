<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Actions;

use DomainException;
use Illuminate\Support\Facades\DB;
use LBHurtado\XProvisioning\Contracts\ProvisioningEvidenceVerifierContract;
use LBHurtado\XProvisioning\Enums\ProvisioningActivationMode;
use LBHurtado\XProvisioning\Enums\ProvisioningRequestStatus;
use LBHurtado\XProvisioning\Models\ProvisioningOffer;
use LBHurtado\XProvisioning\Services\ProvisioningEventRecorder;
use LBHurtado\XProvisioning\Support\CanonicalJson;

final readonly class AcceptProvisioningOffer
{
    public function __construct(
        private ProvisioningEvidenceVerifierContract $evidenceVerifier,
        private ActivateProvisioningAcceptance $activate,
        private ProvisioningEventRecorder $events,
    ) {}

    /**
     * @param  array<string, mixed>  $evidence
     */
    public function handle(
        string $claimToken,
        string $candidateType,
        string $candidateReference,
        array $evidence,
    ): ProvisioningOffer {
        $tokenHash = hash('sha256', $claimToken);
        $offer = DB::transaction(function () use ($tokenHash, $candidateType, $candidateReference, $evidence): ProvisioningOffer {
            $locked = ProvisioningOffer::query()->with(['request', 'revision', 'acceptance'])
                ->where('claim_token_hash', $tokenHash)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === ProvisioningRequestStatus::Activated) {
                return $locked;
            }

            if ($locked->status !== ProvisioningRequestStatus::Offered || $locked->expires_at?->isPast()) {
                throw new DomainException('The provisioning invitation is not available.');
            }

            if (trim($candidateType) === '' || trim($candidateReference) === '') {
                throw new DomainException('A verified candidate identity is required.');
            }

            $this->evidenceVerifier->assertVerified($locked->revision, $evidence);
            $evidenceHash = hash('sha256', CanonicalJson::encode($evidence));
            $locked->acceptance()->create([
                'candidate_type' => trim($candidateType),
                'candidate_reference' => trim($candidateReference),
                'evidence' => $evidence,
                'evidence_hash' => $evidenceHash,
                'accepted_at' => now(),
            ]);
            $locked->forceFill([
                'status' => ProvisioningRequestStatus::ActivationPending,
                'accepted_at' => now(),
            ])->save();
            $locked->request->forceFill([
                'status' => ProvisioningRequestStatus::ActivationPending,
                'subject_type' => trim($candidateType),
                'subject_reference' => trim($candidateReference),
            ])->save();
            $this->events->record($locked->request, 'provisioning.offer.accepted', null, [
                'offer_reference' => $locked->reference,
                'candidate_type' => trim($candidateType),
                'candidate_reference_hash' => hash('sha256', trim($candidateReference)),
                'evidence_hash' => $evidenceHash,
                'snapshot_hash' => $locked->revision->snapshot_hash,
            ]);

            return $locked->refresh()->load(['request', 'revision', 'acceptance']);
        }, attempts: 3);

        if ($offer->revision->activation_mode === ProvisioningActivationMode::ActivateOnVerifiedClaim) {
            return $this->activate->handle($offer);
        }

        if ($offer->revision->activation_mode === ProvisioningActivationMode::SimulationOnly
            && ! app()->environment(['local', 'testing'])) {
            throw new DomainException('Simulation-only provisioning is unavailable in this environment.');
        }

        return $offer;
    }
}
