<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Support\StarshipTables;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(StarshipTables::table('categories'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父级ID');
            $table->string('type', 30)->default('')->comment('栏目类型，对应字典 category_type');
            $table->string('name', 30)->comment('分类名称');
            $table->string('nickname', 50)->default('')->comment('分类别名');
            $table->string('flag', 100)->nullable()->comment('标志: hot,index,recommend');
            $table->string('image', 255)->nullable()->comment('图片');
            $table->string('keywords', 255)->nullable()->comment('关键字');
            $table->string('description', 255)->nullable()->comment('描述');
            $table->string('diyname', 30)->nullable()->comment('自定义名称');
            $table->integer('sort')->default(0)->comment('权重排序');
            $table->string('status', 30)->default('normal')->comment('状态: normal,hidden');
            $table->timestamps();

            $table->index(['sort', 'id']);
            $table->index('parent_id');
            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(StarshipTables::table('categories'));
    }
};
