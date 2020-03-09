<?php

namespace Modules\Quest\Entities;

use Illuminate\Database\Eloquent\Model;

class QuestOutletLog extends Model
{
    protected $table = 'quest_outlet_logs';

    protected $primaryKey = 'id_quest_outlet_log';

    protected $fillable = [
        'id_quest',
        'id_quest_detail',
        'id_user',
        'id_outlet',
        'count',
        'date',
        'enc'
    ];
}
