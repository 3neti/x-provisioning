<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Services;

use DomainException;
use LBHurtado\XProvisioning\Contracts\ProvisioningEvidenceVerifierContract;
use LBHurtado\XProvisioning\Models\ProvisioningRevision;

final readonly class ConfiguredProvisioningEvidenceVerifier implements ProvisioningEvidenceVerifierContract
{
    public function assertVerified(ProvisioningRevision $revision, array $evidence): void
    {
        $required = (array) data_get(
            $revision->snapshot,
            'required_evidence',
            config("x-provisioning.profiles.{$revision->request->profile->value}.required_evidence", []),
        );

        $missing = array_values(array_filter(
            $required,
            static fn (mixed $field): bool => ! is_string($field)
                || ! array_key_exists($field, $evidence)
                || $evidence[$field] === null
                || $evidence[$field] === ''
                || $evidence[$field] === false,
        ));

        if ($missing !== []) {
            throw new DomainException('Provisioning evidence is incomplete: '.implode(', ', $missing));
        }
    }
}
