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
        Schema::table('stores', function (Blueprint $table): void {
            $table->string('phone', 32)->nullable()->after('address');
            $table->time('opening_time')->nullable()->after('phone');
            $table->time('closing_time')->nullable()->after('opening_time');
            $table->decimal('commission_rate', 5, 2)->default(15)->after('closing_time');
            $table->timestamp('approved_at')->nullable()->after('commission_rate');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'phone',
                'opening_time',
                'closing_time',
                'commission_rate',
                'approved_at',
            ]);
        });
    }
};
