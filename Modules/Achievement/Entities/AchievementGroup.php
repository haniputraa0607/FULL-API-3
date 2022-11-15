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
        'progress_text',
        'order_by',
        'status',
        'is_calculate'
    ];

    public function getIdAchievementGroupAttribute($value)
    {
        return \App\Lib\MyHelper::encSlug($value);
    }

    public function achievement_detail()
    {
        return $this->hasMany(AchievementDetail::class, 'id_achievement_group', 'id_achievement_group');
    }
}
