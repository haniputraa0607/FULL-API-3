<?php

namespace Modules\Outlet\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletApiKeySecret extends Model
{
    protected $table = 'outlet_api_key_secrets';
    protected $primaryKey = 'id_outlet_api_key_secrets';

    protected $fillable = [
        'id_outlet',
        'api_key',
        'api_secret'
    ];
}
