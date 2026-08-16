<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Data;

use DateTimeImmutable;
use DomainException;

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

        return [
            'counterparty_reference' => trim($this->counterpartyReference),
            'commercial_role' => trim($this->commercialRole),
            'component_scope' => $componentScope,
            'agreement_reference' => trim($this->agreementReference),
            'settlement_designation_reference' => trim($this->settlementDesignationReference),
            'tax_profile_reference' => $this->taxProfileReference !== null ? trim($this->taxProfileReference) : null,
            'effective_from' => $this->effectiveFrom,
            'effective_until' => $this->effectiveUntil,
        ];
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
