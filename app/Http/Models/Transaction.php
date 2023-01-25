<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use App\Lib\MyHelper;
use Modules\Merchant\Entities\Merchant;
use Modules\Transaction\Entities\TransactionGroup;
use Modules\Transaction\Entities\TransactionShipmentTrackingUpdate;
use DB;

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
        'id_transaction_consultation',
        'transaction_receipt_number',
        'transaction_status',
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
        'transaction_mdr',
        'transaction_mdr_charged',
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
        'discount_charged_outlet',
        'discount_charged_central',
        'need_manual_void',
        'refund_requirement',
        'failed_void_reason',
        'shipment_method',
        'shipment_courier',
        'transaction_maximum_date_process',
        'transaction_maximum_date_delivery',
        'transaction_reject_reason',
        'transaction_reject_at'
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
        return $this->belongsTo(\App\Http\Models\TransactionPaymentMidtran::class, 'id_transaction_group', 'id_transaction_group');
    }

    public function transaction_payment_xendit()
    {
        return $this->belongsTo(\Modules\Xendit\Entities\TransactionPaymentXendit::class, 'id_transaction_group', 'id_transaction_group');
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
                    ->select('product_categories.*', 'products.*')
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
        return $this->hasMany(\App\Http\Models\TransactionPickupGoSendUpdate::class, 'id_transaction', 'id_transaction')->orderBy('created_at', 'desc');
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

    public function point_refund()
    {
        return $this->belongsTo(LogBalance::class, 'id_transaction', 'id_reference')
            ->where('source', 'like', 'Rejected%');
    }

    public function point_use()
    {
        return $this->belongsTo(LogBalance::class, 'id_transaction', 'id_reference')
            ->where('balance', '<', 0)
            ->whereIn('source', ['Online Transaction', 'Transaction']);
    }

    public function disburse_outlet_transaction()
    {
        return $this->hasOne(\Modules\Disburse\Entities\DisburseOutletTransaction::class, 'id_transaction');
    }

    public function subscription_user_voucher()
    {
        return $this->belongsTo(\Modules\Subscription\Entities\SubscriptionUserVoucher::class, 'id_subscription_user_voucher');
    }

    public function outlet_city()
    {
        return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet')
            ->join('cities', 'cities.id_city', 'outlets.id_city');
    }

    public function transaction_products()
    {
        return $this->hasMany(TransactionProduct::class, 'id_transaction', 'id_transaction');
    }

    /**
     * Called when payment completed
     * @return [type] [description]
     */
    public function triggerPaymentCompleted($data = [])
    {
        // check complete allowed
        if ($this->transaction_payment_status != 'Pending') {
            return $this->transaction_payment_status == 'Completed';
        }

        \DB::beginTransaction();
        $this->update([
            'transaction_status' => ($this->trasaction_type == 'Consultation' ? 'Completed' : 'Pending'),
            'transaction_payment_status' => 'Completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'transactions_maximum_date_process' => date('Y-m-d', strtotime(('Y-m-d') . ' + 3 days'))
        ]);

        // trigger payment complete -> service
        switch ($this->trasaction_type) {
            case 'Pickup Order':
                $this->transaction_pickup->triggerPaymentCompleted($data);
                break;
            case 'Consultation':
                $this->consultation->triggerPaymentCompleted($data);
                break;
        }

        // send notification
        $trx = clone $this;
        $mid = [
            'order_id'     => $trx->transaction_receipt_number,
            'gross_amount' => $trx->transaction_multiple_payment->where('type', '<>', 'Balance')->sum(),
        ];
        $trx->load('outlet');
        $trx->load('productTransaction');

        if ($this->trasaction_type == 'Delivery') {
            TransactionShipmentTrackingUpdate::create([
                'id_transaction' => $this->id_transaction,
                'tracking_description' => 'Menunggu konfirmasi penjual',
                'tracking_date_time' => date('Y-m-d H:i:s')
            ]);

            $idMerchant = Merchant::where('id_outlet', $this->id_outlet)->first()['id_merchant'] ?? null;
            $user = User::where('id', $this->id_user)->first();
            app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
                'Merchant Transaction New',
                $idMerchant,
                [
                    'customer_name' => $user['name'] ?? '',
                    'customer_email' =>  $user['email'] ?? '',
                    'customer_phone' =>  $user['phone'] ?? '',
                    'receipt_number' => $this->transaction_receipt_number
                ],
                null,
                false,
                false,
                'merchant'
            );
        }

        $this->recalculateTaxandMDR();

        app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM('Payment Status', $this->user->phone, [
            "date" => MyHelper::dateFormatInd($this->transaction_date),
            'receipt_number'   => $this->transaction_receipt_number,
            'status'    => 'Pembayaran Berhasil'
        ]);

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
            'transaction_status' => 'Rejected',
            'transaction_payment_status' => 'Cancelled',
            'transaction_reject_reason' => 'Pembayaran Dibatalkan',
            'void_date' => date('Y-m-d H:i:s')
        ]);
        MyHelper::updateFlagTransactionOnline($this, 'cancel', $this->user);

        // restore promo status
        if ($this->id_promo_campaign_promo_code) {
            // delete promo campaign report
            $update_promo_report = app('\Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign')->deleteReport($this->id_transaction, $this->id_promo_campaign_promo_code);
            if (!$update_promo_report) {
                \DB::rollBack();
                return false;
            }
        }

        // trigger payment cancelled -> service
        switch ($this->trasaction_type) {
            case 'Pickup Order':
                $this->transaction_pickup->triggerPaymentCancelled($data);
                break;
            case 'Consultation':
                $this->consultation->triggerPaymentCancelled($data);
                break;
        }

        if ($this->trasaction_type == 'Delivery' && !empty($this->trasaction_payment_type)) {
            app('\Modules\Transaction\Http\Controllers\ApiOnlineTransaction')->updateStockProduct($this->id_transaction, 'cancel');
        }

        app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM('Payment Status', $this->user->phone, [
            "date" => MyHelper::dateFormatInd($this->transaction_Date),
            'receipt_number'   => $this->transaction_receipt_number,
            'status'    => 'Pembayaran Dibatalkan'
        ]);

        \DB::commit();
        return true;
    }

    public function triggerReject($data = [])
    {
        \DB::beginTransaction();

        if ($this->transaction_reject_at) {
            return true;
        }

        $this->update([
            'transaction_status' => 'Rejected',
            'transaction_reject_at' => date('Y-m-d H:i:s'),
            'transaction_reject_reason' => $data['reject_reason'] ?? null
        ]);

        $checkCountTrxGroup = Transaction::where('id_transaction_group', $this->id_transaction_group)->count();
        if (isset($data['reject_reason']) && $data['reject_reason'] == 'Auto reject transaction from delivery') {
            $grandTotal = $this->transaction_grandtotal - $this->transaction_shipment + $this->transaction_discount_delivery;
            if ($grandTotal > 0) {
                $refund = app('\Modules\Transaction\Http\Controllers\ApiTransactionRefund')->refundPayment([
                    'id_transaction_group' => $this->id_transaction_group,
                    'id_transaction' => $this->id_transaction,
                    'refund_partial' => $grandTotal,
                    'receipt_number'   => $this->transaction_receipt_number,
                ]);
                if ($refund['status'] == 'fail') {
                    DB::rollback();
                    return false;
                }
            }
        } elseif ($checkCountTrxGroup > 1) {
            $refund = app('\Modules\Transaction\Http\Controllers\ApiTransactionRefund')->refundPayment([
                'id_transaction_group' => $this->id_transaction_group,
                'id_transaction' => $this->id_transaction,
                'refund_partial' => $this->transaction_grandtotal,
                'receipt_number'   => $this->transaction_receipt_number,
            ]);
            if ($refund['status'] == 'fail') {
                DB::rollback();
                return false;
            }
        } else {
            $refund = app('\Modules\Transaction\Http\Controllers\ApiTransactionRefund')->refundPayment([
                'id_transaction_group' => $this->id_transaction_group,
                'id_transaction' => $this->id_transaction,
                'receipt_number'   => $this->transaction_receipt_number,
            ]);
            if ($refund['status'] == 'fail') {
                DB::rollback();
                return false;
            }
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

        // send notification
        // TODO write notification logic here
        $user = User::where('id', $this->id_user)->first();
        if ($this->trasaction_type == 'Consultation') {
            $consultation = TransactionConsultation::where('id_transaction', $this->id_transaction)->with('doctor')->first();
            app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM('Consultation Canceled', $user['phone'], [
                "docter_name"      => $consultation['doctor']['doctor_name'] ?? '',
                'id_transaction'   => $this->id_transaction,
                'receipt_number'   => $this->transaction_receipt_number,
                'consultation_date' => MyHelper::dateFormatInd($consultation['schedule_date'], true, false),
                'consultation_time' => date('H:i', strtotime($consultation['schedule_start_time'])) . ' - ' . date('H:i', strtotime($consultation['schedule_end_time'])),
                'reject_reason' => $data['reject_reason'] ?? null
            ]);
        } else {
            app('\Modules\Transaction\Http\Controllers\ApiOnlineTransaction')->updateStockProduct($this->id_transaction, 'cancel');

            $outlet = Outlet::where('id_outlet', $this->id_outlet)->first();
            app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM('Transaction Reject', $user['phone'], [
                "outlet_name"      => $outlet['outlet_name'],
                "id_reference"     => $this->transaction_receipt_number . ',' . $this->id_outlet,
                "transaction_date" => $this->transaction_date,
                'id_transaction'   => $this->id_transaction,
                'receipt_number'   => $this->transaction_receipt_number,
                'reject_reason'    => $data['reject_reason'] ?? null
            ]);
        }

        \DB::commit();
        return true;
    }

    public function triggerTransactionCompleted($data = [])
    {
        \DB::beginTransaction();

        $this->update([
            'transaction_status' => 'Completed'
        ]);

        //insert point cashback
        $savePoint = app('Modules\Transaction\Http\Controllers\ApiNotification')->savePoint($this);
        if (!$savePoint) {
            DB::rollback();
            return false;
        }

        \DB::commit();
        return true;
    }

    public function consultation()
    {
        return $this->belongsTo(\App\Http\Models\TransactionConsultation::class, 'id_transaction', 'id_transaction');
    }

    public function recalculateTaxandMDR()
    {
        $payment_type = TransactionMultiplePayment::where('id_transaction_group', $this->id_transaction_group)
                        ->where('type', '<>', 'Balance')->first()['type'] ?? null;

        $payment_detail = null;
        switch ($payment_type) {
            case 'Midtrans':
                $payment = $this->transaction_payment_midtrans()->first();
                $payment_detail = optional($payment)->payment_type;
                break;
            case 'Xendit':
                $payment = $this->transaction_payment_xendit()->first();
                $payment_detail = optional($payment)->type;
                break;
        }

        //update mdr
        if ($payment_type && $payment_detail) {
            $code = strtolower($payment_type . '_' . $payment_detail);
            $settingmdr = Setting::where('key', 'mdr_formula')->first()['value_text'] ?? '';
            $settingmdr = (array)json_decode($settingmdr);
            $formula = $settingmdr[$code] ?? '';
            if (!empty($formula)) {
                try {
                    $balanceUse = TransactionPaymentBalance::where('id_transaction', $this->id_transaction)->first()['balance_nominal'] ?? 0;
                    $grandtotal = $this->transaction_grandtotal - $balanceUse;
                    $mdr = MyHelper::calculator($formula, ['transaction_grandtotal' => $grandtotal]);
                    if (!empty($mdr)) {
                        $this->update(['transaction_mdr' => $mdr]);
                    }
                } catch (\Exception $e) {
                }
            }
        }
    }
}
