<?php

namespace Modules\Franchise\Http\Controllers;

use App\Http\Models\Autocrm;
use App\Http\Models\Outlet;
use App\Http\Models\Transaction;
use App\Http\Models\DailyReportTrx;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Franchise\Entities\UserFranchise;
use App\Lib\MyHelper;
use Modules\Franchise\Entities\UserFranchiseOultet;
use Modules\Franchise\Http\Requests\users_create;
use Modules\Report\Entities\DailyReportPayment;
use DB;
use DateTime;

class ApiReportSalesController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function summary(Request $request)
    {
    	$post = $request->json()->all();
        if(!$request->id_outlet){
        	return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
        }

    	$report = Transaction::where('transactions.id_outlet', $request->id_outlet)
    				->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
					// ->whereNull('reject_at')
					->select(DB::raw('
						COUNT(CASE WHEN transactions.id_transaction AND transaction_pickups.reject_at IS NULL THEN 1 ELSE NULL END) AS total_transaction, 
						COUNT(CASE WHEN transaction_pickups.pickup_by = "Customer" AND transaction_pickups.reject_at IS NULL THEN 1 ELSE NULL END) as total_transaction_pickup,
						COUNT(CASE WHEN transaction_pickups.pickup_by = "GO-SEND" AND transaction_pickups.reject_at IS NULL THEN 1 ELSE NULL END) as total_transaction_delivery,
						SUM(CASE WHEN transactions.transaction_subtotal IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN transactions.transaction_subtotal ELSE 0 END) as total_subtotal,
						SUM(
							CASE WHEN transactions.transaction_discount IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN transactions.transaction_discount ELSE 0 END
							+ CASE WHEN transactions.transaction_discount_delivery IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN transactions.transaction_discount_delivery ELSE 0 END
							+ CASE WHEN transactions.transaction_discount_bill IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN transactions.transaction_discount_bill ELSE 0 END
						) as total_discount,
						SUM(CASE WHEN transaction_pickups.reject_at IS NULL THEN transactions.transaction_shipment_go_send + transactions.transaction_shipment ELSE 0 END) as total_delivery,
						SUM(CASE WHEN transaction_pickups.reject_at IS NULL THEN transactions.transaction_grandtotal ELSE 0 END) as total_grandtotal,
						COUNT(CASE WHEN transaction_pickups.receive_at IS NOT NULL THEN 1 ELSE NULL END) as total_accept,
						COUNT(CASE WHEN transaction_pickups.reject_at IS NOT NULL THEN 1 ELSE NULL END) as total_reject
					'));

        if(isset($post['filter_type']) && $post['filter_type'] == 'range_date'){
            $dateStart = date('Y-m-d', strtotime($post['date_start']));
            $dateEnd = date('Y-m-d', strtotime($post['date_end']));
            $report = $report->whereDate('transactions.transaction_date', '>=', $dateStart)->whereDate('transactions.transaction_date', '<=', $dateEnd);
        }elseif (isset($post['filter_type']) && $post['filter_type'] == 'today'){
            $currentDate = date('Y-m-d');
            $report = $report->whereDate('transactions.transaction_date', $currentDate);
        }

        $report = $report->first();

        if (!$report) {
        	return response()->json(['status' => 'fail', 'messages' => ['Empty']]);
        }

    	if ($report['total_accept']) {
    		$report['acceptance_rate'] = floor(( $report['total_accept'] / ($report['total_accept'] + $report['total_reject']) ) * 100);
    	}

    	if ($report['total_discount']) {
    		$report['total_discount'] = abs($report['total_discount']);
    	}

    	$result = [
    		[
                // 'title' => 'Total Transaction',
                'title' => 'Jumlah Transaksi',
                'amount' => number_format($report['total_transaction']??0,0,",",".")
            ],
            [
                // 'title' => 'Total Transaction Pickup',
                'title' => 'Jumlah Pickup',
                'amount' => number_format($report['total_transaction_pickup']??0,0,",",".")
            ],
            [
                // 'title' => 'Total Transaction Delivery',
                'title' => 'Jumlah Delivery',
                'amount' => number_format($report['total_transaction_delivery']??0,0,",",".")
            ],
            [
                // 'title' => 'Total Subtotal',
                'title' => 'Sub Total',
                'amount' => number_format($report['total_subtotal']??0,2,",",".")
            ],
            [
                // 'title' => 'Total Discount',
                'title' => 'Diskon',
                'amount' => number_format($report['total_discount']??0,2,",",".")
            ],
            [
                // 'title' => 'Total Delivery',
                'title' => 'Delivery',
                'amount' => number_format($report['total_delivery']??0,2,",",".")
            ],
            [
                // 'title' => 'Total Grandtotal',
                'title' => 'Grand Total',
                'amount' => number_format($report['total_grandtotal']??0,2,",",".")
            ],
            [
                // 'title' => 'Total Accept',
                'title' => 'Jumlah Accept',
                'amount' => number_format($report['total_accept']??0,0,",",".")
            ],
            [
                // 'title' => 'Total Reject',
                'title' => 'Jumlah Reject',
                'amount' => number_format($report['total_reject']??0,0,",",".")
            ],
            [
                // 'title' => 'Acceptance Rate',
                'title' => 'Acceptance Rate Order',
                'amount' => number_format($report['acceptance_rate']??0,0,",",".")
            ]
    	];

        return MyHelper::checkGet($result);
    }
}
