<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable()->comment('系统分类ID(categories.type=product)');
            $table->unsignedBigInteger('brand_id')->nullable()->comment('品牌ID');
            $table->string('product_type', 30)->default('physical')->comment('商品类型:physical,virtual,digital');
            $table->string('fulfillment_type', 30)->default('shipping')->comment('交付方式:shipping,digital,card,code,manual');
            $table->string('name', 120)->comment('商品名称');
            $table->string('subtitle', 160)->nullable()->comment('副标题');
            $table->unsignedInteger('price')->default(0)->comment('售价(分)');
            $table->unsignedInteger('stock')->default(0)->comment('库存');
            $table->json('media')->nullable()->comment('媒体列表[{type:image|video,url}]');
            $table->text('description')->nullable()->comment('描述');
            $table->tinyInteger('status')->default(2)->comment('状态:0下架,1上架,2草稿');
            $table->integer('sort')->default(0)->comment('排序');
            $table->json('extra')->nullable()->comment('扩展属性');
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id');
            $table->index('brand_id');
            $table->index('product_type');
            $table->index('fulfillment_type');
            $table->index('status');
            $table->index(['sort', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_products');
    }
};
