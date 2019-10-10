<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class LogActivitiesPos extends Model
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
    protected $table = 'log_activities_pos';

    /**
     * @var array
     */
    protected $fillable = [
        'id_log_activities_pos',  
        'url', 
        'subject',  
        'outlet_code',  
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
