<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use App\Jobs\FraudJob;
use App\Lib\MyHelper;

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
		// 'transaction_subtotal' => 'int',
		'transaction_shipment' => 'int',
		// 'transaction_service' => 'int',
		'transaction_discount' => 'int',
		// 'transaction_tax' => 'int',
		'transaction_grandtotal' => 'int',
		'transaction_point_earned' => 'int',
		'transaction_cashback_earned' => 'int'
	];

	protected $dates = [
		'void_date'
	];

	protected $fillable = [
	    'id_transaction_group',
		'id_user',
		'id_outlet',
		'id_promo_campaign_promo_code',
		'id_subscription_user_voucher',
		'transaction_receipt_number',
		'transaction_notes',
		'transaction_subtotal',
        'transaction_gross',
		'transaction_shipment',
		'transaction_shipment_go_send',
		'transaction_is_free',
		'transaction_service',
		'transaction_discount',
        'transaction_discount_item',
        'transaction_discount_bill',
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
		'completed_at',
		'special_memberships',
		'membership_level',
		'id_deals_voucher',
		'latitude',
		'longitude',
        'distance_customer',
		'membership_promo_id',
        'transaction_flag_invalid',
        'image_invalid_flag',
        'fraud_flag',
		'cashback_insert_status',
		'calculate_achievement',
		'show_rate_popup',
		'transaction_discount_delivery',
		'transaction_discount_item',
		'transaction_discount_bill',
		'need_manual_void',
		'failed_void_reason',
		'shipment_method',
		'shipment_courier'
	];

	public $manual_refund = 0;
	public $payment_method = null;
	public $payment_detail = null;
	public $payment_reference_number = null;

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
		return $this->hasOne(\App\Http\Models\TransactionPaymentMidtran::class, 'id_transaction');
	}

	public function transaction_payment_offlines()
	{
		return $this->hasMany(\App\Http\Models\TransactionPaymentOffline::class, 'id_transaction');
	}
	public function transaction_payment_ovo()
	{
		return $this->hasMany(\App\Http\Models\TransactionPaymentOvo::class, 'id_transaction');
	}

	public function transaction_payment_ipay88()
	{
		return $this->hasOne(\Modules\IPay88\Entities\TransactionPaymentIpay88::class, 'id_transaction');
	}

	public function transaction_payment_shopee_pay()
	{
		return $this->hasOne(\Modules\ShopeePay\Entities\TransactionPaymentShopeePay::class, 'id_transaction');
	}

	public function transaction_payment_subscription()
	{
		return $this->hasOne(\Modules\Subscription\Entities\TransactionPaymentSubscription::class, 'id_transaction');
	}

	public function products()
	{
		return $this->belongsToMany(\App\Http\Models\Product::class, 'transaction_products', 'id_transaction', 'id_product')
					->select('product_categories.*','products.*')
					->leftJoin('product_categories', 'product_categories.id_product_category', '=', 'products.id_product_category')
					->withPivot('id_transaction_product', 'transaction_product_qty', 'transaction_product_price', 'transaction_product_price_base', 'transaction_product_price_tax', 'transaction_product_subtotal', 'transaction_modifier_subtotal', 'transaction_product_discount', 'transaction_product_note')
					->withTimestamps();
	}

	public function transaction_shipments()
	{
		return $this->belongsTo(\App\Http\Models\TransactionShipment::class, 'id_transaction', 'id_transaction');
	}

    public function productTransaction() 
    {
    	return $this->hasMany(TransactionProduct::class, 'id_transaction', 'id_transaction')
            ->where('type', 'Product')
            ->whereNull('id_bundling_product')
            ->orderBy('transaction_products.id_product');
	}

    public function allProductTransaction() 
    {
    	return $this->hasMany(TransactionProduct::class, 'id_transaction', 'id_transaction')
            ->where('type', 'Product')
            ->orderBy('transaction_products.id_product');
	}

    public function productTransactionBundling()
    {
        return $this->hasMany(TransactionProduct::class, 'id_transaction', 'id_transaction')
            ->where('type', 'Product')
            ->whereNotNull('id_bundling_product')
            ->orderBy('id_product');
    }

	public function plasticTransaction() 
    {
    	return $this->hasMany(TransactionProduct::class, 'id_transaction', 'id_transaction')->where('type', 'Plastic')->orderBy('id_product');
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

    public function transaction_pickup_go_send()
    {
    	// make sure you have joined transaction_pickups before using this
		return $this->belongsTo(TransactionPickupGoSend::class, 'id_transaction_pickup', 'id_transaction_pickup');
    }

    public function transaction_pickup_wehelpyou()
    {
    	// make sure you have joined transaction_pickups before using this
		return $this->belongsTo(TransactionPickupWehelpyou::class, 'id_transaction_pickup', 'id_transaction_pickup');
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

	public function promo_campaign_promo_code()
	{
		return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaignPromoCode::class, 'id_promo_campaign_promo_code', 'id_promo_campaign_promo_code');
	}

	public function pickup_gosend_update()
	{
		return $this->hasMany(\App\Http\Models\TransactionPickupGoSendUpdate::class, 'id_transaction', 'id_transaction')->orderBy('created_at','desc');
	}
    public function transaction_multiple_payment()
    {
        return $this->hasMany(\App\Http\Models\TransactionMultiplePayment::class, 'id_transaction');
    }

    public function promo_campaign()
    {
        return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaignPromoCode::class, 'id_promo_campaign_promo_code', 'id_promo_campaign_promo_code')
            ->join('promo_campaigns', 'promo_campaigns.id_promo_campaign', 'promo_campaign_promo_codes.id_promo_campaign');
    }

    public function point_refund(){
        return $this->belongsTo(LogBalance::class, 'id_transaction', 'id_reference')
            ->where('source', 'like', 'Rejected%');
    }

    public function point_use(){
        return $this->belongsTo(LogBalance::class, 'id_transaction', 'id_reference')
            ->where('balance', '<', 0)
            ->whereIn('source', ['Online Transaction', 'Transaction']);
    }

    public function disburse_outlet_transaction(){
        return $this->hasOne(\Modules\Disburse\Entities\DisburseOutletTransaction::class, 'id_transaction');
    }

    public function subscription_user_voucher()
	{
		return $this->belongsTo(\Modules\Subscription\Entities\SubscriptionUserVoucher::class, 'id_subscription_user_voucher');
	}

    public function outlet_city()
    {
        return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet')
            ->join('cities','cities.id_city','outlets.id_city');
    }

    /**
     * Called when payment completed
     * @return [type] [description]
     */
    public function triggerPaymentCompleted($data = [])
    {
    	\DB::beginTransaction();
    	// check complete allowed
    	if ($this->transaction_payment_status != 'Pending') {
    		return $this->transaction_payment_status == 'Completed';
    	}

        $this->update([
            'transaction_payment_status' => 'Completed',
            'completed_at' => date('Y-m-d H:i:s')
        ]);

    	// trigger payment complete -> service
    	switch ($this->trasaction_type) {
    		case 'Pickup Order':
    			$this->transaction_pickup->triggerPaymentCompleted($data);
    			break;

    	}

    	// check fraud
    	if ($this->user) {
	    	$this->user->update([
	            'count_transaction_day' => $this->user->count_transaction_day + 1,
	            'count_transaction_week' => $this->user->count_transaction_week + 1,
	    	]);

	    	$config_fraud_use_queue = Configs::where('config_name', 'fraud use queue')->value('is_active');

	        if($config_fraud_use_queue == 1){
	            FraudJob::dispatch($this->user, $this, 'transaction')->onConnection('fraudqueue');
	        }else {
	            $checkFraud = app('\Modules\SettingFraud\Http\Controllers\ApiFraud')->checkFraudTrxOnline($this->user, $this);
	        }
    	}

    	// send notification
        $trx = clone $this;
        $mid = [
            'order_id'     => $trx->transaction_receipt_number,
            'gross_amount' => $trx->transaction_multiple_payment->where('type', '<>', 'Balance')->sum(),
        ];
        $trx->load('outlet');
        $trx->load('productTransaction');

        //app('\Modules\Transaction\Http\Controllers\ApiNotification')->notification($mid, $trx);

        \DB::commit();
    	return true;
    }

    /**
     * Called when payment completed
     * @return [type] [description]
     */
    public function triggerPaymentCancelled($data = [])
    {
    	\DB::beginTransaction();
    	// check complete allowed
    	if ($this->transaction_payment_status != 'Pending') {
    		return $this->transaction_payment_status == 'Completed';
    	}

    	// update transaction payment cancelled
    	$this->update([
    		'transaction_payment_status' => 'Cancelled', 
    		'void_date' => date('Y-m-d H:i:s')
    	]);
		MyHelper::updateFlagTransactionOnline($this, 'cancel', $this->user);

        //reversal balance
        $logBalance = LogBalance::where('id_reference', $this->id_transaction)->whereIn('source', ['Online Transaction', 'Transaction'])->where('balance', '<', 0)->get();
        foreach($logBalance as $logB){
            $reversal = app('\Modules\Balance\Http\Controllers\BalanceController')->addLogBalance( $this->id_user, abs($logB['balance']), $this->id_transaction, 'Reversal', $this->transaction_grandtotal);
            if (!$reversal) {
            	\DB::rollBack();
            	return false;
            }
            $user = User::where('id', $this->id_user)->first();
            $send = app('\Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM('Transaction Failed Point Refund', $this->user->phone,
                [
                    "outlet_name"       => $this->outlet_name->outlet_name,
                    "transaction_date"  => $this->transaction_date,
                    'id_transaction'    => $this->id_transaction,
                    'receipt_number'    => $this->transaction_receipt_number,
                    'received_point'    => (string) abs($logB['balance']),
                    'order_id'          => $this->order_id,
                ]
            );
        }

        // restore promo status
        if ($this->id_promo_campaign_promo_code) {
	        // delete promo campaign report
        	$update_promo_report = app('\Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign')->deleteReport($this->id_transaction, $this->id_promo_campaign_promo_code);
        	if (!$update_promo_report) {
            	\DB::rollBack();
            	return false;
            }	
        }

        // return voucher
        $update_voucher = app('\Modules\Deals\Http\Controllers\ApiDealsVoucher')->returnVoucher($this->id_transaction);

        // return subscription
        $update_subscription = app('\Modules\Subscription\Http\Controllers\ApiSubscriptionVoucher')->returnSubscription($this->id_transaction);

    	// trigger payment cancelled -> service
    	switch ($this->trasaction_type) {
    		case 'Pickup Order':
    			$this->transaction_pickup->triggerPaymentCancelled($data);
    			break;
    	}

        app('\Modules\Transaction\Http\Controllers\ApiOnlineTransaction')->updateStockProduct($this->id_transaction, 'cancel');

    	// send notification
    	// TODO write notification logic here
    	app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
        	'Transaction Expired', 
        	$this->user->phone, 
        	[
	            'date' => $this->transaction_date,
            	'outlet_name' => $this->outlet['outlet_name'],
            	'detail' => $detail ?? null,
            	'receipt_number' => $this->transaction_receipt_number
	        ]
	    );

    	\DB::commit();
    	return true;
    }

    public function triggerReject($data = [])
    {
    	\DB::beginTransaction();

    	if ($this->reject_at) {
    		return true;
    	}

    	$this->update([
    		'reject_at' => date('Y-m-d H:i:s'),
    		'reject_reason' => $data['reject_reason'] ?? null
    	]);

    	$refundPayment = app('\Modules\OutletApp\Http\Controllers\ApiOutletApp')->refundPayment($this);
    	if (empty($refundPayment['status']) || $refundPayment['status'] != 'success') {
        	\DB::rollBack();
        	return false;
        }	

    	// restore promo status
        if ($this->id_promo_campaign_promo_code) {
	        // delete promo campaign report
        	$update_promo_report = app('\Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign')->deleteReport($this->id_transaction, $this->id_promo_campaign_promo_code);
        	if (!$update_promo_report) {
            	\DB::rollBack();
            	return false;
            }	
        }

        // return voucher
        $update_voucher = app('\Modules\Deals\Http\Controllers\ApiDealsVoucher')->returnVoucher($this->id_transaction);

        // return subscription
        $update_subscription = app('\Modules\Subscription\Http\Controllers\ApiSubscriptionVoucher')->returnSubscription($this->id_transaction);

    	// send notification
    	// TODO write notification logic here
    	app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
        	'Transaction Rejected', 
        	$this->user->phone, 
        	[
	            'date' => $this->transaction_date,
            	'outlet_name' => $this->outlet['outlet_name'],
            	'detail' => $detail ?? null,
            	'receipt_number' => $this->transaction_receipt_number
	        ]
	    );

    	\DB::commit();
    	return true;
    }
}
