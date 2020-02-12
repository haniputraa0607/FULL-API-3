<?php

namespace Modules\MokaPOS\Entities;

use Illuminate\Database\Eloquent\Model;

class MokaAccount extends Model
{
    protected $table = 'moka_accounts';

    protected $fillable   = [
        'name',
        'desc',
        'application_id',
        'secret',
        'code',
        'redirect_url',
        'token',
        'refresh_token'
    ];
}
