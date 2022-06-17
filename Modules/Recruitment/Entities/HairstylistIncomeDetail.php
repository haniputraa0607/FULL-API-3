<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistIncomeDetail extends Model
{
    public $primaryKey = 'id_hairstylist_income_detail';
    protected $fillable = [
        'id_hairstylist_income',
        'source',
        'reference',
        'id_outlet',
        'amount',
    ];
}
