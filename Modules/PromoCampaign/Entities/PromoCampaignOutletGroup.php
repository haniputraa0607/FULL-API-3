<?php

/**
 * Created by Reliese Model.
 * Date: Fri, 05 Feb 2021 10:35:03 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class PromoCampaignOutletGroup
 *
 * @property int $id_promo_campaign_outlet_group
 * @property int $id_promo_campaign
 * @property int $id_outlet_group
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \App\Models\OutletGroup $outlet_group
 * @property \App\Models\PromoCampaign $promo_campaign
 *
 * @package App\Models
 */
class PromoCampaignOutletGroup extends Eloquent
{
    protected $primaryKey = 'id_promo_campaign_outlet_group';

    protected $casts = [
        'id_promo_campaign' => 'int',
        'id_outlet_group' => 'int'
    ];

    protected $fillable = [
        'id_promo_campaign',
        'id_outlet_group'
    ];

    public function outlet_group()
    {
        return $this->belongsTo(\Modules\Outlet\Entities\OutletGroup::class, 'id_outlet_group');
    }

    public function promo_campaign()
    {
        return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaign::class, 'id_promo_campaign');
    }
}
