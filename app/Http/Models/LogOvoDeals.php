<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class LogOvoDeals extends Model
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
    protected $table = 'log_ovo_deals';

    public $primaryKey = 'id_log_ovo_deals';

    /**
     * @var array
     */
    protected $fillable = ['id_log_ovo_deals', 'id_deals_payment_ovo', 'order_id', 'url', 'header', 'request', 'response_status', 'response_code', 'response', 'created_at', 'updated_at'];
}
