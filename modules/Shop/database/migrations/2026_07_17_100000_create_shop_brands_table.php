<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->comment('品牌名称');
            $table->string('logo', 255)->nullable()->comment('Logo');
            $table->integer('sort')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('1启用,0禁用');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index(['sort', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_brands');
    }
};
