<?php

namespace Modules\Achievement\Entities;

use Illuminate\Database\Eloquent\Model;

class AchievementDetail extends Model
{
    protected $table = 'achievement_details';

    protected $primaryKey = 'id_achievement_detail';
    
    protected $fillable = [
        'id_achievement_group',
        'name',
        'logo_badge',
        'id_product',
        'product_total',
        'trx_nominal',
        'trx_total',
        'id_outlet',
        'id_province',
        'different_outlet',
        'different_province'
    ];
}
