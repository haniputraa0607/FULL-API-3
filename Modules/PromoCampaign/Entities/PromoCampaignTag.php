<?php

/**
 * Created by Reliese Model.
 * Date: Mon, 16 Dec 2019 16:41:15 +0700.
 */

namespace Modules\PromoCampaign\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class PromoCampaignTag
 * 
 * @property int $id_promo_campaign_tag
 * @property string $tag_name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Illuminate\Database\Eloquent\Collection $promo_campaign_have_tags
 *
 * @package Modules\PromoCampaign\Entities
 */
class PromoCampaignTag extends Eloquent
{
	protected $primaryKey = 'id_promo_campaign_tag';

	protected $fillable = [
		'tag_name'
	];

	public function promo_campaign_have_tags()
	{
		return $this->hasMany(\Modules\PromoCampaign\Entities\PromoCampaignHaveTag::class, 'id_promo_campaign_tag');
	}
}
