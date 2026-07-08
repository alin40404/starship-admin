<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_operation_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable()->comment('操作人ID');
            $table->string('admin_name', 100)->nullable()->comment('操作人账号');
            $table->string('module', 50)->nullable()->comment('模块标识');
            $table->string('action', 100)->comment('操作动作');
            $table->string('title', 200)->comment('操作标题');
            $table->text('content')->nullable()->comment('请求摘要(JSON)');
            $table->string('method', 10)->nullable()->comment('HTTP方法');
            $table->string('url', 500)->nullable()->comment('请求URL');
            $table->unsignedSmallInteger('status_code')->nullable()->comment('响应状态码');
            $table->string('ip', 45)->nullable()->comment('IP');
            $table->string('user_agent', 500)->nullable()->comment('User-Agent');
            $table->timestamp('created_at')->nullable()->comment('创建时间');

            $table->index(['admin_id', 'created_at']);
            $table->index(['module', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_operation_logs');
    }
};
