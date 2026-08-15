<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Enums;

enum ProvisioningProfile: string
{
    case AccountInvitation = 'account_invitation';
    case InstitutionAdministrator = 'institution_administrator';
    case CommercialMaker = 'commercial_maker';
    case CommercialChecker = 'commercial_checker';
    case TreasuryMaker = 'treasury_maker';
    case TreasuryChecker = 'treasury_checker';
    case ApiPartnerAdministrator = 'api_partner_administrator';
    case ApiProductionMaker = 'api_production_maker';
    case ApiProductionChecker = 'api_production_checker';
    case CommercialCounterpartyEnrollment = 'commercial_counterparty_enrollment';
    case CommercialRecipientDesignation = 'commercial_recipient_designation';
}
