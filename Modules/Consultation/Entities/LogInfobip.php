<?php

namespace Modules\Consultation\Entities;

use Illuminate\Database\Eloquent\Model;

class LogInfobip extends Model
{
    protected $table = 'log_infobip';
    protected $primaryKey = 'id_log_infobip';
    protected $connection = 'mysql2';

    protected $fillable = [
        'subject',
        'request',
        'request_url',
        'response'
    ];
}
