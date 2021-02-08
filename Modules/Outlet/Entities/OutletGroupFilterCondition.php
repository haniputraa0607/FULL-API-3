<?php

namespace Modules\Outlet\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletGroupFilterCondition extends Model
{
    protected $table = 'outlet_group_filter_conditions';
    protected $primaryKey = 'id_outlet_group_filter_condition';

    protected $fillable = [
        'id_outlet_group_filter_condition_parent',
        'id_outlet_group',
        'outlet_group_filter_subject',
        'outlet_group_filter_operator',
        'outlet_group_filter_parameter'
    ];
}
