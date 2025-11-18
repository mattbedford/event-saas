<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->foreignId('ticket_type_id')->nullable()->after('event_id')->constrained()->nullOnDelete();
            $table->string('ticket_type_name')->nullable()->after('ticket_type_id'); // Store name for historical purposes
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropForeign(['ticket_type_id']);
            $table->dropColumn(['ticket_type_id', 'ticket_type_name']);
        });
    }
};
