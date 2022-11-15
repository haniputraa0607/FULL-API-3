<?php

/**
 * Created by Reliese Model.
 * Date: Fri, 05 Feb 2021 10:42:39 +0700.
 */

namespace Modules\Promotion\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsPromotionOutletGroup
 *
 * @property int $id_deals_promotion_outlet_group
 * @property int $id_deals
 * @property int $id_outlet_group
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \App\Models\DealsPromotionTemplate $deals_promotion_template
 * @property \App\Models\OutletGroup $outlet_group
 *
 * @package App\Models
 */
class DealsPromotionOutletGroup extends Eloquent
{
    protected $primaryKey = 'id_deals_promotion_outlet_group';

    protected $casts = [
        'id_deals' => 'int',
        'id_outlet_group' => 'int'
    ];

    protected $fillable = [
        'id_deals',
        'id_outlet_group'
    ];

    public function deals_promotion_template()
    {
        return $this->belongsTo(\Modules\Promotion\Entities\DealsPromotionTemplate::class, 'id_deals');
    }

    public function outlet_group()
    {
        return $this->belongsTo(\Modules\Outlet\Entities\OutletGroup::class, 'id_outlet_group');
    }
}
