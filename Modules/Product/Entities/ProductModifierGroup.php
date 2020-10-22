<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductModifierGroup extends Model
{
    public $primaryKey = 'id_product_modifier_group';
    protected $fillable = [
        'product_modifier_group_name',
        'created_at',
        'updated_at'
    ];
}
