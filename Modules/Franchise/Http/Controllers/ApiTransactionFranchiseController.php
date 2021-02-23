<?php

namespace Modules\Franchise\Http\Controllers;

use App\Http\Models\Deal;
use App\Http\Models\TransactionProductModifier;
use Illuminate\Pagination\Paginator;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionPayment;
use App\Http\Models\TransactionPickupGoSend;
use App\Http\Models\Province;
use App\Http\Models\City;
use App\Http\Models\User;
use App\Http\Models\Courier;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use App\Http\Models\ProductModifierPrice;
use App\Http\Models\ProductModifierGlobalPrice;
use App\Http\Models\Setting;
use App\Http\Models\StockLog;
use App\Http\Models\UserAddress;
use App\Http\Models\ManualPayment;
use App\Http\Models\ManualPaymentMethod;
use App\Http\Models\ManualPaymentTutorial;
use App\Http\Models\TransactionPaymentManual;
use App\Http\Models\TransactionPaymentOffline;
use App\Http\Models\TransactionPaymentBalance;
use Modules\Disburse\Entities\MDR;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\Outlet;
use App\Http\Models\LogPoint;
use App\Http\Models\LogBalance;
use App\Http\Models\TransactionShipment;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionPaymentMidtran;
use Modules\ProductVariant\Entities\ProductVariant;
use Modules\ProductVariant\Entities\TransactionProductVariant;
use Modules\ShopeePay\Entities\TransactionPaymentShopeePay;
use App\Http\Models\DealsUser;
use App\Http\Models\DealsPaymentMidtran;
use App\Http\Models\DealsPaymentManual;
use Modules\IPay88\Entities\DealsPaymentIpay88;
use Modules\ShopeePay\Entities\DealsPaymentShopeePay;
use App\Http\Models\UserTrxProduct;
use Modules\Brand\Entities\Brand;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\Subscription\Entities\SubscriptionUserVoucher;
use Modules\Transaction\Entities\LogInvalidTransaction;
use Modules\Transaction\Entities\TransactionBundlingProduct;
use Modules\Transaction\Http\Requests\RuleUpdate;

use Modules\Transaction\Http\Requests\TransactionDetail;
use Modules\Transaction\Http\Requests\TransactionHistory;
use Modules\Transaction\Http\Requests\TransactionFilter;
use Modules\Transaction\Http\Requests\TransactionNew;
use Modules\Transaction\Http\Requests\TransactionShipping;
use Modules\Transaction\Http\Requests\GetProvince;
use Modules\Transaction\Http\Requests\GetCity;
use Modules\Transaction\Http\Requests\GetSub;
use Modules\Transaction\Http\Requests\GetAddress;
use Modules\Transaction\Http\Requests\GetNearbyAddress;
use Modules\Transaction\Http\Requests\AddAddress;
use Modules\Transaction\Http\Requests\UpdateAddress;
use Modules\Transaction\Http\Requests\DeleteAddress;
use Modules\Transaction\Http\Requests\ManualPaymentCreate;
use Modules\Transaction\Http\Requests\ManualPaymentEdit;
use Modules\Transaction\Http\Requests\ManualPaymentUpdate;
use Modules\Transaction\Http\Requests\ManualPaymentDetail;
use Modules\Transaction\Http\Requests\ManualPaymentDelete;
use Modules\Transaction\Http\Requests\MethodSave;
use Modules\Transaction\Http\Requests\MethodDelete;
use Modules\Transaction\Http\Requests\ManualPaymentConfirm;
use Modules\Transaction\Http\Requests\ShippingGoSend;

use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantGroupSpecialPrice;

use App\Jobs\ExportFranchiseJob;
use App\Lib\MyHelper;
use App\Lib\GoSend;
use Validator;
use Hash;
use DB;
use Mail;
use Image;
use Illuminate\Support\Facades\Log;
use App\Exports\MultipleSheetExport;

use Modules\Franchise\Entities\ExportFranchiseQueue;

