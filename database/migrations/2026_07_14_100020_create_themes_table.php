<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('themes')) {
            return;
        }

        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('主题名称');
            $table->string('slug', 100)->comment('主题标识');
            $table->string('type', 50)->default('frontend')->comment('主题类型:frontend 等');
            $table->string('version', 50)->default('1.0.0')->comment('当前版本');
            $table->string('title', 100)->nullable()->comment('展示标题');
            $table->string('description', 500)->nullable()->comment('主题描述');
            $table->string('author', 100)->nullable()->comment('作者');
            $table->string('path', 255)->nullable()->comment('主题目录');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态:0=未安装,1=已安装,2=已启用,3=已禁用');
            $table->unsignedTinyInteger('is_active')->default(0)->comment('是否当前主题:0=否,1=是');
            $table->json('config')->nullable()->comment('theme.json 快照');
            $table->timestamp('installed_at')->nullable()->comment('安装时间');
            $table->timestamp('enabled_at')->nullable()->comment('启用时间');
            $table->timestamps();

            $table->unique('slug');
            $table->index('status');
            $table->index(['type', 'status']);
            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
