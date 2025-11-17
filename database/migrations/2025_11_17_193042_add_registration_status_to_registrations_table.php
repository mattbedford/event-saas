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
        Schema::table('registrations', function (Blueprint $table) {
            // Registration workflow status
            $table->enum('registration_status', [
                'draft',              // User filled form, not submitted yet
                'pending_payment',    // Stripe session created, awaiting payment
                'payment_processing', // User completed Stripe, awaiting webhook
                'confirmed',          // Fully confirmed (paid or 100% coupon)
                'abandoned',          // User bailed during checkout
                'payment_failed'      // Payment failed, can retry
            ])->default('draft')->after('payment_status');

            // Track when registration was confirmed
            $table->timestamp('confirmed_at')->nullable()->after('registration_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn(['registration_status', 'confirmed_at']);
        });
    }
};
