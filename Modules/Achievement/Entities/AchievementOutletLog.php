<?php

namespace Modules\Achievement\Entities;

use Illuminate\Database\Eloquent\Model;

class AchievementOutletLog extends Model
{
    protected $table = 'achievement_outlet_logs';

    protected $primaryKey = 'id_achievement_outlet_log';
    
    protected $fillable = [
        'id_achievement_group',
        'id_achievement_detail',
        'id_user',
        'id_outlet',
        'product_total',
        'product_nominal',
        'count',
        'date',
        'enc'
    ];
}
