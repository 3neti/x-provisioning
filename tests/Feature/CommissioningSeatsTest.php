<?php

declare(strict_types=1);

use LBHurtado\XProvisioning\Actions\AttachCommissioningSeatRequest;
use LBHurtado\XProvisioning\Actions\CreateProvisioningRequest;
use LBHurtado\XProvisioning\Actions\ProvisionCommissioningSeats;
use LBHurtado\XProvisioning\Enums\ProvisioningProfile;
use LBHurtado\XProvisioning\Enums\ProvisioningSeatStatus;
use LBHurtado\XProvisioning\Tests\Fixtures\User;

it('idempotently provisions vacant seats before candidate identities are known', function (): void {
    $seats = [[
        'key' => 'treasury-maker-primary',
        'label' => 'Treasury Maker',
        'profile' => ProvisioningProfile::TreasuryMaker,
        'required' => true,
    ]];

    $first = app(ProvisionCommissioningSeats::class)->handle($seats);
    $second = app(ProvisionCommissioningSeats::class)->handle($seats);

    expect($first[0]->is($second[0]))->toBeTrue()
        ->and($first[0]->status)->toBe(ProvisioningSeatStatus::Vacant)
        ->and($first[0]->activated_subject_reference)->toBeNull();
});

it('attaches only a matching commissioning request to a vacant seat', function (): void {
    $maker = User::query()->create(['name' => 'Commissioning Maker']);
    $seat = app(ProvisionCommissioningSeats::class)->handle([[
        'key' => 'api-admin-primary',
        'label' => 'API Partner Administrator',
        'profile' => ProvisioningProfile::ApiPartnerAdministrator,
    ]])[0];
    $request = app(CreateProvisioningRequest::class)->handle(
        ProvisioningProfile::ApiPartnerAdministrator,
        ['role' => 'API Partner Administrator'],
        $maker,
        commissioning: true,
    );

    $attached = app(AttachCommissioningSeatRequest::class)->handle($seat, $request);

    expect($attached->status)->toBe(ProvisioningSeatStatus::Offered)
        ->and($attached->request_id)->toBe($request->getKey());
});
