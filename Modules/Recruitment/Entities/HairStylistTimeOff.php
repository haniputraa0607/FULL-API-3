<?php

namespace Modules\Recruitment\Entities;

use App\Http\Models\Outlet;
use App\Http\Models\User;
use Illuminate\Database\Eloquent\Model;

class HairStylistTimeOff extends Model
{
    protected $table = 'hairstylist_time_off';
    protected $primaryKey = 'id_hairstylist_time_off';

	protected $dates = [
		'date'
	];

	protected $fillable = [
		'id_user_hair_stylist',
		'id_outlet',
		'approve_by',
		'request_by',
		'date',
		'start_time',
		'end_time',
		'request_at',
		'approve_at',
		'reject_at',

	];

    public function hair_stylist(){
        return $this->belongsTo(UserHairStylist::class, 'id_user_hair_stylist');
    }

    public function outlet(){
        return $this->belongsTo(Outlet::class, 'id_outlet');
    }

    public function approve(){
        return $this->belongsTo(User::class, 'approve_by');
    }

    public function request(){
        return $this->belongsTo(User::class, 'request_by');
    }


}
