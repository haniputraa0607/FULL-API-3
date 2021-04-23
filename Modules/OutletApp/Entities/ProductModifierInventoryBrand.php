<?php

namespace Modules\OutletApp\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductModifierInventoryBrand extends Model
{
    public $primary_key  = null;
    public $incrementing = false;
    public $timestamps   = false;

    protected $fillable = [
        'id_product_modifier',
        'id_brand',
    ];
}
