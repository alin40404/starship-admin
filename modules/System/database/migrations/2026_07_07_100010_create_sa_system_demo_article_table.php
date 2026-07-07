<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_demo_article', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200)->comment('标题');
            $table->string('author', 100)->nullable()->comment('作者');
            $table->text('content')->nullable()->comment('内容');
            $table->tinyInteger('status')->default(1)->comment('状态:1启用,0禁用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->string('remark', 255)->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_demo_article');
    }
};
