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
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');

            // User information
            $table->string('email')->index();
            $table->string('name');
            $table->string('surname');
            $table->string('company')->nullable();
            $table->string('phone')->nullable();
            $table->json('additional_fields')->nullable();

            // Payment information
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'failed', 'refunded'])->default('pending');
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('expected_amount', 10, 2);
            $table->string('stripe_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable();

            // Integration IDs
            $table->string('hubspot_id')->nullable()->index();

            // Coupon usage
            $table->string('coupon_code')->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);

            // Badge generation
            $table->boolean('badge_generated')->default(false);
            $table->timestamp('badge_generated_at')->nullable();
            $table->string('badge_file_path')->nullable();

            // Email tracking
            $table->boolean('confirmation_sent')->default(false);
            $table->timestamp('confirmation_sent_at')->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->timestamp('reminder_sent_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Composite index for event + email lookups
            $table->index(['event_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
