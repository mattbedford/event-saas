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
        Schema::table('coupons', function (Blueprint $table) {
            // Coupon type (staff, member, etc.)
            $table->string('coupon_type')->default('custom')->after('code');

            // Scope: event-specific or global
            $table->enum('scope', ['event', 'global'])->default('event')->after('coupon_type');

            // Global usage limits (across all events in the year)
            $table->integer('max_uses_global')->nullable()->after('max_uses');

            // Per-event usage limits
            $table->integer('max_uses_per_event')->nullable()->after('max_uses_global');

            // Make event_id nullable for global coupons
            $table->unsignedBigInteger('event_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn([
                'coupon_type',
                'scope',
                'max_uses_global',
                'max_uses_per_event',
            ]);
        });
    }
};
