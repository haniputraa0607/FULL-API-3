<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class HairstylistSchedule
 * 
 * @property int $id_hairstylist_schedule
 * @property int $id_user_hair_stylist
 * @property int $id_outlet
 * @property int $approve_by
 * @property \Carbon\Carbon $request_at
 * @property \Carbon\Carbon $approve_at
 * @property \Carbon\Carbon $reject_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package Modules\Recruitment\Entities
 */
class HairstylistUpdateData extends Model
{
	protected $table = 'hairstylist_update_datas';
	protected $primaryKey = 'id_hairstylist_update_data';

	protected $casts = [
		'id_user_hair_stylist' => 'int',
		'approve_by' => 'int'
	];

	protected $dates = [
		'approve_at',
		'reject_at'
	];

	protected $fillable = [
		'id_user_hair_stylist',
		'approve_by',
		'field',
		'new_value',
		'notes',
		'approve_at',
		'reject_at'
	];

	public function user_hair_stylist()
	{
		return $this->belongsTo(\Modules\Recruitment\Entities\UserHairStylist::class, 'id_user_hair_stylist');
	}
}