<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class LogActivitiesBE extends Model
{
    /**
	 * The database name used by the model.
	 *
	 * @var string
	 */
	protected $connection = 'mysql2';
	
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'log_activities_be';

    /**
     * @var array
     */
    protected $fillable = [
        'id_log_activities_be',  
        'module', 
        'url', 
        'subject',
        'phone',
        'user', 
        'request', 
        'response_status', 
        'response', 
        'ip', 
        'useragent', 
        'created_at', 
        'updated_at'
    ];
}
