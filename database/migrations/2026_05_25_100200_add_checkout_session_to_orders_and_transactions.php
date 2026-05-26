<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('checkout_session_id')->nullable()->after('delivery_address_id')->constrained()->nullOnDelete();
            $table->timestamp('paid_at')->nullable()->after('placed_at');
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->foreignId('checkout_session_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('checkout_session_id');
            $table->dropColumn('paid_at');
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('checkout_session_id');
        });
    }
};
