<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('api_key')->nullable()->unique()->after('slug');
            $table->string('webhook_url')->nullable()->after('api_key');
            $table->json('webhook_events')->nullable()->after('webhook_url');
            $table->string('webhook_secret')->nullable()->after('webhook_events');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['api_key', 'webhook_url', 'webhook_events', 'webhook_secret']);
        });
    }
};
