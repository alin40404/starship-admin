<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 原价从 SKU.extra.compare_at_price 提升为独立列，便于检索/索引。
 * 规则对齐 Shopify：可空；有值时须严格大于售价才视为有效划线价。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shop_product_skus')) {
            return;
        }

        if (! Schema::hasColumn('shop_product_skus', 'compare_at_price')) {
            Schema::table('shop_product_skus', function (Blueprint $table) {
                $table->unsignedInteger('compare_at_price')
                    ->nullable()
                    ->after('price')
                    ->comment('原价/划线价(分)，空表示无');
                $table->index('compare_at_price');
            });
        }

        DB::table('shop_product_skus')->orderBy('id')->chunkById(100, function ($rows): void {
            foreach ($rows as $row) {
                $extra = $row->extra;
                if (is_string($extra) && $extra !== '') {
                    $decoded = json_decode($extra, true);
                    $extra = is_array($decoded) ? $decoded : [];
                }
                if (! is_array($extra)) {
                    $extra = [];
                }

                $fromExtra = null;
                foreach (['compare_at_price', 'original_price'] as $key) {
                    if (! array_key_exists($key, $extra)) {
                        continue;
                    }
                    $n = (int) $extra[$key];
                    if ($n > 0) {
                        $fromExtra = $n;
                        break;
                    }
                }

                $price = (int) ($row->price ?? 0);
                $current = $row->compare_at_price ?? null;
                $compareAt = $current !== null ? (int) $current : $fromExtra;
                // 无效划线价（≤售价）落库为空，与 Shopify 展示规则一致
                if ($compareAt !== null && $compareAt <= $price) {
                    $compareAt = null;
                }

                unset($extra['compare_at_price'], $extra['original_price']);
                $nextExtra = $extra === [] ? null : json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                DB::table('shop_product_skus')->where('id', $row->id)->update([
                    'compare_at_price' => $compareAt,
                    'extra' => $nextExtra,
                ]);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('shop_product_skus')) {
            return;
        }

        if (Schema::hasColumn('shop_product_skus', 'compare_at_price')) {
            DB::table('shop_product_skus')->orderBy('id')->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $extra = $row->extra;
                    if (is_string($extra) && $extra !== '') {
                        $decoded = json_decode($extra, true);
                        $extra = is_array($decoded) ? $decoded : [];
                    }
                    if (! is_array($extra)) {
                        $extra = [];
                    }

                    $compareAt = $row->compare_at_price;
                    if ($compareAt !== null && (int) $compareAt > 0) {
                        $extra['compare_at_price'] = (int) $compareAt;
                    }

                    DB::table('shop_product_skus')->where('id', $row->id)->update([
                        'extra' => $extra === []
                            ? null
                            : json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            });

            Schema::table('shop_product_skus', function (Blueprint $table) {
                $table->dropIndex(['compare_at_price']);
                $table->dropColumn('compare_at_price');
            });
        }
    }
};
