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

    	$daily_trx = DailyReportTrx::whereDate('trx_date', '=', $post['date'])
    				->where('id_outlet', '=', $post['id_outlet'])
    				->with('outlet')
    				->first();

    	$daily_payment = DailyReportPayment::whereDate('trx_date', '=', $post['date'])
    				->where('id_outlet', '=', $post['id_outlet'])
    				->get();

    	if ( !$daily_trx || !$daily_payment) {
    		return response()->json(MyHelper::checkGet(null));
    	}

    	$daily_trx 		= $daily_trx->toArray();
    	$daily_payment 	= $daily_payment->toArray();

    	$data_payment = [];
    	foreach ($daily_payment as $key => $value) 
    	{
    		$data_payment[$key]['trx_payment'] = $value['trx_payment'];
    		$data_payment[$key]['trx_payment_count'] = number_format($value['trx_payment_count'],0,",",".");
    		$data_payment[$key]['trx_payment_nominal'] = number_format($value['trx_payment_nominal'],0,",",".");
    	}

    	$data['outlet_name'] 	= $daily_trx['outlet']['outlet_name'];
    	$data['outlet_address'] = $daily_trx['outlet']['outlet_address'];
    	$data['transaction_date'] = date("d F Y", strtotime($daily_trx['trx_date']));
    	$data['first_trx_time'] = date("H:i", strtotime($daily_trx['first_trx_time']));
    	$data['last_trx_time'] 	= date("H:i", strtotime($daily_trx['last_trx_time']));
    	$data['time_server'] 	= date("H:i");
    	$data['trx_grand']		= number_format($daily_trx['trx_grand'],0,",",".");
    	$data['trx_count']		= number_format($daily_trx['trx_count'],0,",",".");
    	$data['trx_total_item']	= number_format($daily_trx['trx_total_item'],0,",",".");
    	$data['payment']		= $data_payment;

    	return response()->json(MyHelper::checkGet($data));
    }

    public function transactionList(ReportSummary $request)
    {
    	$post = $request->json()->all();

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
    				->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction');

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

    	if ( empty($post['is_all']) ) {
    		$trx['data'] = $data_trx;
    		$data = $trx;
    	}else{
			$data = $data_trx;
    	}

    	$result = MyHelper::checkGet($data);
    	$result['time_server'] 	= date("H:i");
    	return response()->json($result);

    }

    public function itemList(ReportSummary $request)
    {
    	$post = $request->json()->all();

    	$daily_trx_menu = DailyReportTrxMenu::whereDate('trx_date', '=', $post['date'])
			->where('id_outlet', '=', $post['id_outlet'])
			->select('id_report_trx_menu','product_name','total_qty')
			->orderBy('total_qty','Desc');

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
}
