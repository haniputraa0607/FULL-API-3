<?php

namespace Modules\PointInjection\Entities;

use Illuminate\Database\Eloquent\Model;

class PointInjection extends Model
{
    protected $table = 'point_injections';

    protected $primaryKey = 'id_point_injection';

    protected $fillable = [
        'created_by',
        'title',
        'send_type',
        'start_date',
        'send_time',
        'duration',
        'point',
        'duration',
        'filter_type',
        'total_point',
        'point_injection_media_push',
        'point_injection_push_subject',
        'point_injection_push_content',
        'point_injection_push_image',
        'point_injection_push_clickto',
        'point_injection_push_link',
        'point_injection_push_id_reference'
    ];

    public function user()
    {
        return $this->belongsTo(\App\Http\Models\User::class, 'created_by');
    }

    public function point_injection_rule_parents()
    {
        return $this->hasMany(\Modules\PointInjection\Entities\PointInjectionRuleParent::class, 'id_point_injection', 'id_point_injection')
            ->select('id_point_injection_rule_parent', 'id_point_injection', 'point_injection_rule as rule', 'point_injection_rule_next as rule_next');
    }

    public function point_injection_users()
    {
        return $this->hasMany(\Modules\PointInjection\Entities\PointInjectionUser::class, 'id_point_injection', 'id_point_injection');
    }
}
