<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:16 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Feature
 * 
 * @property int $id_feature
 * @property string $feature_type
 * @property string $feature_module
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Illuminate\Database\Eloquent\Collection $users
 *
 * @package App\Models
 */
class Feature extends Model
{
	protected $primaryKey = 'id_feature';

	protected $fillable = [
		'feature_type',
		'feature_module'
	];

	public function users()
	{
		return $this->belongsToMany(\App\Http\Models\User::class, 'user_features', 'id_feature', 'id_user');
	}
}
