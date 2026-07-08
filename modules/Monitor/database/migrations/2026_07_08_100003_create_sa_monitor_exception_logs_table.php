<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_exception_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('trace_id')->comment('追踪ID(关联文件日志)');
            $table->string('exception_class', 255)->comment('异常类');
            $table->string('message', 500)->comment('异常消息摘要');
            $table->string('file', 500)->nullable()->comment('文件');
            $table->unsignedInteger('line')->nullable()->comment('行号');
            $table->string('request_method', 10)->nullable()->comment('HTTP方法');
            $table->string('request_url', 500)->nullable()->comment('请求URL');
            $table->unsignedBigInteger('admin_id')->nullable()->comment('管理员ID');
            $table->string('admin_name', 100)->nullable()->comment('管理员账号');
            $table->string('ip', 45)->nullable()->comment('IP');
            $table->string('user_agent', 500)->nullable()->comment('User-Agent');
            $table->timestamp('created_at')->nullable()->comment('创建时间');

            $table->unique('trace_id');
            $table->index(['exception_class', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_exception_logs');
    }
};
