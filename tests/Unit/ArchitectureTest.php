<?php

declare(strict_types=1);

arch('the package remains independent from x-change financial internals')
    ->expect('LBHurtado\XProvisioning')
    ->not->toUse([
        'LBHurtado\XChange',
        'LBHurtado\Wallet',
        'LBHurtado\Voucher',
        'LBHurtado\XJournal',
    ]);

arch('commercial designation authority remains identity-only')
    ->expect([
        'LBHurtado\XProvisioning\Data\CommercialRecipientDesignationData',
        'LBHurtado\XProvisioning\Data\CommercialRecipientDesignationAuthorityData',
        'LBHurtado\XProvisioning\Services\CommercialRecipientDesignationAuthorityFactory',
    ])
    ->not->toUse([
        'Illuminate\Support\Facades\DB',
        'Illuminate\Support\Facades\Http',
        'LBHurtado\XCommerce',
        'LBHurtado\XChange',
        'LBHurtado\Wallet',
    ]);
