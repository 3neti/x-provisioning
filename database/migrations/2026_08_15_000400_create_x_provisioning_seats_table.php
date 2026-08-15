<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('x_provisioning_seats', function (Blueprint $table): void {
            $table->id();
            $table->ulid('reference')->unique();
            $table->string('seat_key', 100)->unique();
            $table->string('label', 120);
            $table->string('profile', 80)->index();
            $table->boolean('required')->default(true)->index();
            $table->string('status', 40)->index();
            $table->foreignId('request_id')->nullable()->unique()->constrained('x_provisioning_requests')->restrictOnDelete();
            $table->string('activated_subject_type')->nullable();
            $table->string('activated_subject_reference')->nullable();
            $table->timestampTz('activated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('x_provisioning_seats');
    }
};
