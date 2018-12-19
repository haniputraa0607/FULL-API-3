<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class AutocrmRuleParent extends Model
{
    protected $primaryKey = 'id_autocrm_rule_parent';

	protected $casts = [
		'id_autocrm' => 'int'
	];

	protected $fillable = [
		'id_autocrm',
		'autocrm_rule',
		'autocrm_rule_next',
	];

	public function autocrm()
	{
		return $this->belongsTo(\App\Http\Models\Autocrm::class, 'id_autocrm');
	}

	public function rules()
	{
		return $this->hasMany(\App\Http\Models\AutocrmRule::class, 'id_autocrm_rule_parent')
					->select('id_autocrm_rule','id_autocrm_rule_parent','autocrm_rule_subject as subject', 'autocrm_rule_operator as operator', 'autocrm_rule_param as parameter');
	}
}
