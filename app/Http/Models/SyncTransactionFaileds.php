<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class SyncTransactionFaileds extends Model
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
    protected $table = 'sync_transaction_faileds';

    /**
     * @var array
     */
    protected $fillable = [
        'id_sync_transaction_faileds', 
        'outlet_code', 
        'request', 
        'message_failed', 
        'created_at',  
        'updated_at'];
}
