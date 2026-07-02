<?php

use App\Support\StarshipTables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(StarshipTables::table('areas'), function (Blueprint $table) {
            $table->increments('id')->comment('ID');
            $table->unsignedInteger('pid')->default(0)->comment('父级ID');
            $table->string('shortname', 100)->nullable()->comment('简称');
            $table->string('name', 100)->comment('名称');
            $table->string('mergename', 255)->nullable()->comment('全称');
            $table->unsignedTinyInteger('level')->nullable()->comment('层级:1=省,2=市,3=区/县');
            $table->string('pinyin', 100)->nullable()->comment('拼音');
            $table->string('code', 100)->nullable()->comment('长途区号');
            $table->string('zip', 100)->nullable()->comment('邮编');
            $table->string('first', 50)->nullable()->comment('首字母');
            $table->string('lng', 100)->nullable()->comment('经度');
            $table->string('lat', 100)->nullable()->comment('纬度');

            $table->index('pid');
            $table->index('level');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(StarshipTables::table('areas'));
    }
};
