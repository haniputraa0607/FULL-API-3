<?php

namespace Modules\ShopeePay\Entities;

use Illuminate\Database\Eloquent\Model;

class LogShopeePay extends Model
{
	public $primaryKey = 'id_log_shopee_pay';
	protected $connection = 'mysql2';
    protected $fillable = [
    	'type',
    	'id_reference',
    	'request',
        'request_url',
    	'request_header',
    	'response',
    	'response_status_code'
    ];
}
