<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class SyncTransactionQueues extends Model
{
    /**
	 * The database name used by the model.
	 *
	 * @var string
	 */
    protected $connection = 'mysql';
	
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'sync_transaction_queues';

    /**
     * @var array
     */
    protected $fillable = [
            'id_sync_transaction_queues', 
            'outlet_code', 
            'request_transaction', 
            'created_at',  
            'updated_at'
        ];
}
