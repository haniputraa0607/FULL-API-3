<?php

namespace Modules\Achievement\Entities;

use Illuminate\Database\Eloquent\Model;

class AchievementUserLog extends Model
{
    protected $table = 'achievement_user_logs';

    protected $primaryKey = 'id_achievement_user_log';
    
    protected $fillable = [
        'id_achievement_detail',
        'id_user',
        'json_rule',
        'json_rule_enc',
        'date'
    ];
}