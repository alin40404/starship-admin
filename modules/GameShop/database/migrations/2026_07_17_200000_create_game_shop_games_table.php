<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_shop_games', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->comment('游戏名称');
            $table->string('code', 64)->nullable()->unique()->comment('游戏编码');
            $table->string('cover', 255)->nullable()->comment('封面');
            $table->string('platform', 60)->nullable()->comment('平台:ios,android,pc,steam等');
            $table->string('publisher', 120)->nullable()->comment('厂商');
            $table->tinyInteger('status')->default(1)->comment('1启用0停用');
            $table->integer('sort')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index(['sort', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_shop_games');
    }
};
