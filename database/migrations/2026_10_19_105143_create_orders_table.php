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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->string('order_number')->unique();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 30);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->string('payment_method')->nullable();
            $table->string('order_status')->default('pending');
            $table->string('payment_status')->default('pending');
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'order_status']);
            $table->index(['customer_email', 'payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
