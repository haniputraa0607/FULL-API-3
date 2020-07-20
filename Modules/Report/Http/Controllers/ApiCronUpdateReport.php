<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Models\User;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\MonthlyReportTrx;
use App\Http\Models\DailyReportTrx;
use App\Http\Models\MonthlyReportTrxMenu;
use App\Http\Models\DailyReportTrxMenu;
use App\Http\Models\DailyMembershipReport;
use App\Http\Models\MonthlyMembershipReport;

use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\TransactionPaymentBalance;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use App\Http\Models\TransactionPaymentOvo;
use App\Http\Models\TransactionPaymentOffline;

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

use App\Http\Models\DailyCustomerReportRegistration;
use App\Http\Models\MonthlyCustomerReportRegistration;

use Modules\Report\Http\Requests\UpdateTrxReport;

use App\Lib\MyHelper;
use Validator;
use DateTime;
use Hash;
use DB;
use Mail;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class ApiCronUpdateReport extends Controller
{
    
    function __construct() 
    {
		date_default_timezone_set('Asia/Jakarta');
		$this->month = '';
		$this->year = '';
    }

    public function cronUpdate(UpdateTrxReport $request)
    {
    	DB::beginTransaction();
    	$post = $request->json()->all();

    	// update daily report
    	$update = $this->updateTotalItemDaily($post['date_start'], $post['date_end']);

    	if(!$update)
    	{
    		DB::rollBack();
    	}
    	elseif(($update['status']??false) == 'success')
    	{	
    		// update monthly report
    		$update = $this->updateTotalItemMonthly($update);

    		if (!$update) {
    			DB::rollBack();
    		}
    		else{
	    		DB::commit();
	    	}
    	}

    	return MyHelper::checkUpdate($update);
    }

    public function updateTotalItemDaily($dateStart, $dateEnd)
    {
    	$trans = DB::select(DB::raw('
                SELECT transactions.id_outlet,
			    (CASE WHEN trasaction_type = \'Offline\' THEN CASE WHEN transactions.id_user IS NOT NULL THEN \'Offline Member\' ELSE \'Offline Non Member\' END ELSE \'Online\' END) AS trx_type,
                (select SUM(trans_p.trx_total_item)) as trx_total_item,
                (select DATE(transaction_date)) as trx_date
                FROM transactions 
                LEFT JOIN transaction_pickups ON transaction_pickups.id_transaction = transactions.id_transaction 
                LEFT JOIN (
                	select 
                    	transaction_products.id_transaction, SUM(transaction_products.transaction_product_qty) trx_total_item
                    	FROM transaction_products 
                    	GROUP BY transaction_products.id_transaction
                ) trans_p
                	ON (transactions.id_transaction = trans_p.id_transaction) 
                WHERE transaction_date BETWEEN "'. date('Y-m-d', strtotime($dateStart)) .' 00:00:00" 
                AND "'. date('Y-m-d', strtotime($dateEnd)) .' 23:59:59"
                AND transaction_payment_status = "Completed"
                AND transaction_pickups.reject_at IS NULL
                GROUP BY transactions.id_outlet,trx_type,trx_date
                ORDER BY trx_date ASC, transactions.id_outlet ASC
            '));

    	if ($trans) {
            $trans 		= json_decode(json_encode($trans), true);
            $month 		= $trans;
			$sum 		= array();
			$allMonth 	= [];
			$trxDate 	= [];

            foreach ($trans as $key => $value) {
            	$trx_date 	= date('Y-m-d', strtotime($value['trx_date']));
            	$trx_month 	= date('Y-m', strtotime($value['trx_date']));
				$sum[$trx_date]['trx_total_item'] = ($sum[$trx_date]['trx_total_item']??0) + $value['trx_total_item'];

				if (!in_array($trx_month, $allMonth)) {
					$trxDate[] = $trx_date;
				}

				$allMonth[] = $trx_month;
				unset($trans[$key]['trx_type']);

                $save = DailyReportTrx::whereDate('trx_date', $trx_date)
                		->where('id_outlet', $value['id_outlet'])
                		->update($trans[$key]);

                if (!$save) {
                    return false;
                }
            }

            foreach ($sum as $key => $value) {
            	$saveGlobal	= GlobalDailyReportTrx::whereDate('trx_date', $key)->update($value);

            	if (!$saveGlobal) {
                    return false;
                }
            }

	        return [
	        	'status'	=> 'success',
	        	'all_month'	=> $allMonth,
	        	'trx_date'	=> $trxDate
	        ];
        }

        return true;
    }

    public function updateTotalItemMonthly($month)
    {	
		foreach ($month['trx_date'] as $key => $value) {
			$trxMonth 	= date('n', strtotime($value));
			$trxYear 	= date('Y', strtotime($value));
			$trans = DB::select(DB::raw('
						SELECT transactions.id_outlet, 
						(select DATE(transaction_date)) as trx_date,
						(select SUM(trans_p.trx_total_item)) as trx_total_item
						FROM transactions 
						LEFT JOIN users ON users.id = transactions.id_user
						LEFT JOIN transaction_pickups ON transaction_pickups.id_transaction = transactions.id_transaction 
						LEFT JOIN (
		                	select 
		                    	transaction_products.id_transaction, SUM(transaction_products.transaction_product_qty) trx_total_item
		                    	FROM transaction_products 
		                    	GROUP BY transaction_products.id_transaction
		                ) trans_p
		                	ON (transactions.id_transaction = trans_p.id_transaction) 
		                WHERE MONTH(transactions.transaction_date) = "'. $trxMonth .'" 
                    	AND YEAR(transactions.transaction_date) ="'. $trxYear .'"
						AND transaction_payment_status = "Completed"
						AND transaction_pickups.reject_at IS NULL
						GROUP BY transactions.id_outlet ASC, YEAR(trx_date) ASC, MONTH(trx_date) ASC
					'));

			if ($trans) {
				$trans = json_decode(json_encode($trans), true);
				$sum = 0;
				
				foreach ($trans as $key2 => $value2) {

					$updateMonth 	= date('n', strtotime($value2['trx_date']));
					$updateYear 	= date('Y', strtotime($value2['trx_date']));
					$data 			= ['trx_total_item' => $value2['trx_total_item']];
					$sum 			= ($sum??0) + $value2['trx_total_item'];

					$save = MonthlyReportTrx::where('trx_month', $updateMonth)
							->where('trx_year', $updateYear)
							->where('id_outlet', $value2['id_outlet'])
							->update($data);

					if (!$save) {
						return false;
					}
				}

				$dataGLobal = ['trx_total_item' => $sum];
				$saveGlobal = GlobalMonthlyReportTrx::where('trx_month', $trxMonth)
							->where('trx_year', $trxYear)
							->update($dataGLobal);

				if (!$saveGlobal) {
					return false;
				}
			}
		}
		
        return true;
    }
}
