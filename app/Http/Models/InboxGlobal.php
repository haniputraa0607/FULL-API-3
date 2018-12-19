<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:16 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class InboxGlobal
 * 
 * @property int $id_inbox_global
 * @property int $id_campaign
 * @property string $inbox_global_subject
 * @property string $inbox_global_content
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\Campaign $campaign
 * @property \App\Http\Models\InboxGlobalRule $inbox_global_rule
 *
 * @package App\Models
 */
class InboxGlobal extends Model
{
	protected $primaryKey = 'id_inbox_global';

	protected $casts = [
		'id_campaign' => 'int'
	];

	protected $fillable = [
		'id_campaign',
		'inbox_global_subject',
		'inbox_global_clickto',
		'inbox_global_content',
		'inbox_global_link',
		'inbox_global_id_reference',
		'inbox_global_start',
		'inbox_global_end',
		'inbox_global_rulenya'
	];

	public function campaign()
	{
		return $this->belongsTo(\App\Http\Models\Campaign::class, 'id_campaign');
	}

	public function inbox_global_rule_parents()
	{
		return $this->hasMany(\App\Http\Models\InboxGlobalRuleParent::class, 'id_inbox_global')
					->select('id_inbox_global_rule_parent', 'id_inbox_global', 'inbox_global_rule as rule', 'inbox_global_rule_next as rule_next');
	}
}
