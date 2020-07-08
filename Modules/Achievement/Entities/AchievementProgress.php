<?php

namespace Modules\Achievement\Entities;

use Illuminate\Database\Eloquent\Model;

class AchievementProgress extends Model
{
    protected $table = 'achievement_progress';

    protected $primaryKey = 'id_achievement_progress';
    
    protected $fillable = [
        'id_achievement_detail',
        'id_user',
        'progress',
        'end_progress'
    ];
}
