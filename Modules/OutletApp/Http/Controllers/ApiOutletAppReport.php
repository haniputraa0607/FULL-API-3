<?php

namespace Modules\OutletApp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\Outlet;
use App\Http\Models\MonthlyReportTrx;
use App\Http\Models\DailyReportTrx;
use App\Http\Models\MonthlyReportTrxMenu;
use App\Http\Models\DailyReportTrxMenu;

use App\Http\Models\GlobalMonthlyReportTrx;
use App\Http\Models\GlobalDailyReportTrx;
use App\Http\Models\GlobalMonthlyReportTrxMenu;
use App\Http\Models\GlobalDailyReportTrxMenu;

use Modules\Report\Entities\DailyReportPayment;
use Modules\Report\Entities\GlobalDailyReportPayment;
use Modules\Report\Entities\MonthlyReportPayment;
use Modules\Report\Entities\GlobalMonthlyReportPayment;

use Modules\Report\Entities\DailyReportTrxModifier;
use Modules\Report\Entities\GlobalDailyReportTrxModifier;
use Modules\Report\Entities\MonthlyReportTrxModifier;
use Modules\Report\Entities\GlobalMonthlyReportTrxModifier;

use Modules\Brand\Entities\Brand;

use Modules\OutletApp\Http\Requests\ReportSummary;
use App\Lib\MyHelper;
use DB;

