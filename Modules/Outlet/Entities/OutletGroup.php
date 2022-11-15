<?php

namespace Modules\Outlet\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletGroup extends Model
{
    protected $table = 'outlet_groups';
    protected $primaryKey = 'id_outlet_group';

    protected $fillable = [
        'outlet_group_name',
        'outlet_group_type',
        'is_all_outlet'
    ];

    public function outlet_group_filter_outlet()
    {
        return $this->hasMany(OutletGroupFilterOutlet::class, 'id_outlet_group', 'id_outlet_group');
    }
}
