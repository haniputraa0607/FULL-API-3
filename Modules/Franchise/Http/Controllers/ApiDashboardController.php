<?php

namespace Modules\Franchise\Http\Controllers;

use App\Http\Models\DailyReportTrx;
use App\Http\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use DB;
use DateTime;

class ApiDashboardController extends Controller
{
    public function dashboard(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_outlet'])) {
            $trx = Transaction::where('transactions.id_outlet', $post['id_outlet'])
                    ->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                    ->leftJoin('disburse_outlet_transactions', 'transactions.id_transaction', 'disburse_outlet_transactions.id_transaction')
                    ->leftJoin('disburse_outlet', 'disburse_outlet.id_disburse_outlet', 'disburse_outlet_transactions.id_disburse_outlet')
                    ->leftJoin('disburse', 'disburse.id_disburse', 'disburse_outlet.id_disburse')
                    ->where('transactions.transaction_payment_status', 'Completed')->whereNull('reject_at')
                    ->selectRaw('
                        COUNT(DISTINCT DATE(transaction_date)) as count_date,
                        COUNT(transactions.id_transaction) as total_sales, SUM(transaction_gross) as total_nominal_sales,
                        SUM(transaction_grandtotal) as grand_total, SUM(transaction_subtotal) as sub_total,
                        SUM(transaction_shipment_go_send) as total_delivery, SUM(transaction_discount_item+transaction_discount_bill+transaction_discount_delivery) as total_dicount,
                        SUM(CASE WHEN disburse.disburse_status = "Success" THEN disburse_outlet.disburse_nominal ELSE NULL END) as disburse_success,
                        SUM(disburse_outlet_transactions.income_outlet) as incomes_outlet
                    ');

            if(isset($post['filter_type']) && $post['filter_type'] == 'range_date') {
                $dateStart = date('Y-m-d', strtotime($post['date_start']));
                $dateEnd = date('Y-m-d', strtotime($post['date_end']));
                $trx = $trx->whereDate('transaction_date', '>=', $dateStart)->whereDate('transaction_date', '<=', $dateEnd);
            }else{
                $trx = $trx->whereDate('transaction_date',  date('Y-m-d'));
            }

            $trx = $trx->first();
            $result = [
                [
                    'title' => 'Total Sales',
                    'amount' => $trx['total_sales']??0
                ],
                [
                    'title' => 'Total Nominal Sales',
                    'amount' => number_format($trx['total_nominal_sales']??0,2,",",".")
                ],
                [
                    'title' => 'Grand Total',
                    'amount' => number_format($trx['grand_total']??0,2,",",".")
                ],
                [
                    'title' => 'Sub Total',
                    'amount' => number_format($trx['sub_total']??0,2,",",".")
                ],
                [
                    'title' => 'Total Delivery',
                    'amount' => number_format($trx['total_delivery']??0,2,",",".")
                ],
                [
                    'title' => 'Total Discount',
                    'amount' => number_format($trx['total_dicount']??0,2,",",".")
                ],
                [
                    'title' => 'Income Outlet',
                    'amount' => number_format($trx['incomes_outlet']??0,2,",",".")
                ],
                [
                    'title' => 'Disburse Success',
                    'amount' => number_format($trx['disburse_success']??0,2,",",".")
                ]
            ];

            if(!empty($trx) && $trx['total_sales'] != 0){
                $result[] =  [
                    'title' => 'Average Grand Total',
                    'amount' => number_format($trx['grand_total']/$trx['total_sales'],2,",",".")
                ];
                $result[] =  [
                    'title' => 'Average Sub Total',
                    'amount' => number_format($trx['sub_total']/$trx['total_sales'],2,",",".")
                ];
                $result[] =  [
                    'title' => 'Average Sales',
                    'amount' => number_format($trx['total_sales']/$trx['count_date'],2,",",".")
                ];
            }else{
                $result[] =  [
                    'title' => 'Average Grand Total',
                    'amount' => number_format(0,2,",",".")
                ];
                $result[] =  [
                    'title' => 'Average Sub Total',
                    'amount' => number_format(0,2,",",".")
                ];
                $result[] =  [
                    'title' => 'Average Sales',
                    'amount' => number_format(0,2,",",".")
                ];
            }
            return response()->json(MyHelper::checkGet($result));
        }

        return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
    }

    function topTenProduct(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_outlet'])) {
            $topTen =  Transaction::where('transactions.id_outlet', $post['id_outlet'])
                ->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                ->join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
                ->join('products', 'products.id_product', 'transaction_products.id_product')
                ->select(DB::raw('sum(transaction_product_qty) as sum_qty'), 'products.product_code', 'products.product_name')
                ->groupBy('transaction_products.id_product')
                ->orderBy('sum_qty', 'desc');

            if(isset($post['filter_type']) && $post['filter_type'] == 'range_date') {
                $dateStart = date('Y-m-d', strtotime($post['date_start']));
                $dateEnd = date('Y-m-d', strtotime($post['date_end']));
                $topTen = $topTen->whereDate('transaction_date', '>=', $dateStart)->whereDate('transaction_date', '<=', $dateEnd);
            }else{
                $topTen = $topTen->whereDate('transaction_date',  date('Y-m-d'));
            }

            $topTen = $topTen->limit(10)->get()->toArray();
            return response()->json(MyHelper::checkGet($topTen));
        }
        return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
    }

    function summarySales(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_outlet'])) {
            $dateStart = date('Y-m-d', strtotime($post['date_start']));
            $dateEnd = date('Y-m-d', strtotime($post['date_end']));
            $begin = new DateTime($dateStart);
            $end   = new DateTime($dateEnd);
            $get = DailyReportTrx::where('id_outlet', $post['id_outlet'])
                ->select('trx_date', 'trx_grand as amount')
                ->get()->toArray();

            $tmp = [];
            for($i = $begin; $i <= $end; $i->modify('+1 day')){
                $date[] = $i->format("d M Y");
                $check = array_search($i->format("Y-m-d"), array_column($get, 'trx_date'));
                if($check !== false){
                    $tmp[] = (int)$get[$check]['amount'];
                }else{
                    $tmp[] = 0;
                }
            }
            $data[] = [
                'name' => 'Grand Total',
                'data' => $tmp
            ];

            $result = [
                'series' => $data,
                'date' => array_unique($date)
            ];
            return response()->json(MyHelper::checkGet($result));
        }
        return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
    }
}
