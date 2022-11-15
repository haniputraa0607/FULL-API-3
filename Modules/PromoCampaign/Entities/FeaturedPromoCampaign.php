<?php

/**
 * Created by Reliese Model.
 * Date: Fri, 17 Sep 2021 15:59:17 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class FeaturedPromoCampaign
 *
 * @property int $id_featured_promo_campaign
 * @property int $id_promo_campaign
 * @property \Carbon\Carbon $date_start
 * @property \Carbon\Carbon $date_end
 * @property int $order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \Modules\PromoCampaign\Entities\PromoCampaign $promo_campaign
 *
 * @package Modules\PromoCampaign\Entities
 */
class FeaturedPromoCampaign extends Eloquent
{
    protected $primaryKey = 'id_featured_promo_campaign';

    protected $casts = [
        'id_promo_campaign' => 'int',
        'order' => 'int'
    ];

    protected $dates = [
        'date_start',
        'date_end'
    ];

    protected $fillable = [
        'id_promo_campaign',
        'date_start',
        'date_end',
        'order',
        'feature_type'
    ];

    public function promo_campaign()
    {
        return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaign::class, 'id_promo_campaign');
    }
}
