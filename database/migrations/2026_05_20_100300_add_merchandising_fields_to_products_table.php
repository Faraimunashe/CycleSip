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
        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('product_category_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('brand')->nullable()->after('name');
            $table->string('image_url')->nullable()->after('description');
            $table->boolean('is_featured')->default(false)->after('image_url');
            $table->boolean('is_promoted')->default(false)->after('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('product_category_id');
            $table->dropColumn(['brand', 'image_url', 'is_featured', 'is_promoted']);
        });
    }
};
