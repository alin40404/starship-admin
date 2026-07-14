<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->comment('用户名');
            $table->string('nickname', 100)->nullable()->comment('昵称');
            $table->string('password')->comment('密码哈希');
            $table->string('avatar', 255)->nullable()->comment('头像');
            $table->string('email', 100)->nullable()->comment('邮箱');
            $table->string('mobile', 20)->nullable()->comment('手机号');
            $table->tinyInteger('status')->default(1)->comment('状态:1启用,0禁用');
            $table->string('last_login_ip', 45)->nullable()->comment('最后登录IP');
            $table->dateTime('last_login_at')->nullable()->comment('最后登录时间');
            $table->string('remark', 255)->nullable()->comment('备注');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('username');
            $table->unique('email');
            $table->unique('mobile');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
