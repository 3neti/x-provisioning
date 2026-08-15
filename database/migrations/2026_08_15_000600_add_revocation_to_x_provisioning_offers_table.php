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
            $table->timestampTz('revoked_at')->nullable()->after('activated_at');
            $table->string('revocation_reference')->nullable()->unique()->after('activation_reference');
        });
    }

    public function down(): void
    {
        Schema::table('x_provisioning_offers', function (Blueprint $table): void {
            $table->dropUnique(['revocation_reference']);
            $table->dropColumn(['revoked_at', 'revocation_reference']);
        });
    }
};
