<?php

namespace Modules\Achievement\Entities;

use Illuminate\Database\Eloquent\Model;

class AchievementProvinceLog extends Model
{
    protected $table = 'achievement_province_logs';

    protected $primaryKey = 'id_achievement_province_log';
    
    protected $fillable = [
        'id_achievement_group',
        'id_achievement_detail',
        'id_user',
        'id_transaction',
        'id_province',
        'date',
        'enc'
    ];
}
