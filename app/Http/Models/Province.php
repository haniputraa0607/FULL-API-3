<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Province
 * 
 * @property int $id_province
 * @property string $province_name
 * 
 * @property \Illuminate\Database\Eloquent\Collection $cities
 *
 * @package App\Models
 */
class Province extends Model
{
	protected $primaryKey = 'id_province';
	public $timestamps = false;

	protected $fillable = [
		'province_name'
	];

	public function cities()
	{
		return $this->hasMany(\App\Http\Models\City::class, 'id_province');
	}
}
