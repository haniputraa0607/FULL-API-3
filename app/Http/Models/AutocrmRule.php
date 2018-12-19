<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:14 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AutocrmRule
 * 
 * @property int $id_campaign_rule
 * @property int $id_autocrm
 * @property string $campaign_rule_subject
 * @property string $campaign_rule_operator
 * @property string $campaign_rule_param
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\Autocrm $autocrm
 *
 * @package App\Models
 */
class AutocrmRule extends Model
{
	protected $primaryKey = 'id_autocrm_rule';

	protected $casts = [
		'id_autocrm_rule_parent' => 'int'
	];

	protected $fillable = [
		'id_autocrm_rule_parent',
		'autocrm_rule_subject',
		'autocrm_rule_operator',
		'autocrm_rule_param'
	];

	public function autocrm_rule_parents()
	{
		return $this->belongsTo(\App\Http\Models\AutocrmRuleParent::class, 'id_autocrm_rule_parent');
	}
}
