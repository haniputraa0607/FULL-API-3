<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace Modules\Promotion\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DealsPromotionOutlet
 *
 * @property int $id_deals
 * @property int $id_outlet
 *
 * @property \App\Http\Models\Deal $deal
 * @property \App\Http\Models\Outlet $outlet
 *
 * @package App\Models
 */
class DealsPromotionOutlet extends Model
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'id_deals' => 'int',
        'id_outlet' => 'int'
    ];

    protected $fillable = [
        'id_deals',
        'id_outlet'
    ];

    public function deals_promotion_template()
    {
        return $this->belongsTo(\Modules\Promotion\Entities\DealsPromotionTemplate::class, 'id_deals');
    }

    public function outlet()
    {
        return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
    }
}
