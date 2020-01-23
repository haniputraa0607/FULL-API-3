<?php

namespace Modules\PointInjection\Entities;

use Illuminate\Database\Eloquent\Model;

class PivotPointInjection extends Model
{
    protected $table = 'pivot_point_injections';

    protected $fillable = [
        'id_point_injection',
        'id_user',
        'title',
        'send_time',
        'point',
        'point_injection_media_push',
        'point_injection_push_subject',
        'point_injection_push_content',
        'point_injection_push_image',
        'point_injection_push_clickto',
        'point_injection_push_link',
        'point_injection_push_id_reference',
    ];
}
