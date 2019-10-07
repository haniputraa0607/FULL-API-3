<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionQueue extends Model
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
    protected $table = 'transaction_queue';

    /**
     * @var array
     */
    protected $fillable = [
            'id_transaction_queue', 
            'outlet_code', 
            'request_transaction', 
            'created_at',  
            'updated_at'
        ];
}
