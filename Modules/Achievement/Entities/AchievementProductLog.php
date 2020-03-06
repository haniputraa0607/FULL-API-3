<?php

namespace Modules\Achievement\Entities;

use Illuminate\Database\Eloquent\Model;

class AchievementProductLog extends Model
{
    protected $table = 'achievement_product_logs';

    protected $primaryKey = 'id_achievement_product_log';
    
    protected $fillable = [
        'id_achievement_group',
        'id_achievement_detail',
        'id_user',
        'id_transaction',
        'id_product',
        'product_total',
        'product_nominal',
        'date',
        'enc'
    ];
}
