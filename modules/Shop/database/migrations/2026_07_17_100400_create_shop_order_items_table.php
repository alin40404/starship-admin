<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->unsignedBigInteger('product_id')->comment('SPU ID');
            $table->unsignedBigInteger('sku_id')->nullable()->comment('SKU ID');
            $table->string('product_name', 120)->comment('商品名称快照');
            $table->string('sku_code', 64)->nullable()->comment('SKU 编码快照');
            $table->json('specs')->nullable()->comment('规格快照');
            $table->unsignedInteger('price')->comment('单价(分)');
            $table->unsignedInteger('quantity')->default(1)->comment('数量');
            $table->unsignedInteger('amount')->comment('行金额(分)');
            $table->string('fulfillment_status', 30)->nullable()->comment('行履约状态');
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
            $table->index('sku_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_order_items');
    }
};
