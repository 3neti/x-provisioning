<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('x_provisioning_requests', function (Blueprint $table): void {
            $table->id();
            $table->ulid('reference')->unique();
            $table->string('profile', 80)->index();
            $table->string('status', 40)->index();
            $table->unsignedInteger('current_revision_number')->default(1);
            $table->boolean('commissioning')->default(false)->index();
            $table->string('subject_type')->nullable();
            $table->string('subject_reference')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['profile', 'status', 'created_at'], 'x_provisioning_requests_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('x_provisioning_requests');
    }
};
