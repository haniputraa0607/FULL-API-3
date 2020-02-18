<?php

namespace Modules\MokaPOS\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletMokaPOS extends Model
{
    protected $table = 'outlets';

    protected $primaryKey = 'id_outlet';

    protected $fillable = [
        'id_moka_account_business',
        'id_moka_outlet',
        'outlet_code',
        'outlet_pin',
        'outlet_name',
        'outlet_address',
        'id_city',
        'outlet_postal_code',
        'outlet_phone',
        'outlet_email',
        'outlet_latitude',
        'outlet_longitude',
        'outlet_status',
        'deep_link_gojek',
        'deep_link_grab',
        'big_order'
    ];
}
