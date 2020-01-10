<?php

namespace Modules\PointInjection\Entities;

use Illuminate\Database\Eloquent\Model;

class PointInjectionReport extends Model
{
    protected $table = 'point_injection_reports';

    protected $fillable = [
        'id_point_injection',
        'id_user',
        'point'
    ];
}
