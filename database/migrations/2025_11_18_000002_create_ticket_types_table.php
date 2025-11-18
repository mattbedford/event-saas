<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g., "Early Bird", "Regular", "VIP"
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('quantity_available')->nullable(); // null = unlimited
            $table->integer('quantity_sold')->default(0);
            $table->timestamp('sale_starts_at')->nullable(); // When this ticket becomes available
            $table->timestamp('sale_ends_at')->nullable(); // When this ticket stops being available
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['event_id', 'is_active', 'sale_starts_at', 'sale_ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};
