<?php

namespace Modules\Generator\App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;


class Products extends Model
{


    protected $table = 'demo_products';

    protected $guarded = [];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
