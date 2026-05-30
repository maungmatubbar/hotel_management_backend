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
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->bigInteger('room_id')->nullable()->index();
            $table->string('guest_name');
            $table->string('guest_phone')->nullable();
            $table->string('guest_email')->nullable();
            $table->text('guest_address')->nullable();
            $table->string('room');
            $table->string('assigned_room_number', 50);
            $table->string('nid_number')->nullable();
            $table->unsignedInteger('room_quantity')->default(1);
            $table->decimal('discount', 12, 2)->default(0);
            $table->string('promo_code')->nullable();
            $table->date('check_in');
            $table->date('check_out');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
