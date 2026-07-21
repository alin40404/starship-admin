<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_product_skus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->comment('SPU ID');
            $table->string('sku_code', 64)->comment('SKU 编码');
            $table->json('specs')->nullable()->comment('规格 JSON');
            $table->unsignedInteger('price')->default(0)->comment('售价(分)');
            $table->unsignedInteger('compare_at_price')->nullable()->comment('原价/划线价(分)，空表示无');
            $table->unsignedInteger('stock')->default(0)->comment('库存');
            $table->tinyInteger('status')->default(1)->comment('1启用,0禁用');
            $table->json('extra')->nullable()->comment('SKU 扩展');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('sku_code');
            $table->index('product_id');
            $table->index('compare_at_price');
            $table->index('status');
        });

        // 为存量商品生成默认 SKU，并同步价格库存
        if (Schema::hasTable('shop_products')) {
            $products = DB::table('shop_products')->whereNull('deleted_at')->get();
            foreach ($products as $product) {
                $exists = DB::table('shop_product_skus')
                    ->where('product_id', $product->id)
                    ->whereNull('deleted_at')
                    ->exists();
                if ($exists) {
                    continue;
                }
                DB::table('shop_product_skus')->insert([
                    'product_id' => $product->id,
                    'sku_code' => 'SKU'.$product->id.'DEFAULT',
                    'specs' => null,
                    'price' => (int) $product->price,
                    'stock' => (int) $product->stock,
                    'status' => 1,
                    'extra' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_product_skus');
    }
};
