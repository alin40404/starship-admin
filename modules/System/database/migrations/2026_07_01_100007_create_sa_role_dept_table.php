<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Support\StarshipTables;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = StarshipTables::table('role_dept');
        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id')->comment('角色ID');
            $table->unsignedBigInteger('dept_id')->comment('部门ID');

            $table->unique(['role_id', 'dept_id'], 'uk_role_dept');
            $table->index('dept_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(StarshipTables::table('role_dept'));
    }
};
