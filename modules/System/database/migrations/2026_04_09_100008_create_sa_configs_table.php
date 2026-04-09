<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sa_configs', function (Blueprint $table) {
            $table->id();
            $table->string('group_name', 100)->default('default')->comment('配置分组');
            $table->string('name', 100)->comment('配置名称');
            $table->string('key', 150)->comment('配置键');
            $table->longText('value')->nullable()->comment('配置值');
            $table->string('type', 50)->default('string')->comment('值类型:string,number,bool,json');
            $table->integer('sort')->default(0)->comment('排序');
            $table->string('remark', 255)->nullable()->comment('备注');
            $table->timestamps();

            $table->unique('key');
            $table->index('group_name');
            $table->index('sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sa_configs');
    }
};
