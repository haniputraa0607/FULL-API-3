<?php

namespace Modules\POS\Http\Controllers;

use Modules\SettingFraud\Entities\FraudDetectionLogTransactionDay;
use Modules\SettingFraud\Entities\FraudDetectionLogTransactionWeek;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Configs;
use App\Http\Models\Outlet;
use App\Http\Models\OutletToken;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionPickupGoSend;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\TransactionPaymentMidtran;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\TransactionPaymentOvo;
use App\Http\Models\TransactionPaymentOffline;
use App\Http\Models\Setting;
use App\Http\Models\User;
use App\Http\Models\Product;
use App\Http\Models\ProductCategory;
use App\Http\Models\ProductPrice;
use App\Http\Models\LogBalance;

use Modules\POS\Http\Requests\Order\listOrder;
use Modules\POS\Http\Requests\Order\detailOrder;
use Modules\POS\Http\Requests\Order\ProductSoldOut;

use App\Lib\Midtrans;
use App\Lib\MyHelper;
use DB;

class ApiOrder extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->balance  = "Modules\Balance\Http\Controllers\BalanceController";
        $this->getNotif = "Modules\Transaction\Http\Controllers\ApiNotification";
        $this->membership    = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->pos    = "Modules\POS\Http\Controllers\ApiPOS";
        $this->trx    = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
    }
    
    public function listOrder(listOrder $request){
        $post = $request->json()->all();
		
		$api = app($this->pos)->checkApi($post['api_key'], $post['api_secret']); 
        if ($api['status'] != 'success') { 
            return response()->json($api); 
        } 
 
        $outlet = Outlet::where('outlet_code', strtoupper($post['store_code']))->first(); 
        if(empty($outlet)){ 
            return response()->json(['status' => 'fail', 'messages' => ['Store not found']]); 
        } 

        $post = $request->json()->all();

        $list = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')->leftJoin('transaction_products', 'transactions.id_transaction', 'transaction_products.id_transaction')->leftJoin('users', 'users.id', 'transactions.id_user')
                            ->select('transactions.id_transaction', 'transaction_receipt_number', 'order_id', 'transaction_date', 'pickup_by', 'pickup_type', 'pickup_at', 'receive_at', 'ready_at', 'taken_at', 'reject_at', DB::raw('sum(transaction_product_qty) as total_item'), 'users.name')
                            ->where('transactions.id_outlet', $outlet->id_outlet)
                            ->whereDate('transaction_date', date('Y-m-d'))
                            ->where('transaction_payment_status', 'Completed')
                            ->where('trasaction_type', 'Pickup Order')
                            ->whereNull('void_date')
                            ->groupBy('transaction_products.id_transaction')
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

    public function detailOrder(detailOrder $request){
        $post = $request->json()->all();


        $api = app($this->pos)->checkApi($post['api_key'], $post['api_secret']); 
        if ($api['status'] != 'success') { 
            return response()->json($api); 
        } 
 
        $outlet = Outlet::where('outlet_code', strtoupper($post['store_code']))->first(); 
        if(empty($outlet)){ 
            return response()->json(['status' => 'fail', 'messages' => ['Store not found']]); 
        } 

        $list = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
                            ->where('id_outlet', $outlet['id_outlet'])
                            ->where('order_id', $post['order_id'])
                            ->whereDate('transaction_date', date('Y-m-d'))
                            ->with('user', 'productTransaction.product.product_category', 'productTransaction.product.product_discounts', 'outlet')->first();

        $qr     = $list['order_id'];

        // $qrCode = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data='.$qr;
        $qrCode = 'https://chart.googleapis.com/chart?chl='.$qr.'&chs=250x250&cht=qr&chld=H%7C0';
        $qrCode = html_entity_decode($qrCode);

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

        $expired = Setting::where('key', 'qrcode_expired')->first();
        if(!$expired || ($expired && $expired->value == null)){
            $expired = '10';
        }else{
            $expired = $expired->value;
        }

        $timestamp = strtotime('+'.$expired.' minutes');
        $memberUid = MyHelper::createQR($timestamp, $list['user']['phone']);
            
        $transactions = [];
        $transactions['member_uid'] = $memberUid;
        $transactions['trx_id_behave'] = $list['transaction_receipt_number'];
        $transactions['trx_date_time'] = $list['transaction_date'];
        $transactions['qrcode'] = $qrCode;
        $transactions['order_id'] = $list['order_id'];
        $transactions['pickup_status'] = $statusPickup;
        $transactions['process_at'] = $list['pickup_type'];
        $transactions['process_date_time'] = $list['pickup_at'];
        $transactions['accepted_date_time'] = $list['receive_at'];
        $transactions['ready_date_time'] = $list['ready_at'];
        $transactions['taken_date_time'] = $list['taken_at'];
        $transactions['total'] = $list['transaction_subtotal'];
        $transactions['sevice'] = $list['transaction_service'];
        $transactions['tax'] = $list['transaction_tax'];
        $transactions['discount'] = $list['transaction_discount'];
        $transactions['grand_total'] = $list['transaction_grandtotal'];
			
        $transactions['payments'] = [];

        //cek di multi payment
        $multi = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get();
        if(!$multi){
            //cek di balance
            $balance = TransactionPaymentBalance::where('id_transaction', $list['id_transaction'])->get();
            if($balance){
                foreach($balance as $payBalance){
                    $pay['payment_type'] = 'Points';
                    $pay['payment_nominal'] = (int)$payBalance['balance_nominal'];
                    $transactions['payments'][] = $pay;
                }
            }else{
                $midtrans = TransactionPaymentMidtran::where('id_transaction', $list['id_transaction'])->get();
                if($midtrans){
                    foreach($midtrans as $payMidtrans){
                        $pay['payment_type'] = 'Midtrans';
                        $pay['payment_nominal'] = (int)$payMidtrans['gross_amount'];
                        $transactions['payments'][] = $pay;
                    }
                }
            }
        }else{
            foreach($multi as $payMulti){
                if($payMulti['type'] == 'Balance'){
                    $balance = TransactionPaymentBalance::find($payMulti['id_payment']);
                    if($balance){
                        $pay['payment_type'] = 'Points';
                        $pay['payment_nominal'] = (int)$balance['balance_nominal'];
                        $transactions['payments'][] = $pay;
                    }
                }elseif($payMulti['type'] == 'Midtrans'){
                    $midtrans = TransactionPaymentmidtran::find($payMulti['id_payment']);
                    if($midtrans){
                        $pay['payment_type'] = 'Midtrans';
                        $pay['payment_nominal'] = (int)$midtrans['gross_amount'];
                        $transactions['payments'][] = $pay;
                    }
                }
            }
        }
			
        $transactions['menu'] = [];
        $transactions['tax'] = 0;
        $transactions['total'] = 0;
        foreach($list['products'] as $key => $menu){
            $val = [];
            $val['plu_id'] = $menu['product_code'];
            $val['name'] = $menu['product_name'];
            $val['price'] = (int)$menu['pivot']['transaction_product_price'];
            $val['qty'] = $menu['pivot']['transaction_product_qty'];
            $val['category'] = $menu['product_category_name'];
            if($menu['pivot']['transaction_product_note'] != null){
                $val['open_modifier'] = $menu['pivot']['transaction_product_note'];
            }
            $val['modifiers'] = $list['product_transaction'][$key]['modifiers'];
            
            array_push($transactions['menu'], $val);
            
            $transactions['tax'] = $transactions['tax']+($menu['pivot']['transaction_product_qty'] * $menu['pivot']['transaction_product_price_tax']);
            $transactions['total'] = $transactions['total']+($menu['pivot']['transaction_product_qty'] * $menu['pivot']['transaction_product_price_base']);
        }
        $transactions['tax'] = round($transactions['tax']);
        $transactions['total'] = round($transactions['total']);
				
        return response()->json(['status' => 'success', 'result' => $transactions]); 

    }

    public function acceptOrder(DetailOrder $request){
        $post = $request->json()->all();

        $api = app($this->pos)->checkApi($post['api_key'], $post['api_secret']); 
        if ($api['status'] != 'success') { 
            return response()->json($api); 
        } 
 
        $outlet = Outlet::where('outlet_code', strtoupper($post['store_code']))->first(); 
        if(empty($outlet)){ 
            return response()->json(['status' => 'fail', 'messages' => ['Store not found']]); 
        } 

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

        if($order->id_outlet != $outlet->id_outlet){
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
                "id_reference" => $order->transaction_receipt_number.','.$order->id_outlet, 
                'id_transaction' => $order->id_transaction, 
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

    public function SetReady(DetailOrder $request){
        $post = $request->json()->all();

        $api = app($this->pos)->checkApi($post['api_key'], $post['api_secret']); 
        if ($api['status'] != 'success') { 
            return response()->json($api); 
        } 
 
        $outlet = Outlet::where('outlet_code', strtoupper($post['store_code']))->first(); 
        if(empty($outlet)){ 
            return response()->json(['status' => 'fail', 'messages' => ['Store not found']]); 
        } 

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

        if($order->id_outlet != $outlet->id_outlet){
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
                "id_reference" => $order->transaction_receipt_number.','.$order->id_outlet,  
                'id_transaction' => $order->id_transaction, 
                "transaction_date" => $order->transaction_date
            ]);
            if($send != true){
                // DB::rollback();
                return response()->json([
                        'status' => 'fail',
                        'messages' => ['Failed Send notification to customer']
                    ]);
            }

            $newTrx = Transaction::with('user.memberships', 'outlet', 'productTransaction', 'transaction_vouchers')->where('id_transaction', $order->id_transaction)->first();
            $checkType = TransactionMultiplePayment::where('id_transaction', $order->id_transaction)->get()->toArray();
            $column = array_column($checkType, 'type');
            MyHelper::updateFlagTransactionOnline($newTrx, 'success', $newTrx->user);

            if (!in_array('Balance', $column)) {

            	$promo_source = null;
            	if ( $newTrx->id_promo_campaign_promo_code || $newTrx->transaction_vouchers ) 
            	{
            		if ( $newTrx->id_promo_campaign_promo_code ) {
            			$promo_source = 'promo_code';
            		}
            		elseif ( ($newTrx->transaction_vouchers[0]->status??false) == 'success' )
            		{
        				$promo_source = 'voucher_online';
            		}
            	}

				if( app($this->trx)->checkPromoGetPoint($promo_source) )
				{
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
            }
        
            $checkMembership = app($this->membership)->calculateMembership($user['phone']);
            
        }
        DB::commit();
        // return  $pickup = TransactionPickup::where('id_transaction', $order->id_transaction)->first();
        return response()->json(MyHelper::checkUpdate($pickup));
    }
    
    public function takenOrder(DetailOrder $request){
        $post = $request->json()->all();

        $api = app($this->pos)->checkApi($post['api_key'], $post['api_secret']); 
        if ($api['status'] != 'success') { 
            return response()->json($api); 
        } 
 
        $outlet = Outlet::where('outlet_code', strtoupper($post['store_code']))->first(); 

        if(empty($outlet)){ 
            return response()->json(['status' => 'fail', 'messages' => ['Store not found']]); 
        } 

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

        if($order->id_outlet != $outlet->id_outlet){
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
        $order->show_rate_popup = 1;
        $order->save();
        if($pickup){
            //send notif to customer
            $user = User::find($order->id_user);
            $send = app($this->autocrm)->SendAutoCRM('Order Taken', $user['phone'], [
                "outlet_name" => $outlet['outlet_name'], 
                "id_reference" => $order->transaction_receipt_number.','.$order->id_outlet, 
                'id_transaction' => $order->id_transaction, 
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

    public function productSoldOut(ProductSoldOut $request){
        $post = $request->json()->all();

        $api = app($this->pos)->checkApi($post['api_key'], $post['api_secret']); 
        if ($api['status'] != 'success') { 
            return response()->json($api); 
        } 
 
        $outlet = Outlet::where('outlet_code', strtoupper($post['store_code']))->first(); 

        if(empty($outlet)){ 
            return response()->json(['status' => 'fail', 'messages' => ['Store not found']]); 
        } 

        //get id product
        $cekProduct = Product::where('product_code', $post['plu_id'])->first();
        if(empty($cekProduct)){ 
            return response()->json(['status' => 'fail', 'messages' => ['Product not found']]); 
        }
        
        $product = ProductPrice::where('id_outlet', $outlet['id_outlet'])
                                ->where('id_product', $cekProduct->id_product)
                                ->update(['product_stock_status' => $post['product_stock_status']]);

        return response()->json(MyHelper::checkUpdate($product));
    }

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

        $uncategorized = ProductPrice::join('products', 'product_prices.id_product', 'products.id_product')
                                        ->whereIn('products.id_product', function($query){
                                            $query->select('id_product')->from('products')->whereNull('id_product_category');
                                        })->where('id_outlet', $outlet['id_outlet'])
                                        ->select('products.id_product', 'product_code', 'product_name', 'product_stock_status')->get();

        $result['categorized'] = $categorized;
        $result['uncategorized'] = $uncategorized;
        return response()->json(MyHelper::checkGet($result));
    }

    public function rejectOrder(DetailOrder $request){
        $post = $request->json()->all();

        $api = app($this->pos)->checkApi($post['api_key'], $post['api_secret']); 
        if ($api['status'] != 'success') { 
            return response()->json($api); 
        } 
 
        $outlet = Outlet::where('outlet_code', strtoupper($post['store_code']))->first(); 

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

        if($order->id_outlet != $outlet->id_outlet){
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
        $user = User::where('id', $order['id_user'])->first()->toArray();
        
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
                            if ($payOvo) {
                                if(Configs::select('is_active')->where('config_name','refund ovo')->pluck('is_active')->first()){
                                    $point = 0;
                                    $transaction = TransactionPaymentOvo::where('transaction_payment_ovos.id_transaction', $post['id_transaction'])
                                        ->join('transactions','transactions.id_transaction','=','transaction_payment_ovos.id_transaction')
                                        ->first();
                                    $refund = Ovo::Void($transaction);
                                    if ($refund['status_code'] != '200') {
                                        DB::rollback();
                                        return response()->json([
                                            'status'   => 'fail',
                                            'messages' => ['Refund Ovo Failed'],
                                        ]);
                                    }
                                }else{
                                    $refund = app($this->balance)->addLogBalance($order['id_user'], $point = $payOvo['amount'], $order['id_transaction'], 'Rejected Order Ovo', $order['transaction_grandtotal']);
                                    if ($refund == false) {
                                        DB::rollback();
                                        return response()->json([
                                            'status'   => 'fail',
                                            'messages' => ['Insert Cashback Failed'],
                                        ]);
                                    }
                                }
                            }
                        } elseif (strtolower($pay['type']) == 'ipay88') {
                            $payIpay = TransactionPaymentIpay88::find($pay['id_payment']);
                            if ($payIpay) {
                                $refund = app($this->balance)->addLogBalance($order['id_user'], $point = ($payIpay['amount']/100), $order['id_transaction'], 'Rejected Order', $order['transaction_grandtotal']);
                                if ($refund == false) {
                                    DB::rollback();
                                    return response()->json([
                                        'status'   => 'fail',
                                        'messages' => ['Insert Cashback Failed'],
                                    ]);
                                }
                            }
                        }
                        else{
                            $payMidtrans = TransactionPaymentMidtran::find($pay['id_payment']);
                            $point = 0;
                            if ($payMidtrans) {
                                if(MyHelper::setting('refund_midtrans')){
                                    $refund = Midtrans::refund($order['transaction_receipt_number'],['reason' => $post['reason']??'']);
                                    if ($refund['status'] != 'success') {
                                        DB::rollback();
                                        return response()->json($refund);
                                    }
                                } else {
                                    $refund = app($this->balance)->addLogBalance( $order['id_user'], $point = $payMidtrans['gross_amount'], $order['id_transaction'], 'Rejected Order Midtrans', $order['transaction_grandtotal']);
                                    if ($refund == false) {
                                        DB::rollback();
                                        return response()->json([
                                            'status'    => 'fail',
                                            'messages'  => ['Insert Cashback Failed']
                                        ]);
                                    }
                                }
                            }
                        }
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
                    }
                }else{
                    $payMidtrans = TransactionPaymentMidtran::where('id_transaction', $order['id_transaction'])->first();
                    $payOvo = TransactionPaymentOvo::where('id_transaction', $order['id_transaction'])->first();
                    $payIpay     = TransactionPaymentIpay88::where('id_transaction', $order['id_transaction'])->first();
                    if($payMidtrans){
                        $point = 0;
                        if(MyHelper::setting('refund_midtrans')){
                            $refund = Midtrans::refund($order['transaction_receipt_number'],['reason' => $post['reason']??'']);
                            if ($refund['status'] != 'success') {
                                DB::rollback();
                                return response()->json($refund);
                            }
                        } else {
                            $refund = app($this->balance)->addLogBalance( $order['id_user'], $point = $payMidtrans['gross_amount'], $order['id_transaction'], 'Rejected Order Midtrans', $order['transaction_grandtotal']);
                            if ($refund == false) {
                                DB::rollback();
                                return response()->json([
                                    'status'    => 'fail',
                                    'messages'  => ['Insert Cashback Failed']
                                ]);
                            }
                        }
                    }
                    elseif($payOvo){
                        if(Configs::select('is_active')->where('config_name','refund ovo')->pluck('is_active')->first()){
                            $point = 0;
                            $transaction = TransactionPaymentOvo::where('transaction_payment_ovos.id_transaction', $post['id_transaction'])
                                ->join('transactions','transactions.id_transaction','=','transaction_payment_ovos.id_transaction')
                                ->first();
                            $refund = Ovo::Void($transaction);
                            if ($refund['status_code'] != '200') {
                                DB::rollback();
                                return response()->json([
                                    'status'   => 'fail',
                                    'messages' => ['Refund Ovo Failed'],
                                ]);
                            }
                        }else{
                            $refund = app($this->balance)->addLogBalance($order['id_user'], $point = $payOvo['amount'], $order['id_transaction'], 'Rejected Order Ovo', $order['transaction_grandtotal']);
                            if ($refund == false) {
                                DB::rollback();
                                return response()->json([
                                    'status'   => 'fail',
                                    'messages' => ['Insert Cashback Failed'],
                                ]);
                            }
                        }
                    } elseif ($payIpay) {
                        $refund = app($this->balance)->addLogBalance($order['id_user'], $point = ($payIpay['amount']/100), $order['id_transaction'], 'Rejected Order', $order['transaction_grandtotal']);
                        if ($refund == false) {
                            DB::rollback();
                            return response()->json([
                                'status'   => 'fail',
                                'messages' => ['Insert Cashback Failed'],
                            ]);
                        }
                    }else{
                        $payBalance = TransactionPaymentBalance::where('id_transaction', $order['id_transaction'])->first();
                        if($payBalance){
                            $refund = app($this->balance)->addLogBalance( $order['id_user'], $payBalance['balance_nominal'], $order['id_transaction'], 'Rejected Order Point', $order['transaction_grandtotal']);
                            if ($refund == false) {
                                DB::rollback();
                                return response()->json([
                                    'status'    => 'fail',
                                    'messages'  => ['Insert Cashback Failed']
                                ]);
                            }
                        }
                    }
                    $send = app($this->autocrm)->SendAutoCRM('Rejected Order Point Refund', $user['phone'], 
                        [
                            "outlet_name"       => $outlet['outlet_name'], 
                            "transaction_date"  => $order->transaction_date,
                            'id_transaction'    => $order->id_transaction, 
                            'receipt_number'    => $order->transaction_receipt_number,
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
            // }

            //send notif to customer
            $user = User::where('id', $order['id_user'])->first()->toArray();
            $send = app($this->autocrm)->SendAutoCRM('Order Reject', $user['phone'], [
                "outlet_name" => $outlet['outlet_name'], 
                "id_reference" => $order->transaction_receipt_number.','.$order->id_outlet, 
                'id_transaction' => $order->id_transaction, 
                "transaction_date" => $order->transaction_date
            ]);
            if($send != true){
                DB::rollback();
                return response()->json([
                        'status' => 'fail',
                        'messages' => ['Failed Send notification to customer']
                    ]);
            }
            
            
            $checkMembership = app($this->membership)->calculateMembership($user['phone']);

        }
        DB::commit();


        return response()->json(MyHelper::checkUpdate($pickup));
    }

}
