<?php

/**
 * Created by Reliese Model.
 * Date: Fri, 05 Feb 2021 10:43:17 +0700.
 */

namespace Modules\Subscription\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class SubscriptionOutletGroup
 *
 * @property int $id_subscription_outlet_group
 * @property int $id_subscription
 * @property int $id_outlet_group
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \App\Models\OutletGroup $outlet_group
 * @property \App\Models\Subscription $subscription
 *
 * @package App\Models
 */
class SubscriptionOutletGroup extends Eloquent
{
    protected $primaryKey = 'id_subscription_outlet_group';

    protected $casts = [
        'id_subscription' => 'int',
        'id_outlet_group' => 'int'
    ];

    protected $fillable = [
        'id_subscription',
        'id_outlet_group'
    ];

    public function outlet_group()
    {
        return $this->belongsTo(\Modules\Outlet\Entities\OutletGroup::class, 'id_outlet_group');
    }

    public function subscription()
    {
        return $this->belongsTo(\Modules\Subscription\Entities\Subscription::class, 'id_subscription');
    }
}
