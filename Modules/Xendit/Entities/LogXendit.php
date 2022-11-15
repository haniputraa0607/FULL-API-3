<?php

namespace Modules\Xendit\Entities;

use Illuminate\Database\Eloquent\Model;

class LogXendit extends Model
{
    public $primaryKey = 'id_log_xendit';
    protected $connection = 'mysql2';
    protected $fillable = [
        'type',
        'id_reference',
        'request',
        'request_url',
        'request_method',
        'request_header',
        'response',
        'response_header',
        'response_status_code'
    ];
}
