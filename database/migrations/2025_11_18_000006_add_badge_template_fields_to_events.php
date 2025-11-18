<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('badge_background_pdf')->nullable()->after('settings');
            $table->json('badge_template_config')->nullable()->after('badge_background_pdf');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['badge_background_pdf', 'badge_template_config']);
        });
    }
};
