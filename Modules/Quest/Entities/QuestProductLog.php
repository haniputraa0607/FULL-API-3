<?php

namespace Modules\Quest\Entities;

use Illuminate\Database\Eloquent\Model;

class QuestProductLog extends Model
{
    protected $table = 'quest_product_logs';

    protected $primaryKey = 'id_quest_product_log';

    protected $fillable = [
        'id_quest',
        'id_quest_detail',
        'id_user',
        'id_transaction',
        'id_product',
        'id_product_variant_group',
        'id_product_category',
        'product_total',
        'product_nominal',
        'date',
        'enc'
    ];
}