class ApiOutletAppReport extends Controller
{
	function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function summary(ReportSummary $request)
    {
    	$post = $request->json()->all();
    	$post['id_outlet'] = auth()->user()->id_outlet;

    	if ($post['date'] < date("Y-m-d"))
    	{
	    	$daily_trx = DailyReportTrx::whereDate('trx_date', '=', $post['date'])
	    				->where('id_outlet', '=', $post['id_outlet'])
	    				->with('outlet')
	    				->first();

	    	$daily_payment = DailyReportPayment::whereDate('trx_date', '=', $post['date'])
	    				->where('id_outlet', '=', $post['id_outlet'])
	    				->get();

	    	if ( !$daily_trx ) {
	    		return response()->json(MyHelper::checkGet(null));
	    	}

	    	if ($daily_trx) {
	    		$daily_trx 		= $daily_trx->toArray();
	    	}
	    	$daily_payment 	= $daily_payment->toArray();
    	}
    	elseif( $post['date'] == date("Y-m-d") )
    	{
    		$post['date'] = date("Y-m-d");
    		$outlet = Outlet::where('id_outlet','=',$post['id_outlet'])->first();

    		$daily_trx = DB::select(DB::raw('
                    SELECT transactions.id_outlet,
                    (select SUM(transaction_subtotal)) as trx_subtotal,
                    (select SUM(transaction_tax)) as trx_tax,
                    (select SUM(transaction_shipment)) as trx_shipment,
                    (select SUM(transaction_service)) as trx_service,
                    (select SUM(transaction_discount)) as trx_discount,
                    (select SUM(transaction_grandtotal)) as trx_grand,
                    (select SUM(transaction_point_earned)) as trx_point_earned,
                    (select SUM(transaction_cashback_earned)) as trx_cashback_earned,
                    (select TIME(MIN(transaction_date))) as first_trx_time,
                    (select TIME(MAX(transaction_date))) as last_trx_time,
                    (select count(DISTINCT transactions.id_transaction)) as trx_count,
                    (select AVG(transaction_grandtotal)) as trx_average,
                    (select SUM(transaction_products.transaction_product_qty)) as trx_total_item,
                    (select DATE(transaction_date)) as trx_date
                    FROM transactions
                    LEFT JOIN transaction_products ON transaction_products.id_transaction = transactions.id_transaction
                    LEFT JOIN transaction_pickups ON transaction_pickups.id_transaction = transactions.id_transaction
                    WHERE transaction_date BETWEEN "'. date('Y-m-d', strtotime($post['date'])) .' 00:00:00"
                    AND "'. date('Y-m-d', strtotime($post['date'])) .' 23:59:59"
                    AND transactions.id_outlet = "'.$post['id_outlet'].'"
                    AND transaction_payment_status = "Completed"
                    AND transaction_pickups.reject_at IS NULL
                    GROUP BY transactions.id_outlet
                '));

    		$daily_trx = json_decode(json_encode($daily_trx), true);

    		$getTransactions = Transaction::whereDate('transactions.created_at', $post['date'])
    			->where('id_outlet','=',$post['id_outlet'])
	            ->whereNotNull('transactions.id_user')
	            ->where('transactions.transaction_payment_status', 'Completed')
	            ->whereNull('transaction_pickups.reject_at')
	            ->groupBy('transactions.id_transaction', 'transactions.id_outlet')
	            ->select(
	            	'transactions.id_transaction',
	            	'transactions.id_outlet',
	            	'transactions.id_user',
	            	'transactions.transaction_date',
	            	'transactions.trasaction_payment_type'
	            )
	            ->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
	            ->get()->toArray();

	        $global = [];
	        foreach ($getTransactions as $dtTrx){
	            $total = 0;
	            $count = 0;
	            $getTransactionPayment = [];
	            $trx_payment = $dtTrx['trasaction_payment_type'];

	            if($dtTrx['trasaction_payment_type'] == 'Manual')
	            {
	                $getTransactionPayment = Transaction::join('transaction_payment_manuals', 'transaction_payment_manuals.id_transaction', 'transactions.id_transaction')
	                    ->where('transactions.id_transaction', $dtTrx['id_transaction'])
	                    ->select(
	                    	'transaction_payment_manuals.payment_method as payment_type',
	                    	'transaction_payment_manuals.payment_bank as payment',
	                    	'transaction_payment_manuals.payment_nominal as trx_payment_nominal'
	                    )->get()->toArray();
	            }
	            elseif($dtTrx['trasaction_payment_type'] == 'Midtrans')
	            {
	                $getTransactionPayment = Transaction::join('transaction_payment_midtrans', 'transaction_payment_midtrans.id_transaction', 'transactions.id_transaction')
	                    ->where('transactions.id_transaction', $dtTrx['id_transaction'])
	                    ->select(
	                    	'transaction_payment_midtrans.payment_type as payment_type',
	                    	'transaction_payment_midtrans.bank as payment',
	                    	'transaction_payment_midtrans.gross_amount as trx_payment_nominal'
	                    )->get()->toArray();
	            }
	            elseif($dtTrx['trasaction_payment_type'] == 'Offline')
	            {
	                $getTransactionPayment = Transaction::join('transaction_payment_offlines', 'transaction_payment_offlines.id_transaction', 'transactions.id_transaction')
	                    ->where('transactions.id_transaction', $dtTrx['id_transaction'])
	                    ->where('payment_amount', '!=', 0)
	                    ->select(
	                    	'transaction_payment_offlines.payment_type as payment_type',
	                    	'transaction_payment_offlines.payment_bank as payment',
	                    	'transaction_payment_offlines.payment_amount as trx_payment_nominal'
	                    )->get()->toArray();
	            }
	            elseif($dtTrx['trasaction_payment_type'] == 'Balance')
	            {
	                $getTransactionPayment = Transaction::join('transaction_payment_balances', 'transaction_payment_balances.id_transaction', 'transactions.id_transaction')
	                    ->where('transactions.id_transaction', $dtTrx['id_transaction'])
	                    ->where('balance_nominal', '!=', 0)
	                    ->select('transaction_payment_balances.balance_nominal AS trx_payment_nominal')->get()->toArray();

	                $trx_payment = 'Balance';
	            }
	            elseif($dtTrx['trasaction_payment_type'] == 'Ovo')
	            {
	                $getTransactionPayment = Transaction::join('transaction_payment_ovos', 'transaction_payment_ovos.id_transaction', 'transactions.id_transaction')
	                    ->where('transactions.id_transaction', $dtTrx['id_transaction'])
	                    ->where('amount', '!=', 0)
	                    ->select('transaction_payment_ovos.amount AS trx_payment_nominal')->get()->toArray();

	                $trx_payment = 'Ovo';
	            }

	            foreach ($getTransactionPayment as $dtPayment){

	            	if ( !empty($dtPayment['payment_type']) && !empty($dtPayment['payment']))
	            	{
	            		$trx_payment = $dtPayment['payment_type'].' '.$dtPayment['payment'];
	            	}
	            	else
	            	{
	            		$trx_payment = $dtPayment['payment_type']??$dtPayment['payment']??$trx_payment;
	            	}

	                $global_key = array_search($trx_payment, array_column($global, 'trx_payment'));

	                if ($global_key || $global_key === 0)
	                {
	                	$global[$global_key]['trx_payment_count'] = $global[$global_key]['trx_payment_count'] + 1;
	                	$global[$global_key]['trx_payment_nominal'] = $global[$global_key]['trx_payment_nominal'] + $dtPayment['trx_payment_nominal'];
	                }
	                else
	                {
	                	$new_global['trx_payment'] = $trx_payment;
	                	$new_global['trx_payment_count'] = 1;
	                	$new_global['trx_payment_nominal'] = $dtPayment['trx_payment_nominal'];
	                	array_push($global, $new_global);

		                $global_key = array_search($trx_payment, array_column($global, 'trx_payment'));
	                }

	            }

	        }

	        if ( empty($outlet) ) {
    			return response()->json(MyHelper::checkGet(null));
    		}
    		$outlet = $outlet->toArray();

    		if ($daily_trx) {
    			$daily_trx = $daily_trx[0];
    		}
    	}
    	else
    	{
    		return response()->json(MyHelper::checkGet(null));
    	}

    	$data_payment = [];
    	foreach ($daily_payment??$global as $key => $value)
    	{
    		$data_payment[$key]['trx_payment'] = $value['trx_payment'];
    		$data_payment[$key]['trx_payment_count'] = number_format($value['trx_payment_count'],0,",",".");
    		$data_payment[$key]['trx_payment_nominal'] = number_format($value['trx_payment_nominal'],0,",",".");
    	}

    	$data['outlet_name'] 	= $daily_trx['outlet']['outlet_name']??$outlet['outlet_name'];
    	$data['outlet_address'] = $daily_trx['outlet']['outlet_address']??$outlet['outlet_address'];
	    $data['transaction_date'] = date("d F Y", strtotime($post['date']));
	    $data['time_server'] 	= date("H:i");

    	if ($daily_trx) {
	    	$data['first_trx_time'] = date("H:i", strtotime($daily_trx['first_trx_time']));
	    	$data['last_trx_time'] 	= date("H:i", strtotime($daily_trx['last_trx_time']));
	    	$data['trx_grand']		= number_format($daily_trx['trx_grand'],0,",",".");
	    	$data['trx_count']		= number_format($daily_trx['trx_count'],0,",",".");
	    	$data['trx_total_item']	= number_format($daily_trx['trx_total_item'],0,",",".");
    	}else{
	    	$data['first_trx_time'] = "";
	    	$data['last_trx_time'] 	= "";
	    	$data['trx_grand']		= 0;
	    	$data['trx_count']		= 0;
	    	$data['trx_total_item']	= 0;
    	}
    	$data['payment']		= $data_payment;

    	return response()->json(MyHelper::checkGet($data));
    }

    public function transactionList(ReportSummary $request)
    {
    	$post = $request->json()->all();
    	$post['id_outlet'] = auth()->user()->id_outlet;
    	$outlet = Outlet::where('id_outlet','=',$post['id_outlet'])->first();

    	$trx = Transaction::whereDate('transaction_date', '=', $post['date'])
    				->where('id_outlet', '=', $post['id_outlet'])
    				->where('transaction_payment_status', '=', 'Completed')
    				->whereNull('transaction_pickups.reject_at')
    				->select(
    					'transactions.id_transaction',
    					'transaction_date',
    					'transaction_receipt_number',
    					'transaction_grandtotal'
    				)
    				->with([
    					'productTransaction' => function($q) {
    						$q->select(
    							'id_transaction_product',
    							'id_transaction',
    							DB::raw('SUM(transaction_product_qty) AS total_qty')
    						)->groupBy('id_transaction');
    					},
    					'transaction_pickup' => function($q) {
    						$q->select(
    							'id_transaction_pickup',
    							'id_transaction',
    							'order_id'
    						);
    					}
    				])
    				->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
    				->orderBy('transactions.transaction_date');

    	if ( empty($outlet) ) {
			return response()->json(MyHelper::checkGet(null));
		}
		$outlet = $outlet->toArray();

    	if ( empty($post['is_all']) ) {
    		$trx = $trx->paginate(10)->toArray();
    	}else{
    		$trx = $trx->get()->toArray();
    	}

    	if (empty($trx['data']??$trx) ) {
    		return response()->json(MyHelper::checkGet(null));
    	}

    	$data_trx = [];
    	foreach ($trx['data']??$trx as $key => $value) {

    		$data_trx[$key]['order_id'] = $value['transaction_pickup']['order_id'];
    		$data_trx[$key]['transaction_time'] = date("H:i", strtotime($value['transaction_date']));
    		$data_trx[$key]['transaction_receipt_number'] = $value['transaction_receipt_number'];
    		$data_trx[$key]['transaction_grandtotal'] = number_format($value['transaction_grandtotal'],0,",",".");
    		$data_trx[$key]['total_item'] = number_format($value['product_transaction'][0]['total_qty'],0,",",".");
    	}

    	$data['outlet_name'] = $outlet['outlet_name'];
    	$data['outlet_address'] = $outlet['outlet_address'];
    	$data['time_server'] 	= date("H:i");
    	if ( empty($post['is_all']) ) {
    		$trx['data'] = $data_trx;
    		$data['transaction'] = $trx;
    	}else{
			$data['transaction'] = $data_trx;
    	}

    	$result = MyHelper::checkGet($data);
    	return response()->json($result);

    }

    public function itemList(ReportSummary $request)
    {
    	$post = $request->json()->all();
    	$post['id_outlet'] = auth()->user()->id_outlet;

    	if ($post['date'] < date("Y-m-d"))
    	{
	    	$daily_trx_menu = DailyReportTrxMenu::whereDate('trx_date', '=', $post['date'])
				->where('id_outlet', '=', $post['id_outlet'])
				->select(
					'id_report_trx_menu',
					'product_name',
					'total_qty',
					'total_nominal',
					'total_product_discount'
				)
				->orderBy('total_qty','Desc');
	    }
	    elseif ($post['date'] == date("Y-m-d"))
	    {
	    	$post['date'] = date("Y-m-d");
	    	$date = date("Y-m-d");

	    	$daily_trx_menu = TransactionProduct::where('transaction_products.id_outlet', $post['id_outlet'])
	    				->whereBetween('transactions.transaction_date',[ date('Y-m-d', strtotime($date)).' 00:00:00', date('Y-m-d', strtotime($date)).' 23:59:59'] )
	    				// ->where('transactions.id_outlet','=',$post['id_outlet'])
	    				->where('transactions.transaction_payment_status','=','Completed')
	    				->select(
	    					DB::raw('(select SUM(transaction_products.transaction_product_qty)) as total_qty'),
	    					DB::raw('(select SUM(transaction_products.transaction_product_subtotal)) as total_nominal'),
	    					DB::raw('(select count(transaction_products.id_product)) as total_rec'),
	    					DB::raw('(select products.product_name) as product_name'),
	    					DB::raw('(SUM(transaction_products.transaction_product_discount)) as total_product_discount')
	    				)
	    				->Join('transactions','transaction_products.id_transaction', '=', 'transactions.id_transaction')
	    				->leftJoin('products','transaction_products.id_product', '=', 'products.id_product')
	    				->groupBy('transaction_products.id_product')
	    				->orderBy('total_qty', 'Desc');
	    }
	    else
    	{
    		return response()->json(MyHelper::checkGet(null));
    	}

	    if ( !empty($post['is_all']) )
    	{
    		$daily_trx_menu = $daily_trx_menu->get()->toArray();
    	}
    	elseif ( !empty($post['take']) )
    	{
    		$daily_trx_menu = $daily_trx_menu->take($post['take'])->get()->toArray();
    	}
    	else
    	{
    		$daily_trx_menu = $daily_trx_menu->paginate(10)->toArray();
    	}

    	if (empty($daily_trx_menu['data']??$daily_trx_menu) ) {
    		return response()->json(MyHelper::checkGet(null));
    	}

    	$data_item = [];
    	foreach ($daily_trx_menu['data']??$daily_trx_menu as $key => $value)
    	{
    		$data_item[$key]['product_name'] = $value['product_name'];
    		$data_item[$key]['total_qty'] = number_format($value['total_qty'],0,",",".");
    		$data_item[$key]['total_nominal'] = number_format($value['total_nominal'],0,",",".");
    		$data_item[$key]['total_product_discount'] = number_format($value['total_product_discount'],0,",",".");
    	}

    	if ( empty($post['is_all']) && empty($post['take']) ) {
    		$daily_trx_menu['data'] = $data_item;
    		$data = $daily_trx_menu;
    	}else{
			$data = $data_item;
    	}

    	// $data['time_server'] 	= date("H:i");
    	$result = MyHelper::checkGet($data);
    	$result['time_server'] 	= date("H:i");
    	return response()->json($result);
    }

    public function allItemList(ReportSummary $request)
    {
    	$post = $request->json()->all();
    	$post['id_outlet'] = auth()->user()->id_outlet;

    	$data = [
    		'product' 	=> null,
    		'modifier'	=> null,
    		'time_server'	=> date("H:i")
    	];

    	if ($request->product) {
    		$product = $this->brandItem($post['id_outlet'], $post['date']);
    		if ($product) {
    			$data['product'] = $product;
    		}
    	}
    	if ($request->modifier) {
    		$modifier = $this->brandModifier($post['id_outlet'], $post['date']);
    		if ($modifier) {
    			$data['modifier'] = $modifier;
    		}
    	}
		
		return response()->json(MyHelper::checkGet($data));
    }

    function brandItem($outlet,$date)
    {
    	if ($date < date("Y-m-d"))
    	{
			$item = Brand::whereHas('daily_report_trx_menus',function($q) use ($outlet,$date){
						$q->whereDate('trx_date', '=', $date)
						->where('id_outlet', '=', $outlet);
					})
					->with(['daily_report_trx_menus' => function($q) use ($outlet, $date){
						$q->select([
							'id_report_trx_menu',
							'id_brand',
							'id_outlet',
							'product_name',
							'total_qty',
							'total_nominal',
							'total_product_discount'
						])
						->whereDate('trx_date', '=', $date)
						->where('id_outlet', '=', $outlet)
						->orderBy('total_qty', 'Desc')
						->orderBy('total_nominal', 'Desc');	
					}])
					->get();
		}			
		elseif ($date == date("Y-m-d"))
	    {
	    	$date = date("Y-m-d");
	    	$now = date("Y-m-d");
	    	// $now = "2020-06-09";

	    	$item = Brand::whereHas('transaction_products', function($q) use ($now, $outlet){
	    				$q->where('transaction_products.id_outlet', $outlet)
	    				->whereBetween('transactions.transaction_date',[ date('Y-m-d', strtotime($now)).' 00:00:00', date('Y-m-d', strtotime($now)).' 23:59:59'] )
	    				->where('transactions.transaction_payment_status','=','Completed')
	    				->whereNull('transaction_pickups.reject_at')
	    				->select(
	    					DB::raw('(select SUM(transaction_products.transaction_product_qty)) as total_qty')
	    				)
	    				->Join('transactions','transaction_products.id_transaction', '=', 'transactions.id_transaction')
	    				->leftJoin('products','transaction_products.id_product', '=', 'products.id_product')
	    				->leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
	    				->groupBy('transaction_products.id_product', 'transaction_products.id_brand')
	    				->orderBy('total_qty', 'Desc');	
	    			})
	    			->with(['transaction_products' => function($q) use ($now, $outlet){
	    				$q->where('transaction_products.id_outlet', $outlet)
	    				->whereBetween('transactions.transaction_date',[ date('Y-m-d', strtotime($now)).' 00:00:00', date('Y-m-d', strtotime($now)).' 23:59:59'] )
	    				->where('transactions.transaction_payment_status','=','Completed')
	    				->whereNull('transaction_pickups.reject_at')
	    				->select(
	    					DB::raw('(select transactions.id_outlet) as id_outlet'),
	    					DB::raw('(select transaction_products.id_brand) as id_brand'),
	    					DB::raw('(select transaction_products.id_transaction_product) as id_transaction_product'),
	    					DB::raw('(select SUM(transaction_products.transaction_product_qty)) as total_qty'),
	    					DB::raw('(select SUM(transaction_products.transaction_product_subtotal)) as total_nominal'),
	    					DB::raw('(select count(transaction_products.id_product)) as total_rec'),
	    					DB::raw('(select products.product_name) as product_name'),
	    					DB::raw('(SUM(transaction_products.transaction_product_discount)) as total_product_discount')
	    				)
	    				->Join('transactions','transaction_products.id_transaction', '=', 'transactions.id_transaction')
	    				->leftJoin('products','transaction_products.id_product', '=', 'products.id_product')
	    				->leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
	    				->groupBy('transaction_products.id_product','transaction_products.id_brand')
	    				->orderBy('total_qty', 'Desc')
	    				->orderBy('total_nominal', 'Desc');	
	    			}])
	    			->get();
	    }
	    else
    	{
    		return null;
    	}

    	$item = $item->toArray();

    	$data = [];
    	foreach ($item as $key => $value) {
    		$data_temp = [
    			'id_brand' => $value['id_brand'],
    			'name_brand' => $value['name_brand']
    		];

    		$data_temp['product'] = [];
    		foreach ($value['transaction_products']??$value['daily_report_trx_menus']??[] as $key2 => $value2) {
    			$data_temp['product'][] = [
    				'product_name' 	=> $value2['product_name'],
    				'total_qty' 	=> (string) $value2['total_qty'],
    				'total_nominal' => (string) $value2['total_nominal'],
    				'total_product_discount' => (string) $value2['total_product_discount'],
    			];
    		}

    		$data[] = $data_temp;
    	}

    	return $data;
    }

	function brandModifier($outlet,$date)
    {
    	if ($date < date("Y-m-d"))
    	{
			$item = Brand::whereHas('daily_report_trx_modifiers',function($q) use ($outlet,$date){
						$q->whereDate('trx_date', '=', $date)
						->where('id_outlet', '=', $outlet);
					})
					->with(['daily_report_trx_modifiers' => function($q) use ($outlet, $date){
						$q->select([
							'id_report_trx_modifier',
							'id_outlet',
							'id_brand',
							'text',
							'total_qty',
							'total_nominal',
							'total_rec'
						])
						->whereDate('trx_date', '=', $date)
						->where('id_outlet', '=', $outlet)
						->orderBy('total_qty', 'Desc')
						->orderBy('total_nominal', 'Desc');	
					}])
					->get();
		}			
		elseif ($date == date("Y-m-d"))
	    {
	    	$date = date("Y-m-d");
	    	$now = date("Y-m-d");
	    	// $now = "2020-06-09";

	    	$item = Brand::whereHas('transaction_products', function($q) use ($now, $outlet){
	    				$q->where('transaction_product_modifiers.id_outlet', $outlet)
	    				->whereBetween('transactions.transaction_date',[ date('Y-m-d', strtotime($now)).' 00:00:00', date('Y-m-d', strtotime($now)).' 23:59:59'] )
	    				->where('transactions.transaction_payment_status','=','Completed')
	    				->whereNull('transaction_pickups.reject_at')
	    				->select(
	    					DB::raw('(select SUM(transaction_product_modifiers.qty)) as total_qty')
	    				)
	    				->Join('transactions','transaction_products.id_transaction', '=', 'transactions.id_transaction')
	    				->Join('transaction_product_modifiers','transaction_product_modifiers.id_transaction_product', '=', 'transaction_products.id_transaction_product')
	    				->leftJoin('product_modifiers','transaction_product_modifiers.id_product_modifier', '=', 'product_modifiers.id_product_modifier')
	    				->leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
	    				->groupBy('transaction_product_modifiers.id_product_modifier','transaction_products.id_brand')
	    				->orderBy('total_qty', 'Desc');	
	    			})
	    			->with(['transaction_products' => function($q) use ($now, $outlet){
	    				$q->where('transaction_product_modifiers.id_outlet', $outlet)
	    				->whereBetween('transactions.transaction_date',[ date('Y-m-d', strtotime($now)).' 00:00:00', date('Y-m-d', strtotime($now)).' 23:59:59'] )
	    				->where('transactions.transaction_payment_status','=','Completed')
	    				->whereNull('transaction_pickups.reject_at')
	    				->select(
	    					DB::raw('(select transactions.id_outlet) as id_outlet'),
	    					DB::raw('(select transaction_products.id_brand) as id_brand'),
	    					DB::raw('(select product_modifiers.text) as text'),
	    					DB::raw('(select SUM(transaction_product_modifiers.qty)) as total_qty'),
	    					DB::raw('(select SUM(transaction_product_modifiers.transaction_product_modifier_price)) as total_nominal'),
	    					DB::raw('(select count(transaction_product_modifiers.id_product_modifier)) as total_rec')
	    				)
	    				->Join('transactions','transaction_products.id_transaction', '=', 'transactions.id_transaction')
	    				->Join('transaction_product_modifiers','transaction_product_modifiers.id_transaction_product', '=', 'transaction_products.id_transaction_product')
	    				->leftJoin('product_modifiers','transaction_product_modifiers.id_product_modifier', '=', 'product_modifiers.id_product_modifier')
	    				->leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
	    				->groupBy('transaction_product_modifiers.id_product_modifier','transaction_products.id_brand')
	    				->orderBy('total_qty', 'Desc')
	    				->orderBy('total_nominal', 'Desc');	
	    			}])
	    			->get();
	    }
	    else
    	{
    		return null;
    	}

    	$item = $item->toArray();

    	$data = [];
    	foreach ($item as $key => $value) {
    		$data_temp = [
    			'id_brand' => $value['id_brand'],
    			'name_brand' => $value['name_brand']
    		];

    		$data_temp['modifier'] = [];
    		foreach ($value['transaction_products']??$value['daily_report_trx_modifiers']??[] as $key2 => $value2) {
    			$data_temp['modifier'][] = [
    				'modifier_name' => $value2['text'],
    				'total_qty' 	=> (string) $value2['total_qty'],
    				'total_nominal' => (string) $value2['total_nominal']
    			];
    		}

    		$data[] = $data_temp;
    	}

    	return $data;
    }
}
