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
            $table->string('name', 120)->comment('商品名称');
            $table->unsignedInteger('price')->default(0)->comment('售价(分)');
            $table->unsignedInteger('stock')->default(0)->comment('库存');
            $table->string('cover', 255)->nullable()->comment('封面图');
            $table->text('description')->nullable()->comment('描述');
            $table->tinyInteger('status')->default(1)->comment('状态:1上架,0下架');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_products');
    }
};
