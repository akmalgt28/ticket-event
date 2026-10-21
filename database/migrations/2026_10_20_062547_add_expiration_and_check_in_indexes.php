<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->index(['order_status', 'expires_at']);
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->index('checked_in_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasIndex('orders', 'orders_order_status_expires_at_index')) {
                $table->dropIndex('orders_order_status_expires_at_index');
            }
        });

        Schema::table('tickets', function (Blueprint $table): void {
            if (Schema::hasIndex('tickets', 'tickets_checked_in_at_index')) {
                $table->dropIndex('tickets_checked_in_at_index');
            }
        });
    }
};
