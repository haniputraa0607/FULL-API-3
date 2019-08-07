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
class LogOvo extends Model
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
    protected $table = 'log_ovos';

    /**
     * @var array
     */
    protected $fillable = ['id_log_ovo', 'id_transaction_payment_ovo', 'transaction_receipt_number', 'url', 'header', 'request', 'response_status', 'response_code', 'response', 'created_at', 'updated_at'];

}
