<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id_log_activity
 * @property integer $id_user
 * @property string $module
 * @property string $action
 * @property string $request
 * @property string $created_at
 * @property string $updated_at
 * @property User $user
 */
class LogActivityReadOnly extends Model
{
	/**
	 * The database name used by the model.
	 *
	 * @var string
	 */
	protected $connection = 'mysql3';
	
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'log_activities';

    /**
     * @var array
     */
    protected $fillable = ['id_log_activity', 'module', 'subject', 'url', 'phone', 'id_store', 'user', 'request', 'response_status', 'response', 'ip', 'useragent', 'created_at', 'updated_at'];

}
