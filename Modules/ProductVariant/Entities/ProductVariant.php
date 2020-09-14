<?php

namespace Modules\ProductVariant\Entities;

use App\Lib\MyHelper;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends \App\Http\Models\BaseModel
{
    protected $table = 'product_variant';

    protected $primaryKey = 'product_variant_id';

    protected $fillable = [
        'product_variant_name',
        'parent_id',
        'product_variant_order',
        'product_variant_visibility'
    ];

    public $childs = [];

    public function product_variant_parent()
    {
        return $this->belongsTo(ProductVariant::class, 'parent_id', 'product_variant_id');
    }

    public function product_variant_child()
    {
        return $this->hasMany(ProductVariant::class, 'parent_id', 'product_variant_id');
    }

    public function getIsCorAttribute()
    {
        return $this->is_cor;
    }

    public function getChildsAttribute()
    {
        return $this->childs;
    }

    public static function getVariantTree($variants = [])
    {
        $to_select = ['product_variant_id', 'product_variant_name', 'parent_id', 'product_variant_order'];
        $variants_raw = self::select($to_select)->orderBy('product_variant_order');
        if ($variants) {
            $variants_raw->whereIn('product_variant_id', $variants)->orWhereNull('parent_id');
        }
        $variants_raw = $variants_raw->get();
        $variants = [];
        $variants_raw->each(function($each) use (&$variants) {
            $variants[$each->product_variant_id] = $each;
        });
        // pc = parent child
        $pc = [];
        foreach ($variants as $key => $variant) {
            $parent_id = $variant->parent_id?:'_root';
            if (!isset($pc[$parent_id]['product_variant_name'])) {
                if ($parent_id == '_root') {
                    $parent = [
                        'product_variant_id' => 0,
                        'product_variant_name' => 'Root',
                        'product_variant_order' => 0,
                    ];
                } else {
                    $parent = $variants[$parent_id]??null;
                    if (!$parent) {
                        $parent = ProductVariant::select($to_select)->where('product_variant_id',$parent_id)->first();
                        self::addToParent($parent, $pc, $variants);
                    }
                    $parent = $parent->toArray();
                }
                $pc[$parent_id] = $parent;
            }
            $pc[$parent_id]['childs'][] = $variant;
        }

        $starter = array_shift($pc['_root']['childs']);
        while($pc['_root']['childs'] && !isset($pc[$starter['product_variant_id']])) {
            $starter = array_shift($pc['_root']['childs']);
        }

        if(!($starter) || !isset($pc[$starter['product_variant_id']])) {
            return [];
        }

        $starter->append('childs');

        $starter->childs = $pc[$starter['product_variant_id']]['childs'];

        $starter = $starter->toArray();

        foreach ($starter['childs'] as &$child) {
            $child = $child->toArray();
            $child['variant'] = $child;
            $child['variant']['childs'] = self::getVariantChildren($child,$pc);
        }

        return $starter;
    }

    protected static function getVariantChildren($variant, $variants) {
        if ($childs = $variants[$variant['product_variant_id']]['childs']?? false) {
            foreach ($childs as $key => $child) {
                $child->variant = self::getVariantChildren($child, $variants);
                $childs[$key] = $child->toArray();
            }
            return $childs;
        } elseif ($variants['_root']['childs']) {
            $starter = array_shift($variants['_root']['childs']);
            foreach ($variants[$starter['product_variant_id']]['childs']??[] as $key => $child) {
                $child->variant = self::getVariantChildren($child, $variants);
                $variants[$starter['product_variant_id']]['childs'][$key] = $child->toArray();
            }
            return $variants[$starter['product_variant_id']]??null;
        }
        return null;
    }

    protected static function addToParent($variant,&$pc,$variants)
    {
        if (!isset($pc[$variant['parent_id']])) {
            if (isset($variants[$variant['parent_id']])) {
                $pc[$variant['parent_id']] = $variants[$variant['parent_id']]->toArray();
            } else {
                $pc[$variant['parent_id']] = ProductVariant::where('parent_id',$variant['parent_id'])->first()->toArray();
            }
            $pc[$variant['parent_id']]['childs'] = [];
        }
        $pc[$variant['parent_id']]['childs'][] = $variant;
        usort($pc[$variant['parent_id']]['childs'], function ($a, $b) {
            return $a['product_variant_order'] <=> $b['product_variant_order'];
        });
    }
}
