<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id')->comment('会员ID');
            $table->timestamps();

            $table->unique('member_id');
        });

        Schema::create('shop_cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id')->comment('购物车ID');
            $table->unsignedBigInteger('product_id')->comment('SPU ID');
            $table->unsignedBigInteger('sku_id')->comment('SKU ID');
            $table->unsignedInteger('quantity')->default(1)->comment('数量');
            $table->timestamps();

            $table->unique(['cart_id', 'sku_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_cart_items');
        Schema::dropIfExists('shop_carts');
    }
};
