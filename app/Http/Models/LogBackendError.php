<?php
namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class LogBackendError extends Model
{
	protected $connection = 'mysql2';
	protected $table = 'log_backend_errors';
	protected $primaryKey = 'id_log_backend_error';
	protected $fillable = [
		'response_status',
		'url', 
		'request_method',
		'error', 
		'file', 
		'line', 
		'ip_address', 
		'user_agent',  
		'created_at', 
		'updated_at'
	];
}
