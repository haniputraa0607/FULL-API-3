<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 06 Aug 2020 15:39:41 +0700.
 */

namespace Modules\RedirectComplex\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;
// use Wildside\Userstamps\Userstamps;
/**
 * Class RedirectComplexOutlet
 * 
 * @property int $id_redirect_complex_outlet
 * @property int $id_redirect_complex_reference
 * @property int $id_outlet
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Models\Outlet $outlet
 * @property \App\Models\RedirectComplexReference $redirect_complex_reference
 *
 * @package App\Models
 */
class RedirectComplexOutlet extends Eloquent
{
	// use Userstamps;
	protected $primaryKey = 'id_redirect_complex_outlet';

	protected $casts = [
		'id_redirect_complex_reference' => 'int',
		'id_outlet' => 'int'
	];

	protected $fillable = [
		'id_redirect_complex_reference',
		'id_outlet'
	];

	public function outlet()
	{
		return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
	}

	public function redirect_complex_reference()
	{
		return $this->belongsTo(\Modules\RedirectComplex\Entities\RedirectComplexReference::class, 'id_redirect_complex_reference');
	}
}
