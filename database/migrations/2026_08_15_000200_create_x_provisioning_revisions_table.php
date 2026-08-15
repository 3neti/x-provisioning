<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('x_provisioning_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('request_id')->constrained('x_provisioning_requests')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->string('status', 40)->index();
            $table->string('activation_mode', 40);
            $table->json('snapshot');
            $table->char('snapshot_hash', 64)->index();
            $table->nullableMorphs('maker');
            $table->timestampTz('submitted_at')->nullable();
            $table->nullableMorphs('checker');
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('rejected_at')->nullable();
            $table->string('rejection_reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['request_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('x_provisioning_revisions');
    }
};
