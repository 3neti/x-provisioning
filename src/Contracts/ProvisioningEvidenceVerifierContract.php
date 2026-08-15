<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Contracts;

use LBHurtado\XProvisioning\Models\ProvisioningRevision;

interface ProvisioningEvidenceVerifierContract
{
    /**
     * @param  array<string, mixed>  $evidence
     */
    public function assertVerified(ProvisioningRevision $revision, array $evidence): void;
}
