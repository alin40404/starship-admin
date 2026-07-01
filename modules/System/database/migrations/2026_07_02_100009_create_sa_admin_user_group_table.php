<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Support\StarshipTables;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = StarshipTables::table('admin_user_group');
        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->comment('管理员ID');
            $table->unsignedBigInteger('user_group_id')->comment('用户组ID');

            $table->unique(['admin_id', 'user_group_id'], 'uk_admin_user_group');
            $table->index('user_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(StarshipTables::table('admin_user_group'));
    }
};
