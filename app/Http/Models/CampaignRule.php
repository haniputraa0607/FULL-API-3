<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CampaignRule
 * 
 * @property int $id_campaign_rule
 * @property int $id_campaign
 * @property string $campaign_rule_subject
 * @property string $campaign_rule_operator
 * @property string $campaign_rule_param
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\Campaign $campaign
 *
 * @package App\Models
 */
class CampaignRule extends Model
{
	protected $primaryKey = 'id_campaign_rule';

	protected $casts = [
		'id_campaign_rule_parent' => 'int'
	];

	protected $fillable = [
		'id_campaign_rule_parent',
		'campaign_rule_subject',
		'campaign_rule_operator',
		'campaign_rule_param'
	];

	public function campaign_rule_parent()
	{
		return $this->belongsTo(\App\Http\Models\CampaignRuleParent::class, 'id_campaign');
	}

}

