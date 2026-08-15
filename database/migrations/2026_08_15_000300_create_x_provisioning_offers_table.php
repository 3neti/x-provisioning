<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('x_provisioning_offers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('request_id')->constrained('x_provisioning_requests')->restrictOnDelete();
            $table->foreignId('revision_id')->constrained('x_provisioning_revisions')->restrictOnDelete();
            $table->ulid('reference')->unique();
            $table->char('claim_token_hash', 64)->unique();
            $table->string('status', 40)->index();
            $table->timestampTz('expires_at')->index();
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampTz('activated_at')->nullable();
            $table->string('activation_reference')->nullable()->unique();
            $table->timestamps();

            $table->unique('request_id');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('x_provisioning_offers');
    }
};
