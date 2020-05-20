<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class LogApiSms extends Model
{
    protected $primaryKey = 'id_log_api_sms';
    protected $fillable=[
	    'request_body',
	    'request_url',
	    'response',
	    'phone'
    ];
}
