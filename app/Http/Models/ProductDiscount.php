<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
// use Carbon;

/**
 * Class ProductDiscount
 * 
 * @property int $id_product_discount
 * @property int $id_product
 * @property int $discount_percentage
 * @property int $discount_nominal
 * @property \Carbon\Carbon $discount_start
 * @property \Carbon\Carbon $discount_end
 * @property \Carbon\Carbon $discount_time_start
 * @property \Carbon\Carbon $discount_time_end
 * @property string $discount_days
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\Product $product
 *
 * @package App\Models
 */
class ProductDiscount extends Model
{
	protected $primaryKey = 'id_product_discount';

	protected $casts = [
		'id_product' => 'int',
		'discount_percentage' => 'int',
		'discount_nominal' => 'int'
	];

	protected $dates = [
		'discount_start' => 'datetime:Y-m-d',
		'discount_end' => 'datetime:Y-m-d',
		'discount_time_start' => 'datetime:H:i:s',
		'discount_time_end' => 'datetime:H:i:s'
	];

	protected $fillable = [
		'id_product',
		'discount_percentage',
		'discount_nominal',
		'discount_start',
		'discount_end',
		'discount_time_start',
		'discount_time_end',
		'discount_days'
	];

	public function product()
	{
		return $this->belongsTo(\App\Http\Models\Product::class, 'id_product');
	}
}
