<?php

namespace Modules\OutletApp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Outlet;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionPickup;
use App\Http\Models\Setting;
use App\Http\Models\User;
use Modules\OutletApp\Http\Requests\DetailOrder;

use App\Lib\MyHelper;
use DB;

class ApiOutletApp extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }

    public function listOrder(Request $request){
        $post = $request->json()->all();
        $outlet = $request->user();

        $list = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
                            ->select('transactions.id_transaction', 'transaction_receipt_number', 'order_id', 'transaction_date', 'pickup_type', 'pickup_at', 'receive_at', 'ready_at', 'taken_at')
                            ->where('id_outlet', $outlet->id_outlet)
                            ->whereDate('transaction_date', date('Y-m-d'))
                            ->where('transaction_payment_status', 'Completed')
                            ->where('trasaction_type', 'Pickup Order')
                            ->whereNull('void_date')
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
                            
        $list = $list->get();

        //dikelompokkan sesuai status
        $listPending = [];
        $listOnGoingSet = [];
        $listOnGoingNow = [];
        $listOnGoingArrival = [];
        $listReady = [];
        $listCompleted = [];
        foreach($list as $i => $dataList){

            $qr     = $dataList['order_id'].strtotime($dataList['transaction_date']);
            $qrCode = 'https://chart.googleapis.com/chart?chl='.$qr.'&chs=250x250&cht=qr&chld=H%7C0';
            $qrCode = html_entity_decode($qrCode);

            $dataList = array_slice($dataList->toArray(), 0, 3, true) +
            array("order_id_qrcode" => $qrCode) +
            array_slice($dataList->toArray(), 3, count($dataList->toArray()) - 1, true) ;

            $dataList['order_id'] = strtoupper($dataList['order_id']);
            if($dataList['receive_at'] == null){
                $dataList['status']  = 'Pending';
                $listPending[] = $dataList;
            }elseif($dataList['receive_at'] != null && $dataList['ready_at'] == null){
                $dataList['status']  = 'Accepted';
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
        $result['on_going']['data']['right_now']['count'] = count($listOnGoingNow);
        $result['on_going']['data']['right_now']['data'] = $listOnGoingNow;
        $result['on_going']['data']['pickup_time']['count'] = count($listOnGoingSet);
        $result['on_going']['data']['pickup_time']['data'] = $listOnGoingSet;
        $result['on_going']['data']['at_arrival']['count'] = count($listOnGoingArrival);
        $result['on_going']['data']['at_arrival']['data'] = $listOnGoingArrival;

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

        if(isset($post['qrcode'])){
            $post['order_id'] = substr($post['qrcode'], 0, 4);

            if(strlen($post['qrcode']) != 14){
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['QRCode Is Not Valid']
                ]);
            }

            $timestamp = str_replace($post['order_id'],'',$post['qrcode']);
            if(date('Y-m-d', $timestamp) != date('Y-m-d')){
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Order ID Is Not Valid']
                ]);
            }

        }
        
        $list = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
                            ->where('order_id', $post['order_id'])
                            ->whereDate('transaction_date', date('Y-m-d'))
                            ->with('user.city.province', 'productTransaction.product.product_category', 'productTransaction.product.product_discounts')->first();

        if(!$list){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Data Order Not Found']
            ]);
        }

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

        if($order->receive_at != null){
            return response()->json([
                'status' => 'fail',
                'messages' => ['Order Has Been Received']
            ]);
        }

        $pickup = TransactionPickup::where('id_transaction', $order->id_transaction)->update(['receive_at' => date('Y-m-d H:i:s')]);

        if($pickup){
            //send notif to customer
            $user = User::find($order->id_user);
            $send = app($this->autocrm)->SendAutoCRM('Order Accepted', $user['phone'], ["outlet_name" => $outlet['outlet_name'], "id_reference" => $order->id_transaction, "transaction_date" => $order->transaction_date]);
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
        
        DB::beginTransaction();
        $pickup = TransactionPickup::where('id_transaction', $order->id_transaction)->update(['ready_at' => date('Y-m-d H:i:s')]);
        if($pickup){
            //send notif to customer
            $user = User::find($order->id_user);
            $send = app($this->autocrm)->SendAutoCRM('Order Ready', $user['phone'], ["outlet_name" => $outlet['outlet_name'], "id_reference" => $order->id_transaction,  "transaction_date" => $order->transaction_date]);
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
            $send = app($this->autocrm)->SendAutoCRM('Order Taken', $user['phone'], ["outlet_name" => $outlet['outlet_name'], "id_reference" => $order->id_transaction, "transaction_date" => $order->transaction_date]);
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

        return response()->json($profile);
    }

}
