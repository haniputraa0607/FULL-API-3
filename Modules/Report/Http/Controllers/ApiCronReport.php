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
                    SELECT id_outlet,
				    (CASE WHEN trasaction_type = \'Offline\' THEN CASE WHEN id_user IS NOT NULL THEN \'Offline Member\' ELSE \'Offline Non Member\' END ELSE \'Online\' END) AS trx_type,
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
                    (select count(id_transaction)) as trx_count, 
                    (select AVG(transaction_grandtotal)) as trx_average, 
                    (select DATE(transaction_date)) as trx_date
                    FROM transactions 
                    LEFT JOIN users ON users.id = transactions.id_user 
                    WHERE transaction_date BETWEEN "'. date('Y-m-d', strtotime($date)) .' 00:00:00" 
                    AND "'. date('Y-m-d', strtotime($date)) .' 23:59:59"
                    AND transaction_payment_status = "Completed"
                    GROUP BY id_outlet,trx_type
                '));
        if ($trans) {
            $trans = json_decode(json_encode($trans), true);
			$sum = array();
			$sum['trx_date'] = $date;
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
                $trans[$key]['trx_max'] = $this->maxTrans($value['trx_date'], $value['id_outlet']);
				
				$sum['trx_subtotal'] += $value['trx_subtotal'];
				$sum['trx_tax'] += $value['trx_tax'];
				$sum['trx_shipment'] += $value['trx_shipment'];
				$sum['trx_service'] += $value['trx_service'];
				$sum['trx_discount'] += $value['trx_discount'];
				$sum['trx_grand'] += $value['trx_grand'];
				$sum['trx_point_earned'] += $value['trx_point_earned'];
				$sum['trx_cashback_earned'] += $value['trx_cashback_earned'];
				$sum['trx_count'] += $value['trx_count'];
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
						(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 11 && floor(datediff (now(), users.birthday)/365) <= 17 Then 1 Else 0 End)) as cust_teens, 
						(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 18 && floor(datediff (now(), users.birthday)/365) <= 24 Then 1 Else 0 End)) as cust_young_adult, 
						(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 25 && floor(datediff (now(), users.birthday)/365) <= 34 Then 1 Else 0 End)) as cust_adult,
						(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 35 && floor(datediff (now(), users.birthday)/365) <= 100 Then 1 Else 0 End)) as cust_old
                        FROM transaction_products 
                        INNER JOIN transactions ON transaction_products.id_transaction = transactions.id_transaction 
						LEFT JOIN users ON users.id = transactions.id_user
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
						SELECT id_outlet, 
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
						(select count(id_transaction)) as trx_count, 
						(select AVG(transaction_grandtotal)) as trx_average
						FROM transactions 
						LEFT JOIN users ON users.id = transactions.id_user 
						WHERE transaction_date BETWEEN "'. $start .' 00:00:00" 
						AND "'. $end .' 23:59:59"
						AND transaction_payment_status = "Completed"
						GROUP BY id_outlet
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
						(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 11 && floor(datediff (now(), users.birthday)/365) <= 17 Then 1 Else 0 End)) as cust_teens, 
						(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 18 && floor(datediff (now(), users.birthday)/365) <= 24 Then 1 Else 0 End)) as cust_young_adult, 
						(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 25 && floor(datediff (now(), users.birthday)/365) <= 34 Then 1 Else 0 End)) as cust_adult,
						(select SUM(Case When floor(datediff (now(), users.birthday)/365) >= 35 && floor(datediff (now(), users.birthday)/365) <= 100 Then 1 Else 0 End)) as cust_old
                        FROM transaction_products 
                        INNER JOIN transactions ON transaction_products.id_transaction = transactions.id_transaction 
						LEFT JOIN users ON users.id = transactions.id_user
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
    
}
