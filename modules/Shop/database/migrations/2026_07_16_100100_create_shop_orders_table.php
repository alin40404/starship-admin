<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 32)->comment('业务订单号');
            $table->unsignedBigInteger('member_id')->nullable()->comment('会员ID');
            $table->unsignedBigInteger('product_id')->comment('商品ID');
            $table->string('product_name', 120)->comment('商品名称快照');
            $table->unsignedInteger('amount')->comment('订单金额(分)');
            $table->unsignedTinyInteger('quantity')->default(1)->comment('数量');
            $table->tinyInteger('status')->default(0)->comment('0待支付,1已支付,2已取消,3已退款');
            $table->string('pay_channel', 32)->nullable()->comment('支付渠道');
            $table->dateTime('paid_at')->nullable()->comment('支付时间');
            $table->string('remark', 255)->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('order_no');
            $table->index('member_id');
            $table->index('product_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_orders');
    }
};
