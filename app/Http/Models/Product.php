<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Cache;
use Modules\ProductVariant\Entities\ProductVariant;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use App\Lib\MyHelper;
use Modules\Product\Entities\ProductModifierGroup;

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
		'position',
		'product_type',
		'product_capacity',
		'plastic_used',
        'product_variant_status',
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
 
    protected static function getCacheName($id_product, $outlet, $with_index = false) 
    {
        return 'product_get_variant_tree_'.$id_product.'_'.$outlet['id_outlet'].'_'.($with_index ? 'true' : 'false');
    }

    public function product_variant_group(){
        return $this->hasMany(\Modules\ProductVariant\Entities\ProductVariantGroup::class,'id_product','id_product')
            ->join('product_variant_pivot', 'product_variant_pivot.id_product_variant_group', 'product_variant_groups.id_product_variant_group')
            ->join('product_variants', 'product_variants.id_product_variant', 'product_variant_pivot.id_product_variant');
    }

    /**
     * Generate fresh product variant tree
     * @param  integer  $id_product     id of product
     * @param  boolean $with_index      result should use id_product_variant as index or not
     * @return array                    array of product variant [tree]
     */
    public static function refreshVariantTree($id_product, $outlet, $with_index = false)
    {
        $cache_name = self::getCacheName($id_product, $outlet, $with_index);
        Cache::forget($cache_name);
        return self::getVariantTree($id_product, $outlet, $with_index);
    }

    /**
     * Generate product variant tree
     * @param  integer  $id_product     id of product
     * @param  boolean $with_index      result should use id_product_variant as index or not
     * @return array                    array of product variant [tree]
     */
    public static function getVariantTree($id_product, $outlet, $with_index = false)
    {
        $cache_name = self::getCacheName($id_product, $outlet, $with_index);
        // retrieve from cache if available
        if (Cache::has($cache_name)) {
            return Cache::get($cache_name);
        }
        // get list variants available in products
        $list_variants = ProductVariant::select('product_variants.id_product_variant')
            ->join('product_variant_pivot', 'product_variant_pivot.id_product_variant', '=', 'product_variants.id_product_variant')
            ->join('product_variant_groups', 'product_variant_groups.id_product_variant_group', '=', 'product_variant_pivot.id_product_variant_group')
            ->where('id_product', $id_product)
            ->distinct()->pluck('id_product_variant');

        // get variant tree from $list_variants
        $variants = ProductVariant::getVariantTree($list_variants);

        // return empty array if no variants found
        if (!$variants) {
            return $variants;
        }

        // get all product variant groups assigned to this product
        $variant_group_raws = ProductVariantGroup::select('product_variant_groups.id_product_variant_group', 'product_variant_group_stock_status')->where('id_product', $id_product)->with(['id_product_variants']);

        if ($outlet['outlet_different_price']) {
            $variant_group_raws->addSelect('product_variant_group_special_prices.product_variant_group_price')->join('product_variant_group_special_prices', function($join) use ($outlet) {
                $join->on('product_variant_groups.id_product_variant_group', '=', 'product_variant_group_special_prices.id_product_variant_group')
                    ->where('product_variant_group_special_prices.id_outlet', '=', $outlet['id_outlet']);
            });
        } else {
            $variant_group_raws->addSelect('product_variant_group_price');
        }

        $variant_group_raws->leftJoin('product_variant_group_details', function($join) use ($outlet) {
            $join->on('product_variant_group_details.id_product_variant_group', '=', 'product_variant_groups.id_product_variant_group')
                ->where('product_variant_group_details.id_outlet', $outlet['id_outlet']);
        })->where(function($query) {
            $query->where('product_variant_group_details.product_variant_group_visibility', 'Visible')
                ->orWhere(function($q2) {
                    $q2->whereNull('product_variant_group_details.product_variant_group_visibility')
                        ->where('product_variant_groups.product_variant_group_visibility', 'Visible');
                });
        })->whereRaw('coalesce(product_variant_group_details.product_variant_group_status, "Active") <> "Inactive"')
        ->whereRaw('coalesce(product_variant_group_details.product_variant_group_stock_status, "Available") <> "Sold Out"');

        $variant_group_raws = $variant_group_raws->get()->toArray();

        // create [id_product_variant_group => ProductVariantGroup,...] array
        $variant_groups = [];
        foreach ($variant_group_raws as $variant_group) {
            $id_variants = array_column($variant_group['id_product_variants'], 'id_product_variant');
            $slug = MyHelper::slugMaker($id_variants); // '2.5.7'

            $variant_groups[$slug] = $variant_group;
        }

        // merge product variant tree and product's product variant group
        self::recursiveCheck($variants, $variant_groups, [], $with_index);

        // get list modifiers group order by name where id_product or id_variant
        $modifier_groups_raw = ProductModifierGroup::select('product_modifier_groups.id_product_modifier_group', 'product_modifier_group_name', \DB::raw('GROUP_CONCAT(id_product) as id_products, GROUP_CONCAT(id_product_variant) as id_product_variants'))->join('product_modifier_group_pivots', 'product_modifier_groups.id_product_modifier_group', 'product_modifier_group_pivots.id_product_modifier_group')->where('id_product', $id_product)->orWhereIn('id_product_variant', $list_variants)->groupBy('product_modifier_groups.id_product_modifier_group')->orderBy('product_modifier_group_name')->get()->toArray();
        // ambil modifier + harga + yang visible dll berdasarkan modifier group
        $modifier_groups = [];
        foreach ($modifier_groups_raw as $key => &$modifier_group) {
            $modifiers = ProductModifier::select('product_modifiers.id_product_modifier as id_product_variant', \DB::raw('coalesce(product_modifier_price,0) as product_variant_price'), 'text as product_variant_name', \DB::raw('coalesce(product_modifier_stock_status, "Available") as product_variant_stock_status'))
                ->where('modifier_type', 'Modifier Group')
                ->where('id_product_modifier_group', $modifier_group['id_product_modifier_group'])
                ->leftJoin('product_modifier_details', function($join) use ($outlet) {
                    $join->on('product_modifier_details.id_product_modifier','=','product_modifiers.id_product_modifier')
                        ->where('product_modifier_details.id_outlet',$outlet['id_outlet']);
                })
                ->where(function($query){
                    $query->where('product_modifier_details.product_modifier_visibility','=','Visible')
                    ->orWhere(function($q){
                        $q->whereNull('product_modifier_details.product_modifier_visibility')
                        ->where('product_modifiers.product_modifier_visibility', 'Visible');
                    });
                })
                ->where(function($q){
                    $q->where('product_modifier_status','Active')->orWhereNull('product_modifier_status');
                })
                ->groupBy('product_modifiers.id_product_modifier');
            if ($outlet['outlet_diferent_price']) {
                $modifiers->leftJoin('product_modifier_prices', function($join) use ($outlet) {
                    $join->on('product_modifier_prices.id_product_modifier','=','product_modifiers.id_product_modifier')
                        ->where('product_modifier_prices.id_outlet',$outlet['id_outlet']);
                });
            } else {
                $modifiers->leftJoin('product_modifier_global_prices', 'product_modifier_global_prices.id_product_modifier','=','product_modifiers.id_product_modifier');
            }
            $modifiers = $modifiers->get()->toArray();
            if (!$modifiers) {
                unset($modifier_groups[$key]);
            }
            $modifier_group['childs'] = $modifiers;
            if (in_array($id_product, explode(',', $modifier_group['id_products']))) {
                unset($modifier_group['id_products']);
                unset($modifier_group['id_product_variants']);
                $modifier_groups['*'][$modifier_group['id_product_modifier_group']] = $modifier_group;
            } else {
                $id_product_variants = explode(',', $modifier_group['id_product_variants']);
                unset($modifier_group['id_products']);
                unset($modifier_group['id_product_variants']);
                foreach ($id_product_variants as $id_product_variant) {
                    $modifier_groups[$id_product_variant][$modifier_group['id_product_modifier_group']] = $modifier_group;
                }
            }
        }
        // masukan ke dalam vaiants
        // \Log::debug($variants);
        self::mergeModifierGroup($variants, $modifier_groups);
        // get base price and unset from array [for nice array structure]
        $base_price = $variants['product_variant_group_price'];
        unset($variants['product_variant_group_price']);
        unset($variants['product_variant_stock_status']);

        // create result
        $result = [
            'base_price'    => $base_price,
            'variants_tree' => $variants,
        ];
        // save to cache
        Cache::forever($cache_name, $result);
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
                    $variant['product_variant_stock_status'] = $variant['variant']['product_variant_stock_status'];
                    // unset price in variant detail
                    unset($variant['variant']['product_variant_group_price']);
                    unset($variant['variant']['product_variant_stock_status']);

                    // set this level lowest price to parent variant detail
                    if (!isset($variants['product_variant_group_price']) || $variants['product_variant_group_price'] > $variant['product_variant_group_price']) {
                        $variants['product_variant_group_price'] = $variant['product_variant_group_price'];
                    }
                    if (!isset($variants['product_variant_stock_status']) || $variant['product_variant_stock_status'] == 'Available') {
                        $variants['product_variant_stock_status'] = $variant['product_variant_stock_status'];
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
                $variant['product_variant_stock_status'] = $variant_group['product_variant_group_stock_status'];

                // set this level lowest price to parent variant detail
                if (!isset($variants['product_variant_group_price']) || $variants['product_variant_group_price'] > $variant_group['product_variant_group_price']) {
                    $variants['product_variant_group_price'] = $variant['product_variant_group_price'];
                }

                if (!isset($variants['product_variant_stock_status']) || $variant_group['product_variant_group_stock_status'] == 'Available') {
                    $variants['product_variant_stock_status'] = $variant_group['product_variant_group_stock_status'];
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
                'product_variant_stock_status' => $variant['product_variant_stock_status'],
            ];

            if ($variant['id_product_variant_group'] ?? false) {
                $new_order['id_product_variant_group']    = $variant['id_product_variant_group'];
                $new_order['product_variant_group_price'] = $variant['product_variant_group_price'];
                $new_order['extra_modifiers'] = [];
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
            'product_variant_stock_status' => $variants['product_variant_stock_status'], // do not remove or rename this
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
            return false;
        }

        foreach ($variant['childs'] as $child) {
            $next_variants = $variants;
            if ($child['is_modifier']??false) {
                return $variants;
            }
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

    /**
     * get list variant price of given product variant group
     * @param  ProductVariantGroup  $product_variant_group eloquent model
     * @param  Array                $variant               Variant tree
     * @param  array                $variants              Temporary variant id and price list
     * @param  integer              $last_price            last price (sum of parent price)
     * @return boolean              true / false
     */
    public static function getVariantParentId($product_variant_group,$variant = null, $variants = [])
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
                $next_variants[] = $child['id_product_variant'];
                if ($result = self::getVariantParentId($product_variant_group, $child['variant'], $next_variants)) {
                    return $result;
                }
            } else {
                if ($child['id_product_variant_group'] == $product_variant_group->id_product_variant_group) {
                    $variants[] = $child['id_product_variant'];
                    return $variants;
                }
            }
        }
        return false;
    }

    /**
     * Merge product modifiers 
     * @param  [type] $variants        [description]
     * @param  [type] $modifier_groups [description]
     * @return [type]                  [description]
     */
    public static function mergeModifierGroup(&$variants, $modifier_groups, $selected_id = [])
    {
        if(!$modifier_groups || !$variants){return;}
        foreach ($variants['childs'] as &$variant) {
            $new_selected_id = array_merge($selected_id, [$variant['id_product_variant']]);
            if($variant['variant']) {
                self::mergeModifierGroup($variant['variant'], $modifier_groups, $new_selected_id);
            } else {
                // ambil kemungkinan modifier group
                $modifiers = $modifier_groups['*']??[];
                foreach($new_selected_id as $variant_id) {
                    $modifiers = array_merge($modifiers, $modifier_groups[$variant_id]??[]);
                }
                // loop modifier group
                $variant['variant'] = self::insertModifierGroup($variant, $modifiers, $variant['id_product_variant_group']);
                // tambah
                // loop masuk
            }
        }
    }

    /**
     * Insert modifier group to variant
     * @param  array    &$variant                 variant to add modifiers group
     * @param  array    $modifier_groups          available modifier groups
     * @param  int      $id_product_variant_group id_product_variant_group
     * @return [type]                           [description]
     */
    public static function insertModifierGroup(&$variant, $modifier_groups, $id_product_variant_group) {
        $starter = array_shift($modifier_groups);
        if(!($starter['childs']??false)) {return null;}
        $result = [
            'product_variant_name'  => $starter['product_modifier_group_name'],
            'id_product_variant'    => $starter['id_product_modifier_group'],
            'childs'                => $starter['childs']
        ];
        foreach($result['childs'] as &$variant_child) {
            $variant_child['product_variant_group_price'] = $variant['product_variant_group_price'] + $variant_child['product_variant_price'];
            $variant_child['extra_modifiers'] = $variant['extra_modifiers'];
            $variant_child['extra_modifiers'][] = $variant_child['id_product_variant'];
            $variant_child['is_modifier']       = true;
            if (!$modifier_groups) { // child
                $variant_child['id_product_variant_group'] = $id_product_variant_group;
                $variant_child['variant'] = null;
            } else {
                $variant_child['variant'] = self::insertModifierGroup($variant_child, $modifier_groups, $id_product_variant_group);
            }
        }
        unset($variant['extra_modifiers']);
        unset($variant['id_product_variant_group']);
        unset($variant['product_variant_group_price']);
        return $result;
    }
}
