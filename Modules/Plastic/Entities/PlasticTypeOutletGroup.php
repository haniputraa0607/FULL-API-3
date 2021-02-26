<?php

namespace Modules\Plastic\Entities;

use Illuminate\Database\Eloquent\Model;

class PlasticTypeOutletGroup extends Model
{
    protected $table = 'plastic_type_outlet_group';
    public $primaryKey = 'id_plastic_type_outlet_group';
    protected $fillable = [
        'id_plastic_type',
        'id_outlet_group'
    ];
}
