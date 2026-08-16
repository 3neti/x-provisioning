<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Services;

use DomainException;
use LBHurtado\XProvisioning\Contracts\CommercialRecipientDesignationAuthorityFactoryContract;
use LBHurtado\XProvisioning\Data\CommercialRecipientDesignationAuthorityData;
use LBHurtado\XProvisioning\Data\CommercialRecipientDesignationData;
use LBHurtado\XProvisioning\Enums\ProvisioningProfile;
use LBHurtado\XProvisioning\Enums\ProvisioningRequestStatus;
use LBHurtado\XProvisioning\Models\ProvisioningOffer;

final readonly class CommercialRecipientDesignationAuthorityFactory implements CommercialRecipientDesignationAuthorityFactoryContract
{
    public function fromActivatedOffer(ProvisioningOffer $offer): CommercialRecipientDesignationAuthorityData
    {
        $offer->loadMissing(['request', 'revision', 'acceptance']);

        if ($offer->status !== ProvisioningRequestStatus::Activated
            || $offer->request->profile !== ProvisioningProfile::CommercialRecipientDesignation
            || $offer->acceptance === null
            || $offer->activated_at === null
            || trim((string) $offer->activation_reference) === ''
            || trim((string) $offer->activated_by_type) === ''
            || trim((string) $offer->activated_by_id) === '') {
            throw new DomainException('Only an independently activated Commercial Recipient Designation can produce authority.');
        }

        return new CommercialRecipientDesignationAuthorityData(
            provisioningRequestReference: (string) $offer->request->reference,
            provisioningOfferReference: (string) $offer->reference,
            designation: CommercialRecipientDesignationData::fromArray((array) $offer->revision->snapshot),
            representativeType: (string) $offer->acceptance->candidate_type,
            representativeReference: (string) $offer->acceptance->candidate_reference,
            acceptedSnapshotHash: (string) $offer->revision->snapshot_hash,
            acceptanceEvidenceHash: (string) $offer->acceptance->evidence_hash,
            activationReference: (string) $offer->activation_reference,
            activatedByType: (string) $offer->activated_by_type,
            activatedByReference: (string) $offer->activated_by_id,
            activatedAt: $offer->activated_at->toIso8601String(),
        );
    }
}
