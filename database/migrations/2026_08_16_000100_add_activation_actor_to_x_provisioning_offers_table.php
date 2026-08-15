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
            $table->nullableMorphs('activated_by', 'x_provisioning_offers_activated_by_idx');
        });
    }

    public function down(): void
    {
        Schema::table('x_provisioning_offers', function (Blueprint $table): void {
            $table->dropMorphs('activated_by', 'x_provisioning_offers_activated_by_idx');
        });
    }
};
