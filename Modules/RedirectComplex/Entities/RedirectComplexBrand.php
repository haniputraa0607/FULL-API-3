<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 11 Aug 2020 15:54:38 +0700.
 */

namespace Modules\RedirectComplex\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;
// use Wildside\Userstamps\Userstamps;
/**
 * Class RedirectComplexBrand
 * 
 * @property int $id_redirect_complex_brand
 * @property int $id_redirect_complex_reference
 * @property int $id_brand
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Models\Brand $brand
 * @property \App\Models\RedirectComplexReference $redirect_complex_reference
 *
 * @package App\Models
 */
class RedirectComplexBrand extends Eloquent
{
	// use Userstamps;
	protected $primaryKey = 'id_redirect_complex_brand';

	protected $casts = [
		'id_redirect_complex_reference' => 'int',
		'id_brand' => 'int'
	];

	protected $fillable = [
		'id_redirect_complex_reference',
		'id_brand'
	];

	public function brand()
	{
		return $this->belongsTo(\Modules\Brand\Entities\Brand::class,'id_brand');
	}

	public function redirect_complex_reference()
	{
		return $this->belongsTo(\Modules\RedirectComplex\Entities\RedirectComplexReference::class, 'id_redirect_complex_reference');
	}
}
