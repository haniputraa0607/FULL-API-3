<?php

namespace Modules\Brand\Entities;

use Illuminate\Database\Eloquent\Model;

class BrandProduct extends Model
{
    protected $table = 'brand_product';

    protected $primaryKey = 'id_brand_product';

    protected $fillable   = [
        'id_brand',
        'id_product'
    ];
}