class ApiTransactionFranchiseController extends Controller
{
    public $saveImage = "img/transaction/manual-payment/";

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->disburse = "Modules\Disburse\Http\Controllers\ApiDisburseController";
        $this->trx = "Modules\Transaction\Http\Controllers\ApiTransaction";
    }

    /**
     * Display list of transactions
     * @param Request $request
     * return Response
     */
    public function transactionFilter(TransactionFilter $request) {
        $post = $request->json()->all();
        
        $conditions = [];
        $rule = '';
        $search = '';
        $start = date('Y-m-d', strtotime($post['date_start']));
        $end = date('Y-m-d', strtotime($post['date_end']));
        $delivery = false;
        if(strtolower($post['key']) == 'delivery'){
            $post['key'] = 'pickup order';
            $delivery = true;
        }

        $query = Transaction::join('transaction_pickups','transaction_pickups.id_transaction','=','transactions.id_transaction')->select('transactions.*',
            'transaction_pickups.*',
            'transaction_pickup_go_sends.*',
            'transaction_products.*',
            'users.*',
            'products.*',
            'product_categories.*',
            'outlets.outlet_code', 'outlets.outlet_name')
            ->leftJoin('outlets','outlets.id_outlet','=','transactions.id_outlet')
            ->leftJoin('transaction_pickup_go_sends','transaction_pickups.id_transaction_pickup','=','transaction_pickup_go_sends.id_transaction_pickup')
            ->leftJoin('transaction_products','transactions.id_transaction','=','transaction_products.id_transaction')
            ->leftJoin('users','transactions.id_user','=','users.id')
            ->leftJoin('products','products.id_product','=','transaction_products.id_product')
            ->leftJoin('product_categories','products.id_product_category','=','product_categories.id_product_category')
            ->whereDate('transactions.transaction_date', '>=', $start)
            ->whereDate('transactions.transaction_date', '<=', $end)
            ->with('user')
            // ->orderBy('transactions.id_transaction', 'DESC')
            ->groupBy('transactions.id_transaction');
        
        if (isset($post['id_outlet'])) {
        	$query->where('transactions.id_outlet', $post['id_outlet']);
        }

        if (strtolower($post['key']) !== 'all') {
            $query->where('trasaction_type', $post['key']);
            if($delivery){
                $query->where('pickup_by','<>','Customer');
            }else{
                $query->where('pickup_by','Customer');
            }
        }
        
        if (isset($post['conditions'])) {
            foreach ($post['conditions'] as $key => $con) {
                if (isset($con['subject'])) {
                    if ($con['subject'] == 'receipt') {
                        $var = 'transactions.transaction_receipt_number';
                    } elseif ($con['subject'] == 'name' || $con['subject'] == 'phone' || $con['subject'] == 'email') {
                        $var = 'users.'.$con['subject'];
                    } elseif ($con['subject'] == 'product_name' || $con['subject'] == 'product_code') {
                        $var = 'products.'.$con['subject'];
                    } elseif ($con['subject'] == 'product_category') {
                        $var = 'product_categories.product_category_name';
                    } elseif ($con['subject'] == 'order_id') {
                        $var = 'transaction_pickups.order_id';
                    }

                    if (in_array($con['subject'], ['outlet_code', 'outlet_name'])) {
                        $var = 'outlets.'.$con['subject'];
                        if ($post['rule'] == 'and') {
                            if ($con['operator'] == 'like') {
                                $query = $query->where($var, 'like', '%'.$con['parameter'].'%');
                            } else {
                                $query = $query->where($var, '=', $con['parameter']);
                            }
                        } else {
                            if ($con['operator'] == 'like') {
                                $query = $query->orWhere($var, 'like', '%'.$con['parameter'].'%');
                            } else {
                                $query = $query->orWhere($var, '=', $con['parameter']);
                            }
                        }
                    }
                    if (in_array($con['subject'], ['receipt', 'name', 'phone', 'email', 'product_name', 'product_code', 'product_category', 'order_id'])) {
                        if ($post['rule'] == 'and') {
                            if ($con['operator'] == 'like') {
                                $query = $query->where($var, 'like', '%'.$con['parameter'].'%');
                            } else {
                                $query = $query->where($var, '=', $con['parameter']);
                            }
                        } else {
                            if ($con['operator'] == 'like') {
                                $query = $query->orWhere($var, 'like', '%'.$con['parameter'].'%');
                            } else {
                                $query = $query->orWhere($var, '=', $con['parameter']);
                            }
                        }
                    }

                    if ($con['subject'] == 'product_weight' || $con['subject'] == 'product_price') {
                        $var = 'products.'.$con['subject'];
                        if ($post['rule'] == 'and') {
                            $query = $query->where($var, $con['operator'], $con['parameter']);
                        } else {
                            $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                        }
                    }

                    if ($con['subject'] == 'grand_total' || $con['subject'] == 'product_tax') {
                        if ($con['subject'] == 'grand_total') {
                            $var = 'transactions.transaction_grandtotal';
                        } else {
                            $var = 'transactions.transaction_tax';
                        }

                        if ($post['rule'] == 'and') {
                            $query = $query->where($var, $con['operator'], $con['parameter']);
                        } else {
                            $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                        }
                    }

                    if ($con['subject'] == 'transaction_status') {
                        if ($post['rule'] == 'and') {
                            if($con['operator'] == 'pending'){
                                $query = $query->whereNull('transaction_pickups.receive_at');
                            }elseif($con['operator'] == 'taken_by_driver'){
                                $query = $query->whereNotNull('transaction_pickups.taken_at')
                                    ->whereNotIn('transaction_pickups.pickup_by', ['Customer']);
                            }elseif ($con['operator'] == 'taken_by_customer'){
                                $query = $query->whereNotNull('transaction_pickups.taken_at')
                                    ->where('transaction_pickups.pickup_by', 'Customer');
                            }elseif ($con['operator'] == 'taken_by_system'){
                                $query = $query->whereNotNull('transaction_pickups.ready_at')
                                    ->whereNotNull('transaction_pickups.taken_by_system_at');
                            }elseif($con['operator'] == 'receive_at'){
                                $query = $query->whereNotNull('transaction_pickups.receive_at')
                                    ->whereNull('transaction_pickups.ready_at');
                            }elseif($con['operator'] == 'ready_at'){
                                $query = $query->whereNotNull('transaction_pickups.ready_at')
                                    ->whereNull('transaction_pickups.taken_at');
                            }else{
                                $query = $query->whereNotNull('transaction_pickups.'.$con['operator']);
                            }
                        } else {
                            if($con['operator'] == 'pending'){
                                $query = $query->orWhereNotNull('transaction_pickups.receive_at');
                            }elseif($con['operator'] == 'taken_by_driver'){
                                $query = $query->orWhere(function ($q){
                                    $q->whereNotNull('transaction_pickups.taken_at')
                                        ->whereNotIn('transaction_pickups.pickup_by', ['Customer']);
                                });
                            }elseif ($con['operator'] == 'taken_by_customer'){
                                $query = $query->orWhere(function ($q){
                                    $q->whereNotNull('transaction_pickups.taken_at')
                                        ->where('transaction_pickups.pickup_by', 'Customer');
                                });
                            }elseif ($con['operator'] == 'taken_by_system'){
                                $query = $query->orWhere(function ($q){
                                    $q->whereNotNull('transaction_pickups.ready_at')
                                        ->whereNotNull('transaction_pickups.taken_by_system_at');
                                });
                            }elseif($con['operator'] == 'receive_at'){
                                $query = $query->orWhere(function ($q){
                                    $q->whereNotNull('transaction_pickups.receive_at')
                                        ->whereNull('transaction_pickups.ready_at');
                                });
                            }elseif($con['operator'] == 'ready_at'){
                                $query = $query->orWhere(function ($q) {
                                    $q->whereNotNull('transaction_pickups.ready_at')
                                        ->whereNull('transaction_pickups.taken_at');
                                });
                            }else{
                                $query = $query->orWhereNotNull('transaction_pickups.'.$con['operator']);
                            }
                        }
                    }

                    if (in_array($con['subject'], ['status', 'courier', 'id_outlet', 'id_product', 'pickup_by'])) {
                        switch ($con['subject']) {
                            case 'status':
                                $var = 'transactions.transaction_payment_status';
                                break;

                            case 'courier':
                                $var = 'transactions.transaction_courier';
                                break;

                            case 'id_product':
                                $var = 'products.id_product';
                                break;

                            case 'id_outlet':
                                $var = 'outlets.id_outlet';
                                break;

                            case 'pickup_by':
                                $var = 'transaction_pickups.pickup_by';
                                break;

                            default:
                                continue 2;
                        }

                        if ($post['rule'] == 'and') {
                            $query = $query->where($var, '=', $con['operator']);
                        } else {
                            $query = $query->orWhere($var, '=', $con['operator']);
                        }
                    }
                }
            }

            $conditions = $post['conditions'];
            $rule       = $post['rule'];
            $search     = '1';
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'transaction_date',
                'outlet_code',
                'trasaction_type',
                'transaction_receipt_number',
                'transaction_grandtotal',
                null,
                null,
                null,
            ];
            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $query->orderBy($colname, $column['dir']);
                }
            }
        }
        
        $akhir = $query->paginate($request->length ?: 10);

        if ($akhir) {
            $result = [
                'status'     => 'success',
                'data'       => $akhir,
                'count'      => count($akhir),
                'conditions' => $conditions,
                'rule'       => $rule,
                'search'     => $search
            ];
        } else {
            $result = [
                'status'     => 'fail',
                'data'       => $akhir,
                'count'      => count($akhir),
                'conditions' => $conditions,
                'rule'       => $rule,
                'search'     => $search
            ];
        }

        return response()->json($result);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function transactionDetail(TransactionDetail $request){
        if ($request->json('transaction_receipt_number') !== null) {
            $trx = Transaction::where(['transaction_receipt_number' => $request->json('transaction_receipt_number')])->first();
            if($trx) {
                $id = $trx->id_transaction;
            } else {
                return MyHelper::checkGet([]);
            }
        } else {
            $id = $request->json('id_transaction');
        }

        $type = $request->json('type');

        if ($type == 'trx') {
            if($request->json('admin')){
                $list = Transaction::where(['transactions.id_transaction' => $id])->with('user');
            }else{
                $list = Transaction::where(['transactions.id_transaction' => $id, 'id_user' => $request->user()->id]);
            }
            $list = $list->leftJoin('transaction_pickups','transaction_pickups.id_transaction','=','transactions.id_transaction')->with([
            // 'user.city.province',
                'productTransaction.product.product_category',
                'productTransaction.modifiers',
                'productTransaction.variants' => function($query){
                    $query->select('id_transaction_product','transaction_product_variants.id_product_variant','transaction_product_variants.id_product_variant','product_variants.product_variant_name', 'transaction_product_variant_price')->join('product_variants','product_variants.id_product_variant','=','transaction_product_variants.id_product_variant');
                },
                'productTransaction.product.product_photos',
                'productTransaction.product.product_discounts',
                'plasticTransaction.product',
                'transaction_payment_offlines',
                'transaction_vouchers.deals_voucher.deal',
                'promo_campaign_promo_code.promo_campaign',
                'transaction_pickup_go_send.transaction_pickup_update',
                'transaction_payment_subscription.subscription_user_voucher',
                'subscription_user_voucher',
                'outlet.city'])->first();
            if(!$list){
                return MyHelper::checkGet([],'empty');
            }
            $list = $list->toArray();
            $label = [];
            $label2 = [];
            $product_count=0;
            //get item bundling
            $listItemBundling = [];
            $quantityItemBundling = 0;
            $getBundling   = TransactionBundlingProduct::join('bundling', 'bundling.id_bundling', 'transaction_bundling_products.id_bundling')
                ->where('id_transaction', $id)->get()->toArray();
            foreach ($getBundling as $key=>$bundling){
                $listItemBundling[$key] = [
                    'bundling_name' => $bundling['bundling_name'],
                    'bundling_qty' => $bundling['transaction_bundling_product_qty']
                ];

                $bundlingProduct = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                    ->where('id_transaction_bundling_product', $bundling['id_transaction_bundling_product'])->get()->toArray();
                $basePriceBundling = 0;
                $subTotalBundlingWithoutModifier = 0;
                $subItemBundlingWithoutModifie = 0;
                foreach ($bundlingProduct as $bp){
                    $mod = TransactionProductModifier::join('product_modifiers', 'product_modifiers.id_product_modifier', 'transaction_product_modifiers.id_product_modifier')
                        ->whereNull('transaction_product_modifiers.id_product_modifier_group')
                        ->where('id_transaction_product', $bp['id_transaction_product'])
                        ->select('transaction_product_modifiers.id_product_modifier', 'transaction_product_modifiers.text as text', DB::raw('FLOOR(transaction_product_modifier_price * '.$bp['transaction_product_bundling_qty'].' * '.$bundling['transaction_bundling_product_qty'].') as product_modifier_price'))->get()->toArray();
                    $variantPrice = TransactionProductVariant::join('product_variants', 'product_variants.id_product_variant', 'transaction_product_variants.id_product_variant')
                        ->where('id_transaction_product', $bp['id_transaction_product'])
                        ->select('product_variants.id_product_variant', 'product_variants.product_variant_name',  DB::raw('FLOOR(transaction_product_variant_price) as product_variant_price'))->get()->toArray();
                    $variantNoPrice =  TransactionProductModifier::join('product_modifiers', 'product_modifiers.id_product_modifier', 'transaction_product_modifiers.id_product_modifier')
                        ->whereNotNull('transaction_product_modifiers.id_product_modifier_group')
                        ->where('id_transaction_product', $bp['id_transaction_product'])
                        ->select('transaction_product_modifiers.id_product_modifier as id_product_variant', 'transaction_product_modifiers.text as product_variant_name', 'transaction_product_modifier_price as product_variant_price')->get()->toArray();
                    $variants = array_merge($variantPrice, $variantNoPrice);

                    $listItemBundling[$key]['products'][] = [
                        'product_qty' => $bp['transaction_product_bundling_qty'],
                        'product_name' => $bp['product_name'],
                        'note' => $bp['transaction_product_note'],
                        'variants' => $variants,
                        'modifiers' => $mod
                    ];
                    $productBasePrice = $bp['transaction_product_price'] + $bp['transaction_variant_subtotal'];
                    $basePriceBundling = $basePriceBundling + ($productBasePrice * $bp['transaction_product_bundling_qty']);
                    $subTotalBundlingWithoutModifier = $subTotalBundlingWithoutModifier + (($bp['transaction_product_subtotal'] - ($bp['transaction_modifier_subtotal'] * $bp['transaction_product_bundling_qty'])));
                    $subItemBundlingWithoutModifie = $subItemBundlingWithoutModifie + ($bp['transaction_product_bundling_price'] * $bp['transaction_product_bundling_qty']);
                }
                $listItemBundling[$key]['bundling_price_no_discount'] = $basePriceBundling * $bundling['transaction_bundling_product_qty'];
                $listItemBundling[$key]['bundling_subtotal'] = $subTotalBundlingWithoutModifier * $bundling['transaction_bundling_product_qty'];
                $listItemBundling[$key]['bundling_sub_item'] = '@'.MyHelper::requestNumber($subItemBundlingWithoutModifie,'_CURRENCY');

                $quantityItemBundling = $quantityItemBundling + ($bp['transaction_product_bundling_qty'] * $bundling['transaction_bundling_product_qty']);
            }

            $list['product_transaction'] = MyHelper::groupIt($list['product_transaction'],'id_brand',null,function($key,&$val) use (&$product_count){
                $product_count += array_sum(array_column($val,'transaction_product_qty'));
                $brand = Brand::select('name_brand')->find($key);
                if(!$brand){
                    return 'No Brand';
                }
                return $brand->name_brand;
            });
            $cart = $list['transaction_subtotal'] + $list['transaction_shipment'] + $list['transaction_service'] + $list['transaction_tax'] - $list['transaction_discount'];

            $list['transaction_carttotal'] = $cart;
            $list['transaction_item_total'] = $product_count;

            $order = Setting::where('key', 'transaction_grand_total_order')->value('value');
            $exp   = explode(',', $order);
            $exp2   = explode(',', $order);

            foreach ($exp as $i => $value) {
                if ($exp[$i] == 'subtotal') {
                    unset($exp[$i]);
                    unset($exp2[$i]);
                    continue;
                }

                if ($exp[$i] == 'tax') {
                    $exp[$i] = 'transaction_tax';
                    $exp2[$i] = 'transaction_tax';
                    array_push($label, 'Tax');
                    array_push($label2, 'Tax');
                }

                if ($exp[$i] == 'service') {
                    $exp[$i] = 'transaction_service';
                    $exp2[$i] = 'transaction_service';
                    array_push($label, 'Service Fee');
                    array_push($label2, 'Service Fee');
                }

                if ($exp[$i] == 'shipping') {
                    if ($list['trasaction_type'] == 'Pickup Order') {
                        unset($exp[$i]);
                        unset($exp2[$i]);
                        continue;
                    } else {
                        $exp[$i] = 'transaction_shipment';
                        $exp2[$i] = 'transaction_shipment';
                        array_push($label, 'Delivery Cost');
                        array_push($label2, 'Delivery Cost');
                    }
                }

                if ($exp[$i] == 'discount') {
                    $exp2[$i] = 'transaction_discount';
                    array_push($label2, 'Discount');
                    unset($exp[$i]);
                    continue;
                }

                if (stristr($exp[$i], 'empty')) {
                    unset($exp[$i]);
                    unset($exp2[$i]);
                    continue;
                }
            }

            switch ($list['trasaction_payment_type']) {
                case 'Balance':
                    $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get()->toArray();
                    if ($multiPayment) {
                        foreach ($multiPayment as $keyMP => $mp) {
                            switch ($mp['type']) {
                                case 'Balance':
                                    $log = LogBalance::where('id_reference', $mp['id_transaction'])->where('source', 'Online Transaction')->first();
                                    if ($log['balance'] < 0) {
                                        $list['balance'] = $log['balance'];
                                        $list['check'] = 'tidak topup';
                                    } else {
                                        $list['balance'] = $list['transaction_grandtotal'] - $log['balance'];
                                        $list['check'] = 'topup';
                                    }
                                    $list['payment'][] = [
                                        'name'      => 'Balance',
                                        'amount'    => $list['balance']
                                    ];
                                    break;
                                case 'Manual':
                                    $payment = TransactionPaymentManual::with('manual_payment_method.manual_payment')->where('id_transaction', $list['id_transaction'])->first();
                                    $list['payment'] = $payment;
                                    $list['payment'][] = [
                                        'name'      => 'Cash',
                                        'amount'    => $payment['payment_nominal']
                                    ];
                                    break;
                                case 'Midtrans':
                                    $payMidtrans = TransactionPaymentMidtran::find($mp['id_payment']);
                                    $payment['name']      = strtoupper(str_replace('_', ' ', $payMidtrans->payment_type)).' '.strtoupper($payMidtrans->bank);
                                    $payment['amount']    = $payMidtrans->gross_amount;
                                    $list['payment'][] = $payment;
                                    break;
                                case 'Ovo':
                                    $payment = TransactionPaymentOvo::find($mp['id_payment']);
                                    $payment['name']    = 'OVO';
                                    $list['payment'][] = $payment;
                                    break;
                                case 'IPay88':
                                    $PayIpay = TransactionPaymentIpay88::find($mp['id_payment']);
                                    $payment['name']    = $PayIpay->payment_method;
                                    $payment['amount']    = $PayIpay->amount / 100;
                                    $list['payment'][] = $payment;
                                    break;
                                case 'Shopeepay':
                                    $shopeePay = TransactionPaymentShopeePay::find($mp['id_payment']);
                                    $payment['name']    = 'ShopeePay';
                                    $payment['amount']  = $shopeePay->amount / 100;
                                    $payment['reject']  = $shopeePay->err_reason?:'payment expired';
                                    $list['payment'][]  = $payment;
                                    break;
                                case 'Offline':
                                    $payment = TransactionPaymentOffline::where('id_transaction', $list['id_transaction'])->get();
                                    foreach ($payment as $key => $value) {
                                        $list['payment'][$key] = [
                                            'name'      => $value['payment_bank'],
                                            'amount'    => $value['payment_amount']
                                        ];
                                    }
                                    break;
                                default:
                                    break;
                            }
                        }
                    } else {
                        $log = LogBalance::where('id_reference', $list['id_transaction'])->first();
                        if ($log['balance'] < 0) {
                            $list['balance'] = $log['balance'];
                            $list['check'] = 'tidak topup';
                        } else {
                            $list['balance'] = $list['transaction_grandtotal'] - $log['balance'];
                            $list['check'] = 'topup';
                        }
                        $list['payment'][] = [
                            'name'      => 'Balance',
                            'amount'    => $list['balance']
                        ];
                    }
                    break;
                case 'Manual':
                    $payment = TransactionPaymentManual::with('manual_payment_method.manual_payment')->where('id_transaction', $list['id_transaction'])->first();
                    $list['payment'] = $payment;
                    $list['payment'][] = [
                        'name'      => 'Cash',
                        'amount'    => $payment['payment_nominal']
                    ];
                    break;
                case 'Midtrans':
                    $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get();
                    $payment = [];
                    foreach($multiPayment as $dataKey => $dataPay){
                        if($dataPay['type'] == 'Midtrans'){
                            $payMidtrans = TransactionPaymentMidtran::find($dataPay['id_payment']);
                            $payment[$dataKey]['name']      = strtoupper(str_replace('_', ' ', $payMidtrans->payment_type)).' '.strtoupper($payMidtrans->bank);
                            $payment[$dataKey]['amount']    = $payMidtrans->gross_amount;
                        }else{
                            $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                            $payment[$dataKey] = $dataPay;
                            $list['balance'] = $dataPay['balance_nominal'];
                            $payment[$dataKey]['name']          = 'Balance';
                            $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                        }
                    }
                    $list['payment'] = $payment;
                    break;
                case 'Ovo':
                    $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get();
                    $payment = [];
                    foreach($multiPayment as $dataKey => $dataPay){
                        if($dataPay['type'] == 'Ovo'){
                            $payment[$dataKey] = TransactionPaymentOvo::find($dataPay['id_payment']);
                            $payment[$dataKey]['name']    = 'OVO';
                        }else{
                            $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                            $payment[$dataKey] = $dataPay;
                            $list['balance'] = $dataPay['balance_nominal'];
                            $payment[$dataKey]['name']          = 'Balance';
                            $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                        }
                    }
                    $list['payment'] = $payment;
                    break;
                case 'Ipay88':
                    $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get();
                    $payment = [];
                    foreach($multiPayment as $dataKey => $dataPay){
                        if($dataPay['type'] == 'IPay88'){
                            $PayIpay = TransactionPaymentIpay88::find($dataPay['id_payment']);
                            $payment[$dataKey]['name']    = $PayIpay->payment_method;
                            $payment[$dataKey]['amount']    = $PayIpay->amount / 100;
                        }else{
                            $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                            $payment[$dataKey] = $dataPay;
                            $list['balance'] = $dataPay['balance_nominal'];
                            $payment[$dataKey]['name']          = 'Balance';
                            $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                        }
                    }
                    $list['payment'] = $payment;
                    break;
                case 'Shopeepay':
                    $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get();
                    $payment = [];
                    foreach($multiPayment as $dataKey => $dataPay){
                        if($dataPay['type'] == 'Shopeepay'){
                            $payShopee = TransactionPaymentShopeePay::find($dataPay['id_payment']);
                            $payment[$dataKey]['name']      = 'ShopeePay';
                            $payment[$dataKey]['amount']    = $payShopee->amount / 100;
                            $payment[$dataKey]['reject']    = $payShopee->err_reason?:'payment expired';
                        }else{
                            $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                            $payment[$dataKey]              = $dataPay;
                            $list['balance']                = $dataPay['balance_nominal'];
                            $payment[$dataKey]['name']      = 'Balance';
                            $payment[$dataKey]['amount']    = $dataPay['balance_nominal'];
                        }
                    }
                    $list['payment'] = $payment;
                    break;
                case 'Offline':
                    $payment = TransactionPaymentOffline::where('id_transaction', $list['id_transaction'])->get();
                    foreach ($payment as $key => $value) {
                        $list['payment'][$key] = [
                            'name'      => $value['payment_bank'],
                            'amount'    => $value['payment_amount']
                        ];
                    }
                    break;
                default:
                    break;
            }

            array_splice($exp, 0, 0, 'transaction_subtotal');
            array_splice($label, 0, 0, 'Cart Total');

            array_splice($exp2, 0, 0, 'transaction_subtotal');
            array_splice($label2, 0, 0, 'Cart Total');

            array_values($exp);
            array_values($label);

            array_values($exp2);
            array_values($label2);

            $imp = implode(',', $exp);
            $order_label = implode(',', $label);

            $imp2 = implode(',', $exp2);
            $order_label2 = implode(',', $label2);

            $detail = [];

            if ($list['trasaction_type'] == 'Pickup Order') {
                $detail = TransactionPickup::where('id_transaction', $list['id_transaction'])->first()->toArray();
                if($detail){
                    $qr      = $detail['order_id'];

                    $qrCode = 'https://chart.googleapis.com/chart?chl='.$qr.'&chs=250x250&cht=qr&chld=H%7C0';
                    $qrCode =   html_entity_decode($qrCode);

                    $newDetail = [];
                    foreach($detail as $key => $value){
                        $newDetail[$key] = $value;
                        if($key == 'order_id'){
                            $newDetail['order_id_qrcode'] = $qrCode;
                        }
                    }

                    $detail = $newDetail;
                }
            } elseif ($list['trasaction_type'] == 'Delivery') {
                $detail = TransactionShipment::with('city.province')->where('id_transaction', $list['id_transaction'])->first();
            }

            $list['detail'] = $detail;
            $list['order'] = $imp;
            $list['order_label'] = $order_label;

            $list['order_v2'] = $imp2;
            $list['order_label_v2'] = $order_label2;

            $list['date'] = $list['transaction_date'];
            $list['type'] = 'trx';

            if(isset($list['pickup_by']) && $list['pickup_by'] == 'GO-SEND'){
                $list['trasaction_type'] = 'Delivery';
            }

            $result = [
                'id_transaction'                => $list['id_transaction'],
                'transaction_receipt_number'    => $list['transaction_receipt_number'],
                'transaction_date'              => date('d M Y H:i', strtotime($list['transaction_date'])),
                'trasaction_type'               => $list['trasaction_type'],
                'transaction_grandtotal'        => MyHelper::requestNumber($list['transaction_grandtotal'],'_CURRENCY'),
                'transaction_subtotal'          => MyHelper::requestNumber($list['transaction_subtotal'],'_CURRENCY'),
                'transaction_discount'          => MyHelper::requestNumber($list['transaction_discount'],'_CURRENCY'),
                'transaction_cashback_earned'   => MyHelper::requestNumber($list['transaction_cashback_earned'],'_POINT'),
                'trasaction_payment_type'       => $list['trasaction_payment_type'],
                'transaction_payment_status'    => $list['transaction_payment_status'],
                'outlet'                        => [
                    'outlet_name'       => $list['outlet']['outlet_name'],
                    'outlet_address'    => $list['outlet']['outlet_address']
                ]
            ];

            if($request->json('admin')){
                $lastLog = LogInvalidTransaction::where('id_transaction', $list['id_transaction'])->orderBy('updated_at', 'desc')->first();

                if(!empty($list['image_invalid_flag'])){
                    $result['image_invalid_flag'] =  config('url.storage_url_api').$list['image_invalid_flag'];
                }else{
                    $result['image_invalid_flag'] = NULL;
                }

                $result['transaction_flag_invalid'] =  $list['transaction_flag_invalid'];
                $result['flag_reason'] =  $lastLog['reason']??'';
            }

            if(isset($list['user']['phone'])){
                $result['user']['phone'] = $list['user']['phone'];
                $result['user']['name'] = $list['user']['name'];
                $result['user']['email'] = $list['user']['email'];
            }

            if ($list['trasaction_payment_type'] != 'Offline') {
                $result['detail'] = [
                    'order_id_qrcode'   => $list['detail']['order_id_qrcode'],
                    'order_id'          => $list['detail']['order_id'],
                    'pickup_type'       => $list['detail']['pickup_type'],
                    'pickup_date'       => date('d F Y', strtotime($list['detail']['pickup_at'])),
                    'pickup_time'       => ($list['detail']['pickup_type'] == 'right now') ? 'RIGHT NOW' : date('H : i', strtotime($list['detail']['pickup_at'])),
                ];
                if (isset($list['transaction_payment_status']) && $list['transaction_payment_status'] == 'Cancelled') {
                    unset($result['detail']['order_id_qrcode']);
                    unset($result['detail']['order_id']);
                    unset($result['detail']['pickup_time']);
                    $result['transaction_status'] = 0;
                    $result['transaction_status_text'] = 'PESANAN TELAH DIBATALKAN';
                } elseif (isset($list['transaction_payment_status']) && $list['transaction_payment_status'] == 'Pending') {
                    unset($result['detail']['order_id_qrcode']);
                    unset($result['detail']['order_id']);
                    unset($result['detail']['pickup_time']);
                    $result['transaction_status'] = 6;
                    $result['transaction_status_text'] = 'MENUNGGU PEMBAYARAN';
                } elseif($list['detail']['reject_at'] != null) {
                    unset($result['detail']['order_id_qrcode']);
                    unset($result['detail']['order_id']);
                    unset($result['detail']['pickup_time']);
                    $result['transaction_status'] = 0;
                    $result['transaction_status_text'] = 'PESANAN DITOLAK';
                } elseif($list['detail']['taken_by_system_at'] != null) {
                    $result['transaction_status'] = 1;
                    $result['transaction_status_text'] = 'ORDER SELESAI';
                } elseif($list['detail']['taken_at'] != null && $list['trasaction_type'] != 'Delivery') {
                    $result['transaction_status'] = 2;
                    $result['transaction_status_text'] = 'PESANAN TELAH DIAMBIL';
                } elseif($list['detail']['ready_at'] != null && $list['trasaction_type'] != 'Delivery') {
                    $result['transaction_status'] = 3;
                    $result['transaction_status_text'] = 'PESANAN SUDAH SIAP DIAMBIL';
                } elseif($list['detail']['receive_at'] != null) {
                    $result['transaction_status'] = 4;
                    $result['transaction_status_text'] = 'PESANAN DITERIMA. ORDER SEDANG DIPERSIAPKAN';
                } else {
                    $result['transaction_status'] = 5;
                    $result['transaction_status_text'] = 'PESANAN MASUK. MENUNGGU JILID UNTUK MENERIMA ORDER';
                }
                if ($list['detail']['ready_at'] != null && $list['transaction_pickup_go_send'] && !$list['detail']['reject_at']) {
                    // $result['transaction_status'] = 5;
                    $result['delivery_info'] = [
                        'driver' => null,
                        'delivery_status' => '',
                        'delivery_address' => $list['transaction_pickup_go_send']['destination_address']?:'',
                        'delivery_address_note' => $list['transaction_pickup_go_send']['destination_note'] ?: '',
                        'booking_status' => 0,
                        'cancelable' => 1,
                        'go_send_order_no' => $list['transaction_pickup_go_send']['go_send_order_no']?:'',
                        'live_tracking_url' => $list['transaction_pickup_go_send']['live_tracking_url']?:''
                    ];
                    if($list['transaction_pickup_go_send']['go_send_id']){
                        $result['delivery_info']['booking_status'] = 1;
                    }
                    switch (strtolower($list['transaction_pickup_go_send']['latest_status'])) {
                        case 'finding driver':
                        case 'confirmed':
                            $result['delivery_info']['delivery_status'] = 'Sedang mencari driver';
                            $result['transaction_status_text']          = 'PESANAN SUDAH SIAP DAN MENUNGGU PICK UP';
                            break;
                        case 'driver allocated':
                        case 'allocated':
                            $result['delivery_info']['delivery_status'] = 'Driver ditemukan';
                            $result['transaction_status_text']          = 'DRIVER DITEMUKAN DAN SEDANG MENUJU OUTLET';
                            $result['delivery_info']['driver']          = [
                                'driver_id'         => $list['transaction_pickup_go_send']['driver_id']?:'',
                                'driver_name'       => $list['transaction_pickup_go_send']['driver_name']?:'',
                                'driver_phone'      => $list['transaction_pickup_go_send']['driver_phone']?:'',
                                'driver_whatsapp'   => env('URL_WA') . $list['transaction_pickup_go_send']['driver_phone']?:'',
                                'driver_photo'      => $list['transaction_pickup_go_send']['driver_photo']?:'',
                                'vehicle_number'    => $list['transaction_pickup_go_send']['vehicle_number']?:'',
                            ];
                            break;
                        case 'enroute pickup':
                        case 'out_for_pickup':
                            $result['delivery_info']['delivery_status'] = 'Driver dalam perjalanan menuju Outlet';
                            $result['transaction_status_text']          = 'DRIVER SEDANG MENUJU OUTLET';
                            $result['delivery_info']['driver']          = [
                                'driver_id'         => $list['transaction_pickup_go_send']['driver_id']?:'',
                                'driver_name'       => $list['transaction_pickup_go_send']['driver_name']?:'',
                                'driver_phone'      => $list['transaction_pickup_go_send']['driver_phone']?:'',
                                'driver_whatsapp'   => env('URL_WA') . $list['transaction_pickup_go_send']['driver_phone']?:'',
                                'driver_photo'      => $list['transaction_pickup_go_send']['driver_photo']?:'',
                                'vehicle_number'    => $list['transaction_pickup_go_send']['vehicle_number']?:'',
                            ];
                            $result['delivery_info']['cancelable'] = 1;
                            break;
                        case 'enroute drop':
                        case 'out_for_delivery':
                            $result['delivery_info']['delivery_status'] = 'Driver mengantarkan pesanan';
                            $result['transaction_status_text']          = 'PESANAN SUDAH DI PICK UP OLEH DRIVER DAN SEDANG MENUJU LOKASI #TEMANSEJIWA';
                            $result['transaction_status']               = 3;
                            $result['delivery_info']['driver']          = [
                                'driver_id'         => $list['transaction_pickup_go_send']['driver_id']?:'',
                                'driver_name'       => $list['transaction_pickup_go_send']['driver_name']?:'',
                                'driver_phone'      => $list['transaction_pickup_go_send']['driver_phone']?:'',
                                'driver_whatsapp'   => env('URL_WA') . $list['transaction_pickup_go_send']['driver_phone']?:'',
                                'driver_photo'      => $list['transaction_pickup_go_send']['driver_photo']?:'',
                                'vehicle_number'    => $list['transaction_pickup_go_send']['vehicle_number']?:'',
                            ];
                            $result['delivery_info']['cancelable'] = 0;
                            break;
                        case 'completed':
                        case 'delivered':
                            $result['transaction_status'] = 2;
                            $result['transaction_status_text']          = 'PESANAN TELAH SELESAI DAN DITERIMA';
                            $result['delivery_info']['delivery_status'] = 'Pesanan sudah diterima Customer';
                            $result['delivery_info']['driver']          = [
                                'driver_id'         => $list['transaction_pickup_go_send']['driver_id']?:'',
                                'driver_name'       => $list['transaction_pickup_go_send']['driver_name']?:'',
                                'driver_phone'      => $list['transaction_pickup_go_send']['driver_phone']?:'',
                                'driver_whatsapp'   => env('URL_WA') . $list['transaction_pickup_go_send']['driver_phone']?:'',
                                'driver_photo'      => $list['transaction_pickup_go_send']['driver_photo']?:'',
                                'vehicle_number'    => $list['transaction_pickup_go_send']['vehicle_number']?:'',
                            ];
                            $result['delivery_info']['cancelable'] = 0;
                            break;
                        case 'cancelled':
                            $result['delivery_info']['booking_status'] = 0;
                            $result['transaction_status_text']         = 'PENGANTARAN PESANAN TELAH DIBATALKAN';
                            $result['delivery_info']['delivery_status'] = 'Pengantaran dibatalkan';
                            $result['delivery_info']['cancelable']     = 0;
                            break;
                        case 'driver not found':
                        case 'no_driver':
                            $result['delivery_info']['booking_status']  = 0;
                            $result['transaction_status_text']          = 'DRIVER TIDAK DITEMUKAN';
                            $result['delivery_info']['delivery_status'] = 'Driver tidak ditemukan';
                            $result['delivery_info']['cancelable']      = 0;
                            break;
                    }
                }
            }

            $nameBrandBundling = Setting::where('key', 'brand_bundling_name')->first();
            $result['name_brand_bundling'] = $nameBrandBundling['value']??'Bundling';
            $result['product_bundling_transaction'] = $listItemBundling;
            $result['product_transaction'] = [];
            $discount = 0;
            $quantity = 0;
            $keynya = 0;
            foreach ($list['product_transaction'] as $keyTrx => $valueTrx) {
                $result['product_transaction'][$keynya]['brand'] = $keyTrx;
                foreach ($valueTrx as $keyProduct => $valueProduct) {
                    $quantity = $quantity + $valueProduct['transaction_product_qty'];
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_qty']              = $valueProduct['transaction_product_qty'];
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_subtotal']         = MyHelper::requestNumber($valueProduct['transaction_product_subtotal'],'_CURRENCY');
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_sub_item']         = '@'.MyHelper::requestNumber($valueProduct['transaction_product_subtotal'] / $valueProduct['transaction_product_qty'],'_CURRENCY');
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_modifier_subtotal']        = MyHelper::requestNumber($valueProduct['transaction_modifier_subtotal'],'_CURRENCY');
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_variant_subtotal']         = MyHelper::requestNumber($valueProduct['transaction_variant_subtotal'],'_CURRENCY');
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_note']             = $valueProduct['transaction_product_note'];
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_discount']         = $valueProduct['transaction_product_discount'];
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_name']              = $valueProduct['product']['product_name'];
                    $discount = $discount + $valueProduct['transaction_product_discount'];
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'] = [];
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'] = [];
                    $extra_modifiers = [];
                    foreach ($valueProduct['modifiers'] as $keyMod => $valueMod) {
                        if (!$valueMod['id_product_modifier_group']) {
                            $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'][$keyMod]['product_modifier_name']   = $valueMod['text'];
                            $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'][$keyMod]['product_modifier_qty']    = $valueMod['qty'];
                            $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'][$keyMod]['product_modifier_price']  = MyHelper::requestNumber($valueMod['transaction_product_modifier_price']*$valueProduct['transaction_product_qty'],'_CURRENCY');
                        } else {
                            $extra_modifiers[] = $valueMod['id_product_modifier'];
                            $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants']['m'.$keyMod]['id_product_variant']   = $valueMod['id_product_modifier'];
                            $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants']['m'.$keyMod]['product_variant_name']   = $valueMod['text'];
                            $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants']['m'.$keyMod]['product_variant_price']  = (int)$valueMod['transaction_product_modifier_price'];
                        }
                    }
                    $variantsPrice = 0;
                    foreach ($valueProduct['variants'] as $keyMod => $valueMod) {
                        $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'][$keyMod]['id_product_variant']   = $valueMod['id_product_variant'];
                        $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'][$keyMod]['product_variant_name']   = $valueMod['product_variant_name'];
                        $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'][$keyMod]['product_variant_price']  = (int)$valueMod['transaction_product_variant_price'];
                        $variantsPrice = $variantsPrice + $valueMod['transaction_product_variant_price'];
                    }
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'] = array_values($result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants']);
                    if ($valueProduct['id_product_variant_group'] ?? false) {
                        $order = array_flip(Product::getVariantParentId($valueProduct['id_product_variant_group'], Product::getVariantTree($valueProduct['id_product'], $list['outlet'])['variants_tree'], $extra_modifiers));
                        usort($result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'], function ($a, $b) use ($order) {
                            return ($order[$a['id_product_variant']]??999) <=> ($order[$b['id_product_variant']]??999);
                        });
                    }
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product_variant_group_price'] = (int)($valueProduct['transaction_product_price'] + $variantsPrice);
                }
                $keynya++;
            }

            if(isset($list['plastic_transaction'])){
                $subtotal_plastic = 0;
                foreach($list['plastic_transaction'] as $key => $value){
                    $subtotal_plastic += $value['transaction_product_subtotal'];
                }

                $result['plastic_transaction'] = [];
                $result['plastic_transaction']['transaction_plastic_total'] = $subtotal_plastic;
            }

            $result['payment_detail'][] = [
                'name'      => 'Subtotal',
                'desc'      => $quantity+$quantityItemBundling . ' items',
                'amount'    => MyHelper::requestNumber($list['transaction_subtotal'],'_CURRENCY')
            ];

            if ($list['transaction_discount']) {
            	$discount = abs($list['transaction_discount']);
	            $p = 0;
	            if (!empty($list['transaction_vouchers'])) {
	                foreach ($list['transaction_vouchers'] as $valueVoc) {
	                    $result['promo']['code'][$p++]   = $valueVoc['deals_voucher']['voucher_code'];
	                    $result['payment_detail'][] = [
	                        'name'          => 'Diskon',
	                        'desc'          => 'Promo',
	                        "is_discount"   => 1,
	                        'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
	                    ];
	                }
	            }

	            if (!empty($list['promo_campaign_promo_code'])) {
	                $result['promo']['code'][$p++]   = $list['promo_campaign_promo_code']['promo_code'];
	                $result['payment_detail'][] = [
	                    'name'          => 'Diskon',
	                    'desc'          => 'Promo',
	                    "is_discount"   => 1,
	                    'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
	                ];
	            }

	            if (!empty($list['id_subscription_user_voucher']) && !empty($list['transaction_discount'])) {
	                $result['payment_detail'][] = [
	                    'name'          => 'Subscription',
	                    'desc'          => 'Diskon',
	                    "is_discount"   => 1,
	                    'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
	                ];
	            }
            }

            if ($list['transaction_shipment_go_send'] > 0) {
                $result['payment_detail'][] = [
                    'name'      => 'Delivery',
                    'desc'      => $list['detail']['pickup_by'],
                    'amount'    => MyHelper::requestNumber($list['transaction_shipment_go_send'],'_CURRENCY')
                ];
            }

            if ($list['transaction_discount_delivery']) {
            	$discount = abs($list['transaction_discount_delivery']);
	            $p = 0;
	            if (!empty($list['transaction_vouchers'])) {
	                foreach ($list['transaction_vouchers'] as $valueVoc) {
	                    $result['promo']['code'][$p++]   = $valueVoc['deals_voucher']['voucher_code'];
	                    $result['payment_detail'][] = [
	                        'name'          => 'Diskon',
	                        'desc'          => 'Delivery',
	                        "is_discount"   => 1,
	                        'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
	                    ];
	                }
	            }

	            if (!empty($list['promo_campaign_promo_code'])) {
	                $result['promo']['code'][$p++]   = $list['promo_campaign_promo_code']['promo_code'];
	                $result['payment_detail'][] = [
	                    'name'          => 'Diskon',
	                    'desc'          => 'Delivery',
	                    "is_discount"   => 1,
	                    'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
	                ];
	            }

	            if (!empty($list['id_subscription_user_voucher']) && !empty($list['transaction_discount_delivery'])) {
	                $result['payment_detail'][] = [
	                    'name'          => 'Subscription',
	                    'desc'          => 'Delivery',
	                    "is_discount"   => 1,
	                    'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
	                ];
	            }
            }



            $result['promo']['discount'] = $discount;
            $result['promo']['discount'] = MyHelper::requestNumber($discount,'_CURRENCY');

            if ($list['trasaction_payment_type'] != 'Offline') {
                if ($list['transaction_payment_status'] == 'Cancelled') {
                    $statusOrder[] = [
                        'text'  => 'Pesanan telah dibatalkan karena pembayaran gagal',
                        'date'  => $list['void_date']??$list['transaction_date']
                    ];
                }
                elseif ($list['transaction_payment_status'] == 'Pending') {
                    $statusOrder[] = [
                        'text'  => 'Menunggu konfirmasi pembayaran',
                        'date'  => $list['transaction_date']
                    ];
                } else {
                    if ($list['detail']['reject_at'] != null) {
                        $statusOrder[] = [
                            'text'  => 'Order rejected',
                            'date'  => $list['detail']['reject_at'],
                            'reason'=> $list['detail']['reject_reason']
                        ];
                    }
                    if ($list['detail']['taken_by_system_at'] != null) {
                        $statusOrder[] = [
                            'text'  => 'Pesanan Anda sudah selesai',
                            'date'  => $list['detail']['taken_by_system_at']
                        ];
                    }
                    if ($list['detail']['taken_at'] != null && empty($list['transaction_shipment_go_send'])) {
                        $statusOrder[] = [
                            'text'  => 'Pesanan telah diambil',
                            'date'  => $list['detail']['taken_at']
                        ];
                    }
                    if ($list['detail']['ready_at'] != null && empty($list['transaction_shipment_go_send'])) {
                        $statusOrder[] = [
                            'text'  => 'Pesanan sudah siap diambil',
                            'date'  => $list['detail']['ready_at']
                        ];
                    }
                    if ($list['transaction_pickup_go_send']) {
                        $flagStatus = [
                            'confirmed' => 0,
                            'no_driver' => 0,
                        ];
                        foreach ($list['transaction_pickup_go_send']['transaction_pickup_update'] as $valueGosend) {
                            switch (strtolower($valueGosend['status'])) {
                                case 'finding driver':
                                case 'confirmed':
                                    if ($flagStatus['confirmed']) {
                                        break;
                                    }
                                    $flagStatus['confirmed'] = 1;
                                    if($list['detail']['ready_at'] != null){
                                        $statusOrder[] = [
                                            'text'  => 'Pesanan sudah siap dan menunggu pick up',
                                            'date'  => $valueGosend['created_at']
                                        ];
                                    }
                                    break;
                                // case 'driver allocated':
                                // case 'allocated':
                                //     $statusOrder[] = [
                                //         'text'  => 'Driver ditemukan',
                                //         'date'  => $valueGosend['created_at']
                                //     ];
                                //     break;
                                // case 'enroute pickup':
                                case 'out_for_pickup':
                                    $statusOrder[] = [
                                        'text'  => 'Driver dalam perjalanan menuju Outlet',
                                        'date'  => $valueGosend['created_at']
                                    ];
                                    break;
                                case 'enroute drop':
                                case 'out_for_delivery':
                                    $statusOrder[] = [
                                        'text'  => 'Pesanan sudah di pick up oleh driver dan sedang menuju lokasi #temansejiwa',
                                        'date'  => $valueGosend['created_at']
                                    ];
                                    break;
                                case 'completed':
                                case 'delivered':
                                    $statusOrder[] = [
                                        'text'  => 'Pesanan telah selesai dan diterima',
                                        'date'  => $valueGosend['created_at']
                                    ];
                                    break;
                                case 'cancelled':
                                    $statusOrder[] = [
                                        'text'  => 'Pengantaran pesanan telah dibatalkan',
                                        'date'  => $valueGosend['created_at']
                                    ];
                                    break;
                                case 'driver not found':
                                case 'no_driver':
                                    if ($flagStatus['no_driver']) {
                                        break;
                                    }
                                    $flagStatus['no_driver'] = 1;
                                    $statusOrder[] = [
                                        'text'  => 'Driver tidak ditemukan',
                                        'date'  => $valueGosend['created_at']
                                    ];
                                    break;
                            }
                        }
                    }
                    if ($list['detail']['receive_at'] != null) {
                        $statusOrder[] = [
                            'text'  => 'Pesanan diterima. Order sedang dipersiapkan',
                            'date'  => $list['detail']['receive_at']
                        ];
                    }
                    $statusOrder[] = [
                        'text'  => 'Pesanan masuk. Menunggu jilid untuk menerima order',
                        'date'  => $list['transaction_date']
                    ];
                }

                usort($statusOrder, function($a1, $a2) {
                    $v1 = strtotime($a1['date']);
                    $v2 = strtotime($a2['date']);
                    return $v2 - $v1; // $v2 - $v1 to reverse direction
                });

                foreach ($statusOrder as $keyStatus => $status) {
                    $result['detail']['detail_status'][$keyStatus] = [
                        'text'  => $status['text'],
                        'date'  => MyHelper::dateFormatInd($status['date'])
                    ];
                    if ($status['text'] == 'Order rejected') {
                        if (strpos($list['detail']['reject_reason'], 'auto reject order by system [no driver]') !== false) {
                            $result['detail']['detail_status'][$keyStatus]['text'] = 'Maaf Pesanan Telah Ditolak karena driver tidak ditemukan, Mohon untuk Melakukan Pemesanannya Kembali';
                        } elseif (strpos($list['detail']['reject_reason'], 'auto reject order by system') !== false) {
                            $result['detail']['detail_status'][$keyStatus]['text'] = 'Maaf Pesanan Telah Ditolak, Mohon untuk Melakukan Pemesanannya Kembali';
                        } else {
                            $result['detail']['detail_status'][$keyStatus]['text'] = 'Pesanan telah ditolak karena '.strtolower($list['detail']['reject_reason']);
                        }

                        $result['detail']['detail_status'][$keyStatus]['reason'] = $list['detail']['reject_reason'];
                    }
                }
            }
            if(!isset($list['payment'])){
                $result['transaction_payment'] = null;
            }else{
                foreach ($list['payment'] as $key => $value) {
                    if ($value['name'] == 'Balance') {
                        $result['transaction_payment'][$key] = [
                            'name'      => (env('POINT_NAME')) ? env('POINT_NAME') : $value['name'],
                            'is_balance'=> 1,
                            'amount'    => MyHelper::requestNumber($value['amount'],'_POINT')
                        ];
                    } else {
                        $result['transaction_payment'][$key] = [
                            'name'      => $value['name'],
                            'amount'    => MyHelper::requestNumber($value['amount'],'_CURRENCY')
                        ];
                    }
                }
            }

            if (!empty($list['transaction_payment_subscription'])) {
            	$payment_subscription = abs($list['transaction_payment_subscription']['subscription_nominal']);
                $result['transaction_payment'][] = [
                    'name'      => 'Subscription',
	                'amount'    => MyHelper::requestNumber($payment_subscription,'_CURRENCY')
                ];
            }

            return response()->json(MyHelper::checkGet($result));
        } else {
            $list = DealsUser::with('outlet', 'dealVoucher.deal')->where('id_deals_user', $id)->orderBy('claimed_at', 'DESC')->first();

            if (empty($list)) {
                return response()->json(MyHelper::checkGet($list));
            }

            $result = [
                'trasaction_type'               => 'voucher',
                'id_deals_user'                 => $list['id_deals_user'],
                'deals_receipt_number'          => implode('', [strtotime($list['claimed_at']), $list['id_deals_user']]),
                'date'                          => date('d M Y H:i', strtotime($list['claimed_at'])),
                'voucher_price_cash'            => MyHelper::requestNumber($list['voucher_price_cash'],'_CURRENCY'),
                'deals_voucher'                 => $list['dealVoucher']['deal']['deals_title'],
                'payment_methods'               => $list['payment_method']
            ];

            if (!is_null($list['balance_nominal'])) {
                $result['payment'][] = [
                    'name'      => (env('POINT_NAME')) ? env('POINT_NAME') : 'Balance',
                    'is_balance'=> 1,
                    'amount'    => MyHelper::requestNumber($list['balance_nominal'],'_POINT'),
                ];
            }
            switch ($list['payment_method']) {
                case 'Manual':
                    $payment = DealsPaymentManual::where('id_deals_user', $id)->first();
                    $result['payment'][] = [
                        'name'      => 'Manual',
                        'amount'    =>  MyHelper::requestNumber($payment->payment_nominal,'_CURRENCY')
                    ];
                    break;
                case 'Midtrans':
                    $payment = DealsPaymentMidtran::where('id_deals_user', $id)->first();
                    $result['payment'][] = [
                        'name'      => strtoupper(str_replace('_', ' ', $payment->payment_type)).' '.strtoupper($payment->bank),
                        'amount'    => MyHelper::requestNumber($payment->gross_amount,'_CURRENCY')
                    ];
                    break;
                case 'OVO':
                    $payment = DealsPaymentOvo::where('id_deals_user', $id)->first();
                    $result['payment'][] = [
                        'name'      => 'OVO',
                        'amount'    =>  MyHelper::requestNumber($payment->amount,'_CURRENCY')
                    ];
                    break;
                case 'Ipay88':
                    $payment = DealsPaymentIpay88::where('id_deals_user', $id)->first();
                    $result['payment'][] = [
                        'name'      => $payment->payment_method,
                        'amount'    =>  MyHelper::requestNumber($payment->amount / 100,'_CURRENCY')
                    ];
                    break;
                case 'Shopeepay':
                    $payment = DealsPaymentShopeePay::where('id_deals_user', $id)->first();
                    $result['payment'][] = [
                        'name'      => 'ShopeePay',
                        'amount'    =>  MyHelper::requestNumber($payment->amount,'_CURRENCY')
                    ];
                    break;
            }

            return response()->json(MyHelper::checkGet($result));
        }
    }

    /**
     * Create a new export queue
     * @param  Request $request
     * @return Response
     */
    public function newExport(Request $request)
    {
        $post = $request->json()->all();
        unset($post['filter']['_token']);

        $insertToQueue = [
            'id_user_franchise' => $request->user()->id_user_franchise,
            'id_outlet' => $post['id_outlet'],
            'filter' => json_encode($post['filter']),
            'report_type' => 'Transaction',
            'status_export' => 'Running'
        ];

        $create = ExportFranchiseQueue::create($insertToQueue);
        if($create){
            ExportFranchiseJob::dispatch($create)->allOnConnection('export_franchise_queue');
        }
        return response()->json(MyHelper::checkCreate($create));
    }

    /**
     * Display list of exported transaction
     * @param Request $request
     * return Response
     */
    public function listExport(Request $request) {
    	// return $request->all();
        $result = ExportFranchiseQueue::where('report_type', 'Transaction')->where('id_user_franchise', $request->user()->id_user_franchise);
        if ($request->id_outlet) {
        	$result->where('id_outlet', $request->id_outlet);
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'created_at',
                 null,
                'status_export',
            ];
            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }

        $result->orderBy('id_export_franchise_queue', 'DESC');

        if ($request->page) {
            $result = $result->paginate($request->length ?: 15)->toArray();
            $countTotal = $result['total'];
            // needed for datatables
            $result['recordsTotal'] = $countTotal;
        } else {
            $result = $result->get();
        }

        return MyHelper::checkGet($result);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroyExport(ExportQueue $export_queue)
    {
        $filename = str_replace([env('STORAGE_URL_API').'download/', env('STORAGE_URL_API')], '', $export_queue->url_export);
        $delete = Storage::delete($filename);
        if ($delete) {
            $export_queue->status_export = 'Deleted';
            $export_queue->save();
        }
        return MyHelper::checkDelete($delete);
    }

}
