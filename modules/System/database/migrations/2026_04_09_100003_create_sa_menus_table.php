<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sa_menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父级ID');
            $table->tinyInteger('type')->comment('类型:1目录,2菜单,3按钮');
            $table->string('name', 100)->comment('名称');
            $table->string('path', 255)->nullable()->comment('路由路径');
            $table->string('component', 255)->nullable()->comment('前端组件路径');
            $table->string('permission', 150)->nullable()->comment('权限标识');
            $table->string('icon', 100)->nullable()->comment('图标');
            $table->integer('sort')->default(0)->comment('排序');
            $table->tinyInteger('visible')->default(1)->comment('是否显示:1是,0否');
            $table->tinyInteger('keep_alive')->default(0)->comment('是否缓存');
            $table->tinyInteger('status')->default(1)->comment('状态:1启用,0禁用');
            $table->string('remark', 255)->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();

            $table->index('parent_id');
            $table->index('type');
            $table->index('permission');
            $table->index('status');
            $table->index('sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sa_menus');
    }
};
