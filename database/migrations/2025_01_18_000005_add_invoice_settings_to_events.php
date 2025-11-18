<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('automatic_invoices_enabled')->default(true)->after('charity_suggested_amount');
            $table->text('invoice_message')->nullable()->after('automatic_invoices_enabled');
            $table->string('invoice_contact_email')->nullable()->after('invoice_message');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'automatic_invoices_enabled',
                'invoice_message',
                'invoice_contact_email',
            ]);
        });
    }
};
