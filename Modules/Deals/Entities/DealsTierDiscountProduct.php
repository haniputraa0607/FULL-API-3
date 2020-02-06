<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 04 Feb 2020 14:41:25 +0700.
 */

namespace Modules\Deals\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsTierDiscountProduct
 * 
 * @property int $id_deals_tier_discount_products
 * @property int $id_deals
 * @property int $id_product
 * @property int $id_product_category
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\Deals\Entities\Deal $deal
 * @property \Modules\Deals\Entities\Product $product
 * @property \Modules\Deals\Entities\ProductCategory $product_category
 *
 * @package Modules\Deals\Entities
 */
class DealsTierDiscountProduct extends Eloquent
{
	protected $primaryKey = 'id_deals_tier_discount_products';

	protected $casts = [
		'id_deals' => 'int',
		'id_product' => 'int',
		'id_product_category' => 'int'
	];

	protected $fillable = [
		'id_deals',
		'id_product',
		'id_product_category'
	];

	public function deal()
	{
		return $this->belongsTo(\App\Models\Deal::class, 'id_deals');
	}

	public function product()
	{
		return $this->belongsTo(\App\Models\Product::class, 'id_product');
	}

	public function product_category()
	{
		return $this->belongsTo(\App\Models\ProductCategory::class, 'id_product_category');
	}
}
