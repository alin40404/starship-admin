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
            $table->unsignedBigInteger('admin_id');
            $table->string('type', 20);
            $table->string('title', 200);
            $table->text('content')->nullable();
            $table->string('link', 500)->nullable();
            $table->unsignedTinyInteger('is_read')->default(0);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'is_read', 'created_at']);
            $table->index(['created_at']);
            $table->foreign('admin_id')->references('id')->on('admins')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbox_messages');
    }
};
