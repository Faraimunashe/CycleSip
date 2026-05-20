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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone', 32)->nullable()->after('email');
            $table->date('date_of_birth')->nullable()->after('phone');
            $table->timestamp('age_verified_at')->nullable()->after('date_of_birth');
            $table->string('status')->default('active')->after('age_verified_at');
            $table->timestamp('last_seen_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'phone',
                'date_of_birth',
                'age_verified_at',
                'status',
                'last_seen_at',
            ]);
        });
    }
};
