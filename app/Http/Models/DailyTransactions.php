<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class DailyTransactions extends Model
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
    protected $table = 'daily_transactions';

    /**
     * @var array
     */
    protected $fillable = [
        'id_daily_transaction',
        'transaction_date',
        'id_transaction',
        'id_user',
        'id_outlet',
        'created_at', 
        'updated_at'
    ];
}
