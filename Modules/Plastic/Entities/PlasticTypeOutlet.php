<?php

namespace Modules\Plastic\Entities;

use Illuminate\Database\Eloquent\Model;

class PlasticTypeOutlet extends Model
{
    protected $table = 'plastic_type_outlet';
    public $primaryKey = 'id_plastic_type_outlet';
    protected $fillable = [
        'id_plastic_type',
        'id_outlet'
    ];
}
