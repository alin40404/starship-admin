<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_card_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sku_id')->comment('关联 SKU');
            $table->string('code', 128)->comment('卡密');
            $table->tinyInteger('status')->default(0)->comment('0未售,1已售,2作废');
            $table->unsignedBigInteger('order_id')->nullable()->comment('售出订单');
            $table->unsignedBigInteger('order_item_id')->nullable()->comment('售出订单行');
            $table->dateTime('sold_at')->nullable()->comment('售出时间');
            $table->timestamps();

            $table->unique('code');
            $table->index(['sku_id', 'status']);
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_card_codes');
    }
};
