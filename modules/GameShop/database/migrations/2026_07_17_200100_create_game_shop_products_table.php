<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_shop_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id')->comment('游戏ID');
            $table->string('name', 120)->comment('商品名称');
            $table->string('sku_code', 64)->nullable()->comment('SKU编码');
            $table->string('product_kind', 30)->default('topup')->comment('topup直充,cdkey卡密,item道具');
            $table->unsignedInteger('price')->default(0)->comment('售价(分)');
            $table->unsignedInteger('stock')->default(0)->comment('库存');
            $table->string('cover', 255)->nullable();
            $table->text('description')->nullable();
            $table->json('extra')->nullable()->comment('面额/区服等扩展');
            $table->tinyInteger('status')->default(1);
            $table->integer('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('game_id');
            $table->index('product_kind');
            $table->index('status');
            $table->index(['sort', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_shop_products');
    }
};
