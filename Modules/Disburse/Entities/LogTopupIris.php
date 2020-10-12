<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class LogTopupIris extends Model
{
    protected $table = 'log_topup_iris';
	protected $primaryKey = 'id_log_topup_iris';

	protected $fillable = [
	    'response'
	];
}
