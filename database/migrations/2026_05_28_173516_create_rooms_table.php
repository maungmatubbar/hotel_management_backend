<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->string('room_name');
            $table->string('room_type', 20)->default('non_ac');
            $table->unsignedInteger('capacity');
            $table->decimal('rate', 12, 2);
            $table->unsignedInteger('available_rooms')->default(0);
            $table->string('status', 50)->default('available');
            $table->json('amenities')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
