<?php

namespace Modules\Outlet\Entities;

use Illuminate\Database\Eloquent\Model;

class DeliveryOutlet extends Model
{
    protected $table = 'delivery_outlet';
    protected $primaryKey = 'id_delivery_outlet';

    protected $fillable = [
        'id_outlet',
        'code',
        'available_status',
        'show_status'
    ];
}
