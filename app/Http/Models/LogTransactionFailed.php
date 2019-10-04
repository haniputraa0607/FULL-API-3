<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class LogTransactionFailed extends Model
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
    protected $table = 'log_transaction_failed';

    /**
     * @var array
     */
    protected $fillable = [
        'id_log_transaction_failed', 
        'outlet_code', 
        'request', 
        'message_failed', 
        'created_at',  
        'updated_at'];
}
