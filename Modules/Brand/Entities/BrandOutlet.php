<?php

namespace Modules\Brand\Entities;

use Illuminate\Database\Eloquent\Model;

class BrandOutlet extends Model
{
    protected $table = 'brand_outlet';

    protected $primaryKey = 'id_brand_outlet';

    protected $fillable   = [
        'id_brand',
        'id_outlet'
    ];
}
