<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LBHurtado\XProvisioning\Enums\ProvisioningActivationMode;
use LBHurtado\XProvisioning\Enums\ProvisioningRequestStatus;

final class ProvisioningRevision extends Model
{
    protected $table = 'x_provisioning_revisions';

    protected $fillable = [
        'request_id', 'version', 'status', 'activation_mode', 'snapshot', 'snapshot_hash',
        'maker_type', 'maker_id', 'submitted_at', 'checker_type', 'checker_id',
        'approved_at', 'rejected_at', 'rejection_reason',
    ];

    protected $attributes = ['status' => 'draft'];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'status' => ProvisioningRequestStatus::class,
            'activation_mode' => ProvisioningActivationMode::class,
            'snapshot' => 'array',
            'submitted_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'rejected_at' => 'immutable_datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ProvisioningRequest::class, 'request_id');
    }

    public function maker(): MorphTo
    {
        return $this->morphTo();
    }

    public function checker(): MorphTo
    {
        return $this->morphTo();
    }
}
