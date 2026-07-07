<?php

namespace Modules\System\App\Models;

use App\Models\Concerns\HasStarshipTable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class DemoArticle extends Model
{
    use HasStarshipTable;
    use SoftDeletes;


    protected static function starshipTableSuffix(): string
    {
        return 'system_demo_article';
    }

    protected $guarded = [];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
