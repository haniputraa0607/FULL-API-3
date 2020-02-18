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

    public function moka_account_business()
    {
        return $this->hasMany(MokaAccountBusiness::class, 'id_moka_account', 'id_moka_account');
    }

    public function moka_outlet()
    {
        return $this->hasMany(MokaPOSOutlet::class, 'id_moka_account', 'id_moka_account');
    }
}
