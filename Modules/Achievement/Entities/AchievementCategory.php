<?php

namespace Modules\Achievement\Entities;

use Illuminate\Database\Eloquent\Model;

class AchievementCategory extends Model
{
    protected $table = 'achievement_categories';

    protected $primaryKey = 'id_achievement_category';
    
    protected $fillable = [
        'name'
    ];
}
