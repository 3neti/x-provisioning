<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LBHurtado\XProvisioning\Enums\ProvisioningProfile;
use LBHurtado\XProvisioning\Enums\ProvisioningSeatStatus;

final class ProvisioningSeat extends Model
{
    protected $table = 'x_provisioning_seats';

    protected $fillable = [
        'reference', 'seat_key', 'label', 'profile', 'required', 'status',
        'request_id', 'activated_subject_type', 'activated_subject_reference', 'activated_at',
    ];

    protected $attributes = ['required' => true, 'status' => 'vacant'];

    protected function casts(): array
    {
        return [
            'profile' => ProvisioningProfile::class,
            'required' => 'boolean',
            'status' => ProvisioningSeatStatus::class,
            'activated_at' => 'immutable_datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ProvisioningRequest::class, 'request_id');
    }
}
