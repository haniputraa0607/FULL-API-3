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

use App\Lib\MyHelper;
use App\Lib\GoSend;
use Validator;
use Hash;
use DB;
use Mail;
use Image;
use Illuminate\Support\Facades\Log;

class ApiTransactionFranchiseController extends Controller
{
    public $saveImage = "img/transaction/manual-payment/";

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

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
}
