<?php

namespace Modules\Achievement\Entities;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\Province;
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

    public function product()
    {
        return $this->belongsTo('App\Http\Models\Product', 'id_product');
    }
    public function outlet()
    {
        return $this->belongsTo('App\Http\Models\Outlet', 'id_outlet');
    }
    public function province()
    {
        return $this->belongsTo('App\Http\Models\Province', 'id_province');
    }
}
