<?php

namespace Modules\ProductVariant\Entities;

use App\Lib\MyHelper;
use Illuminate\Database\Eloquent\Model;

class ProductVariantPivot extends \App\Http\Models\BaseModel
{
    protected $table = 'product_variant_pivot';

    protected $primaryKey = 'product_variant_pivot_id';

    protected $fillable = [
        'product_variant_id',
        'product_variant_group_id'
    ];

    public function product_variant() {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
