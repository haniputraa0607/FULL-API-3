<?php

namespace Modules\Franchise\Http\Controllers;

use Modules\Franchise\Entities\Transaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use DB;

class ApiReportSalesController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function summary(Request $request)
    {
        $post = $request->json()->all();
        if (!$request->id_outlet) {
            return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
        }

        $report = Transaction::where('transactions.id_outlet', $request->id_outlet)
                    ->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                    ->join('disburse_outlet_transactions', 'transactions.id_transaction', 'disburse_outlet_transactions.id_transaction')
                    ->where('transactions.transaction_payment_status', 'Completed')
                    // ->whereNull('reject_at')
                    ->select(DB::raw('
						# tanggal transaksi
						Date(transactions.transaction_date) as transaction_date,

						# total transaksi
						COUNT(CASE WHEN transactions.id_transaction AND transaction_pickups.reject_at IS NULL THEN 1 ELSE NULL END) AS total_transaction, 

						# pickup
						COUNT(CASE WHEN transaction_pickups.pickup_by = "Customer" AND transaction_pickups.reject_at IS NULL THEN 1 ELSE NULL END) as total_transaction_pickup,

						# delivery
						COUNT(CASE WHEN transaction_pickups.pickup_by != "Customer" AND transaction_pickups.reject_at IS NULL THEN 1 ELSE NULL END) as total_transaction_delivery,

						# subtotal
						SUM(CASE WHEN transactions.transaction_gross IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN transactions.transaction_gross ELSE 0 END) as total_subtotal,

						# diskon
						SUM(
							CASE WHEN transactions.transaction_discount_item IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN ABS(transactions.transaction_discount_item) 
								WHEN transactions.transaction_discount IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN ABS(transactions.transaction_discount)
								ELSE 0 END
							+ CASE WHEN transactions.transaction_discount_delivery IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN ABS(transactions.transaction_discount_delivery) ELSE 0 END
							+ CASE WHEN transactions.transaction_discount_bill IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN ABS(transactions.transaction_discount_bill) ELSE 0 END
						) as total_discount,

						# delivery
						SUM(CASE WHEN transaction_pickups.reject_at IS NULL THEN transactions.transaction_shipment_go_send + transactions.transaction_shipment ELSE 0 END) as total_delivery,

						# grandtotal
						SUM(CASE WHEN transaction_pickups.reject_at IS NULL THEN transactions.transaction_grandtotal ELSE 0 END) as total_grandtotal,

						# accept
						COUNT(CASE WHEN transaction_pickups.receive_at IS NOT NULL THEN 1 ELSE NULL END) as total_accept,

						# reject
						COUNT(CASE WHEN transaction_pickups.reject_at IS NOT NULL THEN 1 ELSE NULL END) as total_reject,

						# rate accept
						FLOOR(
							(
								COUNT(CASE WHEN transaction_pickups.receive_at IS NOT NULL THEN 1 ELSE NULL END) 
								/ COUNT(CASE WHEN transactions.transaction_payment_status = "Completed" THEN 1 ELSE NULL END)
							)
							* 100
						) as acceptance_rate,

						# response
						COUNT(
							CASE WHEN transaction_pickups.receive_at IS NOT NULL THEN 1
								 WHEN transaction_pickups.reject_reason NOT LIKE "auto reject order by system%" THEN 1
							ELSE NULL END
						) as total_response,

						# auto reject response
						COUNT(
							CASE WHEN transaction_pickups.reject_reason = "auto reject order by system" THEN 1
							ELSE NULL END
						) as total_auto_reject,

						# manual reject response
						COUNT(
							CASE WHEN transaction_pickups.receive_at IS NULL AND transaction_pickups.reject_reason NOT LIKE "auto reject order by system%" THEN 1
							ELSE NULL END
						) as total_manual_reject,

						# rate response
						FLOOR(
							(
								COUNT(
									CASE WHEN transaction_pickups.receive_at IS NOT NULL THEN 1
										 WHEN transaction_pickups.reject_reason NOT LIKE "auto reject order by system%" THEN 1
									ELSE NULL END
								)/ COUNT(CASE WHEN transactions.transaction_payment_status = "Completed" THEN 1 ELSE NULL END)
							)
							* 100
						) as response_rate,

						# payment complete
						COUNT(CASE WHEN transactions.transaction_payment_status = "Completed" THEN 1 ELSE NULL END) as total_complete_payment
					'));

        if (isset($post['filter_type']) && $post['filter_type'] == 'range_date') {
            $dateStart = date('Y-m-d', strtotime($post['date_start']));
            $dateEnd = date('Y-m-d', strtotime($post['date_end']));
            $report = $report->whereDate('transactions.transaction_date', '>=', $dateStart)->whereDate('transactions.transaction_date', '<=', $dateEnd);
        } elseif (isset($post['filter_type']) && $post['filter_type'] == 'today') {
            $currentDate = date('Y-m-d');
            $report = $report->whereDate('transactions.transaction_date', $currentDate);
        } else {
            $report = $report->whereDate('transactions.transaction_date', date('Y-m-d'));
        }

        $report = $report->first();

        if (!$report) {
            return response()->json(['status' => 'fail', 'messages' => ['Empty']]);
        }

        /*$report['acceptance_rate'] = 0;
        if ($report['total_accept']) {
            $report['acceptance_rate'] = floor(( $report['total_accept'] / ($report['total_accept'] + $report['total_reject']) ) * 100);
        }

        if ($report['total_discount']) {
            $report['total_discount'] = abs($report['total_discount']);
        }*/

        $result = [
            'total_subtotal' => [
                'title' => 'Penjualan Kotor',
                'amount' => 'Rp. ' . number_format($report['total_subtotal'] ?? 0, 0, ",", "."),
                "tooltip" => 'Total nominal transaksi sebelum dipotong diskon dan ditambah biaya pengiriman',
                "show" => 1
            ],
            'total_discount' => [
                'title' => 'Total Diskon',
                'amount' => 'Rp. ' . number_format($report['total_discount'] ?? 0, 0, ",", "."),
                "tooltip" => 'Total diskon transaksi (diskon produk, diskon biaya pengiriman dan diskon bill)',
                "show" => 1
            ],
            'total_delivery' => [
                'title' => 'Biaya Pengiriman',
                'amount' => 'Rp. ' . number_format($report['total_delivery'] ?? 0, 0, ",", "."),
                "tooltip" => 'Total biaya pengiriman',
                "show" => 1
            ],
            'total_grandtotal' => [
                'title' => 'Penjualan Bersih',
                'amount' => 'Rp. ' . number_format($report['total_grandtotal'] ?? 0, 0, ",", "."),
                "tooltip" => 'Total nominal transaksi setelah dipotong diskon dan ditambah biaya pengiriman',
                "show" => 1
            ],
            'total_complete_payment' => [
                'title' => 'Pembayaran Sukses',
                'amount' => number_format($report['total_complete_payment'] ?? 0, 0, ",", "."),
                "tooltip" => 'jumlah transaksi dengan status pembyaran sukses (mengabaikan status reject order)'
            ],
            'total_transaction_pickup' => [
                'title' => 'Pickup Order',
                'amount' => number_format($report['total_transaction_pickup'] ?? 0, 0, ",", "."),
                "tooltip" => 'jumlah transaksi sukses dengan tipe pickup'
            ],
            'total_transaction_delivery' => [
                'title' => 'Delivery Order',
                'amount' => number_format($report['total_transaction_delivery'] ?? 0, 0, ",", "."),
                "tooltip" => 'jumlah transaksi sukses dengan tipe delivery'
            ],
            'total_transaction' => [
                'title' => 'Total Order',
                'amount' => number_format($report['total_transaction'] ?? 0, 0, ",", "."),
                "tooltip" => 'Jumlah order sukses dan order ditolak',
                "show" => 1
            ],
            'total_response' => [
                'title' => 'Order Direspons',
                'amount' => number_format($report['total_response'] ?? 0, 0, ",", "."),
                "tooltip" => 'Jumlah transaksi yang di respons oleh outlet (diterima atau ditolak)',
                "show" => 1
            ],
            'response_rate' => [
                'title' => 'Response Rate Order',
                'amount' => number_format($report['response_rate'] ?? 0, 0, ",", ".") . "%",
                "tooltip" => 'persentase jumlah order yang di response oleh outlet dibandingkan dengan jumlah transaksi dengan status pembayaran suskes (transaksi yang masuk)'
            ],
            'total_accept' => [
                'title' => 'Order Diterima',
                'amount' => number_format($report['total_accept'] ?? 0, 0, ",", "."),
                "tooltip" => 'Jumlah transaksi yang diterima oleh outlet',
                "show" => 1
            ],
            'acceptance_rate' => [
                'title' => 'Acceptance Rate Order',
                'amount' => number_format($report['acceptance_rate'] ?? 0, 0, ",", ".") . "%",
                "tooltip" => 'persentase jumlah transaksi yang diterima oleh outlet dibandingkan dengan jumlah transaksi dengan status pembayaran suskes (transaksi yang masuk)'
            ],
            'total_manual_reject' => [
                'title' => 'Manual Rejected Order',
                'amount' => number_format($report['total_manual_reject'] ?? 0, 0, ",", "."),
                "tooltip" => 'jumlah transaksi yang di reject oleh outlet saat transaksi masuk ke jilid+	'
            ],
            'total_auto_reject' => [
                'title' => 'Auto Rejected Order',
                'amount' => number_format($report['total_auto_reject'] ?? 0, 0, ",", "."),
                "tooltip" => 'jumlah transaksi yang tidak di response oleh outlet dan terproses auto reject oleh sistem'
            ],
            'total_reject' => [
                'title' => 'Total Order Ditolak',
                'amount' => number_format($report['total_reject'] ?? 0, 0, ",", "."),
                "tooltip" => 'Jumlah transaksi ditolak (secara manual maupun otomatis oleh sistem)',
                "show" => 1
            ],
        ];

        return MyHelper::checkGet($result);
    }

    public function listDaily(Request $request)
    {
        $post = $request->json()->all();
        if (!$request->id_outlet) {
            return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
        }

        $list = Transaction::where('transactions.id_outlet', $request->id_outlet)
                    ->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                    ->where('transactions.transaction_payment_status', 'Completed')
                    // ->whereNull('reject_at')
                    ->select(DB::raw('
						Date(transactions.transaction_date) as transaction_date,
						COUNT(CASE WHEN transactions.id_transaction AND transaction_pickups.reject_at IS NULL THEN 1 ELSE NULL END) AS total_transaction, 
						COUNT(CASE WHEN transaction_pickups.pickup_by = "Customer" AND transaction_pickups.reject_at IS NULL THEN 1 ELSE NULL END) as total_transaction_pickup,
						COUNT(CASE WHEN transaction_pickups.pickup_by != "Customer" AND transaction_pickups.reject_at IS NULL THEN 1 ELSE NULL END) as total_transaction_delivery,
						SUM(CASE WHEN transactions.transaction_gross IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN transactions.transaction_gross ELSE 0 END) as total_subtotal,
						SUM(
							CASE WHEN transactions.transaction_discount_item IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN ABS(transactions.transaction_discount_item) 
								WHEN transactions.transaction_discount IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN ABS(transactions.transaction_discount)
								ELSE 0 END
							+ CASE WHEN transactions.transaction_discount_delivery IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN ABS(transactions.transaction_discount_delivery) ELSE 0 END
							+ CASE WHEN transactions.transaction_discount_bill IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN ABS(transactions.transaction_discount_bill) ELSE 0 END
						) as total_discount,
						SUM(CASE WHEN transaction_pickups.reject_at IS NULL THEN transactions.transaction_shipment_go_send + transactions.transaction_shipment ELSE 0 END) as total_delivery,
						SUM(CASE WHEN transaction_pickups.reject_at IS NULL THEN transactions.transaction_grandtotal ELSE 0 END) as total_grandtotal,
						COUNT(CASE WHEN transaction_pickups.receive_at IS NOT NULL THEN 1 ELSE NULL END) as total_accept,
						COUNT(CASE WHEN transaction_pickups.reject_at IS NOT NULL THEN 1 ELSE NULL END) as total_reject,
						FLOOR(
							(
								COUNT(CASE WHEN transaction_pickups.receive_at IS NOT NULL THEN 1 ELSE NULL END) 
								/ COUNT(CASE WHEN transactions.transaction_payment_status = "Completed" THEN 1 ELSE NULL END)
							)
							* 100
						) as acceptance_rate,
						COUNT(
							CASE WHEN transaction_pickups.receive_at IS NOT NULL THEN 1
								 WHEN transaction_pickups.reject_reason NOT LIKE "auto reject order by system%" THEN 1
							ELSE NULL END
						) as total_response,
						COUNT(
							CASE WHEN transaction_pickups.reject_reason = "auto reject order by system" THEN 1
							ELSE NULL END
						) as total_auto_reject,
						COUNT(
							CASE WHEN transaction_pickups.receive_at IS NULL AND transaction_pickups.reject_reason NOT LIKE "auto reject order by system%" THEN 1
							ELSE NULL END
						) as total_manual_reject,
						FLOOR(
							(
								COUNT(
									CASE WHEN transaction_pickups.receive_at IS NOT NULL THEN 1
										 WHEN transaction_pickups.reject_reason NOT LIKE "auto reject order by system%" THEN 1
									ELSE NULL END
								)/ COUNT(CASE WHEN transactions.transaction_payment_status = "Completed" THEN 1 ELSE NULL END)
							)
							* 100
						) as response_rate,
						COUNT(CASE WHEN transactions.transaction_payment_status = "Completed" THEN 1 ELSE NULL END) as total_complete_payment
					'))
                    ->groupBy(DB::raw('Date(transactions.transaction_date)'));

        if (isset($post['filter_type']) && $post['filter_type'] == 'range_date') {
            $dateStart = date('Y-m-d', strtotime($post['date_start']));
            $dateEnd = date('Y-m-d', strtotime($post['date_end']));
            $list = $list->whereDate('transactions.transaction_date', '>=', $dateStart)->whereDate('transactions.transaction_date', '<=', $dateEnd);
        } elseif (isset($post['filter_type']) && $post['filter_type'] == 'today') {
            $currentDate = date('Y-m-d');
            $list = $list->whereDate('transactions.transaction_date', $currentDate);
        } else {
            $list = $list->whereDate('transactions.transaction_date', date('Y-m-d'));
        }

        $order = $post['order'] ?? 'transaction_date';
        $orderType = $post['order_type'] ?? 'desc';
        $list = $list->orderBy($order, $orderType);

        $sub = $list;

        $query = DB::table(DB::raw('(' . $sub->toSql() . ') as report_sales'))
                ->mergeBindings($sub->getQuery());

        $this->filterSalesReport($query, $post);

        if ($post['export'] == 1) {
            $query = $query->get();
        } else {
            $query = $query->paginate(30);
        }

        if (!$query) {
            return response()->json(['status' => 'fail', 'messages' => ['Empty']]);
        }

        $result = $query->toArray();

        /*$data = $result['data'] ?? $result;
        foreach ($data as $key => &$value) {
      //    $value['acceptance_rate'] = 0;
            // if ($value['total_accept']) {
            //  $value['acceptance_rate'] = floor(( $value['total_accept'] / ($value['total_accept'] + $value['total_reject']) ) * 100);
            // }

            // if ($value['total_discount']) {
            //  $value['total_discount'] = abs($value['total_discount']);
            // }
        }

        if($post['export'] != 1){
            $result['data'] = $data;
            $data = $result;
        }

        return MyHelper::checkGet($data);*/
        return MyHelper::checkGet($result);
    }

    public function filterSalesReport($query, $filter)
    {
        if (isset($filter['rule'])) {
            foreach ($filter['rule'] as $key => $con) {
                if (is_object($con)) {
                    $con = (array)$con;
                }
                if (isset($con['subject'])) {
                    if ($con['subject'] != 'all_transaction') {
                        $var = $con['subject'];

                        if ($filter['operator'] == 'and') {
                            $query = $query->where($var, $con['operator'], $con['parameter']);
                        } else {
                            $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                        }
                    }
                }
            }
        }

        return $query;
    }
}
