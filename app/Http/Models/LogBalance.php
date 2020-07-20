<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserAddress
 * 
 * @property int $id_user_address
 * @property string $name
 * @property string $phone
 * @property int $id_user
 * @property int $id_city
 * @property string $address
 * @property string $postal_code
 * @property string $description
 * @property string $primary
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\City $city
 * @property \App\Http\Models\User $user
 *
 * @package App\Models
 */
class LogBalance extends Model
{
	protected $primaryKey = 'id_log_balance';

	protected $casts = [
		'id_user' => 'int'
	];

	protected $fillable = [
		'id_user',
		'balance',
		'balance_before',
		'balance_after',
		'id_reference',
		'source',
		'grand_total',
		'ccashback_conversion',
		'membership_level',
		'membership_cashback_percentage',
		'enc',
		'created_at',
		'updated_at'
	];

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}
	
	public function detail_trx()
	{
		return $this->belongsTo(\App\Http\Models\Transaction::class, 'id_reference')->select('id_transaction', 'transaction_receipt_number', 'trasaction_type');
	}

	public function transaction()
	{
		return $this->belongsTo(\App\Http\Models\Transaction::class, 'id_reference', 'id_transaction');
	}

	public function pointInjection()
	{
		return $this->belongsTo(\Modules\PointInjection\Entities\PointInjection::class, 'id_reference', 'id_point_injection');
	}

	public function getGetReferenceAttribute() {

        if( $this->source == 'Transaction' || $this->source == 'Rejected Order' || $this->source == 'Rejected Order Midtrans' || $this->source == 'Rejected Order Point' || $this->source == 'Reversal' || $this->source == 'Online Transaction')
        {
        	$this->load(['transaction' => function($q){
        		$q->select('id_transaction', 'transaction_receipt_number', 'trasaction_type');
        	}]);
        } 
        elseif( $this->source == 'Point Injection' )
        {
        	$this->load(['pointInjection' => function($q){
        		$q->select('id_point_injection', 'title');
        	}]);
        }
    }
}
