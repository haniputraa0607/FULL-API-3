<?php

namespace Modules\MokaPOS\Entities;

use Illuminate\Database\Eloquent\Model;

class MokaAccountBusiness extends Model
{
    protected $table = 'moka_account_business';

    protected $fillable   = [
        'id_moka_account',
        'ib_moka_business',
        'name',
        'email',
        'phone'
    ];
}
