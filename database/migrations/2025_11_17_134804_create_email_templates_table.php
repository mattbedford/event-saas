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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Internal name
            $table->string('slug')->unique();
            $table->string('subject');
            $table->text('html_content'); // HTML email body
            $table->text('text_content')->nullable(); // Plain text fallback
            $table->json('available_variables')->nullable(); // List of available placeholders
            $table->boolean('is_system')->default(false); // System templates can't be deleted
            $table->timestamps();
        });

        Schema::create('email_chains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_template_id')->constrained()->cascadeOnDelete();
            $table->integer('send_after_minutes'); // Send X minutes after registration
            $table->integer('order')->default(0); // Order in the chain
            $table->boolean('is_active')->default(true);
            $table->boolean('send_only_before_event')->default(true); // Don't send after event date
            $table->timestamps();

            $table->index(['event_id', 'order']);
        });

        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_chain_id')->nullable()->constrained()->nullOnDelete();
            $table->string('brevo_message_id')->nullable();
            $table->enum('status', ['pending', 'sent', 'delivered', 'opened', 'clicked', 'bounced', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->json('brevo_stats')->nullable(); // Store Brevo API stats
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['registration_id', 'email_template_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('email_chains');
        Schema::dropIfExists('email_templates');
    }
};
