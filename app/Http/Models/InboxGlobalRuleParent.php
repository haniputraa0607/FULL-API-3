<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class InboxGlobalRuleParent extends Model
{
    protected $primaryKey = 'id_inbox_global_rule_parent';

	protected $casts = [
		'id_inbox_global' => 'int'
	];

	protected $fillable = [
		'id_inbox_global',
		'inbox_global_rule',
		'inbox_global_rule_next',
	];

	public function inbox_global()
	{
		return $this->belongsTo(\App\Http\Models\inbox_global::class, 'id_inbox_global');
	}

	public function rules()
	{
		return $this->hasMany(\App\Http\Models\InboxGlobalRule::class, 'id_inbox_global_rule_parent')
					->select('id_inbox_global_rule','id_inbox_global_rule_parent','inbox_global_rule_subject as subject', 'inbox_global_rule_operator as operator', 'inbox_global_rule_param as parameter', 'inbox_global_rule_param_id as id');
	}
}
