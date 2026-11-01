<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->string('organizer_name')->nullable();
        });

        DB::table('events')
            ->whereNull('organizer_name')
            ->update(['organizer_name' => 'Penyelenggara Event']);
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn('organizer_name');
        });
    }
};
