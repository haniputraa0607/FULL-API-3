<?php

/**
 * Created by Reliese Model.
 * Date: Wed, 04 Mar 2020 16:16:53 +0700.
 */

namespace Modules\Promotion\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsPromotionContentDetail
 * 
 * @property int $id_deals_content_detail
 * @property int $id_deals_content
 * @property string $content
 * @property int $order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Models\DealsPromotionContent $deals_promotion_content
 *
 * @package App\Models
 */
class DealsPromotionContentDetail extends Eloquent
{
	protected $primaryKey = 'id_deals_content_detail';

	protected $casts = [
		'id_deals_content' => 'int',
		'order' => 'int'
	];

	protected $fillable = [
		'id_deals_content',
		'content',
		'order'
	];

	public function deals_promotion_content()
	{
		return $this->belongsTo(\Modules\Promotion\Entities\DealsPromotionContent::class, 'id_deals_content');
	}
}
