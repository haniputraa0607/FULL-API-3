<?php

namespace Modules\Achievement\Entities;

use Illuminate\Database\Eloquent\Model;

class AchievementProvinceDifferentLog extends Model
{
    protected $table = 'achievement_province_different_logs';

    protected $primaryKey = 'id_achievement_outlet_different_log';
    
    protected $fillable = [
        'id_achievement_group',
        'id_user',
        'id_province',
    ];
}
