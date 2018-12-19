<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class LogTopupPos extends Model
{
	protected $table = 'log_topup_pos';

    protected $primaryKey = 'id_log_topup_pos';

	protected $fillable = [
		'id_log_topup',
		'id_outlet',
		'otp',
		'status',
		'expired_at',
		'created_at',
		'updated_at',
	];
}
