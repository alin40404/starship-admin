<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sa_admins', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->comment('用户名');
            $table->string('nickname', 100)->nullable()->comment('昵称');
            $table->string('real_name', 100)->nullable()->comment('真实姓名');
            $table->string('password')->comment('密码哈希');
            $table->string('avatar', 255)->nullable()->comment('头像');
            $table->string('email', 100)->nullable()->comment('邮箱');
            $table->string('mobile', 20)->nullable()->comment('手机号');
            $table->unsignedBigInteger('dept_id')->nullable()->comment('主部门ID');
            $table->tinyInteger('status')->default(1)->comment('状态:1启用,0禁用');
            $table->tinyInteger('is_super')->default(0)->comment('是否超管:1是,0否');
            $table->string('last_login_ip', 45)->nullable()->comment('最后登录IP');
            $table->dateTime('last_login_at')->nullable()->comment('最后登录时间');
            $table->string('remark', 255)->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('username');
            $table->index('mobile');
            $table->index('email');
            $table->index('dept_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sa_admins');
    }
};
