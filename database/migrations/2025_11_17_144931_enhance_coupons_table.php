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
            // Hubspot linking
            $table->string('hubspot_company_id')->nullable()->after('code');
            $table->string('hubspot_contact_id')->nullable()->after('hubspot_company_id');
            $table->string('company_name')->nullable()->after('hubspot_contact_id');

            // Year-based expiration
            $table->integer('year')->nullable()->after('valid_until');

            // Manual vs auto-generated
            $table->boolean('is_manual')->default(false)->after('is_active');
            $table->string('generated_by')->nullable()->after('is_manual'); // User who created it

            // Usage context (null = all events, or specific event_id)
            $table->foreignId('restricted_to_event_id')->nullable()
                ->after('event_id')
                ->constrained('events')
                ->nullOnDelete();

            // Notes for internal use
            $table->text('notes')->nullable()->after('generated_by');

            $table->index(['hubspot_company_id']);
            $table->index(['year', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropIndex(['hubspot_company_id']);
            $table->dropIndex(['year', 'is_active']);

            $table->dropForeign(['restricted_to_event_id']);

            $table->dropColumn([
                'hubspot_company_id',
                'hubspot_contact_id',
                'company_name',
                'year',
                'is_manual',
                'generated_by',
                'restricted_to_event_id',
                'notes',
            ]);
        });
    }
};
