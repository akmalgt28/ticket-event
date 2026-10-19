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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ticket_number')->unique();
            $table->string('qr_token_hash')->unique();
            $table->string('status')->default('issued');
            $table->timestampTz('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
