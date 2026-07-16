<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->comment('栏目名称');
            $table->string('slug', 80)->comment('标识');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父级ID');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('状态:1启用,0禁用');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('slug');
            $table->index('parent_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_categories');
    }
};
