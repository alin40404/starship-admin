<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Support\StarshipTables;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(StarshipTables::table('admin_post'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->comment('管理员ID');
            $table->unsignedBigInteger('post_id')->comment('岗位ID');

            $table->unique(['admin_id', 'post_id'], 'uk_admin_post');
            $table->index('post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(StarshipTables::table('admin_post'));
    }
};
