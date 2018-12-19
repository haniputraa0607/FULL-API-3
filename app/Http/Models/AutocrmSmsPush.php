<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class AutocrmSmsPush extends Model
{
    protected $connection = 'mysql'; 
    
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'autocrm_push_logs';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'id_autocrm_push_log';

    /**
     * @var array
     */
    protected $fillable = ['id_user', 
						   'push_log_to',
						   'push_log_subject',
						   'push_log_content',
						   'created_at',
						   'updated_at'
						   ];
						   
	public function user() {
        return $this->belongsTo(User::class, 'users', 'id_user', 'id');
    }
}
