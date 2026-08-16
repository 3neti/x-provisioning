<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Enums;

enum CommercialSettlementAccountBinding: string
{
    case ExactAccount = 'exact_account';
    case AcceptedCandidateAccount = 'accepted_candidate_account';
}
