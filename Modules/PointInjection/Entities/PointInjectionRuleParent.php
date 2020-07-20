<?php

namespace Modules\PointInjection\Entities;

use Illuminate\Database\Eloquent\Model;

class PointInjectionRuleParent extends Model
{
    protected $table = 'point_injection_rule_parents';

    protected $primaryKey = 'id_point_injection_rule_parent';

    protected $fillable = [
        'id_point_injection',
        'point_injection_rule',
        'point_injection_rule_next'
    ];

    public function rules()
    {
        return $this->hasMany(\Modules\PointInjection\Entities\PointInjectionRule::class, 'id_point_injection_rule_parent', 'id_point_injection_rule_parent')
            ->select('id_point_injection_rule', 'id_point_injection_rule_parent', 'point_injection_rule_subject as subject', 'point_injection_rule_operator as operator', 'point_injection_rule_param as parameter');
    }
}
