<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('插件名称');
            $table->string('slug', 100)->comment('插件标识');
            $table->string('type', 50)->nullable()->comment('插件类型');
            $table->string('version', 50)->default('1.0.0')->comment('当前版本');
            $table->string('description', 500)->nullable()->comment('插件描述');
            $table->string('author', 100)->nullable()->comment('作者');
            $table->string('namespace', 255)->nullable()->comment('命名空间');
            $table->string('path', 255)->nullable()->comment('插件目录');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态:0=未安装,1=已安装,2=已启用,3=已禁用');
            $table->json('config')->nullable()->comment('插件 manifest 快照');
            $table->timestamp('installed_at')->nullable()->comment('安装时间');
            $table->timestamp('enabled_at')->nullable()->comment('启用时间');
            $table->timestamps();

            $table->unique('slug');
            $table->index('status');
        });

        Schema::create('plugin_configs', function (Blueprint $table) {
            $table->id();
            $table->string('plugin_slug', 100)->comment('插件标识');
            $table->string('config_key', 100)->comment('配置键');
            $table->text('config_value')->nullable()->comment('配置值');
            $table->string('value_type', 30)->default('string')->comment('值类型:string,int,bool,json,array');
            $table->unsignedTinyInteger('is_encrypted')->default(0)->comment('是否加密:0=否,1=是');
            $table->timestamps();

            $table->unique(['plugin_slug', 'config_key']);
            $table->index('plugin_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_configs');
        Schema::dropIfExists('plugins');
    }
};
