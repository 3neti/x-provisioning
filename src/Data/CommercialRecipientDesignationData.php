<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Data;

use DateTimeImmutable;
use DomainException;
use LBHurtado\XProvisioning\Enums\CommercialSettlementAccountBinding;
use LBHurtado\XProvisioning\Enums\CommercialSettlementDisposition;

final readonly class CommercialRecipientDesignationData
{
    /** @param list<string> $componentScope */
    public function __construct(
        public string $counterpartyReference,
        public string $commercialRole,
        public array $componentScope,
        public string $agreementReference,
        public string $settlementDesignationReference,
        public ?string $taxProfileReference,
        public string $effectiveFrom,
        public ?string $effectiveUntil = null,
        public CommercialSettlementDisposition $settlementDisposition = CommercialSettlementDisposition::RetainPayable,
        public ?string $settlementAccountReference = null,
        public ?string $settlementPrincipalReference = null,
        public CommercialSettlementAccountBinding $settlementAccountBinding = CommercialSettlementAccountBinding::ExactAccount,
        public ?string $supersedesDesignationReference = null,
    ) {
        foreach ([
            'counterparty reference' => $this->counterpartyReference,
            'commercial role' => $this->commercialRole,
            'agreement reference' => $this->agreementReference,
            'settlement designation reference' => $this->settlementDesignationReference,
        ] as $field => $value) {
            if (trim($value) === '') {
                throw new DomainException("Commercial Recipient Designation {$field} is required.");
            }
        }

        if ($this->componentScope === []) {
            throw new DomainException('Commercial Recipient Designation component scope is required.');
        }

        $components = [];
        foreach ($this->componentScope as $component) {
            if (! is_string($component) || trim($component) === '') {
                throw new DomainException('Commercial Recipient Designation component references must be non-empty strings.');
            }
            $components[] = trim($component);
        }

        if (count($components) !== count(array_unique($components))) {
            throw new DomainException('Commercial Recipient Designation component references must be unique.');
        }

        if ($this->taxProfileReference !== null && trim($this->taxProfileReference) === '') {
            throw new DomainException('Commercial Recipient Designation tax profile reference cannot be empty.');
        }

        $settlementAccountReference = $this->settlementAccountReference !== null
            ? trim($this->settlementAccountReference)
            : null;
        $settlementPrincipalReference = $this->settlementPrincipalReference !== null
            ? trim($this->settlementPrincipalReference)
            : null;

        if ($this->settlementDisposition === CommercialSettlementDisposition::InternalAccountCredit
            && $this->settlementAccountBinding === CommercialSettlementAccountBinding::ExactAccount
            && (($settlementAccountReference === null || $settlementAccountReference === '')
                || ($settlementPrincipalReference === null || $settlementPrincipalReference === ''))) {
            throw new DomainException('Commercial Recipient Designation internal Account credit requires Account and principal references.');
        }

        if ($this->settlementDisposition === CommercialSettlementDisposition::InternalAccountCredit
            && $this->settlementAccountBinding === CommercialSettlementAccountBinding::AcceptedCandidateAccount
            && (($settlementAccountReference !== null && $settlementAccountReference !== '')
                || ($settlementPrincipalReference !== null && $settlementPrincipalReference !== ''))) {
            throw new DomainException('Candidate-bound internal Account credit cannot name an Account or principal before acceptance.');
        }

        if ($this->settlementDisposition === CommercialSettlementDisposition::RetainPayable
            && ($this->settlementAccountBinding !== CommercialSettlementAccountBinding::ExactAccount
                || ($settlementAccountReference !== null && $settlementAccountReference !== '')
                || ($settlementPrincipalReference !== null && $settlementPrincipalReference !== ''))) {
            throw new DomainException('Commercial Recipient Designation retained payables cannot name a settlement Account or principal.');
        }

        if ($this->supersedesDesignationReference !== null
            && trim($this->supersedesDesignationReference) === '') {
            throw new DomainException('Commercial Recipient Designation predecessor reference cannot be empty.');
        }

        if ($this->supersedesDesignationReference !== null
            && trim($this->supersedesDesignationReference) === trim($this->settlementDesignationReference)) {
            throw new DomainException('Commercial Recipient Designation cannot supersede itself.');
        }

        $effectiveFrom = $this->timestamp($this->effectiveFrom, 'effective-from');
        if ($this->effectiveUntil !== null
            && $this->timestamp($this->effectiveUntil, 'effective-until') <= $effectiveFrom) {
            throw new DomainException('Commercial Recipient Designation effective-until must follow effective-from.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $componentScope = array_map('trim', $this->componentScope);
        sort($componentScope, SORT_STRING);

        $payload = [
            'counterparty_reference' => trim($this->counterpartyReference),
            'commercial_role' => trim($this->commercialRole),
            'component_scope' => $componentScope,
            'agreement_reference' => trim($this->agreementReference),
            'settlement_designation_reference' => trim($this->settlementDesignationReference),
            'tax_profile_reference' => $this->taxProfileReference !== null ? trim($this->taxProfileReference) : null,
            'settlement_disposition' => $this->settlementDisposition->value,
            'settlement_account_reference' => $this->settlementAccountReference !== null
                ? trim($this->settlementAccountReference)
                : null,
            'effective_from' => $this->effectiveFrom,
            'effective_until' => $this->effectiveUntil,
        ];

        if ($this->settlementPrincipalReference !== null) {
            $payload['settlement_principal_reference'] = trim($this->settlementPrincipalReference);
        }

        if ($this->settlementAccountBinding !== CommercialSettlementAccountBinding::ExactAccount) {
            $payload['settlement_account_binding'] = $this->settlementAccountBinding->value;
        }

        if ($this->supersedesDesignationReference !== null) {
            $payload['supersedes_designation_reference'] = trim($this->supersedesDesignationReference);
        }

        return $payload;
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            counterpartyReference: (string) ($payload['counterparty_reference'] ?? ''),
            commercialRole: (string) ($payload['commercial_role'] ?? ''),
            componentScope: array_values((array) ($payload['component_scope'] ?? [])),
            agreementReference: (string) ($payload['agreement_reference'] ?? ''),
            settlementDesignationReference: (string) ($payload['settlement_designation_reference'] ?? ''),
            taxProfileReference: isset($payload['tax_profile_reference']) ? (string) $payload['tax_profile_reference'] : null,
            effectiveFrom: (string) ($payload['effective_from'] ?? ''),
            effectiveUntil: isset($payload['effective_until']) ? (string) $payload['effective_until'] : null,
            settlementDisposition: CommercialSettlementDisposition::from(
                (string) ($payload['settlement_disposition'] ?? CommercialSettlementDisposition::RetainPayable->value),
            ),
            settlementAccountReference: isset($payload['settlement_account_reference'])
                ? (string) $payload['settlement_account_reference']
                : null,
            settlementPrincipalReference: isset($payload['settlement_principal_reference'])
                ? (string) $payload['settlement_principal_reference']
                : null,
            settlementAccountBinding: CommercialSettlementAccountBinding::from(
                (string) ($payload['settlement_account_binding'] ?? CommercialSettlementAccountBinding::ExactAccount->value),
            ),
            supersedesDesignationReference: isset($payload['supersedes_designation_reference'])
                ? (string) $payload['supersedes_designation_reference']
                : null,
        );
    }

    private function timestamp(string $value, string $field): DateTimeImmutable
    {
        if (trim($value) === '') {
            throw new DomainException("Commercial Recipient Designation {$field} timestamp is required.");
        }

        $timestamp = DateTimeImmutable::createFromFormat(DATE_ATOM, $value);

        if (! $timestamp instanceof DateTimeImmutable || $timestamp->format(DATE_ATOM) !== $value) {
            throw new DomainException("Commercial Recipient Designation {$field} timestamp is invalid.");
        }

        return $timestamp;
    }
}
