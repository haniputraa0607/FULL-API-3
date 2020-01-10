<?php

namespace Modules\PointInjection\Entities;

use Illuminate\Database\Eloquent\Model;

class PointInjectionRule extends Model
{
    protected $table = 'point_injection_rules';

    protected $fillable = [
        'id_point_injection_rule_parent',
        'point_injection_rule_subject',
        'point_injection_rule_operator',
        'point_injection_rule_param'
    ];
}
