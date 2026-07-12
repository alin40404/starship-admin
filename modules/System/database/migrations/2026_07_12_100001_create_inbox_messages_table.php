<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbox_messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('admin_id')->comment('收件管理员ID');
            $table->string('type', 20)->comment('消息类型:system,todo,account');
            $table->string('category', 20)->default('notice')->comment('展示分类:notice,approval,task,crm,alert');
            $table->string('sub_type', 50)->nullable()->comment('业务子类型，如 leave、lead_assign');
            $table->string('priority', 10)->default('normal')->comment('优先级:low,normal,high,urgent');
            $table->string('biz_type', 50)->nullable()->comment('关联业务类型，如 oa_leave、crm_lead');
            $table->unsignedBigInteger('biz_id')->nullable()->comment('关联业务主键');
            $table->unsignedBigInteger('sender_id')->nullable()->comment('发起人管理员ID');
            $table->json('payload')->nullable()->comment('扩展展示与操作配置 JSON');
            $table->string('status', 20)->nullable()->comment('业务状态:pending,done,expired,cancelled');
            $table->timestamp('action_deadline')->nullable()->comment('待处理截止时间');
            $table->string('group_key', 100)->nullable()->comment('同单据聚合键，如 leave:123');
            $table->string('title', 200)->comment('标题');
            $table->text('content')->nullable()->comment('正文摘要');
            $table->string('link', 500)->nullable()->comment('跳转链接');
            $table->unsignedTinyInteger('is_read')->default(0)->comment('是否已读:0未读,1已读');
            $table->timestamp('read_at')->nullable()->comment('已读时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['admin_id', 'is_read', 'created_at'], 'inbox_admin_read_created_idx');
            $table->index(['admin_id', 'category', 'is_read'], 'inbox_admin_category_read_idx');
            $table->index(['group_key'], 'inbox_group_key_idx');
            $table->index(['created_at'], 'inbox_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbox_messages');
    }
};
