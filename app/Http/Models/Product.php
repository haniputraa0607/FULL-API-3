<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Product
 *
 * @property int $id_product
 * @property int $id_product_category
 * @property string $product_code
 * @property string $product_name
 * @property string $product_name_pos
 * @property string $product_description
 * @property string $product_video
 * @property int $product_weight
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \App\Http\Models\ProductCategory $product_category
 * @property \Illuminate\Database\Eloquent\Collection $deals
 * @property \Illuminate\Database\Eloquent\Collection $news
 * @property \Illuminate\Database\Eloquent\Collection $product_discounts
 * @property \Illuminate\Database\Eloquent\Collection $product_photos
 * @property \Illuminate\Database\Eloquent\Collection $product_prices
 * @property \Illuminate\Database\Eloquent\Collection $transactions
 *
 * @package App\Models
 */
class Product extends Model
{
	protected $primaryKey = 'id_product';

	protected $casts = [
		'id_product_category' => 'int',
		'product_weight' => 'int'
	];

	protected $fillable = [
		'id_product_category',
		'product_code',
		'product_name',
		'product_name_pos',
		'product_description',
		'product_video',
		'product_weight',
		'product_allow_sync',
		'product_visibility',
		'position'
	];
	public function getPhotoAttribute() {
		return config('url.storage_url_api').($this->photos[0]['product_photo']??'img/product/item/default.png');
	}
	public function product_category()
	{
		return $this->belongsTo(\App\Http\Models\ProductCategory::class, 'id_product_category');
	}

	 public function category()
    {
        return $this->belongsToMany(ProductCategory::class,'brand_product', 'id_product', 'id_product_category');
    }

    public function photos() {
        return $this->hasMany(ProductPhoto::class, 'id_product', 'id_product')->orderBy('product_photo_order', 'ASC');
    }

    public function discount() {
        return $this->hasMany(ProductDiscount::class, 'id_product', 'id_product');
    }

	public function deals()
	{
		return $this->hasMany(\App\Http\Models\Deal::class, 'id_product');
	}

	public function news()
	{
		return $this->belongsToMany(\App\Http\Models\News::class, 'news_products', 'id_product', 'id_news');
	}

	public function product_discounts()
	{
		return $this->hasMany(\App\Http\Models\ProductDiscount::class, 'id_product');
	}

	public function product_photos()
	{
		return $this->hasMany(\App\Http\Models\ProductPhoto::class, 'id_product');
	}

	public function product_prices()
	{
		return $this->hasMany(\App\Http\Models\ProductPrice::class, 'id_product');
	}

    public function product_detail()
    {
        return $this->hasMany(\Modules\Product\Entities\ProductDetail::class, 'id_product')->where('product_detail_visibility', 'Visible');
    }

	public function prices()
	{
		return $this->hasMany(\App\Http\Models\ProductPrice::class, 'id_product')->join('outlets', 'product_prices.id_outlet', 'outlets.id_outlet')->select('id_product', 'outlets.id_outlet', 'product_price');
	}

	public function transactions()
	{
		return $this->belongsToMany(\App\Http\Models\Transaction::class, 'transaction_products', 'id_product', 'id_transaction')
					->withPivot('id_transaction_product', 'transaction_product_qty', 'transaction_product_price', 'transaction_product_subtotal', 'transaction_product_note')
					->withTimestamps();
	}

	public function product_tags()
	{
		return $this->hasMany(\App\Http\Models\ProductTag::class, 'id_product');
	}

	public function product_price_hiddens()
	{
		return $this->hasMany(\App\Http\Models\ProductPrice::class, 'id_product')->where('product_visibility', 'Hidden');
	}

    public function product_detail_hiddens()
    {
        return $this->hasMany(\Modules\Product\Entities\ProductDetail::class, 'id_product')->where('product_detail_visibility', 'Hidden');
    }

    public function global_price()
    {
        return $this->hasMany(\Modules\Product\Entities\ProductGlobalPrice::class, 'id_product');
    }

    public function product_special_price()
    {
        return $this->hasMany(\Modules\Product\Entities\ProductSpecialPrice::class, 'id_product');
    }

	public function all_prices()
	{
		return $this->hasMany(\App\Http\Models\ProductPrice::class, 'id_product');
	}
	public function brands()
    {
        return $this->belongsToMany(\Modules\Brand\Entities\Brand::class, 'brand_product','id_product','id_brand');
    }
	public function brand_category()
    {
        return $this->hasMany(\Modules\Brand\Entities\BrandProduct::class, 'id_product','id_product')->select('id_brand','id_product_category','id_product');
    }

