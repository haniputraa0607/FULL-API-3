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
class LogActivityLocation extends Model
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
    protected $table = 'log_activity_locations';

    /**
     * @var array
     */
    protected $fillable = [
        'id_log_activity_location', 
        'id_user', 
        'id_reference', 
        'activity', 
        'action', 
        'latitude',
        'longitude', 
        'response_json', 
        'street_address', 
        'route', 
        'administrative_area_level_5', 
        'administrative_area_level_4', 
        'administrative_area_level_3', 
        'administrative_area_level_2', 
        'administrative_area_level_1',
        'country',
        'postal_code',
        'formatted_address'
    ];

}
