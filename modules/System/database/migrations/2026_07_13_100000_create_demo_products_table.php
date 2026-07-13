<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('产品名称');
            $table->string('code', 50)->nullable()->comment('产品编码');
            $table->decimal('price', 10, 2)->default(0)->comment('价格');
            $table->string('status', 30)->default('normal')->comment('状态: normal,hidden');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_products');
    }
};
