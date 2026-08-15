<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('x_provisioning_acceptances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('offer_id')->unique()->constrained('x_provisioning_offers')->restrictOnDelete();
            $table->string('candidate_type');
            $table->string('candidate_reference');
            $table->longText('evidence');
            $table->char('evidence_hash', 64)->index();
            $table->timestampTz('accepted_at');
            $table->timestamps();

            $table->index(['candidate_type', 'candidate_reference'], 'x_provisioning_acceptance_candidate');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('x_provisioning_acceptances');
    }
};
