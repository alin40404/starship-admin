<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('storage', 50)->default('public')->comment('存储驱动');
            $table->string('category', 50)->nullable()->comment('附件分类，对应字典 attachment_category');
            $table->string('file_name', 255)->comment('原始文件名');
            $table->string('file_path', 500)->comment('文件路径');
            $table->string('file_url', 500)->nullable()->comment('访问地址');
            $table->string('file_ext', 20)->nullable()->comment('扩展名');
            $table->string('mime_type', 100)->nullable()->comment('MIME类型');
            $table->unsignedBigInteger('file_size')->default(0)->comment('文件大小');
            $table->string('hash', 64)->nullable()->comment('文件哈希');
            $table->unsignedBigInteger('admin_id')->nullable()->comment('上传管理员ID');
            $table->timestamps();

            $table->index('storage');
            $table->index('category');
            $table->index('admin_id');
            $table->index('hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
