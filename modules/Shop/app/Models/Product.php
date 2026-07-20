<?php

namespace Modules\Shop\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\System\App\Models\Category;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'shop_products';

    protected $appends = [
        'cover',
        'images',
    ];

    protected $fillable = [
        'category_id',
        'brand_id',
        'product_type',
        'fulfillment_type',
        'name',
        'subtitle',
        'price',
        'stock',
        'media',
        'description',
        'status',
        'sort',
        'extra',
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'brand_id' => 'integer',
            'price' => 'integer',
            'stock' => 'integer',
            'media' => 'array',
            'status' => 'integer',
            'sort' => 'integer',
            'extra' => 'array',
        ];
    }

    /**
     * 封面图：取 media 中第一张图片。
     */
    public function getCoverAttribute(): ?string
    {
        $media = $this->media;
        if (! is_array($media)) {
            return null;
        }
        foreach ($media as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (($item['type'] ?? 'image') === 'image') {
                $url = trim((string) ($item['url'] ?? ''));
                if ($url !== '') {
                    return $url;
                }
            }
        }

        return null;
    }

    /**
     * 图集：media 中全部图片 URL。
     *
     * @return list<string>
     */
    public function getImagesAttribute(): array
    {
        $media = $this->media;
        if (! is_array($media)) {
            return [];
        }
        $images = [];
        foreach ($media as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (($item['type'] ?? 'image') !== 'image') {
                continue;
            }
            $url = trim((string) ($item['url'] ?? ''));
            if ($url !== '') {
                $images[] = $url;
            }
        }

        return $images;
    }

    public function skus(): HasMany
    {
        return $this->hasMany(ProductSku::class, 'product_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function defaultSku(): ?ProductSku
    {
        return $this->skus()->orderBy('id')->first();
    }
}
