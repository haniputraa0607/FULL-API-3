<?php

namespace Modules\Outlet\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletGroupFilterOutlet extends Model
{
    protected $table = 'outlet_group_filter_outlets';
    protected $primaryKey = 'id_outlet_group_filter_outlet';

    protected $fillable = [
        'id_outlet_group',
        'id_outlet'
    ];
}
