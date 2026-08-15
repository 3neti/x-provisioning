<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LBHurtado\XProvisioning\Enums\ProvisioningRequestStatus;

final class ProvisioningOffer extends Model
{
    protected $table = 'x_provisioning_offers';

    protected $fillable = [
        'request_id', 'revision_id', 'reference', 'claim_token_hash', 'status',
        'expires_at', 'accepted_at', 'activated_at', 'activation_reference',
        'activated_by_type', 'activated_by_id',
        'revoked_at', 'revocation_reference',
    ];

    protected $hidden = ['claim_token_hash'];

    protected $attributes = ['status' => 'offered'];

    protected function casts(): array
    {
        return [
            'status' => ProvisioningRequestStatus::class,
            'expires_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime',
            'activated_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ProvisioningRequest::class, 'request_id');
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(ProvisioningRevision::class, 'revision_id');
    }

    public function acceptance(): HasOne
    {
        return $this->hasOne(ProvisioningAcceptance::class, 'offer_id');
    }

    public function activatedBy(): MorphTo
    {
        return $this->morphTo();
    }
}
