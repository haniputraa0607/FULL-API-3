<?php

namespace Modules\IPay88\Entities;

use Illuminate\Database\Eloquent\Model;

class LogIpay88 extends Model
{
	public $primaryKey = 'id_log_ipay88';
	protected $connection = 'mysql2';
    protected $fillable = [
    	'type',
    	'id_reference',
    	'triggers',
    	'request',
        'request_url',
    	'request_header',
    	'response',
    	'response_status_code'
    ];
}
