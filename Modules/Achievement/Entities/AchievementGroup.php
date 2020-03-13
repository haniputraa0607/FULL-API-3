<?php

namespace Modules\Achievement\Entities;

use Illuminate\Database\Eloquent\Model;

class AchievementGroup extends Model
{
    protected $table = 'achievement_groups';

    protected $primaryKey = 'id_achievement_group';
    
    protected $fillable = [
        'id_achievement_category',
        'name',
        'logo_badge_default',
        'date_start',
        'date_end',
        'publish_start',
        'publish_end',
        'description',
        'order_by'
    ];

    public function getIdAchievementGroupAttribute($value) {
        return \App\Lib\MyHelper::encSlug($value);
    }
}
