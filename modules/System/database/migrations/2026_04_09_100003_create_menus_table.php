<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父级ID');
            $table->tinyInteger('type')->comment('类型:1目录,2菜单,3按钮,4内嵌,5外链');
            $table->string('name', 100)->comment('名称');
            $table->string('title', 100)->nullable()->comment('菜单标题(展示)，空则同名称');
            $table->string('path', 255)->nullable()->comment('路由路径');
            $table->string('active_path', 255)
                ->nullable()
                ->comment('激活路径：侧栏高亮时指向的已有菜单路由');
            $table->string('component', 255)->nullable()->comment('页面组件路径');
            $table->string('permission', 150)->nullable()->comment('权限标识');
            $table->string('icon', 100)->nullable()->comment('图标');
            $table->string('active_icon', 100)->nullable()->comment('激活图标');
            $table->string('badge_type', 20)->nullable()->comment('徽标类型:dot,normal');
            $table->string('badge', 50)->nullable()->comment('徽标内容');
            $table->string('badge_variants', 30)->nullable()->comment('徽标样式');
            $table->string('link_url', 500)->nullable()->comment('外链地址，type=5');
            $table->string('iframe_src', 500)->nullable()->comment('内嵌地址，type=4');
            $table->integer('sort')->default(0)->comment('排序');
            $table->json('other_settings')->nullable()->comment(
                '其它设置：hide_menu/hide_submenu/hide_in_breadcrumb/hide_in_tab/affix_tab/keep_alive，均为0|1'
            );
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
        Schema::dropIfExists('menus');
    }
};
