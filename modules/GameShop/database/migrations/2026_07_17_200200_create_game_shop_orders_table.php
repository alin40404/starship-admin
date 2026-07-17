<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_shop_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 32)->unique();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedBigInteger('game_id');
            $table->unsignedBigInteger('product_id');
            $table->string('product_name', 120);
            $table->unsignedInteger('amount')->default(0)->comment('金额(分)');
            $table->unsignedInteger('quantity')->default(1);
            $table->tinyInteger('status')->default(0)->comment('0待支付1已支付2已完成3失败4退款');
            $table->string('pay_channel', 30)->nullable();
            $table->string('trade_no', 64)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('player_account', 120)->nullable()->comment('玩家账号');
            $table->string('server_name', 120)->nullable()->comment('区服');
            $table->string('role_id', 120)->nullable()->comment('角色ID');
            $table->string('fulfill_status', 30)->nullable()->comment('pending,success,failed');
            $table->json('fulfill_payload')->nullable();
            $table->string('remark', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('member_id');
            $table->index('game_id');
            $table->index('product_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_shop_orders');
    }
};
