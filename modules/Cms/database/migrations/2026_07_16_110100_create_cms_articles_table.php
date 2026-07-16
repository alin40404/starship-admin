<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_articles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable()->comment('系统分类ID(categories.type=article)');
            $table->string('title', 160)->comment('标题');
            $table->string('slug', 160)->comment('标识');
            $table->string('summary', 500)->nullable()->comment('摘要');
            $table->longText('content')->nullable()->comment('正文');
            $table->string('cover', 255)->nullable()->comment('封面');
            $table->tinyInteger('status')->default(0)->comment('0草稿,1已发布');
            $table->dateTime('published_at')->nullable()->comment('发布时间');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('slug');
            $table->index('category_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_articles');
    }
};
