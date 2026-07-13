<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_login_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable()->comment('管理员ID');
            $table->string('username', 50)->comment('登录账号');
            $table->string('action', 20)->default('login')->comment('动作:login=登录,logout=退出');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态:0=失败,1=成功');
            $table->string('message', 255)->nullable()->comment('说明');
            $table->string('ip', 45)->nullable()->comment('IP');
            $table->string('user_agent', 500)->nullable()->comment('User-Agent');
            $table->timestamp('created_at')->nullable()->comment('创建时间');

            $table->index(['username', 'created_at']);
            $table->index(['admin_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_login_logs');
    }
};
