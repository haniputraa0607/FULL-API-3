<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class UserHairStylistDevice extends Model
{
    protected $primaryKey = 'id_user_hair_stylist_device';
    protected $fillable = [
        'id_user_hair_stylist',
        'device_type',
        'device_id',
        'device_token',
    ];
}
