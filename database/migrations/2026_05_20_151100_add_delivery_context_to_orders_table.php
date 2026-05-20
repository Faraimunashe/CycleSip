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
            $table->foreignId('delivery_address_id')->nullable()->after('delivery_zone_id')->constrained('user_addresses')->nullOnDelete();
            $table->string('recipient_name', 120)->nullable()->after('customer_phone');
            $table->string('recipient_phone', 32)->nullable()->after('recipient_name');
            $table->text('delivery_instructions')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('delivery_address_id');
            $table->dropColumn([
                'recipient_name',
                'recipient_phone',
                'delivery_instructions',
            ]);
        });
    }
};
