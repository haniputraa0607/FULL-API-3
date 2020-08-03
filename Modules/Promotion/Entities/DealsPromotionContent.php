<?php

/**
 * Created by Reliese Model.
 * Date: Wed, 04 Mar 2020 16:16:42 +0700.
 */

namespace Modules\Promotion\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsPromotionContent
 * 
 * @property int $id_deals_content
 * @property int $id_deals
 * @property string $title
 * @property int $order
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Models\DealsPromotionTemplate $deals_promotion_template
 * @property \Illuminate\Database\Eloquent\Collection $deals_promotion_content_details
 *
 * @package App\Models
 */
class DealsPromotionContent extends Eloquent
{
	protected $primaryKey = 'id_deals_content';

	protected $casts = [
		'id_deals' => 'int',
		'order' => 'int',
		'is_active' => 'bool'
	];

	protected $fillable = [
		'id_deals',
		'title',
		'order',
		'is_active'
	];

	public function deals_promotion_template()
	{
		return $this->belongsTo(\App\Http\Models\DealsPromotionTemplate::class, 'id_deals');
	}

	public function deals_promotion_content_details()
	{
		return $this->hasMany(\Modules\Promotion\Entities\DealsPromotionContentDetail::class, 'id_deals_content');
	}
}
