<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:16 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class InboxGlobalRule
 * 
 * @property int $id_inbox_global_rule
 * @property int $id_inbox_global
 * @property string $inbox_rule_subject
 * @property string $inbox_rule_operator
 * @property string $inbox_rule_param
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\InboxGlobal $inbox_global
 *
 * @package App\Models
 */
class InboxGlobalRule extends Model
{
	public $incrementing = false;

	protected $casts = [
		'id_inbox_global_rule' => 'int',
		'id_inbox_global_rule_parent' => 'int'
	];

	protected $fillable = [
		'id_inbox_global_rule',
		'id_inbox_global_rule_parent',
		'inbox_global_rule_subject',
		'inbox_global_rule_operator',
		'inbox_global_rule_param'
	];

	public function inbox_global_rule_parent()
	{
		return $this->belongsTo(\App\Http\Models\InboxGlobalRuleParent::class, 'id_inbox_global_rule_parent');
	}
}
