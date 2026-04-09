<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Support\StarshipTables;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(StarshipTables::table('roles'), function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('角色名称');
            $table->string('code', 100)->comment('角色编码');
            $table->tinyInteger('data_scope')->default(1)->comment('数据范围:1全部,2本部门,3本部门及下级,4本人,5自定义');
            $table->tinyInteger('status')->default(1)->comment('状态:1启用,0禁用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->string('remark', 255)->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('code');
            $table->index('status');
            $table->index('sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(StarshipTables::table('roles'));
    }
};