    public function modifiers(){
        return $this->hasMany(ProductModifier::class, 'id_product','id_product');
    }
    
    public function discountActive()
    {
        $now = date('Y-m-d');
        $time = date('H:i:s');
        $day = date('l');

        return $this->hasMany(ProductDiscount::class, 'id_product', 'id_product')->where('discount_days', 'like', '%'.$day.'%')->where('discount_start', '<=', $now)->where('discount_end', '>=', $now)->where('discount_time_start', '<=', $time)->where('discount_time_end', '>=', $time);
    }

    public function product_promo_categories(){
        return $this->belongsToMany(\Modules\Product\Entities\ProductPromoCategory::class,'product_product_promo_categories', 'id_product','id_product_promo_category')->withPivot('id_product','id_product_promo_category','position');
    }
 

    /**
     * Generate fresh product variant tree
     * @param  integer  $id_product     id of product
     * @param  boolean $with_index      result should use id_product_variant as index or not
     * @return array                    array of product variant [tree]
     */
    public static function refreshVariantTree($id_product, $with_index = false)
    {
        Cache::forget('product_get_variant_tree_'.$id_product.($with_index ? 'true' : 'false'));
        return self::getVariantTree($id_product, $with_index);
    }

    /**
     * Generate product variant tree
     * @param  integer  $id_product     id of product
     * @param  boolean $with_index      result should use id_product_variant as index or not
     * @return array                    array of product variant [tree]
     */
    public static function getVariantTree($id_product, $with_index = false)
    {
        // retrieve from cache if available
        if (Cache::has('product_get_variant_tree_'.$id_product.($with_index ? 'true' : 'false'))) {
            return Cache::get('product_get_variant_tree_'.$id_product.($with_index ? 'true' : 'false'));
        }
        // get list variants available in products
        $list_variants = ProductVariant::select('product_variant.id_product_variant')
            ->join('product_variant_pivot', 'product_variant_pivot.id_product_variant', '=', 'product_variant.id_product_variant')
            ->join('product_variant_group', 'product_variant_group.id_product_variant_group', '=', 'product_variant_pivot.id_product_variant_group')
            ->where('id_product', $id_product)
            ->distinct()->pluck('id_product_variant');

        // get variant tree from $list_variants
        $variants = ProductVariant::getVariantTree($list_variants);

        // return empty array if no variants found
        if (!$variants) {
            return $variants;
        }

        // get all product variant groups assigned to this product
        $variant_group_raws = ProductVariantGroup::select('id_product_variant_group', 'product_variant_group_price')->where('id_product', $id_product)->with(['id_product_variants'])->get()->toArray();

        // create [id_product_variant_group => ProductVariantGroup,...] array
        $variant_groups = [];
        foreach ($variant_group_raws as $variant_group) {
            $id_variants = array_column($variant_group['id_product_variants'], 'id_product_variant');
            $slug = MyHelper::slugMaker($id_variants); // '2.5.7'

            $variant_groups[$slug] = $variant_group;
        }

        // merge product variant tree and product's product variant group
        self::recursiveCheck($variants, $variant_groups, [], $with_index);

        // get base price and unset from array [for nice array structure]
        $base_price = $variants['product_variant_group_price'];
        unset($variants['product_variant_group_price']);

        // create result
        $result = [
            'base_price'    => $base_price,
            'variants_tree' => $variants,
        ];
        // save to cache
        Cache::forever('product_get_variant_tree_'.$id_product.($with_index ? 'true' : 'false'), $result);
        // return the result
        return $result;
    }

