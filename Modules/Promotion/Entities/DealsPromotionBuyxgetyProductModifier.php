<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 08 Dec 2020 09:51:43 +0700.
 */

namespace Modules\Promotion\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsPromotionBuyxgetyProductModifier
 *
 * @property int $id_deals_promotion_buyxgety_product_modifier
 * @property int $id_deals_buyxgety_rule
 * @property int $id_product_modifier
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \Modules\Promotion\Entities\DealsPromotionBuyxgetyRule $deals_promotion_buyxgety_rule
 *
 * @package Modules\Promotion\Entities
 */
class DealsPromotionBuyxgetyProductModifier extends Eloquent
{
    protected $primaryKey = 'id_deals_promotion_buyxgety_product_modifier';

    protected $casts = [
        'id_deals_buyxgety_rule' => 'int',
        'id_product_modifier' => 'int'
    ];

    protected $fillable = [
        'id_deals_buyxgety_rule',
        'id_product_modifier'
    ];

    public function deals_promotion_buyxgety_rule()
    {
        return $this->belongsTo(\Modules\Promotion\Entities\DealsPromotionBuyxgetyRule::class, 'id_deals_buyxgety_rule');
    }

    public function modifier()
    {
        return $this->hasOne(\App\Http\Models\ProductModifier::class, 'id_product_modifier', 'id_product_modifier');
    }
}
