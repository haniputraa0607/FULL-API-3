<?php

namespace Modules\Quest\Entities;

use Illuminate\Database\Eloquent\Model;

class QuestProvinceLog extends Model
{
    protected $table = 'quest_province_logs';

    protected $primaryKey = 'id_quest_province_log';

    protected $fillable = [
        'id_quest',
        'id_quest_detail',
        'id_user',
        'id_transaction',
        'id_province',
        'date',
        'enc'
    ];
}