    /**
     * Generate product variant tree
     * @param  array  &$variants       available variant tree
     * @param  array  $variant_groups  available product variant groups
     * @param  array   $last           list of last parent id
     * @param  boolean $with_index     result should use id_product_variant as index or not
     * @return array                   generated product variant tree
     */
    protected static function recursiveCheck(&$variants, $variant_groups, $last = [], $with_index = false)
    {
        if (!($variants['childs']??false)) {
            $variants = null;
            return;
        }
        // looping through childs of variant
        foreach ($variants['childs'] as $key => &$variant) {
            // list of parent id and current id
            if (!$variant['variant'] || ($variant['variant']['childs'][0]['id_parent']??false) !== $variant['id_product_variant']) {
                $current = array_merge($last, [$variant['id_product_variant']]);
            } else{
                $current = $last;
            }
            // variant has variant / this a parent variant?
            if ($variant['variant']) { // a parent
                // get variant tree of variant childs
                self::recursiveCheck($variant['variant'], $variant_groups, $current, $with_index);
                // check if still a parent
                if ($variant['variant']) { 
                    // assign price, from lowest price of variant with lower level, [previously saved in variant detail]
                    $variant['product_variant_group_price'] = $variant['variant']['product_variant_group_price'];
                    // unset price in variant detail
                    unset($variant['variant']['product_variant_group_price']);

                    // set this level lowest price to parent variant detail
                    if (!isset($variants['product_variant_group_price']) || $variants['product_variant_group_price'] > $variant['product_variant_group_price']) {
                        $variants['product_variant_group_price'] = $variant['product_variant_group_price'];
                    }
                    continue;
                }
            }
            // not a parent
            // create array keys from current list parent id and current id
            $slug = MyHelper::slugMaker($current);

            // product has this variant combination (product variant group)?
            if ($variant_group = ($variant_groups[$slug] ?? false)) { // it has
                // assigning product_variant_group_price and id_product_variant_group to this variant
                $variant['id_product_variant_group']    = $variant_group['id_product_variant_group'];
                $variant['product_variant_group_price'] = (double) $variant_group['product_variant_group_price'];

                // set this level lowest price to parent variant detail
                if (!isset($variants['product_variant_group_price']) || $variants['product_variant_group_price'] > $variant_group['product_variant_group_price']) {
                    $variants['product_variant_group_price'] = $variant['product_variant_group_price'];
                }
            } else { // doesn't has
                // delete from array
                unset($variants['childs'][$key]);
            }
        }

        $new_variants = []; // initial variable for sorted array
        foreach ($variants['childs'] as $key => &$variant) {
            $variant['product_variant_price'] = $variant['product_variant_group_price'] - $variants['product_variant_group_price'];

            // sorting key
            $new_order = [
                'id_product_variant'    => $variant['id_product_variant'],
                'product_variant_name'  => $variant['product_variant_name'],
                'product_variant_price' => $variant['product_variant_price'],
            ];

            if ($variant['id_product_variant_group'] ?? false) {
                $new_order['id_product_variant_group']    = $variant['id_product_variant_group'];
                $new_order['product_variant_group_price'] = $variant['product_variant_group_price'];
            }
            $new_order['variant'] = $variant['variant'];

            $variant = $new_order;
            // end sorting key

            // add index if necessary
            if ($with_index) {
                $new_variants[$variant['id_product_variant']] = &$variant;
            }
        }

        // add index if necessary
        if ($with_index) {
            $variants['childs'] = $new_variants;
        } else {
            $variants['childs'] = array_values($variants['childs']);
        }
        if (!$variants['childs']) {
            $variants = null;
            return;
        }
        // sorting key,
        $new_order = [
            'id_product_variant'          => $variants['id_product_variant'],
            'product_variant_name'        => $variants['product_variant_name'],
            'childs'                      => $variants['childs'],
            'product_variant_group_price' => $variants['product_variant_group_price'], // do not remove or rename this
        ];

        $variants = $new_order;
        // end sorting key
    }

    /**
     * get list variant price of given product variant group
     * @param  ProductVariantGroup  $product_variant_group eloquent model
     * @param  Array                $variant               Variant tree
     * @param  array                $variants              Temporary variant id and price list
     * @param  integer              $last_price            last price (sum of parent price)
     * @return boolean              true / false
     */
    public static function getVariantPrice($product_variant_group,$variant = null, $variants = [], $last_price = 0) 
    {
        if (is_numeric($product_variant_group)) {
            $product_variant_group = ProductVariantGroup::where('id_product_variant_group', $product_variant_group)->first();
            if (!$product_variant_group) {
                return false;
            }
        }

        if (!$variant) {
            $variant = self::getVariantTree($product_variant_group->id_product)['variants_tree'];
            if(!$variant) {
                return false;
            }
        }

        foreach ($variant['childs'] as $child) {
            $next_variants = $variants;
            if($child['variant']) {
                // check child or parent
                if ($child['id_product_variant'] != $child['variant']['id_product_variant']) { //child
                    $next_variants[$child['id_product_variant']] = $last_price + $child['product_variant_price'];
                    $next_last_price = 0;
                } else { //parent
                    $next_variants = $variants;
                    $next_last_price = $last_price + $child['product_variant_price'];
                }
                if ($result = self::getVariantPrice($product_variant_group, $child['variant'], $next_variants, $next_last_price)) {
                    return $result;
                }
            } else {
                if ($child['id_product_variant_group'] == $product_variant_group->id_product_variant_group) {
                    $variants[$child['id_product_variant']] = $last_price + $child['product_variant_price'];
                    return $variants;
                }
            }
        }
        return false;
    }
}
