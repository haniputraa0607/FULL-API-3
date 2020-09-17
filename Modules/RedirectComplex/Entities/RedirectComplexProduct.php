<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 06 Aug 2020 15:39:51 +0700.
 */

namespace Modules\RedirectComplex\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;
// use Wildside\Userstamps\Userstamps;
/**
 * Class RedirectComplexProduct
 * 
 * @property int $id_redirect_complex_product
 * @property int $id_redirect_complex_reference
 * @property int $id_product
 * @property int $qty
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Models\Product $product
 * @property \App\Models\RedirectComplexReference $redirect_complex_reference
 *
 * @package App\Models
 */
class RedirectComplexProduct extends Eloquent
{
	// use Userstamps;
	protected $primaryKey = 'id_redirect_complex_product';

	protected $casts = [
		'id_redirect_complex_reference' => 'int',
		'id_product' => 'int',
		'qty' => 'int'
	];

	protected $fillable = [
		'id_redirect_complex_reference',
		'id_brand',
		'id_product',
		'qty'
	];

	public function product()
	{
		return $this->belongsTo(\App\Http\Models\Product::class, 'id_product');
	}

	public function redirect_complex_reference()
	{
		return $this->belongsTo(\Modules\RedirectComplex\Entities\RedirectComplexReference::class, 'id_redirect_complex_reference');
	}

	public function brand()
	{
		return $this->belongsTo(\Modules\Brand\Entities\Brand::class,'id_brand');
	}
}
