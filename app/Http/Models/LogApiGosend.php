<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class LogApiGosend extends Model
{
	public $primaryKey = 'id_log_api_gosend';
	protected $connection = 'mysql2';
    protected $fillable = [
    	'type',
    	'id_reference',
    	'request_url',
    	'request_method',
        'request_header',
        'request_parameter',
    	'response_body',
        'response_header',
    	'response_code'
    ];
}
