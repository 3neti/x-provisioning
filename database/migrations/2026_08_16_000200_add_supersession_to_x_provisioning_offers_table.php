<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('x_provisioning_offers', function (Blueprint $table): void {
            $table->foreignId('superseded_by_offer_id')
                ->nullable()
                ->constrained('x_provisioning_offers')
                ->restrictOnDelete();
            $table->string('supersession_reference')->nullable()->unique();
            $table->timestampTz('superseded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('x_provisioning_offers', function (Blueprint $table): void {
            $table->dropForeign(['superseded_by_offer_id']);
            $table->dropUnique(['supersession_reference']);
            $table->dropColumn(['superseded_by_offer_id', 'supersession_reference', 'superseded_at']);
        });
    }
};
