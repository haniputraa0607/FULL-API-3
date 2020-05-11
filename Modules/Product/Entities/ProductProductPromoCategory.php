<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductProductPromoCategory extends Model
{
    public $timestamps  = false;
    protected $fillable = ['id_product', 'id_product_promo_category', 'position'];
}
