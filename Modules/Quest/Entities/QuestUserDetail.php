<?php

namespace Modules\Quest\Entities;

use Illuminate\Database\Eloquent\Model;

class QuestUserDetail extends Model
{
    protected $table = 'quest_user_details';

    protected $primaryKey = 'id_quest_user_detail';

    protected $fillable = [
        'id_quest',
        'id_quest_detail',
        'id_user',
        'json_rule',
        'json_rule_enc',
        'date'
    ];
}
