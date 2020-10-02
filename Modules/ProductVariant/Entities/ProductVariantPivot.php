<?php

namespace Modules\ProductVariant\Entities;

use App\Lib\MyHelper;
use Illuminate\Database\Eloquent\Model;

class ProductVariantPivot extends Model
{
    protected $table = 'product_variant_pivot';

    protected $primaryKey = 'id_product_variant_pivot';

    protected $fillable = [
        'id_product_variant',
        'id_product_variant_group'
    ];

    public function product_variant() {
        return $this->belongsTo(ProductVariant::class, 'id_product_variant');
    }
}
