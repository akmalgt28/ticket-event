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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('banner_path')->nullable();
            $table->dateTimeTz('start_at');
            $table->dateTimeTz('end_at');
            $table->string('location_name');
            $table->text('location_address');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('status')->default('draft');
            $table->timestampTz('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'start_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
