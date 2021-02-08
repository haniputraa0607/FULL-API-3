<?php

namespace Modules\Outlet\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletGroupFilterConditionParent extends Model
{
    protected $table = 'outlet_group_filter_condition_parents';
    protected $primaryKey = 'id_outlet_group_filter_condition_parent';

    protected $fillable = [
        'id_outlet_group',
        'condition_parent_rule',
        'condition_parent_rule_next'
    ];
}
