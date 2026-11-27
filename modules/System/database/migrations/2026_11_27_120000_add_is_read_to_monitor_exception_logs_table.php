<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitor_exception_logs', function (Blueprint $table): void {
            $table->unsignedTinyInteger('is_read')
                ->default(0)
                ->after('log_path')
                ->comment('读状态：0未读 1已读');
            $table->index('is_read');
        });
    }

    public function down(): void
    {
        Schema::table('monitor_exception_logs', function (Blueprint $table): void {
            $table->dropIndex(['is_read']);
            $table->dropColumn('is_read');
        });
    }
};
