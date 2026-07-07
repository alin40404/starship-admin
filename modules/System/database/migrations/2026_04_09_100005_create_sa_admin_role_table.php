<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_role', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->comment('管理员ID');
            $table->unsignedBigInteger('role_id')->comment('角色ID');

            $table->unique(['admin_id', 'role_id'], 'uk_admin_role');
            $table->index('role_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_role');
    }
};
