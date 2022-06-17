<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistAnnouncement extends Model
{
	protected $primaryKey = 'id_hairstylist_announcement';

	protected $dates = [
		'date_start',
		'date_end'
	];

	protected $fillable = [
		'date_start',
		'date_end',
		'content'
	];

	public function hairstylist_announcement_rule_parents()
	{
		return $this->hasMany(\Modules\Recruitment\Entities\HairstylistAnnouncementRuleParent::class, 'id_hairstylist_announcement')
					->select('id_hairstylist_announcement_rule_parent', 'id_hairstylist_announcement', 'hairstylist_announcement_rule as rule', 'hairstylist_announcement_rule_next as rule_next');
	}

}
