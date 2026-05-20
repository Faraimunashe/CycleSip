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
        Schema::table('store_products', function (Blueprint $table): void {
            $table->boolean('is_available')->default(true)->after('stock_quantity');
            $table->decimal('promotion_price', 10, 2)->nullable()->after('is_available');
            $table->timestamp('promotion_ends_at')->nullable()->after('promotion_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_products', function (Blueprint $table): void {
            $table->dropColumn(['is_available', 'promotion_price', 'promotion_ends_at']);
        });
    }
};
