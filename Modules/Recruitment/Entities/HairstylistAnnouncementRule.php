<?php

/**
 * Created by Reliese Model.
 * Date: Mon, 18 Oct 2021 16:58:00 +0700.
 */

namespace Modules\Recruitment\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class HairstylistAnnouncementRule
 * 
 * @property int $id_hairstylist_announcement_rule
 * @property int $id_hairstylist_announcement_rule_parent
 * @property string $announcement_rule_subject
 * @property string $announcement_rule_operator
 * @property string $announcement_rule_param
 * @property string $announcement_rule_param_select
 * @property int $announcement_rule_param_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\Recruitment\Entities\HairstylistAnnouncementRuleParent $hairstylist_announcement_rule_parent
 *
 * @package Modules\Recruitment\Entities
 */
class HairstylistAnnouncementRule extends Eloquent
{
	protected $primaryKey = 'id_hairstylist_announcement_rule';

	protected $casts = [
		'id_hairstylist_announcement_rule_parent' => 'int',
		'hairstylist_announcement_rule_param_id' => 'int'
	];

	protected $fillable = [
		'id_hairstylist_announcement_rule_parent',
		'hairstylist_announcement_rule_subject',
		'hairstylist_announcement_rule_operator',
		'hairstylist_announcement_rule_param',
		'hairstylist_announcement_rule_param_select',
		'hairstylist_announcement_rule_param_id'
	];

	public function hairstylist_announcement_rule_parent()
	{
		return $this->belongsTo(\Modules\Recruitment\Entities\HairstylistAnnouncementRuleParent::class, 'id_hairstylist_announcement_rule_parent');
	}
}
