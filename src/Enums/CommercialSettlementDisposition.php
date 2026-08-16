<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Enums;

enum CommercialSettlementDisposition: string
{
    case RetainPayable = 'retain_payable';
    case InternalAccountCredit = 'internal_account_credit';
}
