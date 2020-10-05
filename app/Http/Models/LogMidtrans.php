<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class LogMidtrans extends Model
{
	public $primaryKey = 'id_log_midtrans';
	protected $connection = 'mysql2';
    protected $fillable = [
    	'type',
    	'id_reference',
    	'request',
        'request_url',
    	'request_header',
    	'response',
        'response_header',
    	'response_status_code'
    ];
}
