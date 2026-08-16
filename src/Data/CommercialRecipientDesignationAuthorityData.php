<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Data;

use DomainException;
use LBHurtado\XProvisioning\Support\CanonicalJson;

final readonly class CommercialRecipientDesignationAuthorityData
{
    public const Schema = '3neti.x-provisioning.commercial-recipient-designation-authority.v1';

    public function __construct(
        public string $provisioningRequestReference,
        public string $provisioningOfferReference,
        public CommercialRecipientDesignationData $designation,
        public string $representativeType,
        public string $representativeReference,
        public string $acceptedSnapshotHash,
        public string $acceptanceEvidenceHash,
        public string $activationReference,
        public string $activatedByType,
        public string $activatedByReference,
        public string $activatedAt,
    ) {
        foreach ([
            $this->provisioningRequestReference,
            $this->provisioningOfferReference,
            $this->representativeType,
            $this->representativeReference,
            $this->activationReference,
            $this->activatedByType,
            $this->activatedByReference,
            $this->activatedAt,
        ] as $value) {
            if (trim($value) === '') {
                throw new DomainException('Commercial Recipient Designation authority references are required.');
            }
        }

        foreach ([$this->acceptedSnapshotHash, $this->acceptanceEvidenceHash] as $hash) {
            if (preg_match('/^[a-f0-9]{64}$/', $hash) !== 1) {
                throw new DomainException('Commercial Recipient Designation authority hashes must be lowercase SHA-256 values.');
            }
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => self::Schema,
            'provisioning_request_reference' => $this->provisioningRequestReference,
            'provisioning_offer_reference' => $this->provisioningOfferReference,
            'designation' => $this->designation->toArray(),
            'representative' => [
                'type' => $this->representativeType,
                'reference' => $this->representativeReference,
            ],
            'accepted_snapshot_hash' => $this->acceptedSnapshotHash,
            'acceptance_evidence_hash' => $this->acceptanceEvidenceHash,
            'activation_reference' => $this->activationReference,
            'activated_by' => [
                'type' => $this->activatedByType,
                'reference' => $this->activatedByReference,
            ],
            'activated_at' => $this->activatedAt,
        ];
    }

    public function authorityHash(): string
    {
        return hash('sha256', CanonicalJson::encode($this->toArray()));
    }
}
