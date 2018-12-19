<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionRule extends Model
{
	protected $primaryKey = 'id_promotion_rule';

	protected $casts = [
		'id_promotion_rule_parent' => 'int',
	];

	protected $fillable = [
		'id_promotion_rule_parent',
		'promotion_rule_subject',
		'promotion_rule_operator',
		'promotion_rule_param',
		'created_at',
		'updated_at',
	];

	public function promotion_rule_parents()
	{
		return $this->belongsTo(\App\Http\Models\PromotionRuleParent::class, 'id_promotion_rule_parent');
	}
}

