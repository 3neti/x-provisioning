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
