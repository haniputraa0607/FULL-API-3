<?php

namespace Modules\MokaPOS\Entities;

use Illuminate\Database\Eloquent\Model;

class MokaPOSOutlet extends Model
{
    protected $table = 'moka_outlets';

    protected $fillable   = [
        'id_moka_outlet',
        'id_outlet',
        'id_moka_account'
    ];
}
