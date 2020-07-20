<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;


class LogIRIS extends Model
{
    protected $table = 'log_iris';
	protected $primaryKey = 'id_log_iris';
    protected $connection = 'mysql2';

	protected $fillable = [
	    'subject',
	    'id_reference',
		'request',
        'request_header',
        'request_url',
        'response'
	];
}
