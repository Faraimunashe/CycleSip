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
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('rider_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('delivery_zone_id')->nullable()->after('store_id')->constrained()->nullOnDelete();
            $table->decimal('subtotal_amount', 10, 2)->default(0)->after('payment_method');
            $table->decimal('delivery_fee', 10, 2)->default(0)->after('subtotal_amount');
            $table->decimal('platform_commission', 10, 2)->default(0)->after('delivery_fee');
            $table->string('payment_status')->default('unpaid')->after('total_amount');
            $table->string('customer_phone', 32)->nullable()->after('delivery_address');
            $table->timestamp('accepted_at')->nullable()->after('placed_at');
            $table->timestamp('delivered_at')->nullable()->after('accepted_at');
            $table->timestamp('completed_at')->nullable()->after('delivered_at');
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            $table->string('cancellation_reason')->nullable()->after('cancelled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('rider_id');
            $table->dropConstrainedForeignId('delivery_zone_id');
            $table->dropColumn([
                'subtotal_amount',
                'delivery_fee',
                'platform_commission',
                'payment_status',
                'customer_phone',
                'accepted_at',
                'delivered_at',
                'completed_at',
                'cancelled_at',
                'cancellation_reason',
            ]);
        });
    }
};
