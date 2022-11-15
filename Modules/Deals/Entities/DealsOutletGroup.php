<?php

/**
 * Created by Reliese Model.
 * Date: Fri, 05 Feb 2021 10:37:43 +0700.
 */

namespace Modules\Deals\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsOutletGroup
 *
 * @property int $id_deals_outlet_group
 * @property int $id_deals
 * @property int $id_outlet_group
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \App\Models\Deal $deal
 * @property \App\Models\OutletGroup $outlet_group
 *
 * @package App\Models
 */
class DealsOutletGroup extends Eloquent
{
    protected $primaryKey = 'id_deals_outlet_group';

    protected $casts = [
        'id_deals' => 'int',
        'id_outlet_group' => 'int'
    ];

    protected $fillable = [
        'id_deals',
        'id_outlet_group'
    ];

    public function deal()
    {
        return $this->belongsTo(\Modules\Deals\Entities\Deal::class, 'id_deals');
    }

    public function outlet_group()
    {
        return $this->belongsTo(\Modules\Outlet\Entities\OutletGroup::class, 'id_outlet_group');
    }
}
