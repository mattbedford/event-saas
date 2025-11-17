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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');

            $table->string('code')->index();
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_value', 10, 2);

            $table->integer('max_uses')->nullable(); // null = unlimited
            $table->integer('used_count')->default(0);

            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();

            $table->boolean('is_active')->default(true);
            $table->json('restrictions')->nullable(); // For future use (e.g., specific ticket types)

            $table->timestamps();
            $table->softDeletes();

            // Ensure coupon codes are unique per event
            $table->unique(['event_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
