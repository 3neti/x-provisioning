<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('x_provisioning_events', function (Blueprint $table): void {
            $table->id();
            $table->ulid('reference')->unique();
            $table->foreignId('request_id')->constrained('x_provisioning_requests')->restrictOnDelete();
            $table->string('event_type', 100)->index();
            $table->nullableMorphs('actor');
            $table->json('facts');
            $table->char('facts_hash', 64)->index();
            $table->timestampTz('occurred_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('x_provisioning_events');
    }
};
