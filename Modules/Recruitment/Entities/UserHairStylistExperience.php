<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class UserHairStylistExperience extends Model
{
    protected $table = 'user_hair_stylist_experiences';

    protected $primaryKey = 'id_user_hair_stylist_experience';

    protected $fillable   = [
        'id_user_hair_stylist',
        'value',
    ];
}
