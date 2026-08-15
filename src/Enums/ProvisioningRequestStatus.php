<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Enums;

enum ProvisioningRequestStatus: string
{
    case Draft = 'draft';
    case AwaitingApproval = 'awaiting_approval';
    case Approved = 'approved';
    case Offered = 'offered';
    case Accepted = 'accepted';
    case ActivationPending = 'activation_pending';
    case Activated = 'activated';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Withdrawn = 'withdrawn';
    case ActivationFailed = 'activation_failed';
    case Revoked = 'revoked';
    case Superseded = 'superseded';
}
