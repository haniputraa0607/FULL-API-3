<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Models\User;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\DailyReportTrx;
use App\Http\Models\DailyReportTrxMenu;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\Report\Http\Requests\DetailReport;

use App\Lib\MyHelper;
use Validator;
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
        // DAILY
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

    /* NEW DAILY REPORT */
    function newDailyReport($date) 
    {
        $trans = DB::select(DB::raw('
                    SELECT id_outlet, 
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
                    WHERE transaction_date BETWEEN "'. date('Y-m-d', strtotime($date)) .' 00:00:00" 
                    AND "'. date('Y-m-d', strtotime($date)) .' 23:59:59"
                    AND transaction_payment_status = "Completed"
                    GROUP BY id_outlet
                '));
        
        if ($trans) {
            $trans = json_decode(json_encode($trans), true);

            foreach ($trans as $key => $value) {
                $trans[$key]['trx_max'] = $this->maxTrans($value['trx_date'], $value['id_outlet']);

                $save = DailyReportTrx::updateOrCreate([
                    'trx_date'  => date('Y-m-d', strtotime($value['trx_date'])),
                    'id_outlet' => $value['id_outlet']
                ], $trans[$key]);

                if (!$save) {
                    return false;
                }
            }

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
                        (select DATE(transactions.transaction_date)) as trx_date
                        FROM transaction_products 
                        INNER JOIN transactions ON transaction_products.id_transaction = transactions.id_transaction 
                        WHERE transactions.transaction_date BETWEEN "'. date('Y-m-d', strtotime($date)) .' 00:00:00" 
                        AND "'. date('Y-m-d', strtotime($date)) .' 23:59:59"
                        AND transactions.id_outlet = "'. $outlet .'"
                        AND transaction_payment_status = "Completed"
                        GROUP BY id_product
                        ORDER BY id_product ASC
                    '));

            if (!empty($product)) {
                $product = json_decode(json_encode($product), true);

                foreach ($product as $key => $value) {
                    $save = DailyReportTrxMenu::updateOrCreate([
                        'trx_date'   => date('Y-m-d', strtotime($value['trx_date'])), 
                        'id_product' => $value['id_product'],
                        'id_outlet'  => $value['id_outlet']
                    ], $value);

                    if (!$save) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
    
}
