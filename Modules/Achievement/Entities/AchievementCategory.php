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
    
    public function achievement_group()
    {
        return $this->hasMany(AchievementGroup::class, 'id_achievement_category', 'id_achievement_category');
    }
}
