<?php

namespace Modules\Quest\Entities;

use Illuminate\Database\Eloquent\Model;

class QuestUserLog extends Model
{
    protected $table = 'quest_user_logs';

    protected $primaryKey = 'id_quest_user_log';

    protected $fillable = [
        'id_quest',
        'id_quest_detail',
        'id_user',
        'json_rule',
        'json_rule_enc',
        'date'
    ];
}
