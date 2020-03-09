<?php

namespace Modules\Quest\Entities;

use Illuminate\Database\Eloquent\Model;

class QuestBenefit extends Model
{
    protected $table = 'quest_benefits';

    protected $primaryKey = 'id_quest_benefit';

    protected $fillable = [
        'id_quest',
        'benefit_type',
        'value',
        'id_deals'
    ];
}
