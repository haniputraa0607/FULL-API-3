<?php

namespace Modules\ProductVariant\Entities;

use App\Lib\MyHelper;
use Illuminate\Database\Eloquent\Model;

class ProductVariantGroup extends \App\Http\Models\BaseModel
{
    protected $table = 'product_variant_group';

    protected $primaryKey = 'product_variant_group_id';

    protected $fillable = [
        'product_id',
        'product_variant_group_code',
        'product_variant_group_name',
        'product_variant_group_visibility'
    ];

    public function product_variant_pivot()
    {
        return $this->hasMany(ProductVariantPivot::class, 'product_variant_group_id', 'product_variant_group_id')
            ->join('product_variant', 'product_variant.product_variant_id', 'product_variant_pivot.product_variant_id');
    }

    public function product_variant_ids()
    {
        return $this->hasMany(ProductVariantPivot::class, 'product_variant_group_id', 'product_variant_group_id')->select('product_variant_group_id','product_variant_id');
    }
}
