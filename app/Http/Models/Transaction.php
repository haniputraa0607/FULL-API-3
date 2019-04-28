<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Transaction
 * 
 * @property int $id_transaction
 * @property int $id_user
 * @property string $transaction_receipt_number
 * @property string $transaction_notes
 * @property int $transaction_subtotal
 * @property int $transaction_shipment
 * @property int $transaction_service
 * @property int $transaction_discount
 * @property int $transaction_tax
 * @property int $transaction_grandtotal
 * @property int $transaction_point_earned
 * @property int $transaction_cashback_earned
 * @property string $transaction_payment_status
 * @property \Carbon\Carbon $void_date
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\User $user
 * @property \Illuminate\Database\Eloquent\Collection $transaction_payment_manuals
 * @property \Illuminate\Database\Eloquent\Collection $transaction_payment_midtrans
 * @property \Illuminate\Database\Eloquent\Collection $transaction_payment_offlines
 * @property \Illuminate\Database\Eloquent\Collection $products
 * @property \Illuminate\Database\Eloquent\Collection $transaction_shipments
 *
 * @package App\Models
 */
class Transaction extends Model
{
	protected $primaryKey = 'id_transaction';

	protected $casts = [
		'id_user' => 'int',
		'transaction_subtotal' => 'int',
		'transaction_shipment' => 'int',
		'transaction_service' => 'int',
		'transaction_discount' => 'int',
		'transaction_tax' => 'int',
		'transaction_grandtotal' => 'int',
		'transaction_point_earned' => 'int',
		'transaction_cashback_earned' => 'int'
	];

	protected $dates = [
		'void_date'
	];

	protected $fillable = [
		'id_user',
		'id_outlet',
		'transaction_receipt_number',
		'transaction_notes',
		'transaction_subtotal',
		'transaction_shipment',
		'transaction_shipment_go_send',
		'transaction_is_free',
		'transaction_service',
		'transaction_discount',
		'transaction_tax',
		'trasaction_type',
		'transaction_cashier',
		'sales_type',
		'transaction_device_type',
		'transaction_grandtotal',
		'transaction_point_earned',
		'transaction_cashback_earned',
		'transaction_payment_status',
		'trasaction_payment_type',
		'void_date',
		'transaction_date',
		'special_memberships',
		'membership_level',
		'id_deals_voucher',
		'latitude',
		'longitude',
		'membership_promo_id'
	];

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}

	public function outlet()
	{
		return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
	}
	
	public function outlet_name()
	{
		return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet')->select('id_outlet', 'outlet_name');
	}

	public function transaction_payment_manuals()
	{
		return $this->hasMany(\App\Http\Models\TransactionPaymentManual::class, 'id_transaction');
	}

	public function transaction_payment_midtrans()
	{
		return $this->hasMany(\App\Http\Models\TransactionPaymentMidtran::class, 'id_transaction');
	}

	public function transaction_payment_offlines()
	{
		return $this->hasMany(\App\Http\Models\TransactionPaymentOffline::class, 'id_transaction');
	}

	public function products()
	{
		return $this->belongsToMany(\App\Http\Models\Product::class, 'transaction_products', 'id_transaction', 'id_product')
					->select('product_categories.*','products.*')
					->leftJoin('product_categories', 'product_categories.id_product_category', '=', 'products.id_product_category')
					->withPivot('id_transaction_product', 'transaction_product_qty', 'transaction_product_price', 'transaction_product_price_base', 'transaction_product_price_tax', 'transaction_product_subtotal', 'transaction_product_note')
					->withTimestamps();
	}

	public function transaction_shipments()
	{
		return $this->belongsTo(\App\Http\Models\TransactionShipment::class, 'id_transaction', 'id_transaction');
	}

    public function productTransaction() 
    {
    	return $this->hasMany(TransactionProduct::class, 'id_transaction', 'id_transaction');
    }

    public function product_detail()
    {
    	if ($this->trasaction_type == 'Delivery') {
    		return $this->belongsTo(TransactionShipment::class, 'id_transaction', 'id_transaction');
    	} else {
    		return $this->belongsTo(TransactionPickup::class, 'id_transaction', 'id_transaction');
    	}
	}
	
    public function transaction_pickup()
    {
		return $this->belongsTo(TransactionPickup::class, 'id_transaction', 'id_transaction');
    }

    public function logTopup() 
    {
    	return $this->belongsTo(LogTopup::class, 'id_transaction', 'transaction_reference');
	}
	
	public function vouchers()
	{
		return $this->belongsToMany(\App\Http\Models\DealsVoucher::class, 'transaction_vouchers', 'id_transaction', 'id_deals_voucher');
	}

	public function transaction_vouchers()
	{
		return $this->hasMany(\App\Http\Models\TransactionVoucher::class, 'id_transaction', 'id_transaction');
	}
}
