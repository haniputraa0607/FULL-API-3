<?php

namespace Modules\OutletApp\Http\Controllers;

use Modules\SettingFraud\Entities\FraudDetectionLogTransactionDay;
use Modules\SettingFraud\Entities\FraudDetectionLogTransactionWeek;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Outlet;
use App\Http\Models\OutletToken;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionPickupGoSend;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\TransactionPaymentOvo;
use App\Http\Models\TransactionPaymentOffline;
use App\Http\Models\Setting;
use App\Http\Models\User;
use App\Http\Models\Product;
use App\Http\Models\ProductCategory;
use App\Http\Models\ProductPrice;
use App\Http\Models\LogBalance;
use Modules\Brand\Entities\BrandProduct;
use Modules\Brand\Entities\Brand;

use Modules\OutletApp\Http\Requests\ListProduct;
use Modules\OutletApp\Http\Requests\UpdateToken;
use Modules\OutletApp\Http\Requests\DeleteToken;
use Modules\OutletApp\Http\Requests\DetailOrder;
use Modules\OutletApp\Http\Requests\ProductSoldOut;

use App\Lib\MyHelper;
use DB;

class ApiOutletApp extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->balance  = "Modules\Balance\Http\Controllers\BalanceController";
        $this->getNotif = "Modules\Transaction\Http\Controllers\ApiNotification";
        $this->membership    = "Modules\Membership\Http\Controllers\ApiMembership";
    }

    public function deleteToken(DeleteToken $request)
    {
        $post = $request->json()->all();
        $delete = OutletToken::where('token', $post['token'])->first();
        if (!empty($delete)) {
            $delete->delete();
            if (!$delete) {
                return response()->json(['status' => 'fail', 'messages' => ['Delete token failed']]);
            }
        }

        return response()->json(['status' => 'success', 'messages' => ['Delete token success']]);
    }

    public function updateToken(UpdateToken $request){
        $post = $request->json()->all();
        $outlet = $request->user();

        $check = OutletToken::where('id_outlet','=',$outlet['id_outlet'])
                            ->where('token','=',$post['token'])
                            ->get()
                            ->toArray();

        if($check){
            return response()->json(['status' => 'success']);
        } else {
            $query = OutletToken::create(['id_outlet' => $outlet['id_outlet'], 'token' => $post['token']]);     return response()->json(MyHelper::checkUpdate($query));
        }
    }

    public function listOrder(Request $request){
        $post = $request->json()->all();
        $outlet = $request->user();

        $list = Transaction::leftjoin('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
                            ->leftJoin('transaction_products', 'transactions.id_transaction', 'transaction_products.id_transaction')
                            ->leftJoin('users', 'users.id', 'transactions.id_user')
                            ->select('transactions.id_transaction', 'transaction_receipt_number', 'order_id', 'transaction_date',
                                    DB::raw('(CASE WHEN pickup_by = "Customer" THEN "Pickup Order" ELSE "Delivery" END) AS transaction_type'),
                                    'pickup_by', 'pickup_type', 'pickup_at', 'receive_at', 'ready_at', 'taken_at', 'reject_at', 'transaction_grandtotal', DB::raw('sum(transaction_product_qty) as total_item'), 'users.name')
                            ->where('transactions.id_outlet', $outlet->id_outlet)
                            ->whereDate('transaction_date', date('Y-m-d'))
                            ->where('transaction_payment_status', 'Completed')
                            ->where('trasaction_type', 'Pickup Order')
                            ->whereNull('void_date')
                            ->groupBy('transaction_products.id_transaction')
                            ->orderBy('pickup_at', 'ASC')
                            ->orderBy('transaction_date', 'ASC')
                            ->orderBy('transactions.id_transaction', 'ASC');

        //untuk search
        if(isset($post['search_order_id'])){
            $list = $list->where('order_id', 'LIKE', '%'.$post['search_order_id'].'%');
        }

        //by status
        if(isset($post['status'])){
            if($post['status'] == 'Pending'){
                $list = $list->whereNull('receive_at')
                             ->whereNull('ready_at')
                             ->whereNull('taken_at');
            }
            if($post['status'] == 'Accepted'){
                $list = $list->whereNull('ready_at')
                        ->whereNull('taken_at');
            }
            if($post['status'] == 'Ready'){
                $list = $list->whereNull('taken_at');
            }
            if($post['status'] == 'Taken'){
                $list = $list->whereNotNull('taken_at');
            }
        }

        $list = $list->get()->toArray();

        //dikelompokkan sesuai status
        $listPending = [];
        $listOnGoing = [];
        $listOnGoingSet = [];
        $listOnGoingNow = [];
        $listOnGoingArrival = [];
        $listReady = [];
        $listCompleted = [];

        foreach($list as $i => $dataList){
            $qr     = $dataList['order_id'];

            // $qrCode = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data='.$qr;
            $qrCode = 'https://chart.googleapis.com/chart?chl='.$qr.'&chs=250x250&cht=qr&chld=H%7C0';
            $qrCode = html_entity_decode($qrCode);

            $dataList = array_slice($dataList, 0, 3, true) +
            array("order_id_qrcode" => $qrCode) +
            array_slice($dataList, 3, count($dataList) - 1, true) ;

            $dataList['order_id'] = strtoupper($dataList['order_id']);
            if($dataList['reject_at'] != null){
                $dataList['status']  = 'Rejected';
                $listCompleted[] = $dataList;
            }elseif($dataList['receive_at'] == null){
                $dataList['status']  = 'Pending';
                $listPending[] = $dataList;
            }elseif($dataList['receive_at'] != null && $dataList['ready_at'] == null){
                $dataList['status']  = 'Accepted';
                $listOnGoing[] = $dataList;
                if($dataList['pickup_type'] == 'set time'){
                    $listOnGoingSet[] = $dataList;
                }elseif($dataList['pickup_type'] == 'right now'){
                    $listOnGoingNow[] = $dataList;
                }elseif($dataList['pickup_type'] == 'at arrival'){
                    $listOnGoingArrival[] = $dataList;
                }
            }elseif($dataList['receive_at'] != null && $dataList['ready_at'] != null && $dataList['taken_at'] == null){
                $dataList['status']  = 'Ready';
                $listReady[] = $dataList;
            }elseif($dataList['receive_at'] != null && $dataList['ready_at'] != null && $dataList['taken_at'] != null){
                $dataList['status']  = 'Completed';
                $listCompleted[] = $dataList;
            }
        }

        //sorting pickup time list on going yg set time
        usort($listOnGoingSet, function($a, $b) {
            return $a['pickup_at'] <=> $b['pickup_at'];
        });

        //return 1 array
        $result['pending']['count'] = count($listPending);
        $result['pending']['data'] = $listPending;

        $result['on_going']['count'] = count($listOnGoingNow) + count($listOnGoingSet) + count($listOnGoingArrival);
        $result['on_going']['data'] = $listOnGoing;
        // $result['on_going']['data']['right_now']['count'] = count($listOnGoingNow);
        // $result['on_going']['data']['right_now']['data'] = $listOnGoingNow;
        // $result['on_going']['data']['pickup_time']['count'] = count($listOnGoingSet);
        // $result['on_going']['data']['pickup_time']['data'] = $listOnGoingSet;
        // $result['on_going']['data']['at_arrival']['count'] = count($listOnGoingArrival);
        // $result['on_going']['data']['at_arrival']['data'] = $listOnGoingArrival;

        $result['ready']['count'] = count($listReady);
        $result['ready']['data'] = $listReady;

        $result['completed']['count'] = count($listCompleted);
        $result['completed']['data'] = $listCompleted;

        if(isset($post['status'])){
            if($post['status'] == 'Pending'){
                unset($result['on_going']);
                unset($result['ready']);
                unset($result['completed']);
            }
            if($post['status'] == 'Accepted'){
                unset($result['pending']);
                unset($result['ready']);
                unset($result['completed']);
            }
            if($post['status'] == 'Ready'){
                unset($result['pending']);
                unset($result['on_going']);
                unset($result['completed']);
            }
            if($post['status'] == 'Completed'){
                unset($result['pending']);
                unset($result['on_going']);
                unset($result['ready']);
            }
        }

        return response()->json(MyHelper::checkGet($result));

    }

    public function detailOrder(DetailOrder $request){
        $post = $request->json()->all();

        $list = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
                            ->where('order_id', $post['order_id'])
                            ->whereDate('transaction_date', date('Y-m-d'))
                            ->with('user.city.province', 'productTransaction.product.product_category', 'productTransaction.product.product_discounts', 'outlet')->first();

        $qr     = $list['order_id'];

        // $qrCode = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data='.$qr;
        $qrCode = 'https://chart.googleapis.com/chart?chl='.$qr.'&chs=250x250&cht=qr&chld=H%7C0';
        $list['qr'] = html_entity_decode($qrCode);

        if(!$list){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Data Order Not Found']
            ]);
        }

        if($list['reject_at'] != null){
            $statusPickup  = 'Reject';
        }
        elseif($list['taken_at'] != null){
            $statusPickup  = 'Taken';
        }
        elseif($list['ready_at'] != null){
            $statusPickup  = 'Ready';
        }
        elseif($list['receive_at'] != null){
            $statusPickup  = 'On Going';
        }
        else{
            $statusPickup  = 'Pending';
        }

        $list = array_slice($list->toArray(), 0, 29, true) +
        array("status" => $statusPickup) +
        array_slice($list->toArray(), 29, count($list->toArray()) - 1, true) ;



        $label = [];

        $order = Setting::where('key', 'transaction_grand_total_order')->value('value');
        $exp   = explode(',', $order);

        foreach ($exp as $i => $value) {
            if ($exp[$i] == 'subtotal') {
                unset($exp[$i]);
                continue;
            }

            if ($exp[$i] == 'tax') {
                $exp[$i] = 'transaction_tax';
                array_push($label, 'Tax');
            }

            if ($exp[$i] == 'service') {
                $exp[$i] = 'transaction_service';
                array_push($label, 'Service Fee');
            }

            if ($exp[$i] == 'shipping') {
                if ($list['trasaction_type'] == 'Pickup Order') {
                    unset($exp[$i]);
                    continue;
                } else {
                    $exp[$i] = 'transaction_shipment';
                    array_push($label, 'Delivery Cost');
                }
            }

            if ($exp[$i] == 'discount') {
                $exp[$i] = 'transaction_discount';
                array_push($label, 'Discount');
                continue;
            }

            if (stristr($exp[$i], 'empty')) {
                unset($exp[$i]);
                continue;
            }
        }

        array_splice($exp, 0, 0, 'transaction_subtotal');
        array_splice($label, 0, 0, 'Cart Total');

        array_values($exp);
        array_values($label);

        $imp = implode(',', $exp);
        $order_label = implode(',', $label);

        $detail = [];

        $list['order'] = $imp;
        $list['order_label'] = $order_label;

        return response()->json(MyHelper::checkGet($list));
    }

    public function detailWebviewPage(Request $request)
    {
        $id = $request->json('receipt');

        if($request->json('id_transaction')){
            $list = Transaction::where('id_transaction', $request->json('id_transaction'))->with('user.city.province', 'productTransaction.product.product_category', 'productTransaction.product.product_photos', 'productTransaction.product.product_discounts', 'transaction_payment_offlines', 'outlet.city')->first();
        }else{
            $list = Transaction::where('transaction_receipt_number', $id)->with('user.city.province', 'productTransaction.product.product_category', 'productTransaction.product.product_photos', 'productTransaction.product.product_discounts', 'transaction_payment_offlines', 'outlet.city')->first();
        }
        $label = [];
        $label2 = [];

        $cart = $list['transaction_subtotal'] + $list['transaction_shipment'] + $list['transaction_service'] + $list['transaction_tax'] - $list['transaction_discount'];

        $list['transaction_carttotal'] = $cart;

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

        if ($list['trasaction_payment_type'] == 'Balance') {
            $log = LogBalance::where('id_reference', $list['id_transaction'])->where('source', 'Transaction')->where('balance', '<', 0)->first();
            if ($log['balance'] < 0) {
                $list['balance'] = $log['balance'];
                $list['check'] = 'tidak topup';
            } else {
                $list['balance'] = $list['transaction_grandtotal'] - $log['balance'];
                $list['check'] = 'topup';
            }
        }

        if ($list['trasaction_payment_type'] == 'Manual') {
            $payment = TransactionPaymentManual::with('manual_payment_method.manual_payment')->where('id_transaction', $list['id_transaction'])->first();
            $list['payment'] = $payment;
        }

        if ($list['trasaction_payment_type'] == 'Midtrans' || $list['trasaction_payment_type'] == 'Balance') {
            //cek multi payment
            $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get();
            if($multiPayment){
                foreach($multiPayment as $dataPay){
                    if($dataPay['type'] == 'Balance'){
                        $paymentBalance = TransactionPaymentBalance::find($dataPay['id_payment']);
                        if($paymentBalance){
                            $list['balance'] = -$paymentBalance['balance_nominal'];
                        }
                    }else{
                        $payment = TransactionPaymentMidtran::find($dataPay['id_payment']);
                    }
                }
                if(isset($payment)){
                    $list['payment'] = $payment;
                }
            }else{
                if ($list['trasaction_payment_type'] == 'Balance') {
                    $paymentBalance = TransactionPaymentBalance::where('id_transaction', $list['id_transaction'])->first();
                    if($paymentBalance){
                        $list['balance'] = -$paymentBalance['balance_nominal'];
                    }
                }

                if ($list['trasaction_payment_type'] == 'Midtrans') {
                    $paymentMidtrans = TransactionPaymentMidtran::where('id_transaction', $list['id_transaction'])->first();
                    if($paymentMidtrans){
                        $list['payment'] = $paymentMidtrans;
                    }
                }
            }
        }

        if ($list['trasaction_payment_type'] == 'Offline') {
            $payment = TransactionPaymentOffline::where('id_transaction', $list['id_transaction'])->get();
            $list['payment'] = $payment;
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

        $qrTest= '';

        if ($list['trasaction_type'] == 'Pickup Order') {
            $detail = TransactionPickup::where('id_transaction', $list['id_transaction'])->with('transaction_pickup_go_send')->first();
            $qrTest = $detail['order_id'];
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

        $list['kind'] = $list['trasaction_type'];

        $warning = 0;
        $takenLabel = '';

        if($detail['reject_at'] != null){
            $statusPickup  = 'Reject';
        }
        elseif($detail['taken_at'] != null){
            $statusPickup  = 'Taken';
            $warning = 1;
            $takenLabel = $this->convertMonth($detail['taken_at']);
        }
        elseif($detail['ready_at'] != null){
            $statusPickup  = 'Ready';
        }
        elseif($detail['receive_at'] != null){
            $statusPickup  = 'On Going';
        }
        else{
            $statusPickup  = 'Pending';
        }

        if (isset($success)) {
            $list['success'] = 1;

        }

        // $qrCode = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data='.$qrTest;
        $qrCode = 'https://chart.googleapis.com/chart?chl='.$qrTest.'&chs=250x250&cht=qr&chld=H%7C0';
        $qrCode = html_entity_decode($qrCode);
        $list['qr'] = $qrCode;

        $settingService = Setting::where('key', 'service')->first();
        $settingTax = Setting::where('key', 'tax')->first();

        $list['valueService'] = 100 * $settingService['value'];
        $list['valueTax'] = 100 * $settingTax['value'];
        $list['status'] = $statusPickup;
        $list['warning'] = $warning;
        $list['taken_label'] = $takenLabel;

        return response()->json(MyHelper::checkGet($list));
    }

    public function convertMonth($date)
    {
        if (date('m', strtotime($date)) == '01') {
            $month = 'Januari';
        } elseif (date('m', strtotime($date)) == '02') {
            $month = 'Februari';
        } elseif (date('m', strtotime($date)) == '03') {
            $month = 'Maret';
        } elseif (date('m', strtotime($date)) == '04') {
            $month = 'April';
        } elseif (date('m', strtotime($date)) == '05') {
            $month = 'Mei';
        } elseif (date('m', strtotime($date)) == '06') {
            $month = 'Juni';
        } elseif (date('m', strtotime($date)) == '07') {
            $month = 'Juli';
        } elseif (date('m', strtotime($date)) == '08') {
            $month = 'Agustus';
        } elseif (date('m', strtotime($date)) == '09') {
            $month = 'September';
        } elseif (date('m', strtotime($date)) == '10') {
            $month = 'Oktober';
        } elseif (date('m', strtotime($date)) == '11') {
            $month = 'November';
        } elseif (date('m', strtotime($date)) == '12') {
            $month = 'Desember';
        }

        $day = date('d', strtotime($date));
        $year = date('Y', strtotime($date));

        $time = date('H:i', strtotime($date));

        return $day.' '.$month.' '.$year.' '.$time;
    }

    public function detailWebview(DetailOrder $request){
        $post = $request->json()->all();

        if (empty($check)) {
            $list = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
                                ->where('order_id', $post['order_id'])
                                ->whereIn('transaction_payment_status',['Pending','Completed'])
                                ->whereDate('transaction_date', date('Y-m-d',strtotime($post['transaction_date'])))
                                ->first();

            if(!$list){
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Data Order Not Found']
                ]);
            }

            if($list['reject_at'] != null){
                $statusPickup  = 'Reject';
            }
            elseif($list['taken_at'] != null){
                $statusPickup  = 'Taken';
            }
            elseif($list['ready_at'] != null){
                $statusPickup  = 'Ready';
            }
            elseif($list['receive_at'] != null){
                $statusPickup  = 'On Going';
            }
            else{
                $statusPickup  = 'Pending';
            }


            $dataEncode = [
                'order_id' => $list->order_id,
                'receipt'  => $list->transaction_receipt_number,
            ];

            $encode = json_encode($dataEncode);
            $base = base64_encode($encode);

            $send = [
                'status' => 'success',
                'result' => [
                    'status'    => $statusPickup,
                    'date'      => $list->transaction_date,
                    'reject_at' => $list->reject_at,
                    'url'       => env('API_URL').'/transaction/web/view/outletapp?data='.$base
                ],
            ];

            return response()->json($send);
        }

    }

    public function acceptOrder(DetailOrder $request){
        $post = $request->json()->all();
        $outlet = $request->user();

        $order = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
                            ->where('order_id', $post['order_id'])
                            ->whereDate('transaction_date', date('Y-m-d'))
                            ->first();

        if(!$order){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Data Transaction Not Found']
            ]);
        }

        if($order->id_outlet != $request->user()->id_outlet){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Data Transaction Outlet Does Not Match']
            ]);
        }

        if($order->reject_at != null){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Order Has Been Rejected']
            ]);
        }

        if($order->receive_at != null){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Order Has Been Received']
            ]);
        }

        DB::beginTransaction();

        $pickup = TransactionPickup::where('id_transaction', $order->id_transaction)->update(['receive_at' => date('Y-m-d H:i:s')]);

        if($pickup){
            //send notif to customer
            $user = User::find($order->id_user);
            $send = app($this->autocrm)->SendAutoCRM('Order Accepted', $user['phone'], [
                "outlet_name" => $outlet['outlet_name'],
                'id_transaction' => $order->id_transaction,
                "id_reference" => $order->transaction_receipt_number.','.$order->id_outlet,
                "transaction_date" => $order->transaction_date]
            );
            if($send != true){
                DB::rollback();
                return response()->json([
                        'status' => 'fail',
                        'messages' => ['Failed Send notification to customer']
                    ]);
            }

            DB::commit();
        }

        return response()->json(MyHelper::checkUpdate($pickup));
    }

    public function SetReady(DetailOrder $request){
        $post = $request->json()->all();
        $outlet = $request->user();

        $order = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
                            ->where('order_id', $post['order_id'])
                            ->whereDate('transaction_date', date('Y-m-d'))
                            ->first();

        if(!$order){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Data Transaction Not Found']
            ]);
        }

        if($order->id_outlet != $request->user()->id_outlet){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Data Transaction Outlet Does Not Match']
            ]);
        }

        if($order->reject_at != null){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Order Has Been Rejected']
            ]);
        }

        if($order->receive_at == null){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Order Has Not Been Accepted']
            ]);
        }

        if($order->ready_at != null){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Order Has Been Marked as Ready']
            ]);
        }

        // DB::beginTransaction();
        $pickup = TransactionPickup::where('id_transaction', $order->id_transaction)->update(['ready_at' => date('Y-m-d H:i:s')]);
        // dd($pickup);
        if($pickup){
            //send notif to customer
            $user = User::find($order->id_user);
            $send = app($this->autocrm)->SendAutoCRM('Order Ready', $user['phone'], [
                "outlet_name" => $outlet['outlet_name'],
                'id_transaction' => $order->id_transaction,
                "id_reference" => $order->transaction_receipt_number.','.$order->id_outlet,
                "transaction_date" => $order->transaction_date
            ]);
            if($send != true){
                // DB::rollback();
                return response()->json([
                        'status' => 'fail',
                        'messages' => ['Failed Send notification to customer']
                    ]);
            }

            $newTrx = Transaction::with('user.memberships', 'outlet', 'productTransaction')->where('id_transaction', $order->id_transaction)->first();

            $checkType = TransactionMultiplePayment::where('id_transaction', $order->id_transaction)->get()->toArray();
            $column = array_column($checkType, 'type');

            if (!in_array('Balance', $column)) {
                $savePoint = app($this->getNotif)->savePoint($newTrx);
                // return $savePoint;
                if (!$savePoint) {
                    // DB::rollback();
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => ['Transaction failed']
                    ]);
                }
            }

            $checkMembership = app($this->membership)->calculateMembership($user['phone']);

        }
        DB::commit();
        // return  $pickup = TransactionPickup::where('id_transaction', $order->id_transaction)->first();
        return response()->json(MyHelper::checkUpdate($pickup));
    }

    public function takenOrder(DetailOrder $request){
        $post = $request->json()->all();
        $outlet = $request->user();

        $order = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
                            ->where('order_id', $post['order_id'])
                            ->whereDate('transaction_date', date('Y-m-d'))
                            ->first();

        if(!$order){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Data Transaction Not Found']
            ]);
        }

        if($order->id_outlet != $request->user()->id_outlet){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Data Transaction Outlet Does Not Match']
            ]);
        }

        if($order->reject_at != null){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Order Has Been Rejected']
            ]);
        }

        if($order->receive_at == null){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Order Has Not Been Accepted']
            ]);
        }

        if($order->ready_at == null){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Order Has Not Been Marked as Ready']
            ]);
        }

        if($order->taken_at != null){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Order Has Been Taken']
            ]);
        }

        DB::beginTransaction();

        $pickup = TransactionPickup::where('id_transaction', $order->id_transaction)->update(['taken_at' => date('Y-m-d H:i:s')]);
        if($pickup){
            //send notif to customer
            $user = User::find($order->id_user);
            $send = app($this->autocrm)->SendAutoCRM('Order Taken', $user['phone'], [
                "outlet_name" => $outlet['outlet_name'],
                'id_transaction' => $order->id_transaction,
                "id_reference" => $order->transaction_receipt_number.','.$order->id_outlet,
                "transaction_date" => $order->transaction_date
            ]);
            if($send != true){
                DB::rollback();
                return response()->json([
                        'status' => 'fail',
                        'messages' => ['Failed Send notification to customer']
                    ]);
            }

            DB::commit();
        }


        return response()->json(MyHelper::checkUpdate($pickup));
    }

    public function profile(Request $request){
        $outlet = $request->user();
        $profile['outlet_name'] = $outlet['outlet_name'];
        $profile['outlet_code'] = $outlet['outlet_code'];
        $profile['outlet_address'] = $outlet['outlet_address'];
        $profile['outlet_phone'] = $outlet['outlet_phone'];
        $profile['status'] = 'success';

        return response()->json($profile);
    }

    public function productSoldOut(ProductSoldOut $request){
        $post = $request->json()->all();
        $outlet = $request->user();
        $updated = 0;
        if($post['sold_out']){
            $updated += ProductPrice::where('id_outlet', $outlet['id_outlet'])
                ->whereIn('id_product', $post['sold_out'])
                ->where('product_stock_status','<>', 'Sold Out')
                ->update(['product_stock_status' => 'Sold Out']);
        }
        if($post['available']){
            $updated += ProductPrice::where('id_outlet', $outlet['id_outlet'])
                ->whereIn('id_product', $post['available'])
                ->where('product_stock_status','<>', 'Available')
                ->update(['product_stock_status' => 'Available']);
        }
        return [
            'status'=>'success',
            'result'=>['updated'=>$updated]
        ];
    }
    /**
     * return list category group by brand
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function listCategory(Request $request) {
        $outlet = $request->user();
        $sub = BrandProduct::select('id_brand','id_product','id_product_category')->distinct();
        $data = DB::query()->fromSub($sub, 'brand_product')->select(\DB::raw('brand_product.id_brand,brand_product.id_product_category,count(*) as total_product,sum(case product_stock_status when "Sold Out" then 1 else 0 end) total_sold_out,product_category_name'))
            ->join('product_categories','product_categories.id_product_category','=','brand_product.id_product_category')
            ->join('products',function($query){
                $query->on('brand_product.id_product','=','products.id_product')
                ->groupBy('products.id_product');
            })
            // product availbale in outlet
            ->join('product_prices','product_prices.id_product','=','products.id_product')
            ->where('product_prices.id_outlet','=',$outlet['id_outlet'])
            // brand produk ada di outlet
            ->whereColumn('brand_outlet.id_outlet','=','product_prices.id_outlet')
            ->join('brand_outlet','brand_outlet.id_brand','=','brand_product.id_brand')
            ->where(function($query){
                $query->where('product_prices.product_visibility','=','Visible')
                        ->orWhere(function($q){
                            $q->whereNull('product_prices.product_visibility')
                            ->where('products.product_visibility', 'Visible');
                        });
            })
            ->where('product_prices.product_status','=','Active')
            ->whereNotNull('product_prices.product_price')
            ->groupBy('brand_product.id_brand','brand_product.id_product_category')
            ->orderBy('product_category_order')
            ->get()->toArray();
        $result = MyHelper::groupIt($data,'id_brand',null,function($key,&$val){
            $brand = Brand::select('id_brand','name_brand','order_brand')
            ->where([
                'id_brand'=>$key,
                'brand_active'=>1,
                'brand_visibility'=>1
            ])->first();
            $brand['categories'] = $val;
            $val = $brand;
            return $key;
        });
        usort($result, function($a,$b){
            return $a['order_brand'] <=> $b['order_brand'];
        });
        return MyHelper::checkGet(array_values($result));
    }
    /**
     * Return only list product based on selected brand and category
     * @param string $value [description]
     */
    public function productList(ListProduct $request) {
        $outlet = $request->user();
        $post = $request->json()->all();
        $post['id_outlet'] = $outlet['id_outlet'];
        $products = Product::select([
                'products.id_product','products.product_code','products.product_name','product_prices.product_stock_status'
            ])
            ->join('brand_product','brand_product.id_product','=','products.id_product')
            ->where('brand_product.id_brand','=',$post['id_brand'])
            ->where('brand_product.id_product_category','=',$post['id_product_category'])
            // produk tersedia di outlet
            ->join('product_prices','product_prices.id_product','=','products.id_product')
            ->where('product_prices.id_outlet','=',$post['id_outlet'])
            // brand produk ada di outlet
            ->where('brand_outlet.id_outlet','=',$post['id_outlet'])
            ->join('brand_outlet','brand_outlet.id_brand','=','brand_product.id_brand')
            ->where(function($query){
                $query->where('product_prices.product_visibility','=','Visible')
                        ->orWhere(function($q){
                            $q->whereNull('product_prices.product_visibility')
                            ->where('products.product_visibility', 'Visible');
                        });
            })
            ->where('product_prices.product_status','=','Active')
            ->whereNotNull('product_prices.product_price')
            ->groupBy('products.id_product')
            ->orderBy('products.position')
            ->orderBy('products.id_product');
        if($request->page){
            $data = $products->paginate()->toArray();
            if(empty($data['data'])){
                return MyHelper::checkGet($data['data']);
            }
            return MyHelper::checkGet($data);
        }else{
            return MyHelper::checkGet($products->get()->toArray());
        }
    }

    /**
     * Return list product and groub by its category
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function listProduct(Request $request){
        $outlet = $request->user();
        $listCategory = ProductCategory::join('products', 'product_categories.id_product_category', 'products.id_product_category')
                                        ->join('product_prices', 'product_prices.id_product', 'products.id_product')
                                        ->where('id_outlet', $outlet['id_outlet'])
                                        ->where('product_prices.product_visibility','=','Visible')
                                        ->where('product_prices.product_status','=','Active')
                                        ->with('product_category')
                                        // ->select('id_product_category', 'product_category_name')
                                        ->get();

        $result = [];
        $idParent = [];
        $idParent2 = [];
        $categorized = [];
        foreach($listCategory as $i => $category){
            $dataCategory = [];
            $dataProduct = [];
            if(isset($category['product_category']['id_product_category'])){
                //masukin ke array result
                $position = array_search($category['product_category']['id_product_category'], $idParent);
                if(!is_integer($position)){

                    $dataProduct['id_product'] = $category['id_product'];
                    $dataProduct['product_code'] = $category['product_code'];
                    $dataProduct['product_name'] = $category['product_name'];
                    $dataProduct['product_stock_status'] = $category['product_stock_status'];

                    $child['id_product_category'] = $category['id_product_category'];
                    $child['product_category_name'] = $category['product_category_name'];
                    $child['products'][] = $dataProduct;

                    $dataCategory['id_product_category'] = $category['product_category']['id_product_category'];
                    $dataCategory['product_category_name'] = $category['product_category']['product_category_name'];
                    $dataCategory['child_category'][] = $child;

                    $categorized[] = $dataCategory;
                    $idParent[] = $category['product_category']['id_product_category'];
                    $idParent2[][] = $category['id_product_category'];
                }else{
                    $positionChild = array_search($category['id_product_category'], $idParent2[$position]);
                    if(!is_integer($positionChild)){
                        //masukin product ke child baru
                        $idParent2[$position][] = $category['id_product_category'];

                        $dataCategory['id_product_category'] = $category['id_product_category'];
                        $dataCategory['product_category_name'] = $category['product_category_name'];

                        $dataProduct['id_product'] = $category['id_product'];
                        $dataProduct['product_code'] = $category['product_code'];
                        $dataProduct['product_name'] = $category['product_name'];
                        $dataProduct['product_stock_status'] = $category['product_stock_status'];

                        $dataCategory['products'][] = $dataProduct;
                        $categorized[$position]['child_category'][] = $dataCategory;

                    }else{
                        //masukin product child yang sudah ada
                        $dataProduct['id_product'] = $category['id_product'];
                        $dataProduct['product_code'] = $category['product_code'];
                        $dataProduct['product_name'] = $category['product_name'];
                        $dataProduct['product_stock_status'] = $category['product_stock_status'];

                        $categorized[$position]['child_category'][$positionChild]['products'][]= $dataProduct;
                    }
                }
            }else{
                $position = array_search($category['id_product_category'], $idParent);
                if(!is_integer($position)){
                    $dataProduct['id_product'] = $category['id_product'];
                    $dataProduct['product_code'] = $category['product_code'];
                    $dataProduct['product_name'] = $category['product_name'];
                    $dataProduct['product_stock_status'] = $category['product_stock_status'];

                    $dataCategory['id_product_category'] = $category['id_product_category'];
                    $dataCategory['product_category_name'] = $category['product_category_name'];
                    $dataCategory['products'][] = $dataProduct;

                    $categorized[] = $dataCategory;
                    $idParent[] = $category['id_product_category'];
                    $idParent2[][] = [];
                }else{
                    $idParent2[$position][] = $category['id_product_category'];

                    $dataProduct['id_product'] = $category['id_product'];
                    $dataProduct['product_code'] = $category['product_code'];
                    $dataProduct['product_name'] = $category['product_name'];
                    $dataProduct['product_stock_status'] = $category['product_stock_status'];

                    $categorized[$position]['products'][] = $dataProduct;
                }

            }

        }

        // $uncategorized = ProductPrice::join('products', 'product_prices.id_product', 'products.id_product')
        //                                 ->whereIn('products.id_product', function($query){
        //                                     $query->select('id_product')->from('products')->whereNull('id_product_category');
        //                                 })->where('id_outlet', $outlet['id_outlet'])
        //                                 ->select('products.id_product', 'product_code', 'product_name', 'product_stock_status')->get();

        $result['categorized'] = $categorized;
        // $result['uncategorized'] = $uncategorized;
        return response()->json(MyHelper::checkGet($result));
    }

    public function rejectOrder(DetailOrder $request){
        $post = $request->json()->all();

        $outlet = $request->user();

        $order = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
                            ->where('order_id', $post['order_id'])
                            ->whereDate('transaction_date', date('Y-m-d'))
                            ->first();

        if(!$order){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Data Transaction Not Found']
            ]);
        }

        if($order->id_outlet != $request->user()->id_outlet){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Data Transaction Outlet Does Not Match']
            ]);
        }


        if($order->ready_at){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Order Has Been Ready']
            ]);
        }

        if($order->taken_at){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Order Has Been Taken']
            ]);
        }

        if($order->reject_at){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Order Has Been Rejected']
            ]);
        }

        DB::beginTransaction();

        if(!isset($post['reason'])){
            $post['reason'] = null;
        }

        $pickup = TransactionPickup::where('id_transaction', $order->id_transaction)->update([
            'reject_at' => date('Y-m-d H:i:s'),
            'reject_reason'   => $post['reason']
        ]);

        if($pickup){
            $getLogFraudDay = FraudDetectionLogTransactionDay::whereRaw('Date(fraud_detection_date) ="'.date('Y-m-d', strtotime($order->transaction_date)).'"')
                ->where('id_user',$order->id_user)
                ->first();
            if($getLogFraudDay){
                $checkCount = $getLogFraudDay['count_transaction_day'] - 1;
                if($checkCount <= 0){
                    $delLogTransactionDay = FraudDetectionLogTransactionDay::where('id_fraud_detection_log_transaction_day',$getLogFraudDay['id_fraud_detection_log_transaction_day'])
                        ->delete();
                }else{
                    $updateLogTransactionDay = FraudDetectionLogTransactionDay::where('id_fraud_detection_log_transaction_day',$getLogFraudDay['id_fraud_detection_log_transaction_day'])->update([
                        'count_transaction_day' =>$checkCount,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }

            }

            $getLogFraudWeek= FraudDetectionLogTransactionWeek::where('fraud_detection_week', date('W', strtotime($order->transaction_date)))
                ->where('fraud_detection_week', date('Y', strtotime($order->transaction_date)))
                ->where('id_user',$order->id_user)
                ->first();
            if($getLogFraudWeek){
                $checkCount = $getLogFraudWeek['count_transaction_week'] - 1;
                if($checkCount <= 0){
                    $delLogTransactionWeek = FraudDetectionLogTransactionWeek::where('id_fraud_detection_log_transaction_week',$getLogFraudWeek['id_fraud_detection_log_transaction_week'])
                        ->delete();
                }else{
                    $updateLogTransactionWeek = FraudDetectionLogTransactionWeek::where('id_fraud_detection_log_transaction_week',$getLogFraudWeek['id_fraud_detection_log_transaction_week'])->update([
                        'count_transaction_week' => $checkCount,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }

              //refund ke balance
            // if($order['trasaction_payment_type'] == "Midtrans"){
                $multiple = TransactionMultiplePayment::where('id_transaction', $order->id_transaction)->get()->toArray();
                if($multiple){
                    foreach($multiple as $pay){
                        if($pay['type'] == 'Balance'){
                            $payBalance = TransactionPaymentBalance::find($pay['id_payment']);
                            if($payBalance){
                                $refund = app($this->balance)->addLogBalance( $order['id_user'], $point=$payBalance['balance_nominal'], $order['id_transaction'], 'Rejected Order Point', $order['transaction_grandtotal']);
                                if ($refund == false) {
                                    DB::rollback();
                                    return response()->json([
                                        'status'    => 'fail',
                                        'messages'  => ['Insert Cashback Failed']
                                    ]);
                                }
                            }
                        }
                        elseif($pay['type'] == 'Ovo'){
                            $payOvo = TransactionPaymentOvo::find($pay['id_payment']);
                            if($payOvo){
                                $refund = app($this->balance)->addLogBalance( $order['id_user'], $point=$payOvo['amount'], $order['id_transaction'], 'Rejected Order Ovo', $order['transaction_grandtotal']);
                                if ($refund == false) {
                                    DB::rollback();
                                    return response()->json([
                                        'status'    => 'fail',
                                        'messages'  => ['Insert Cashback Failed']
                                    ]);
                                }
                            }
                        }
                        else{
                            $payMidtrans = TransactionPaymentMidtran::find($pay['id_payment']);
                            if($payMidtrans){
                                $refund = app($this->balance)->addLogBalance( $order['id_user'], $point=$payMidtrans['gross_amount'], $order['id_transaction'], 'Rejected Order Midtrans', $order['transaction_grandtotal']);
                                if ($refund == false) {
                                    DB::rollback();
                                    return response()->json([
                                        'status'    => 'fail',
                                        'messages'  => ['Insert Cashback Failed']
                                    ]);
                                }
                            }
                        }
                        $user = User::where('id', $order['id_user'])->first()->toArray();
                        $send = app($this->autocrm)->SendAutoCRM('Rejected Order Point Refund', $user['phone'],
                            [
                                "outlet_name"       => $outlet['outlet_name'],
                                'id_transaction'    => $order['id_transaction'],
                                "transaction_date"  => $order['transaction_date'],
                                'receipt_number'    => $order['transaction_receipt_number'],
                                'received_point'    => (string) $point
                            ]
                        );
                        if($send != true){
                            DB::rollback();
                            return response()->json([
                                    'status' => 'fail',
                                    'messages' => ['Failed Send notification to customer']
                                ]);
                        }
                    }
                }else{
                    $payMidtrans = TransactionPaymentMidtran::where('id_transaction', $order['id_transaction'])->first();
                    $payOvo = TransactionPaymentOvo::where('id_transaction', $order['id_transaction'])->first();
                    if($payMidtrans){
                        $refund = app($this->balance)->addLogBalance( $order['id_user'], $point=$payMidtrans['gross_amount'], $order['id_transaction'], 'Rejected Order Midtrans', $order['transaction_grandtotal']);
                        if ($refund == false) {
                            DB::rollback();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Insert Cashback Failed']
                            ]);
                        }
                    }elseif($payOvo){
                        $refund = app($this->balance)->addLogBalance( $order['id_user'], $point=$payOvo['amount'], $order['id_transaction'], 'Rejected Order Ovo', $order['transaction_grandtotal']);
                        if ($refund == false) {
                            DB::rollback();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Insert Cashback Failed']
                            ]);
                        }
                    }else{
                        $payBalance = TransactionPaymentBalance::where('id_transaction', $order['id_transaction'])->first();
                        if($payBalance){
                            $refund = app($this->balance)->addLogBalance( $order['id_user'], $point=$payBalance['balance_nominal'], $order['id_transaction'], 'Rejected Order Point', $order['transaction_grandtotal']);
                            if ($refund == false) {
                                DB::rollback();
                                return response()->json([
                                    'status'    => 'fail',
                                    'messages'  => ['Insert Cashback Failed']
                                ]);
                            }
                        }
                    }
                    //send notif to customer
                    $user = User::where('id', $order['id_user'])->first()->toArray();
                    $send = app($this->autocrm)->SendAutoCRM('Rejected Order Point Refund', $user['phone'],
                        [
                            "outlet_name"       => $outlet['outlet_name'],
                            "transaction_date"  => $order['transaction_date'],
                            'id_transaction'    => $order['id_transaction'],
                            'receipt_number'    => $order['transaction_receipt_number'],
                            'received_point'    => (string) $point
                        ]
                    );
                    if($send != true){
                        DB::rollback();
                        return response()->json([
                                'status' => 'fail',
                                'messages' => ['Failed Send notification to customer']
                            ]);
                    }

                    $send = app($this->autocrm)->SendAutoCRM('Order Reject', $user['phone'], [
                        "outlet_name" => $outlet['outlet_name'],
                        "id_reference" => $order->transaction_receipt_number.','.$order->id_outlet,
                        "transaction_date" => $order->transaction_date,
                        'id_transaction' => $order->id_transaction,
                    ]);
                    if($send != true){
                        DB::rollback();
                        return response()->json([
                                'status' => 'fail',
                                'messages' => ['Failed Send notification to customer']
                            ]);
                    }
                }
            // }




            $checkMembership = app($this->membership)->calculateMembership($user['phone']);

        }
        DB::commit();


        return response()->json(MyHelper::checkUpdate($pickup));
    }

    public function listSchedule(Request $request) {
        $schedules = $request->user()->outlet_schedules()->get();
        return MyHelper::checkGet($schedules);
    }

    public function updateSchedule(Request $request) {
        $post = $request->json()->all();
        DB::beginTransaction();
        $id_outlet = $request->user()->id_outlet;
        foreach ($post['schedule'] as $value) {
            $save = OutletSchedule::updateOrCreate(['id_outlet' => $id_outlet, 'day' => $value['day']], $value);
            if (!$save) {
                DB::rollBack();
                return response()->json(['status' => 'fail']);
            }
        }
        DB::commit();
        return response()->json(['status' => 'success']);
    }

    public function history(Request $request) {
        $trx_date = $request->json('trx_date');
        $trx_status = $request->json('trx_status');
        $trx_type = $request->json('trx_type');
        $keyword = $request->json('search_order_id');
        $perpage = $request->json('perpage');
        $request_number = $request->json('request_number')?:'thousand';
        $data = Transaction::select(\DB::raw('transactions.id_transaction,order_id,DATE_FORMAT(transaction_date, "%Y-%m-%d") as trx_date,DATE_FORMAT(transaction_date, "%H:%i") as trx_time,transaction_receipt_number,count(*) as total_products,transaction_grandtotal'))
            ->where('trasaction_type','Pickup Order')
            ->join('transaction_pickups','transactions.id_transaction','=','transaction_pickups.id_transaction')
            ->whereDate('transaction_date',$trx_date)
            ->join('transaction_products','transaction_products.id_transaction','=','transactions.id_transaction')
            ->groupBy('transactions.id_transaction');

        if ($trx_status == 'taken') {
            $data->where(function($query){
                $query->whereNotNull('taken_at')
                    ->orWhereNotNull('taken_by_system_at');
            });
        }elseif($trx_status == 'rejected'){
            $data->whereNotNull('reject_at');
        }elseif($trx_status == 'unpaid'){
            $data->where('transaction_payment_status','Pending')
            ->whereNull('taken_at')
            ->whereNull('taken_by_system_at')
            ->whereNull('reject_at');
        }else{
            $data->where(function($query){
                $query->whereNotNull('taken_at')
                    ->orWhereNotNull('taken_by_system_at')
                    ->orWhereNotNull('reject_at');
            });
        }

        if($trx_type == 'Delivery'){
            $data->where('pickup_by','GO-SEND');
        }elseif($trx_type == 'Pickup Order'){
            $data->where('pickup_by','Customer');
        }

        if($keyword){
            $data->where('order_id','like',"%$keyword%");
        }

        if($request->page){
            $return = $data->paginate($perpage?:15)->toArray();
            if(!$return['data']){
                $return = [];
            }elseif($request_number){
                $return['data'] = array_map(function($var) use ($request_number){
                    $var['transaction_grandtotal'] = MyHelper::requestNumber($var['transaction_grandtotal'],$request_number);
                    return $var;
                },$return['data']);
            }
        }else{
            $return = $data->get()->toArray();
            $return = array_map(function($var) use ($request_number){
                $var['transaction_grandtotal'] = MyHelper::requestNumber($var['transaction_grandtotal'],$request_number);
                return $var;
            },$return);
        }
        return MyHelper::checkGet($return);
    }
}