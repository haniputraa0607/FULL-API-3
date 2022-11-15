<?php

namespace Modules\UserRating\Entities;

use Illuminate\Database\Eloquent\Model;

class UserRatingSummary extends Model
{
    protected $primaryKey = 'id_user_rating_summary';
    protected $fillable = ['id_outlet','id_product','id_doctor','summary_type','key','value'];

    public function doctor()
    {
        return $this->belongsTo(\Modules\Doctor\Entities\Doctor::class, 'id_doctor');
    }
}
