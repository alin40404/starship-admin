<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_menu', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id')->comment('角色ID');
            $table->unsignedBigInteger('menu_id')->comment('菜单ID');

            $table->unique(['role_id', 'menu_id'], 'uk_role_menu');
            $table->index('menu_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_menu');
    }
};
