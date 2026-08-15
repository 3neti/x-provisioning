<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class ProvisioningEvent extends Model
{
    protected $table = 'x_provisioning_events';

    protected $fillable = [
        'reference', 'request_id', 'event_type', 'actor_type', 'actor_id',
        'facts', 'facts_hash', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'facts' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ProvisioningRequest::class, 'request_id');
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}
