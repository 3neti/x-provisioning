<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProvisioningAcceptance extends Model
{
    protected $table = 'x_provisioning_acceptances';

    protected $fillable = [
        'offer_id', 'candidate_type', 'candidate_reference', 'evidence',
        'evidence_hash', 'accepted_at',
    ];

    protected $hidden = ['evidence'];

    protected function casts(): array
    {
        return [
            'evidence' => 'encrypted:array',
            'accepted_at' => 'immutable_datetime',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(ProvisioningOffer::class, 'offer_id');
    }
}
