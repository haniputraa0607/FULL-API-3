<?php

/**
 * Created by Reliese Model.
 * Date: Mon, 18 Oct 2021 16:58:31 +0700.
 */

namespace Modules\Recruitment\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class HairstylistAnnouncementRuleParent
 * 
 * @property int $id_hairstylist_announcement_rule_parent
 * @property int $id_hairstylist_announcement
 * @property string $announcement_rule
 * @property string $announcement_rule_next
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\Recruitment\Entities\HairstylistAnnouncement $hairstylist_announcement
 * @property \Illuminate\Database\Eloquent\Collection $hairstylist_announcement_rules
 *
 * @package Modules\Recruitment\Entities
 */
class HairstylistAnnouncementRuleParent extends Eloquent
{
	protected $primaryKey = 'id_hairstylist_announcement_rule_parent';

	protected $casts = [
		'id_hairstylist_announcement' => 'int'
	];

	protected $fillable = [
		'id_hairstylist_announcement',
		'hairstylist_announcement_rule',
		'hairstylist_announcement_rule_next'
	];

	public function hairstylist_announcement()
	{
		return $this->belongsTo(\Modules\Recruitment\Entities\HairstylistAnnouncement::class, 'id_hairstylist_announcement');
	}

	public function hairstylist_announcement_rules()
	{
		return $this->hasMany(\Modules\Recruitment\Entities\HairstylistAnnouncementRule::class, 'id_hairstylist_announcement_rule_parent');
	}

	public function rules()
	{
		return $this->hasMany(\Modules\Recruitment\Entities\HairstylistAnnouncementRule::class, 'id_hairstylist_announcement_rule_parent')
					->select('id_hairstylist_announcement_rule','id_hairstylist_announcement_rule_parent','hairstylist_announcement_rule_subject as subject', 'hairstylist_announcement_rule_operator as operator', 'hairstylist_announcement_rule_param as parameter', 'hairstylist_announcement_rule_param_id as id', 'hairstylist_announcement_rule_param_select as parameter_select');
	}
}
