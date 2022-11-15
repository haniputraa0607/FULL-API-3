<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class LogShipper extends Model
{
    protected $table = 'log_shipper';
    protected $primaryKey = 'id_log_shipper';
    protected $connection = 'mysql2';

    protected $fillable = [
        'subject',
        'id_transaction',
        'request',
        'request_url',
        'response'
    ];
}
