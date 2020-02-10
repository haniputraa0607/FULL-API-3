<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 04 Feb 2020 14:40:25 +0700.
 */

namespace Modules\Deals\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsBuyxgetyProductRequirement
 * 
 * @property int $id_deals_buyxgety_product
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
class DealsBuyxgetyProductRequirement extends Eloquent
{
	protected $primaryKey = 'id_deals_buyxgety_product';

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
		return $this->belongsTo(\App\Http\Models\Deal::class, 'id_deals');
	}

	public function product()
	{
		return $this->belongsTo(\App\Http\Models\Product::class, 'id_product');
	}

	public function product_category()
	{
		return $this->belongsTo(\App\Http\Models\ProductCategory::class, 'id_product_category');
	}
}
