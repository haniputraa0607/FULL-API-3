<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class LogCron extends Model
{
	protected $primaryKey = 'id_log_cron';
	protected $connection = 'mysql2';

	protected $fillable = [
		'cron',
		'status',
		'start_date',
		'end_date',
		'description'
	];

	public function success($msg = null)
	{
		$this->status = 'success';
		$this->end_date = date('Y-m-d H:i:s');
		$this->description = $msg;
		$this->save();
	}

	public function fail($msg = null)
	{
		$this->status = 'fail';
		$this->end_date = date('Y-m-d H:i:s');
		$this->description = $msg;
		$this->save();
	}
}
