<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Enums;

enum ProvisioningSeatStatus: string
{
    case Vacant = 'vacant';
    case Offered = 'offered';
    case Claimed = 'claimed';
    case Activated = 'activated';
    case Withdrawn = 'withdrawn';
}
