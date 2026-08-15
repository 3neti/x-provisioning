<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LBHurtado\XProvisioning\Enums\ProvisioningProfile;
use LBHurtado\XProvisioning\Enums\ProvisioningRequestStatus;

final class ProvisioningRequest extends Model
{
    protected $table = 'x_provisioning_requests';

    protected $fillable = [
        'reference', 'profile', 'status', 'current_revision_number', 'commissioning',
        'subject_type', 'subject_reference', 'metadata',
    ];

    protected $attributes = [
        'status' => 'draft',
        'current_revision_number' => 1,
        'commissioning' => false,
    ];

    protected function casts(): array
    {
        return [
            'profile' => ProvisioningProfile::class,
            'status' => ProvisioningRequestStatus::class,
            'current_revision_number' => 'integer',
            'commissioning' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ProvisioningRevision::class, 'request_id');
    }

    public function offer(): HasOne
    {
        return $this->hasOne(ProvisioningOffer::class, 'request_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ProvisioningEvent::class, 'request_id');
    }
}
