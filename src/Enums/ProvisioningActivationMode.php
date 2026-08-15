<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Enums;

enum ProvisioningActivationMode: string
{
    case ActivateOnVerifiedClaim = 'activate_on_verified_claim';
    case ReviewRequired = 'review_required';
    case SimulationOnly = 'simulation_only';
}
