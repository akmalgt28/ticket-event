<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('proof_path')->nullable()->after('raw_response');
            $table->timestampTz('proof_uploaded_at')->nullable()->after('proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn(['proof_path', 'proof_uploaded_at']);
        });
    }
};
