<?php

/**
 * Created by Reliese Model.
 * Date: Fri, 04 Dec 2020 09:32:19 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class PromoCampaignBuyxgetyProductModifier
 *
 * @property int $id_promo_campaign_buyxgety_product_modifier
 * @property int $id_promo_campaign_buyxgety_product
 * @property int $id_product_modifier_group
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \Modules\PromoCampaign\Entities\PromoCampaignBuyxgetyProductRequirement $promo_campaign_buyxgety_product_requirement
 *
 * @package Modules\PromoCampaign\Entities
 */
class PromoCampaignBuyxgetyProductModifier extends Eloquent
{
    protected $primaryKey = 'id_promo_campaign_buyxgety_product_modifier';

    protected $casts = [
        'id_promo_campaign_buyxgety_rule' => 'int',
        'id_product_modifier' => 'int'
    ];

    protected $fillable = [
        'id_promo_campaign_buyxgety_rule',
        'id_product_modifier'
    ];

    public function promo_campaign_buyxgety_rules()
    {
        return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaignBuyxgetyRule::class, 'id_promo_campaign');
    }

    public function modifier()
    {
        return $this->hasOne(\App\Http\Models\ProductModifier::class, 'id_product_modifier', 'id_product_modifier');
    }
}
