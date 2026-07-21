<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 历史库在 create 迁移已执行后才把 cover/images 改为 media，
 * 这里为已有表补齐 media，并回填旧封面/图集数据。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shop_products')) {
            return;
        }

        if (! Schema::hasColumn('shop_products', 'media')) {
            Schema::table('shop_products', function (Blueprint $table) {
                $after = Schema::hasColumn('shop_products', 'stock') ? 'stock' : null;
                if ($after) {
                    $table->json('media')->nullable()->after($after)->comment('媒体列表[{type:image|video,url}]');
                } else {
                    $table->json('media')->nullable()->comment('媒体列表[{type:image|video,url}]');
                }
            });
        }

        $hasCover = Schema::hasColumn('shop_products', 'cover');
        $hasImages = Schema::hasColumn('shop_products', 'images');
        if ($hasCover || $hasImages) {
            DB::table('shop_products')->orderBy('id')->chunkById(100, function ($rows) use ($hasCover, $hasImages): void {
                foreach ($rows as $row) {
                    $media = [];
                    $seen = [];

                    if ($hasCover) {
                        $cover = trim((string) ($row->cover ?? ''));
                        if ($cover !== '') {
                            $media[] = ['type' => 'image', 'url' => $cover];
                            $seen[$cover] = true;
                        }
                    }

                    if ($hasImages) {
                        $images = $row->images;
                        if (is_string($images) && $images !== '') {
                            $decoded = json_decode($images, true);
                            $images = is_array($decoded) ? $decoded : [];
                        }
                        if (is_array($images)) {
                            foreach ($images as $item) {
                                $url = is_array($item)
                                    ? trim((string) ($item['url'] ?? ''))
                                    : trim((string) $item);
                                if ($url === '' || isset($seen[$url])) {
                                    continue;
                                }
                                $media[] = ['type' => 'image', 'url' => $url];
                                $seen[$url] = true;
                            }
                        }
                    }

                    DB::table('shop_products')->where('id', $row->id)->update([
                        'media' => $media === [] ? null : json_encode($media, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            });

            Schema::table('shop_products', function (Blueprint $table) use ($hasCover, $hasImages): void {
                $drop = [];
                if ($hasCover) {
                    $drop[] = 'cover';
                }
                if ($hasImages) {
                    $drop[] = 'images';
                }
                if ($drop !== []) {
                    $table->dropColumn($drop);
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('shop_products')) {
            return;
        }

        if (! Schema::hasColumn('shop_products', 'cover')) {
            Schema::table('shop_products', function (Blueprint $table) {
                $table->string('cover', 255)->nullable()->after('stock')->comment('封面图');
            });
        }
        if (! Schema::hasColumn('shop_products', 'images')) {
            Schema::table('shop_products', function (Blueprint $table) {
                $table->json('images')->nullable()->after('cover')->comment('图集');
            });
        }

        if (Schema::hasColumn('shop_products', 'media')) {
            DB::table('shop_products')->orderBy('id')->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $media = $row->media;
                    if (is_string($media) && $media !== '') {
                        $decoded = json_decode($media, true);
                        $media = is_array($decoded) ? $decoded : [];
                    }
                    if (! is_array($media)) {
                        $media = [];
                    }

                    $cover = null;
                    $images = [];
                    foreach ($media as $item) {
                        if (! is_array($item)) {
                            continue;
                        }
                        if (($item['type'] ?? 'image') !== 'image') {
                            continue;
                        }
                        $url = trim((string) ($item['url'] ?? ''));
                        if ($url === '') {
                            continue;
                        }
                        if ($cover === null) {
                            $cover = $url;
                        }
                        $images[] = $url;
                    }

                    DB::table('shop_products')->where('id', $row->id)->update([
                        'cover' => $cover,
                        'images' => $images === [] ? null : json_encode($images, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            });

            Schema::table('shop_products', function (Blueprint $table) {
                $table->dropColumn('media');
            });
        }
    }
};
