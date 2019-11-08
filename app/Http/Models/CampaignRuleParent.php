<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignRuleParent extends Model
{
    protected $primaryKey = 'id_campaign_rule_parent';

	protected $casts = [
		'id_campaign' => 'int'
	];

	protected $fillable = [
		'id_campaign',
		'campaign_rule',
		'campaign_rule_next',
	];

	public function campaign()
	{
		return $this->belongsTo(\App\Http\Models\Campaign::class, 'id_campaign');
	}

	public function rules()
	{
		return $this->hasMany(\App\Http\Models\CampaignRule::class, 'id_campaign_rule_parent')
					->select('id_campaign_rule','id_campaign_rule_parent','campaign_rule_subject as subject', 'campaign_rule_operator as operator', 'campaign_rule_param as parameter', 'campaign_rule_param_id as id');
	}
}
