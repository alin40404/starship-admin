<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dict_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dict_type_id')->comment('字典类型ID');
            $table->string('label', 100)->comment('显示文本');
            $table->string('value', 100)->comment('字典值');
            $table->string('color', 50)->nullable()->comment('颜色标记');
            $table->integer('sort')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('状态:1启用,0禁用');
            $table->string('remark', 255)->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['dict_type_id', 'value'], 'uk_type_value');
            $table->index('status');
            $table->index('sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dict_items');
    }
};
