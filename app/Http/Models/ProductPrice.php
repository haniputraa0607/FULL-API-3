<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductPrice
 * 
 * @property int $id_product_price
 * @property int $id_product
 * @property int $id_outlet
 * @property int $product_price
 * @property string $product_visibility
 * @property string $product_status
 * @property int $created_at
 * @property int $updated_at
 * 
 * @property \App\Http\Models\Outlet $outlet
 * @property \App\Http\Models\Product $product
 *
 * @package App\Models
 */
class ProductPrice extends Model
{
	protected $table = 'product_prices';
	
	protected $primaryKey = 'id_product_price';

	protected $fillable = [
		'id_product',
		'id_outlet',
		'product_price',
		'product_visibility',
		'product_status',
		'product_stock_status',
		'created_at',
		'updated_at'
	];

	public function outlet()
	{
		return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
	}

	public function product()
	{
		return $this->belongsTo(\App\Http\Models\Product::class, 'id_product');
	}

}
