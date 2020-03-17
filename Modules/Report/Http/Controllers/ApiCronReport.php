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

use App\Http\Models\GlobalMonthlyReportTrx;
use App\Http\Models\GlobalDailyReportTrx;
use App\Http\Models\GlobalMonthlyReportTrxMenu;
use App\Http\Models\GlobalDailyReportTrxMenu;

use Modules\Report\Entities\DailyReportPayment;
use Modules\Report\Entities\GlobalDailyReportPayment;
use Modules\Report\Entities\MonthlyReportPayment;
use Modules\Report\Entities\GlobalMonthlyReportPayment;

use App\Http\Models\DailyCustomerReportRegistration;
use App\Http\Models\MonthlyCustomerReportRegistration;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\Report\Http\Requests\DetailReport;

use App\Lib\MyHelper;
use Validator;
use DateTime;
use Hash;
use DB;
use Mail;


class ApiCronReport extends Controller
{
    function __construct() 
    {
        date_default_timezone_set('Asia/Jakarta');
    }
	
    /* CRON */
    function transactionCron(Request $request) 
    {
        DB::beginTransaction();
        // CHECK TABLES
        // if ($this->checkReportTable()) {
        //     $date = date('Y-m-d', strtotime("-1 days"));

        //     // CALCULATION
        //     $calculation = $this->calculation($date);

        //     if (!$calculation) {
        //         return response()->json([
        //             'status'   => 'fail',
        //             'messages' => 'Failed to update data.'
        //         ]);
        //     }
        // }
        // else {
            // DATE START
            $dateStart = $this->firstTrx();
            // $dateStart = "2019-01-01";

            if ($dateStart) {
                // UP TO YESTERDAY
                while (strtotime($dateStart) < strtotime(date('Y-m-d'))) {
                    // CALCULATION
                    $calculation = $this->calculation($dateStart);

                    if (!$calculation) {
                        DB::rollback();
                        return response()->json([
                            'status'   => 'fail',
                            'messages' => 'Failed to update data.'
                        ]);
                    }

                    // INCREMENT
                    $dateStart = date('Y-m-d', strtotime("+1 days", strtotime($dateStart)));
                }
            }
            else {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => 'Data transaction is empty.'
                ]);
            }
        // }

        DB::commit();
        // RETURN SUCCESS
        return response()->json([
            'status' => 'success'
        ]);
    }

    /* CHECK TABLES */
    function checkReportTable() 
    {
        $table = DailyReportTrx::count();

        if ($table > 1) {
            return true;
        }

        return false;
    }

    /* FIRST TRX */
    function firstTrx() 
    {
        // CEK TABEL REPORT
        if ($this->checkReportTable()) {
            $lastDate = DailyReportTrx::orderBy('trx_date', 'DESC')->first();

            if ($lastDate) {
                return $lastDate->trx_date;
            }
        }
        else {
            $firstTrx = Transaction::orderBy('transaction_date', 'ASC')->first();

            if (!empty($firstTrx)) {
                return $firstTrx->transaction_date;
            }
        }

        return false;
    }

    /* CALCULATION */
    function calculation($date) 
    {
        // TRANSACTION & PRODUCT DAILY
        $daily = $this->newDailyReport($date);

        if (!$daily) {
            return false;
        }
        if (!is_bool($daily)) {
            $daily = array_column($daily, 'id_outlet'); 

            // PRODUCT
            if (!$this->dailyReportProduct($daily, $date)) {
                return false;
            }
            // PAYMENT
            if (!$this->dailyReportPayment($date)) {
                return false;
            }
        }
		
		// TRANSACTION & PRODUCT MONTHLY
        $monthly = $this->newMonthlyReport($date);
        if (!$monthly) {
            return false;
        }
        if (!is_bool($monthly)) {
            $monthly = array_column($monthly, 'id_outlet'); 
            // PRODUCT
            if (!$this->monthlyReportProduct($monthly, $date)) {
                return false;
            }

			if (!$this->monthlyReportPayment($date)) {
                return false;
            }
        }
		
		// MEMBERSHIP REGISTRATION DAILY
        $daily = $this->newCustomerRegistrationDailyReport($date);
        if (!$daily) {
            return false;
        }
		
		// MEMBERSHIP REGISTRATION MONTHLY
        $monthly = $this->newCustomerRegistrationMonthlyReport($date);
        if (!$monthly) {
            return false;
        }
		
		// MEMBERSHIP LEVEL DAILY
        $daily = $this->customerLevelDailyReport($date);
        if (!$daily) {
            return false;
        }
		
		// MEMBERSHIP LEVEL MONTHLY
        $monthly = $this->customerLevelMonthlyReport($date);
        if (!$monthly) {
            return false;
        }
		
        return true;
    }

    /* MAX TRX */
    function maxTrans($date, $id_outlet) 
    {
        $dateStart = date('Y-m-d 00:00:00', strtotime($date));
        $dateEnd   = date('Y-m-d 23:59:59', strtotime($date));

        $trans = Transaction::whereBetween('transaction_date', [$dateStart, $dateEnd])
                ->where('transaction_payment_status', 'Completed')
                ->where('id_outlet', $id_outlet)
                ->orderBy('transaction_grandtotal', 'DESC')
                ->first();

        if ($trans) {
            return json_encode($trans);
        }

        return null;        
    }

    /* TRX TIME */
    function trxTime($time1, $time2, $time_type=null) 
    {
    	$str_time1 = strtotime($time1);
    	$str_time2 = strtotime($time2);
    	
    	if ($time_type == 'first') 
    	{
	    	if ($str_time1 < $str_time2) {
	    		$time = $str_time1;
	    	}else{
	    		$time = $str_time2;
	    	}
    	}elseif($time_type == 'last') 
    	{
    		if ($str_time1 > $str_time2) {
	    		$time = $str_time1;
	    	}else{
	    		$time = $str_time2;
	    	}
    	}else{
    		$time = $str_time1;
    	}

        return date('H:i:s',$time);
    }
	
	/* CUSTOMER LEVEL DAILY REPORT */
    function customerLevelDailyReport($date) 
    {
		// $date = date('Y-m-d', strtotime("-7 days", strtotime($date)));
		
		$now = time(); // or your date as well
		$your_date = strtotime($date);
		$datediff = $now - $your_date;

		$diff = round($datediff / (60 * 60 * 24));
		
		for($x = 0;$x <= $diff; $x++){
			$start = date('Y-m-d', strtotime("+ ".$x." days", strtotime($date)));
			
			$trans = DB::select(DB::raw('
					SELECT COUNT(id), id_membership,
					(select COUNT(users.id)) as cust_total,
					(select SUM(Case When users.gender = \'Male\' Then 1 Else 0 End)) as cust_male, 
					(select SUM(Case When users.gender = \'Female\' Then 1 Else 0 End)) as cust_female, 
					(select SUM(Case When users.android_device is not null Then 1 Else 0 End)) as cust_android, 
					(select SUM(Case When users.ios_device is not null Then 1 Else 0 End)) as cust_ios, 
					(select SUM(Case When users.provider = \'Telkomsel\' Then 1 Else 0 End)) as cust_telkomsel, 
					(select SUM(Case When users.provider = \'XL\' Then 1 Else 0 End)) as cust_xl, 
					(select SUM(Case When users.provider = \'Indosat\' Then 1 Else 0 End)) as cust_indosat, 
					(select SUM(Case When users.provider = \'Tri\' Then 1 Else 0 End)) as cust_tri, 
					(select SUM(Case When users.provider = \'Axis\' Then 1 Else 0 End)) as cust_axis, 
					(select SUM(Case When users.provider = \'Smart\' Then 1 Else 0 End)) as cust_smart, 
					(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 11 && floor(datediff (now(), users.birthday)/365) <= 17 Then 1 Else 0 End)) as cust_teens, 
					(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 18 && floor(datediff (now(), users.birthday)/365) <= 24 Then 1 Else 0 End)) as cust_young_adult, 
					(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 25 && floor(datediff (now(), users.birthday)/365) <= 34 Then 1 Else 0 End)) as cust_adult,
					(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 35 && floor(datediff (now(), users.birthday)/365) <= 100 Then 1 Else 0 End)) as cust_old,
					(select DATE(created_at)) as mem_date
					FROM users 
					WHERE users.created_at BETWEEN "'. $start .' 00:00:00" AND "'. $start .' 23:59:59"
					GROUP BY users.id_membership
				'));
			$trans = json_decode(json_encode($trans), true);
			
			if(!empty($trans[0]['cust_total'])){
				foreach ($trans as $key => $value) {
					$save = DailyMembershipReport::updateOrCreate([
							'mem_date'  => $start,
							'id_membership' => $value['id_membership']
						], $value);

					if (!$save) {
						return false;
					}
				}
				return $trans;
			}
		}
		return true;
	}
	
	/* CUSTOMER LEVEL MONTHLY REPORT */
    function customerLevelMonthlyReport($date) 
    {
		$d1 = new DateTime($date);
		$d2 = new DateTime(date('Y-m-d'));
		$interval = date_diff($d1, $d2);
		$diff = $interval->m + ($interval->y * 12);
		
		for($x = 0;$x <= $diff; $x++){
			$start = date('Y-m-1', strtotime("+ ".$x." month", strtotime($date)));
			if($x != $diff){
				$end = date('Y-m-t', strtotime("+ ".$x." month", strtotime($date)));
			} else {
				$end = date('Y-m-d');
			}
			
			$trans = DB::select(DB::raw('
					SELECT COUNT(id), id_membership,
					(select COUNT(users.id)) as cust_total,
					(select SUM(Case When users.gender = \'Male\' Then 1 Else 0 End)) as cust_male, 
					(select SUM(Case When users.gender = \'Female\' Then 1 Else 0 End)) as cust_female, 
					(select SUM(Case When users.android_device is not null Then 1 Else 0 End)) as cust_android, 
					(select SUM(Case When users.ios_device is not null Then 1 Else 0 End)) as cust_ios, 
					(select SUM(Case When users.provider = \'Telkomsel\' Then 1 Else 0 End)) as cust_telkomsel, 
					(select SUM(Case When users.provider = \'XL\' Then 1 Else 0 End)) as cust_xl, 
					(select SUM(Case When users.provider = \'Indosat\' Then 1 Else 0 End)) as cust_indosat, 
					(select SUM(Case When users.provider = \'Tri\' Then 1 Else 0 End)) as cust_tri, 
					(select SUM(Case When users.provider = \'Axis\' Then 1 Else 0 End)) as cust_axis, 
					(select SUM(Case When users.provider = \'Smart\' Then 1 Else 0 End)) as cust_smart, 
					(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 11 && floor(datediff (now(), users.birthday)/365) <= 17 Then 1 Else 0 End)) as cust_teens, 
					(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 18 && floor(datediff (now(), users.birthday)/365) <= 24 Then 1 Else 0 End)) as cust_young_adult, 
					(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 25 && floor(datediff (now(), users.birthday)/365) <= 34 Then 1 Else 0 End)) as cust_adult,
					(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 35 && floor(datediff (now(), users.birthday)/365) <= 100 Then 1 Else 0 End)) as cust_old,
					(select DATE(created_at)) as mem_date
					FROM users 
					WHERE users.created_at BETWEEN "'. $start .' 00:00:00" AND "'. $end .' 23:59:59"
					GROUP BY users.id_membership
				'));
			$trans = json_decode(json_encode($trans), true);
			
			if(!empty($trans[0]['cust_total'])){
				foreach ($trans as $key => $value) {
					$value['mem_month'] = date('n', strtotime($end));
					$value['mem_year'] = date('Y', strtotime($end));
					$save = MonthlyMembershipReport::updateOrCreate([
							'mem_month'  => date('n', strtotime($end)),
							'mem_year'  => date('Y', strtotime($end)),
							'id_membership' => $value['id_membership']
						], $value);

					if (!$save) {
						return false;
					}
				}
				return $trans;
			}
		}
		return true;
	}
	
	/* NEW CUSTOMER REGISTRATION DAILY REPORT */
    function newCustomerRegistrationDailyReport($date) 
    {
		$date = date('Y-m-d', strtotime("-7 days", strtotime($date)));
		
		$now = time(); // or your date as well
		$your_date = strtotime($date);
		$datediff = $now - $your_date;

		$diff = round($datediff / (60 * 60 * 24));
		
		for($x = 0;$x <= $diff; $x++){
			$start = date('Y-m-d', strtotime("+ ".$x." days", strtotime($date)));
			
			$trans = DB::select(DB::raw('
					SELECT (select COUNT(users.id)) as total,
					(select SUM(Case When users.gender = \'Male\' Then 1 Else 0 End)) as cust_male, 
					(select SUM(Case When users.gender = \'Female\' Then 1 Else 0 End)) as cust_female, 
					(select SUM(Case When users.android_device is not null Then 1 Else 0 End)) as cust_android, 
					(select SUM(Case When users.ios_device is not null Then 1 Else 0 End)) as cust_ios, 
					(select SUM(Case When users.provider = \'Telkomsel\' Then 1 Else 0 End)) as cust_telkomsel, 
					(select SUM(Case When users.provider = \'XL\' Then 1 Else 0 End)) as cust_xl, 
					(select SUM(Case When users.provider = \'Indosat\' Then 1 Else 0 End)) as cust_indosat, 
					(select SUM(Case When users.provider = \'Tri\' Then 1 Else 0 End)) as cust_tri, 
					(select SUM(Case When users.provider = \'Axis\' Then 1 Else 0 End)) as cust_axis, 
					(select SUM(Case When users.provider = \'Smart\' Then 1 Else 0 End)) as cust_smart, 
					(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 11 && floor(datediff (now(), users.birthday)/365) <= 17 Then 1 Else 0 End)) as cust_teens, 
					(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 18 && floor(datediff (now(), users.birthday)/365) <= 24 Then 1 Else 0 End)) as cust_young_adult, 
					(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 25 && floor(datediff (now(), users.birthday)/365) <= 34 Then 1 Else 0 End)) as cust_adult,
					(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 35 && floor(datediff (now(), users.birthday)/365) <= 100 Then 1 Else 0 End)) as cust_old,
					(select DATE(created_at)) as reg_date
					FROM users
					WHERE created_at BETWEEN "'. $start .' 00:00:00" 
					AND "'. $start .' 23:59:59"
				'));
			$trans = json_decode(json_encode($trans), true);
			if(!empty($trans[0]['reg_date'])){
				foreach ($trans as $key => $value) {
					$save = DailyCustomerReportRegistration::updateOrCreate([
							'reg_date'  => $start
						], $value);

					if (!$save) {
						return false;
					}
				}
				return $trans;
			}
		}
		return true;
	}
	
	/* NEW CUSTOMER REGISTRATION MONTHLY REPORT */
    function newCustomerRegistrationMonthlyReport($date) 
    {
		$date = date('Y-m-d', strtotime("-1 month", strtotime($date)));
		
		$d1 = new DateTime($date);
		$d2 = new DateTime(date('Y-m-d'));
		$interval = date_diff($d1, $d2);
		$diff = $interval->m + ($interval->y * 12);
		// print_r(['diff' => $diff]);exit;
		for($x = 0;$x <= $diff; $x++){
			$start = date('Y-m-1', strtotime("+ ".$x." month", strtotime($date)));
			if($x != $diff){
				$end = date('Y-m-t', strtotime("+ ".$x." month", strtotime($date)));
			} else {
				$end = date('Y-m-d');
			}
			
			$trans = DB::select(DB::raw('
					SELECT (select COUNT(users.id)) as total,
					(select SUM(Case When users.gender = \'Male\' Then 1 Else 0 End)) as cust_male, 
					(select SUM(Case When users.gender = \'Female\' Then 1 Else 0 End)) as cust_female, 
					(select SUM(Case When users.android_device is not null Then 1 Else 0 End)) as cust_android, 
					(select SUM(Case When users.ios_device is not null Then 1 Else 0 End)) as cust_ios, 
					(select SUM(Case When users.provider = \'Telkomsel\' Then 1 Else 0 End)) as cust_telkomsel, 
					(select SUM(Case When users.provider = \'XL\' Then 1 Else 0 End)) as cust_xl, 
					(select SUM(Case When users.provider = \'Indosat\' Then 1 Else 0 End)) as cust_indosat, 
					(select SUM(Case When users.provider = \'Tri\' Then 1 Else 0 End)) as cust_tri, 
					(select SUM(Case When users.provider = \'Axis\' Then 1 Else 0 End)) as cust_axis, 
					(select SUM(Case When users.provider = \'Smart\' Then 1 Else 0 End)) as cust_smart, 
					(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 11 && floor(datediff (now(), users.birthday)/365) <= 17 Then 1 Else 0 End)) as cust_teens, 
					(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 18 && floor(datediff (now(), users.birthday)/365) <= 24 Then 1 Else 0 End)) as cust_young_adult, 
					(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 25 && floor(datediff (now(), users.birthday)/365) <= 34 Then 1 Else 0 End)) as cust_adult,
					(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 35 && floor(datediff (now(), users.birthday)/365) <= 100 Then 1 Else 0 End)) as cust_old
					FROM users
					WHERE created_at BETWEEN "'. $start .' 00:00:00" 
					AND "'. $end .' 23:59:59"
					
				'));
			$trans = json_decode(json_encode($trans), true);
			
			if(!empty($trans[0]['total'])){
				foreach ($trans as $key => $value) {
					$value['reg_month'] = date('n', strtotime($end));
					$value['reg_year'] = date('Y', strtotime($end));

					$save = MonthlyCustomerReportRegistration::updateOrCreate([
							'reg_month'  => date('n', strtotime($end)),
							'reg_year'  => date('Y', strtotime($end))
						], $value);

					if (!$save) {
						return false;
					}
				}
				return $trans;
			}
		}
		return true;
	}
	
    /* NEW DAILY REPORT */
    function newDailyReport($date) 
    {
        $trans = DB::select(DB::raw('
                    SELECT transactions.id_outlet,
				    (CASE WHEN trasaction_type = \'Offline\' THEN CASE WHEN transactions.id_user IS NOT NULL THEN \'Offline Member\' ELSE \'Offline Non Member\' END ELSE \'Online\' END) AS trx_type,
					(select SUM(DISTINCT Case When users.gender = \'Male\' Then 1 Else 0 End)) as cust_male, 
					(select SUM(DISTINCT Case When users.gender = \'Female\' Then 1 Else 0 End)) as cust_female, 
					(select SUM(DISTINCT Case When users.android_device is not null Then 1 Else 0 End)) as cust_android, 
					(select SUM(DISTINCT Case When users.ios_device is not null Then 1 Else 0 End)) as cust_ios, 
					(select SUM(DISTINCT Case When users.provider = \'Telkomsel\' Then 1 Else 0 End)) as cust_telkomsel, 
					(select SUM(DISTINCT Case When users.provider = \'XL\' Then 1 Else 0 End)) as cust_xl, 
					(select SUM(DISTINCT Case When users.provider = \'Indosat\' Then 1 Else 0 End)) as cust_indosat, 
					(select SUM(DISTINCT Case When users.provider = \'Tri\' Then 1 Else 0 End)) as cust_tri, 
					(select SUM(DISTINCT Case When users.provider = \'Axis\' Then 1 Else 0 End)) as cust_axis, 
					(select SUM(DISTINCT Case When users.provider = \'Smart\' Then 1 Else 0 End)) as cust_smart, 
					(select SUM(DISTINCT Case When floor(datediff (now(), users.birthday)/365) >= 11 && floor(datediff (now(), users.birthday)/365) <= 17 Then 1 Else 0 End)) as cust_teens, 
					(select SUM(DISTINCT Case When floor(datediff (now(), users.birthday)/365) >= 18 && floor(datediff (now(), users.birthday)/365) <= 24 Then 1 Else 0 End)) as cust_young_adult, 
					(select SUM(DISTINCT Case When floor(datediff (now(), users.birthday)/365) >= 25 && floor(datediff (now(), users.birthday)/365) <= 34 Then 1 Else 0 End)) as cust_adult,
					(select SUM(DISTINCT Case When floor(datediff (now(), users.birthday)/365) >= 35 && floor(datediff (now(), users.birthday)/365) <= 100 Then 1 Else 0 End)) as cust_old,
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
                    LEFT JOIN users ON users.id = transactions.id_user 
                    LEFT JOIN transaction_products ON transaction_products.id_transaction = transactions.id_transaction 
                    WHERE transaction_date BETWEEN "'. date('Y-m-d', strtotime($date)) .' 00:00:00" 
                    AND "'. date('Y-m-d', strtotime($date)) .' 23:59:59"
                    AND transaction_payment_status = "Completed"
                    GROUP BY transactions.id_outlet,trx_type
                '));
        if ($trans) {
            $trans = json_decode(json_encode($trans), true);
			$sum = array();
			$sum['trx_date'] = $date;
			$sum['first_trx_time'] = date('H:i:s',strtotime($date));
			$sum['last_trx_time'] = date('H:i:s',strtotime($date));
			$sum['trx_type'] = $trans[0]['trx_type'];
			$sum['trx_subtotal'] = 0;
			$sum['trx_tax'] = 0;
			$sum['trx_shipment'] = 0;
			$sum['trx_service'] = 0;
			$sum['trx_discount'] = 0;
			$sum['trx_grand'] = 0;
			$sum['trx_point_earned'] = 0;
			$sum['trx_cashback_earned'] = 0;
			$sum['trx_count'] = 0;
			$sum['trx_total_item'] = 0;
			$sum['trx_average'] = 0;
			$sum['cust_male'] = 0;
			$sum['cust_female'] = 0;
			$sum['cust_android'] = 0;
			$sum['cust_ios'] = 0;
			$sum['cust_telkomsel'] = 0;
			$sum['cust_xl'] = 0;
			$sum['cust_indosat'] = 0;
			$sum['cust_tri'] = 0;
			$sum['cust_axis'] = 0;
			$sum['cust_smart'] = 0;
			$sum['cust_teens'] = 0;
			$sum['cust_young_adult'] = 0;
			$sum['cust_adult'] = 0;
			$sum['cust_old'] = 0;
			$st_time = date('H:i:s',strtotime($date));
			$ed_time = date('H:i:s',strtotime($date));
            foreach ($trans as $key => $value) {
                $trans[$key]['trx_max'] = $this->maxTrans($value['trx_date'], $value['id_outlet']);
                $sum['first_trx_time'] = $this->trxTime($st_time, $value['first_trx_time'],'first');
                $st_time = $sum['first_trx_time'];
                $sum['last_trx_time'] = $this->trxTime($ed_time, $value['last_trx_time'],'last');
                $ed_time = $sum['last_trx_time'];
				$sum['trx_subtotal'] += $value['trx_subtotal'];
				$sum['trx_tax'] += $value['trx_tax'];
				$sum['trx_shipment'] += $value['trx_shipment'];
				$sum['trx_service'] += $value['trx_service'];
				$sum['trx_discount'] += $value['trx_discount'];
				$sum['trx_grand'] += $value['trx_grand'];
				$sum['trx_point_earned'] += $value['trx_point_earned'];
				$sum['trx_cashback_earned'] += $value['trx_cashback_earned'];
				$sum['trx_count'] += $value['trx_count'];
				$sum['trx_total_item'] += $value['trx_total_item'];
				$sum['trx_average'] += $value['trx_average'];
				$sum['cust_male'] += $value['cust_male'];
				$sum['cust_female'] += $value['cust_female'];
				$sum['cust_android'] += $value['cust_android'];
				$sum['cust_ios'] += $value['cust_ios'];
				$sum['cust_telkomsel'] += $value['cust_telkomsel'];
				$sum['cust_xl'] += $value['cust_xl'];
				$sum['cust_indosat'] += $value['cust_indosat'];
				$sum['cust_tri'] += $value['cust_tri'];
				$sum['cust_axis'] += $value['cust_axis'];
				$sum['cust_smart'] += $value['cust_smart'];
				$sum['cust_teens'] += $value['cust_teens'];
				$sum['cust_young_adult'] += $value['cust_young_adult'];
				$sum['cust_adult'] += $value['cust_adult'];
				$sum['cust_old'] += $value['cust_old'];
					
                $save = DailyReportTrx::updateOrCreate([
                    'trx_date'  => date('Y-m-d', strtotime($value['trx_date'])),
                    'id_outlet' => $value['id_outlet']
                ], $trans[$key]);

                if (!$save) {
                    return false;
                }
            }

			$saveGlobal = GlobalDailyReportTrx::updateOrCreate([
                    'trx_date'  => date('Y-m-d', strtotime($date))
                ], $sum);
			
            return $trans;
        }

        return true;
    }

    /* REPORT PRODUCT */
    function dailyReportProduct($outletAll, $date) 
    {
        foreach ($outletAll as $outlet) {
             $product = DB::select(DB::raw('
                        SELECT transaction_products.id_product, transactions.id_outlet, 
                        (select SUM(transaction_products.transaction_product_qty)) as total_qty, 
                        (select SUM(transaction_products.transaction_product_subtotal)) as total_nominal, 
                        (select count(transaction_products.id_product)) as total_rec, 
                        (select DATE(transactions.transaction_date)) as trx_date,
						(select SUM(Case When users.gender = \'Male\' Then 1 Else 0 End)) as cust_male, 
						(select SUM(Case When users.gender = \'Female\' Then 1 Else 0 End)) as cust_female, 
						(select SUM(Case When users.android_device is not null Then 1 Else 0 End)) as cust_android, 
						(select SUM(Case When users.ios_device is not null Then 1 Else 0 End)) as cust_ios, 
						(select SUM(Case When users.provider = \'Telkomsel\' Then 1 Else 0 End)) as cust_telkomsel, 
						(select SUM(Case When users.provider = \'XL\' Then 1 Else 0 End)) as cust_xl, 
						(select SUM(Case When users.provider = \'Indosat\' Then 1 Else 0 End)) as cust_indosat, 
						(select SUM(Case When users.provider = \'Tri\' Then 1 Else 0 End)) as cust_tri, 
						(select SUM(Case When users.provider = \'Axis\' Then 1 Else 0 End)) as cust_axis, 
						(select SUM(Case When users.provider = \'Smart\' Then 1 Else 0 End)) as cust_smart, 
						(select products.product_name) as product_name, 
						(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 11 && floor(datediff (now(), users.birthday)/365) <= 17 Then 1 Else 0 End)) as cust_teens, 
						(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 18 && floor(datediff (now(), users.birthday)/365) <= 24 Then 1 Else 0 End)) as cust_young_adult, 
						(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 25 && floor(datediff (now(), users.birthday)/365) <= 34 Then 1 Else 0 End)) as cust_adult,
						(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 35 && floor(datediff (now(), users.birthday)/365) <= 100 Then 1 Else 0 End)) as cust_old
                        FROM transaction_products 
                        INNER JOIN transactions ON transaction_products.id_transaction = transactions.id_transaction 
						LEFT JOIN users ON users.id = transactions.id_user
						LEFT JOIN products ON transaction_products.id_product = products.id_product
						WHERE transactions.transaction_date BETWEEN "'. date('Y-m-d', strtotime($date)) .' 00:00:00" 
                        AND "'. date('Y-m-d', strtotime($date)) .' 23:59:59"
                        AND transactions.id_outlet = "'. $outlet .'"
                        AND transaction_payment_status = "Completed"
                        GROUP BY id_product
                        ORDER BY id_product ASC
                    '));
			// print_r($product);exit;
            if (!empty($product)) {
                $product = json_decode(json_encode($product), true);
                foreach ($product as $key => $value) {
					$sum = array();
					$sum['trx_date'] = $date;
					$sum['id_product'] = $value['id_product'];
					$sum['product_name'] = $value['product_name'];
					$sum['total_qty'] = $value['total_qty'];
					$sum['total_nominal'] = $value['total_nominal'];
					$sum['total_rec'] = $value['total_rec'];
					$sum['cust_male'] = $value['cust_male'];
					$sum['cust_female'] = $value['cust_female'];
					$sum['cust_android'] = $value['cust_android'];
					$sum['cust_ios'] = $value['cust_ios'];
					$sum['cust_telkomsel'] = $value['cust_telkomsel'];
					$sum['cust_xl'] = $value['cust_xl'];
					$sum['cust_indosat'] = $value['cust_indosat'];
					$sum['cust_tri'] = $value['cust_tri'];
					$sum['cust_axis'] = $value['cust_axis'];
					$sum['cust_smart'] = $value['cust_smart'];
					$sum['cust_teens'] = $value['cust_teens'];
					$sum['cust_young_adult'] = $value['cust_young_adult'];
					$sum['cust_adult'] = $value['cust_adult'];
					$sum['cust_old'] = $value['cust_old'];
					
                    $save = DailyReportTrxMenu::updateOrCreate([
                        'trx_date'   => date('Y-m-d', strtotime($value['trx_date'])), 
                        'id_product' => $value['id_product'],
                        'id_outlet'  => $value['id_outlet']
                    ], $value);
					
					$saveGlobal = GlobalDailyReportTrxMenu::updateOrCreate([
                        'trx_date'   => date('Y-m-d', strtotime($value['trx_date'])), 
                        'id_product' => $value['id_product']
                    ], $sum);
					
                    if (!$save) {
                        return false;
                    }
                }
				
            }
        }

        return true;
    }
	
	/* REPORT PAYMENT */
    function dailyReportPayment($date) 
    {
        $date = date('Y-m-d', strtotime($date));

        $getTransactions = Transaction::whereDate('transactions.created_at', $date)
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

                $getDaily = DailyReportPayment::where('id_outlet', $dtTrx['id_outlet'])
                    ->where('trx_date', date('Y-m-d', strtotime($dtTrx['transaction_date'])))
                    ->where('trx_payment', $trx_payment)->first();

                $dataToInsert = [
                    'id_outlet' => $dtTrx['id_outlet'],
                    'trx_date' => date('Y-m-d', strtotime($dtTrx['transaction_date'])),
                    'trx_payment_count' => 1,
                    'trx_payment_nominal' => $dtPayment['trx_payment_nominal'],
                    'trx_payment' => $trx_payment
                ];

                if($getDaily){
                    $dataToInsert['trx_payment_count'] = $getDaily['trx_payment_count'] + 1;
                    $dataToInsert['trx_payment_nominal'] = $getDaily['trx_payment_nominal'] + ($dtPayment['trx_payment_nominal']??0);
                    DailyReportPayment::where('id_daily_report_payment', $getDaily['id_daily_report_payment'])
                        ->update($dataToInsert);
                }else{
                    DailyReportPayment::create($dataToInsert);
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

		        $saveGlobal = GlobalDailyReportPayment::updateOrCreate([
		            'trx_date'  => date('Y-m-d', strtotime($date)),
		            'trx_payment' => $trx_payment
		        ], $global[$global_key]);
                
            }

        }

        return true;
    }

	/* NEW MONTHLY REPORT */
    function newMonthlyReport($date) 
    {
		$d1 = new DateTime($date);
		$d2 = new DateTime(date('Y-m-d'));
		$interval = date_diff($d1, $d2);
		$diff = $interval->m + ($interval->y * 12);
		
		for($x = 0;$x <= $diff; $x++){
			$start = date('Y-m-1', strtotime("+ ".$x." month", strtotime($date)));
			if($x != $diff){
				$end = date('Y-m-t', strtotime("+ ".$x." month", strtotime($date)));
			} else {
				$end = date('Y-m-d');
			}

			// print_r(['start' => $start, 'end' => $end]);exit;
			$trans = DB::select(DB::raw('
						SELECT transactions.id_outlet, 
						(select SUM(Case When users.gender = \'Male\' Then 1 Else 0 End)) as cust_male, 
						(select SUM(Case When users.gender = \'Female\' Then 1 Else 0 End)) as cust_female, 
						(select SUM(Case When users.android_device is not null Then 1 Else 0 End)) as cust_android, 
						(select SUM(Case When users.ios_device is not null Then 1 Else 0 End)) as cust_ios, 
						(select SUM(Case When users.provider = \'Telkomsel\' Then 1 Else 0 End)) as cust_telkomsel, 
						(select SUM(Case When users.provider = \'XL\' Then 1 Else 0 End)) as cust_xl, 
						(select SUM(Case When users.provider = \'Indosat\' Then 1 Else 0 End)) as cust_indosat, 
						(select SUM(Case When users.provider = \'Tri\' Then 1 Else 0 End)) as cust_tri, 
						(select SUM(Case When users.provider = \'Axis\' Then 1 Else 0 End)) as cust_axis, 
						(select SUM(Case When users.provider = \'Smart\' Then 1 Else 0 End)) as cust_smart, 
						(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 11 && floor(datediff (now(), users.birthday)/365) <= 17 Then 1 Else 0 End)) as cust_teens, 
						(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 18 && floor(datediff (now(), users.birthday)/365) <= 24 Then 1 Else 0 End)) as cust_young_adult, 
						(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 25 && floor(datediff (now(), users.birthday)/365) <= 34 Then 1 Else 0 End)) as cust_adult,
						(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 35 && floor(datediff (now(), users.birthday)/365) <= 100 Then 1 Else 0 End)) as cust_old,
						(select SUM(transaction_subtotal)) as trx_subtotal, 
						(select SUM(transaction_tax)) as trx_tax, 
						(select SUM(transaction_shipment)) as trx_shipment, 
						(select SUM(transaction_service)) as trx_service, 
						(select SUM(transaction_discount)) as trx_discount, 
						(select SUM(transaction_grandtotal)) as trx_grand, 
						(select SUM(transaction_point_earned)) as trx_point_earned, 
						(select SUM(transaction_cashback_earned)) as trx_cashback_earned, 
						(select count(DISTINCT transactions.id_transaction)) as trx_count, 
						(select AVG(transaction_grandtotal)) as trx_average,
						(select SUM(transaction_products.transaction_product_qty)) as trx_total_item
						FROM transactions 
						LEFT JOIN users ON users.id = transactions.id_user 
						LEFT JOIN transaction_products ON transactions.id_transaction = transaction_products.id_transaction
						WHERE transaction_date BETWEEN "'. $start .' 00:00:00" 
						AND "'. $end .' 23:59:59"
						AND transaction_payment_status = "Completed"
						GROUP BY transactions.id_outlet
					'));
			// print_r($trans);exit;
			if ($trans) {
				$trans = json_decode(json_encode($trans), true);
				$sum = array();
				$sum['trx_month'] = date('n', strtotime($end));
				$sum['trx_year'] = date('Y', strtotime($end));
				$sum['trx_subtotal'] = 0;
				$sum['trx_tax'] = 0;
				$sum['trx_shipment'] = 0;
				$sum['trx_service'] = 0;
				$sum['trx_discount'] = 0;
				$sum['trx_grand'] = 0;
				$sum['trx_point_earned'] = 0;
				$sum['trx_cashback_earned'] = 0;
				$sum['trx_count'] = 0;
				$sum['trx_total_item'] = 0;
				$sum['trx_average'] = 0;
				$sum['cust_male'] = 0;
				$sum['cust_female'] = 0;
				$sum['cust_android'] = 0;
				$sum['cust_ios'] = 0;
				$sum['cust_telkomsel'] = 0;
				$sum['cust_xl'] = 0;
				$sum['cust_indosat'] = 0;
				$sum['cust_tri'] = 0;
				$sum['cust_axis'] = 0;
				$sum['cust_smart'] = 0;
				$sum['cust_teens'] = 0;
				$sum['cust_young_adult'] = 0;
				$sum['cust_adult'] = 0;
				$sum['cust_old'] = 0;
				
				foreach ($trans as $key => $value) {
					$value['trx_month'] = date('n', strtotime($end));
					$value['trx_year'] = date('Y', strtotime($end));
					$save = MonthlyReportTrx::updateOrCreate([
						'trx_month' => $value['trx_month'],
						'trx_year' => $value['trx_year'],
						'id_outlet' => $value['id_outlet']
					], $value);

					if (!$save) {
						return false;
					}
					
					$sum['trx_subtotal'] += $value['trx_subtotal'];
					$sum['trx_tax'] += $value['trx_tax'];
					$sum['trx_shipment'] += $value['trx_shipment'];
					$sum['trx_service'] += $value['trx_service'];
					$sum['trx_discount'] += $value['trx_discount'];
					$sum['trx_grand'] += $value['trx_grand'];
					$sum['trx_point_earned'] += $value['trx_point_earned'];
					$sum['trx_cashback_earned'] += $value['trx_cashback_earned'];
					$sum['trx_count'] += $value['trx_count'];
					$sum['trx_total_item'] += $value['trx_total_item'];
					$sum['trx_average'] += $value['trx_average'];
					$sum['cust_male'] += $value['cust_male'];
					$sum['cust_female'] += $value['cust_female'];
					$sum['cust_android'] += $value['cust_android'];
					$sum['cust_ios'] += $value['cust_ios'];
					$sum['cust_telkomsel'] += $value['cust_telkomsel'];
					$sum['cust_xl'] += $value['cust_xl'];
					$sum['cust_indosat'] += $value['cust_indosat'];
					$sum['cust_tri'] += $value['cust_tri'];
					$sum['cust_axis'] += $value['cust_axis'];
					$sum['cust_smart'] += $value['cust_smart'];
					$sum['cust_teens'] += $value['cust_teens'];
					$sum['cust_young_adult'] += $value['cust_young_adult'];
					$sum['cust_adult'] += $value['cust_adult'];
					$sum['cust_old'] += $value['cust_old'];
				}
				$saveGlobal = GlobalMonthlyReportTrx::updateOrCreate([
						'trx_month'  => date('n', strtotime($end)),
						'trx_year'  => date('Y', strtotime($end))
					], $sum);
					
				return $trans;
			}
		}
        return true;
    }

    /* REPORT PRODUCT */
    function monthlyReportProduct($outletAll, $date) 
    {
        foreach ($outletAll as $outlet) {
            $product = DB::select(DB::raw('
                        SELECT transaction_products.id_product, transactions.id_outlet, 
                        (select SUM(transaction_products.transaction_product_qty)) as total_qty, 
                        (select SUM(transaction_products.transaction_product_subtotal)) as total_nominal, 
                        (select count(transaction_products.id_product)) as total_rec, 
                        (select MONTH(transaction_date)) as trx_month,
						(select YEAR(transaction_date)) as trx_year,
						(select SUM(Case When users.gender = \'Male\' Then 1 Else 0 End)) as cust_male, 
						(select SUM(Case When users.gender = \'Female\' Then 1 Else 0 End)) as cust_female, 
						(select SUM(Case When users.android_device is not null Then 1 Else 0 End)) as cust_android, 
						(select SUM(Case When users.ios_device is not null Then 1 Else 0 End)) as cust_ios, 
						(select SUM(Case When users.provider = \'Telkomsel\' Then 1 Else 0 End)) as cust_telkomsel, 
						(select SUM(Case When users.provider = \'XL\' Then 1 Else 0 End)) as cust_xl, 
						(select SUM(Case When users.provider = \'Indosat\' Then 1 Else 0 End)) as cust_indosat, 
						(select SUM(Case When users.provider = \'Tri\' Then 1 Else 0 End)) as cust_tri, 
						(select SUM(Case When users.provider = \'Axis\' Then 1 Else 0 End)) as cust_axis, 
						(select SUM(Case When users.provider = \'Smart\' Then 1 Else 0 End)) as cust_smart, 
						(select products.product_name) as product_name,
						(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 11 && floor(datediff (now(), users.birthday)/365) <= 17 Then 1 Else 0 End)) as cust_teens, 
						(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 18 && floor(datediff (now(), users.birthday)/365) <= 24 Then 1 Else 0 End)) as cust_young_adult, 
						(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 25 && floor(datediff (now(), users.birthday)/365) <= 34 Then 1 Else 0 End)) as cust_adult,
						(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 35 && floor(datediff (now(), users.birthday)/365) <= 100 Then 1 Else 0 End)) as cust_old
                        FROM transaction_products 
                        INNER JOIN transactions ON transaction_products.id_transaction = transactions.id_transaction 
						LEFT JOIN users ON users.id = transactions.id_user
						LEFT JOIN products ON transaction_products.id_product = products.id_product
                        WHERE transactions.transaction_date BETWEEN "'. date('1-m-d') .' 00:00:00" 
                        AND "'. date('Y-m-d', strtotime($date)) .' 23:59:59"
                        AND transactions.id_outlet = "'. $outlet .'"
                        AND transaction_payment_status = "Completed"
                        GROUP BY id_product
                        ORDER BY id_product ASC
                    '));

            if (!empty($product)) {
                $product = json_decode(json_encode($product), true);

                foreach ($product as $key => $value) {
					$sum = array();
					$sum['trx_month'] = date('n', strtotime($date));
					$sum['trx_year'] = date('Y', strtotime($date));
					$sum['id_product'] = $value['id_product'];
					$sum['product_name'] = $value['product_name'];
					$sum['total_qty'] = $value['total_qty'];
					$sum['total_nominal'] = $value['total_nominal'];
					$sum['total_rec'] = $value['total_rec'];
					$sum['cust_male'] = $value['cust_male'];
					$sum['cust_female'] = $value['cust_female'];
					$sum['cust_android'] = $value['cust_android'];
					$sum['cust_ios'] = $value['cust_ios'];
					$sum['cust_telkomsel'] = $value['cust_telkomsel'];
					$sum['cust_xl'] = $value['cust_xl'];
					$sum['cust_indosat'] = $value['cust_indosat'];
					$sum['cust_tri'] = $value['cust_tri'];
					$sum['cust_axis'] = $value['cust_axis'];
					$sum['cust_smart'] = $value['cust_smart'];
					$sum['cust_teens'] = $value['cust_teens'];
					$sum['cust_young_adult'] = $value['cust_young_adult'];
					$sum['cust_adult'] = $value['cust_adult'];
					$sum['cust_old'] = $value['cust_old'];
					
                    $save = MonthlyReportTrxMenu::updateOrCreate([
                        'id_product' => $value['id_product'],
                        'id_outlet'  => $value['id_outlet']
                    ], $value);
					
					$saveGlobal = GlobalMonthlyReportTrxMenu::updateOrCreate([
                        'trx_month'   => date('n', strtotime($date)), 
                        'trx_year'   => date('Y', strtotime($date)), 
                        'id_product' => $value['id_product']
                    ], $sum);
					
                    if (!$save) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /* REPORT PAYMENT */
    function monthlyReportPayment($date) 
    {
        $date = date('Y-m-d', strtotime($date));

        $getTransactions = Transaction::whereDate('transactions.created_at', $date)
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

            $month = date('m', strtotime($dtTrx['transaction_date']));
            $year = date('Y', strtotime($dtTrx['transaction_date']));

            foreach ($getTransactionPayment as $dtPayment){

            	if ( !empty($dtPayment['payment_type']) && !empty($dtPayment['payment'])) 
            	{
            		$trx_payment = $dtPayment['payment_type'].' '.$dtPayment['payment'];
            	}
            	else
            	{
            		$trx_payment = $dtPayment['payment_type']??$dtPayment['payment']??$trx_payment;
            	}

                $getMonthly = MonthlyReportPayment::where('id_outlet', $dtTrx['id_outlet'])
                    ->where('trx_month', $month)
                    ->where('trx_year', $year)
                    ->where('trx_payment', $trx_payment)->first();

                $dataToInsert = [
                    'id_outlet' => $dtTrx['id_outlet'],
                    'trx_month' => $month,
                    'trx_year' => $year,
                    'trx_payment_count' => 1,
                    'trx_payment_nominal' => $dtPayment['trx_payment_nominal'],
                    'trx_payment' => $trx_payment
                ];

                if($getMonthly){
                    $dataToInsert['trx_payment_count'] = $getMonthly['trx_payment_count'] + 1;
                    $dataToInsert['trx_payment_nominal'] = $getMonthly['trx_payment_nominal'] + ($dtPayment['trx_payment_nominal']??0);
                    MonthlyReportPayment::where('id_monthly_report_payment', $getMonthly['id_monthly_report_payment'])
                        ->update($dataToInsert);
                }else{
                    MonthlyReportPayment::create($dataToInsert);
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

		        $saveGlobal = GlobalMonthlyReportPayment::updateOrCreate([
		            'trx_month' => $month,
                    'trx_year' => $year,
		            'trx_payment' => $trx_payment
		        ], $global[$global_key]);
            }
        }
        // dd($global);
        return true;
    }
    
}
