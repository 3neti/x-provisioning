<?php

declare(strict_types=1);

use LBHurtado\XProvisioning\Enums\ProvisioningActivationMode;
use LBHurtado\XProvisioning\Enums\ProvisioningProfile;

return [
    'offer_ttl_seconds' => 604_800,

    'profiles' => [
        ProvisioningProfile::AccountInvitation->value => [
            'activation_mode' => ProvisioningActivationMode::ActivateOnVerifiedClaim->value,
            'required_evidence' => ['name', 'email', 'mobile', 'otp'],
        ],
        ProvisioningProfile::InstitutionAdministrator->value => [
            'activation_mode' => ProvisioningActivationMode::ActivateOnVerifiedClaim->value,
            'required_evidence' => ['name', 'email', 'mobile', 'otp', 'responsibility_attestation'],
        ],
        ProvisioningProfile::CommercialMaker->value => [
            'activation_mode' => ProvisioningActivationMode::ActivateOnVerifiedClaim->value,
            'required_evidence' => ['name', 'email', 'mobile', 'otp', 'responsibility_attestation'],
        ],
        ProvisioningProfile::CommercialChecker->value => [
            'activation_mode' => ProvisioningActivationMode::ActivateOnVerifiedClaim->value,
            'required_evidence' => ['name', 'email', 'mobile', 'otp', 'responsibility_attestation'],
        ],
        ProvisioningProfile::TreasuryMaker->value => [
            'activation_mode' => ProvisioningActivationMode::ActivateOnVerifiedClaim->value,
            'required_evidence' => ['name', 'email', 'mobile', 'otp', 'responsibility_attestation'],
        ],
        ProvisioningProfile::TreasuryChecker->value => [
            'activation_mode' => ProvisioningActivationMode::ActivateOnVerifiedClaim->value,
            'required_evidence' => ['name', 'email', 'mobile', 'otp', 'responsibility_attestation'],
        ],
        ProvisioningProfile::ApiPartnerAdministrator->value => [
            'activation_mode' => ProvisioningActivationMode::ActivateOnVerifiedClaim->value,
            'required_evidence' => ['name', 'email', 'mobile', 'otp', 'responsibility_attestation'],
        ],
        ProvisioningProfile::ApiProductionMaker->value => [
            'activation_mode' => ProvisioningActivationMode::ActivateOnVerifiedClaim->value,
            'required_evidence' => ['name', 'email', 'mobile', 'otp', 'responsibility_attestation'],
        ],
        ProvisioningProfile::ApiProductionChecker->value => [
            'activation_mode' => ProvisioningActivationMode::ActivateOnVerifiedClaim->value,
            'required_evidence' => ['name', 'email', 'mobile', 'otp', 'responsibility_attestation'],
        ],
        ProvisioningProfile::CommercialCounterpartyEnrollment->value => [
            'activation_mode' => ProvisioningActivationMode::ReviewRequired->value,
            'required_evidence' => ['organization', 'representative', 'authority', 'tax_profile', 'settlement_destination'],
        ],
        ProvisioningProfile::CommercialRecipientDesignation->value => [
            'activation_mode' => ProvisioningActivationMode::ReviewRequired->value,
            'required_evidence' => ['representative', 'authority', 'agreement'],
        ],
    ],
];
