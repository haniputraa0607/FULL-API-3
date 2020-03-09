<?php

namespace Modules\Quest\Entities;

use Illuminate\Database\Eloquent\Model;

class QuestUser extends Model
{
    protected $table = 'quest_users';

    protected $primaryKey = 'id_quest_user';

    protected $fillable = [
        'id_quest',
        'id_quest_detail',
        'id_user',
        'json_rule',
        'json_rule_enc',
        'date'
    ];
}
