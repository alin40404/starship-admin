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
            $table->string('secret', 100)->nullable()->comment('访问密钥');
            $table->decimal('price', 10, 2)->default(0)->comment('价格');
            $table->unsignedInteger('stock')->default(0)->comment('库存');
            $table->unsignedSmallInteger('sort')->default(0)->comment('排序');
            $table->enum('product_type', ['physical', 'virtual'])->default('physical')->comment('产品类型: physical=实物,virtual=虚拟');
            $table->string('flags', 100)->nullable()->comment('标签(多选): hot=热门,new=新品,recommend=推荐');
            $table->boolean('is_featured')->default(false)->comment('是否推荐');
            $table->unsignedTinyInteger('score')->default(5)->comment('评分');
            $table->unsignedBigInteger('category_id')->nullable()->comment('分类ID');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父级产品');
            $table->string('cover', 500)->nullable()->comment('封面图');
            $table->text('images')->nullable()->comment('图片相册');
            $table->string('attachment', 500)->nullable()->comment('附件');
            $table->string('brochure', 500)->nullable()->comment('宣传册文件');
            $table->string('summary', 255)->nullable()->comment('简介');
            $table->longText('content')->nullable()->comment('详情内容');
            $table->string('icon', 100)->nullable()->comment('图标');
            $table->string('keywords', 255)->nullable()->comment('关键词;自动完成: 热门,新品,限时,包邮');
            $table->string('tag', 50)->nullable()->comment('标签;字典:product_tag');
            $table->string('color', 30)->nullable()->comment('颜色: red=红,blue=蓝,green=绿');
            $table->dateTime('published_at')->nullable()->comment('发布时间');
            $table->date('sale_date')->nullable()->comment('上架日期');
            $table->time('sale_time')->nullable()->comment('开售时间');
            $table->text('remark')->nullable()->comment('备注');
            $table->string('status', 30)->default('normal')->comment('状态: normal,hidden');
            $table->timestamps();

            $table->index('category_id');
            $table->index('parent_id');
            $table->index('product_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_products');
    }
};
