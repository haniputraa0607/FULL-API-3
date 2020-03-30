<?php

/**
 * Created by Reliese Model.
 * Date: Mon, 02 Mar 2020 11:44:34 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class UserPromo
 * 
 * @property int $id
 * @property int $id_user
 * @property string $promo_type
 * @property int $id_reference
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Models\User $user
 *
 * @package App\Models
 */
class UserPromo extends Eloquent
{
	protected $casts = [
		'id_user' => 'int',
		'id_reference' => 'int'
	];

	protected $fillable = [
		'id_user',
		'promo_type',
		'id_reference'
	];

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}
}
