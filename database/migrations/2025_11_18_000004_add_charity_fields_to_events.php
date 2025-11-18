<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('charity_enabled')->default(false)->after('settings');
            $table->string('charity_name')->nullable()->after('charity_enabled');
            $table->text('charity_description')->nullable()->after('charity_name');
            $table->string('charity_logo_url')->nullable()->after('charity_description');
            $table->string('charity_website_url')->nullable()->after('charity_logo_url');
            $table->string('charity_donation_url')->nullable()->after('charity_website_url');
            $table->decimal('charity_suggested_amount', 10, 2)->nullable()->after('charity_donation_url');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'charity_enabled',
                'charity_name',
                'charity_description',
                'charity_logo_url',
                'charity_website_url',
                'charity_donation_url',
                'charity_suggested_amount',
            ]);
        });
    }
};
