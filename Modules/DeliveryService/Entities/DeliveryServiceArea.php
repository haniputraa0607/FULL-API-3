<?php

namespace Modules\DeliveryService\Entities;

use Illuminate\Database\Eloquent\Model;

class DeliveryServiceArea extends Model
{
    protected $table = 'delivery_service_area';

    protected $fillable = [
        'nama_area',
        'phone_number'
    ];
}
