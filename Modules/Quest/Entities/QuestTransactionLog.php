<?php

namespace Modules\Quest\Entities;

use Illuminate\Database\Eloquent\Model;

class QuestTransactionLog extends Model
{
    protected $primaryKey = 'id_quest_transaction_log';

    protected $fillable = [
        'id_quest',
        'id_quest_detail',
        'id_user',
        'id_transaction',
        'id_outlet',
        'transaction_total',
        'transaction_nominal',
        'date',
        'enc'
    ];
}
