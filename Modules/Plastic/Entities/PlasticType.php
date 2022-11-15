<?php

namespace Modules\Plastic\Entities;

use Illuminate\Database\Eloquent\Model;

class PlasticType extends Model
{
    protected $table = 'plastic_type';
    public $primaryKey = 'id_plastic_type';
    protected $fillable = [
        'plastic_type_name',
        'plastic_type_order'
    ];

    public function outlet_group()
    {
        return $this->hasMany(PlasticTypeOutletGroup::class, 'id_plastic_type', 'id_plastic_type')
            ->join('outlet_groups', 'outlet_groups.id_outlet_group', 'plastic_type_outlet_group.id_outlet_group');
    }
}
