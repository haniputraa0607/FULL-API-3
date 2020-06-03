<?php

namespace Modules\Achievement\Entities;

use Illuminate\Database\Eloquent\Model;

class AchievementUser extends Model
{
    protected $table = 'achievement_users';

    protected $primaryKey = 'id_achievement_user';
    
    protected $fillable = [
        'id_achievement_detail',
        'id_user',
        'json_rule',
        'json_rule_enc',
        'date'
    ];

    public function user()
    {
        return $this->belongsTo('App\Http\Models\Product', 'id_product');
    }
}
