<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generator_tables', function (Blueprint $table) {
            $table->id();
            $table->string('table_name', 128)->comment('物理表名');
            $table->string('class_name', 100)->comment('模型/控制器类名');
            $table->string('module_slug', 50)->comment('目标模块，如 System');
            $table->string('business_name', 100)->comment('业务中文名');
            $table->string('route_group', 50)->default('org')->comment('前端 views 分组');
            $table->string('route_path', 150)->comment('前端路由，如 /org/articles');
            $table->string('api_prefix', 100)->comment('API 路径段，如 articles');
            $table->string('permission_prefix', 150)->comment('权限前缀，如 system.articles');
            $table->string('menu_parent_path', 150)->nullable()->comment('菜单父级路由');
            $table->string('template_type', 30)->default('crud')->comment('模板类型');
            $table->json('options')->nullable()->comment('生成选项');
            $table->string('status', 20)->default('draft')->comment('draft/generated');
            $table->timestamp('generated_at')->nullable()->comment('最后生成时间');
            $table->timestamps();

            $table->unique('table_name');
            $table->index('module_slug');
            $table->index('status');
        });

        Schema::create('generator_columns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('generator_table_id')->comment('表配置ID');
            $table->string('column_name', 100)->comment('字段名');
            $table->string('column_type', 100)->comment('数据库类型');
            $table->string('column_comment', 255)->nullable()->comment('字段注释');
            $table->string('ts_type', 50)->default('string')->comment('TS 类型');
            $table->boolean('is_pk')->default(false);
            $table->boolean('is_list')->default(true);
            $table->boolean('is_search')->default(false);
            $table->boolean('is_form')->default(true);
            $table->string('form_component', 50)->default('Input')->comment('表单组件');
            $table->string('dict_code', 100)->nullable()->comment('字典编码');
            $table->json('validation_rules')->nullable()->comment('验证规则');
            $table->integer('sort')->default(0);
            $table->timestamps();

            $table->index('generator_table_id');
            $table->unique(['generator_table_id', 'column_name']);
        });

        Schema::create('generator_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('generator_table_id')->comment('表配置ID');
            $table->json('files')->comment('生成文件清单');
            $table->unsignedBigInteger('operator_id')->nullable()->comment('操作人ID');
            $table->string('operator_name', 100)->nullable()->comment('操作人');
            $table->string('source', 20)->default('cli')->comment('cli/web');
            $table->json('options')->nullable()->comment('生成参数');
            $table->timestamps();

            $table->index('generator_table_id');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generator_records');
        Schema::dropIfExists('generator_columns');
        Schema::dropIfExists('generator_tables');
    }
};
