<?php

declare(strict_types=1);

namespace LBHurtado\XProvisioning\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LBHurtado\XProvisioning\Models\ProvisioningEvent;
use LBHurtado\XProvisioning\Models\ProvisioningRequest;
use LBHurtado\XProvisioning\Support\CanonicalJson;

final readonly class ProvisioningEventRecorder
{
    /**
     * @param  array<string, mixed>  $facts
     */
    public function record(
        ProvisioningRequest $request,
        string $eventType,
        ?Model $actor = null,
        array $facts = [],
    ): ProvisioningEvent {
        $safeFacts = $this->safeFacts($facts);

        return ProvisioningEvent::query()->create([
            'reference' => (string) Str::ulid(),
            'request_id' => $request->getKey(),
            'event_type' => $eventType,
            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor === null ? null : (string) $actor->getKey(),
            'facts' => $safeFacts,
            'facts_hash' => hash('sha256', CanonicalJson::encode($safeFacts)),
            'occurred_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $facts
     * @return array<string, mixed>
     */
    private function safeFacts(array $facts): array
    {
        foreach (['token', 'claim_token', 'secret', 'password', 'otp', 'evidence'] as $key) {
            unset($facts[$key]);
        }

        return $facts;
    }
}
