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
        Schema::table('events', function (Blueprint $table) {
            // Remove per-event API keys (now shared in config)
            $table->dropColumn([
                'stripe_public_key',
                'stripe_secret_key',
                'stripe_webhook_secret',
                'hubspot_api_key',
                'hubspot_portal_id',
                'hubspot_settings',
            ]);

            // Add per-event identifiers for shared services
            $table->string('stripe_product_id')->nullable()->after('ticket_price');
            $table->string('hubspot_list_id')->nullable()->after('stripe_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Restore per-event API key columns
            $table->string('stripe_public_key')->nullable();
            $table->string('stripe_secret_key')->nullable();
            $table->string('stripe_webhook_secret')->nullable();
            $table->string('hubspot_api_key')->nullable();
            $table->string('hubspot_portal_id')->nullable();
            $table->json('hubspot_settings')->nullable();

            // Remove shared service identifiers
            $table->dropColumn(['stripe_product_id', 'hubspot_list_id']);
        });
    }
};
