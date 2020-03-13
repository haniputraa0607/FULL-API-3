<?php

namespace Modules\Quest\Entities;

use Illuminate\Database\Eloquent\Model;

class QuestDetail extends Model
{
    protected $table = 'quest_details';

    protected $primaryKey = 'id_quest_detail';

    protected $fillable = [
        'id_quest',
        'name',
        'id_product',
        'product_total',
        'trx_nominal',
        'trx_total',
        'id_outlet',
        'id_province',
        'different_outlet',
        'different_province'
    ];
}
