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
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->unsigned();
            $table->unsignedInteger('quota');
            $table->unsignedInteger('sold_count')->default(0);
            $table->dateTimeTz('sale_start_at')->nullable();
            $table->dateTimeTz('sale_end_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['event_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};
