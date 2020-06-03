<?php

namespace Modules\OutletApp\Http\Controllers;

use App\Http\Models\Configs;
use App\Http\Models\DateHoliday;
use App\Http\Models\Holiday;
use App\Http\Models\LogBalance;
use App\Http\Models\OutletHoliday;
use App\Http\Models\OutletSchedule;
use App\Http\Models\OutletToken;
use App\Http\Models\Product;
use App\Http\Models\ProductCategory;
use App\Http\Models\ProductPrice;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionMultiplePayment;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\TransactionPaymentOffline;
use App\Http\Models\TransactionPaymentOvo;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionPickupGoSend;
use App\Http\Models\User;
use App\Http\Models\UserOutlet;
use App\Lib\GoSend;
use App\Lib\Midtrans;
use App\Lib\Ovo;
use App\Lib\MyHelper;
use App\Lib\PushNotificationHelper;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Brand\Entities\Brand;
use Modules\Brand\Entities\BrandProduct;
use Modules\OutletApp\Entities\OutletAppOtp;
use Modules\OutletApp\Http\Requests\DeleteToken;
use Modules\OutletApp\Http\Requests\DetailOrder;
use Modules\OutletApp\Http\Requests\HolidayUpdate;
use Modules\OutletApp\Http\Requests\ListProduct;
use Modules\OutletApp\Http\Requests\ProductSoldOut;
use Modules\OutletApp\Http\Requests\UpdateToken;
use Modules\Outlet\Entities\OutletScheduleUpdate;
use Modules\OutletApp\Jobs\AchievementCheck;
use Modules\Product\Entities\ProductStockStatusUpdate;
use Modules\SettingFraud\Entities\FraudDetectionLogTransactionDay;
use Modules\SettingFraud\Entities\FraudDetectionLogTransactionWeek;
use Modules\Transaction\Http\Requests\TransactionDetail;

class ApiOutletApp extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm    = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->balance    = "Modules\Balance\Http\Controllers\BalanceController";
        $this->getNotif   = "Modules\Transaction\Http\Controllers\ApiNotification";
        $this->membership = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->trx        = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
    }

    public function deleteToken(DeleteToken $request)
    {
        $post   = $request->json()->all();
        $delete = OutletToken::where('token', $post['token'])->first();
        if (!empty($delete)) {
            $delete->delete();
            if (!$delete) {
                return response()->json(['status' => 'fail', 'messages' => ['Delete token failed']]);
            }
        }

        return response()->json(['status' => 'success', 'messages' => ['Delete token success']]);
    }

    public function updateToken(UpdateToken $request)
    {
        $post   = $request->json()->all();
        $outlet = $request->user();

        $check = OutletToken::where('id_outlet', '=', $outlet['id_outlet'])
            ->where('token', '=', $post['token'])
            ->get()
            ->toArray();

        if ($check) {
            return response()->json(['status' => 'success']);
        } else {
            $query = OutletToken::create(['id_outlet' => $outlet['id_outlet'], 'token' => $post['token']]);return response()->json(MyHelper::checkUpdate($query));
        }
    }

    public function listOrder(Request $request)
    {
        $post   = $request->json()->all();
        $outlet = $request->user();

        $list = Transaction::leftjoin('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
            ->leftJoin('transaction_products', 'transactions.id_transaction', 'transaction_products.id_transaction')
            ->leftJoin('users', 'users.id', 'transactions.id_user')
            ->select('transactions.id_transaction', 'transaction_receipt_number', 'order_id', 'transaction_date',
                DB::raw('(CASE WHEN pickup_by = "Customer" THEN "Pickup Order" ELSE "Delivery" END) AS transaction_type'),
                'pickup_by', 'pickup_type', 'pickup_at', 'receive_at', 'ready_at', 'taken_at', 'reject_at', 'transaction_grandtotal', DB::raw('sum(transaction_product_qty) as total_item'), 'users.name')
            ->where('transactions.id_outlet', $outlet->id_outlet)
            ->whereDate('transaction_date', date('Y-m-d'))
            ->where('transaction_payment_status', 'Completed')
            ->where('trasaction_type', 'Pickup Order')
            ->whereNull('void_date')
            ->groupBy('transaction_products.id_transaction')
            ->orderBy('pickup_at', 'ASC')
            ->orderBy('transaction_date', 'ASC')
            ->orderBy('transactions.id_transaction', 'ASC');

        //untuk search
        if (isset($post['search_order_id'])) {
            $list = $list->where('order_id', 'LIKE', '%' . $post['search_order_id'] . '%');
        }

        //by status
        if (isset($post['status'])) {
            if ($post['status'] == 'Pending') {
                $list = $list->whereNull('receive_at')
                    ->whereNull('ready_at')
                    ->whereNull('taken_at');
            }
            if ($post['status'] == 'Accepted') {
                $list = $list->whereNull('ready_at')
                    ->whereNull('taken_at');
            }
            if ($post['status'] == 'Ready') {
                $list = $list->whereNull('taken_at');
            }
            if ($post['status'] == 'Taken') {
                $list = $list->whereNotNull('taken_at');
            }
        }

        $list = $list->get()->toArray();

        //dikelompokkan sesuai status
        $listPending        = [];
        $listOnGoing        = [];
        $listOnGoingSet     = [];
        $listOnGoingNow     = [];
        $listOnGoingArrival = [];
        $listReady          = [];
        $listCompleted      = [];

        foreach ($list as $i => $dataList) {
            $qr = $dataList['order_id'];

            // $qrCode = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data='.$qr;
            $qrCode = 'https://chart.googleapis.com/chart?chl=' . $qr . '&chs=250x250&cht=qr&chld=H%7C0';
            $qrCode = html_entity_decode($qrCode);

            $dataList = array_slice($dataList, 0, 3, true) +
            array("order_id_qrcode" => $qrCode) +
            array_slice($dataList, 3, count($dataList) - 1, true);

            $dataList['order_id'] = strtoupper($dataList['order_id']);
            if ($dataList['reject_at'] != null) {
                $dataList['status'] = 'Rejected';
                $listCompleted[]    = $dataList;
            } elseif ($dataList['receive_at'] == null) {
                $dataList['status'] = 'Pending';
                $listPending[]      = $dataList;
            } elseif ($dataList['receive_at'] != null && $dataList['ready_at'] == null) {
                $dataList['status'] = 'Accepted';
                $listOnGoing[]      = $dataList;
                if ($dataList['pickup_type'] == 'set time') {
                    $listOnGoingSet[] = $dataList;
                } elseif ($dataList['pickup_type'] == 'right now') {
                    $listOnGoingNow[] = $dataList;
                } elseif ($dataList['pickup_type'] == 'at arrival') {
                    $listOnGoingArrival[] = $dataList;
                }
            } elseif ($dataList['receive_at'] != null && $dataList['ready_at'] != null && $dataList['taken_at'] == null) {
                $dataList['status'] = 'Ready';
                $listReady[]        = $dataList;
            } elseif ($dataList['receive_at'] != null && $dataList['ready_at'] != null && $dataList['taken_at'] != null) {
                $dataList['status'] = 'Completed';
                $listCompleted[]    = $dataList;
            }
        }

        //sorting pickup time list on going yg set time
        usort($listOnGoingSet, function ($a, $b) {
            return $a['pickup_at'] <=> $b['pickup_at'];
        });

        //return 1 array
        $result['pending']['count'] = count($listPending);
        $result['pending']['data']  = $listPending;

        $result['on_going']['count'] = count($listOnGoingNow) + count($listOnGoingSet) + count($listOnGoingArrival);
        $result['on_going']['data']  = $listOnGoing;
        // $result['on_going']['data']['right_now']['count'] = count($listOnGoingNow);
        // $result['on_going']['data']['right_now']['data'] = $listOnGoingNow;
        // $result['on_going']['data']['pickup_time']['count'] = count($listOnGoingSet);
        // $result['on_going']['data']['pickup_time']['data'] = $listOnGoingSet;
        // $result['on_going']['data']['at_arrival']['count'] = count($listOnGoingArrival);
        // $result['on_going']['data']['at_arrival']['data'] = $listOnGoingArrival;

        $result['ready']['count'] = count($listReady);
        $result['ready']['data']  = $listReady;

        $result['completed']['count'] = count($listCompleted);
        $result['completed']['data']  = $listCompleted;

        if (isset($post['status'])) {
            if ($post['status'] == 'Pending') {
                unset($result['on_going']);
                unset($result['ready']);
                unset($result['completed']);
            }
            if ($post['status'] == 'Accepted') {
                unset($result['pending']);
                unset($result['ready']);
                unset($result['completed']);
            }
            if ($post['status'] == 'Ready') {
                unset($result['pending']);
                unset($result['on_going']);
                unset($result['completed']);
            }
            if ($post['status'] == 'Completed') {
                unset($result['pending']);
                unset($result['on_going']);
                unset($result['ready']);
            }
        }

        return response()->json(MyHelper::checkGet($result));

    }

    public function detailOrder(DetailOrder $request)
    {
        $post = $request->json()->all();

        $list = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
            ->where('order_id', $post['order_id'])
            ->whereDate('transaction_date', date('Y-m-d'))
            ->with('user.city.province', 'productTransaction.product.product_category', 'productTransaction.product.product_discounts', 'outlet')->first();

        $qr = $list['order_id'];

        // $qrCode = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data='.$qr;
        $qrCode     = 'https://chart.googleapis.com/chart?chl=' . $qr . '&chs=250x250&cht=qr&chld=H%7C0';
        $list['qr'] = html_entity_decode($qrCode);

        if (!$list) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Data Order Not Found'],
            ]);
        }

        if ($list['reject_at'] != null) {
            $statusPickup = 'Reject';
        } elseif ($list['taken_at'] != null) {
            $statusPickup = 'Taken';
        } elseif ($list['ready_at'] != null) {
            $statusPickup = 'Ready';
        } elseif ($list['receive_at'] != null) {
            $statusPickup = 'On Going';
        } else {
            $statusPickup = 'Pending';
        }

        $list = array_slice($list->toArray(), 0, 29, true) +
        array("status" => $statusPickup) +
        array_slice($list->toArray(), 29, count($list->toArray()) - 1, true);

        $label = [];

        $order = Setting::where('key', 'transaction_grand_total_order')->value('value');
        $exp   = explode(',', $order);

        foreach ($exp as $i => $value) {
            if ($exp[$i] == 'subtotal') {
                unset($exp[$i]);
                continue;
            }

            if ($exp[$i] == 'tax') {
                $exp[$i] = 'transaction_tax';
                array_push($label, 'Tax');
            }

            if ($exp[$i] == 'service') {
                $exp[$i] = 'transaction_service';
                array_push($label, 'Service Fee');
            }

            if ($exp[$i] == 'shipping') {
                if ($list['trasaction_type'] == 'Pickup Order') {
                    unset($exp[$i]);
                    continue;
                } else {
                    $exp[$i] = 'transaction_shipment';
                    array_push($label, 'Delivery Cost');
                }
            }

            if ($exp[$i] == 'discount') {
                $exp[$i] = 'transaction_discount';
                array_push($label, 'Discount');
                continue;
            }

            if (stristr($exp[$i], 'empty')) {
                unset($exp[$i]);
                continue;
            }
        }

        array_splice($exp, 0, 0, 'transaction_subtotal');
        array_splice($label, 0, 0, 'Cart Total');

        array_values($exp);
        array_values($label);

        $imp         = implode(',', $exp);
        $order_label = implode(',', $label);

        $detail = [];

        $list['order']       = $imp;
        $list['order_label'] = $order_label;

        return response()->json(MyHelper::checkGet($list));
    }

    public function detailWebviewPage(Request $request)
    {
        $id = $request->json('receipt');

        if ($request->json('id_transaction')) {
            $list = Transaction::where('id_transaction', $request->json('id_transaction'))->with('user.city.province', 'productTransaction.product.product_category', 'productTransaction.product.product_photos', 'productTransaction.product.product_discounts', 'transaction_payment_offlines', 'outlet.city')->first();
        } else {
            $list = Transaction::where('transaction_receipt_number', $id)->with('user.city.province', 'productTransaction.product.product_category', 'productTransaction.product.product_photos', 'productTransaction.product.product_discounts', 'transaction_payment_offlines', 'outlet.city')->first();
        }
        $label  = [];
        $label2 = [];

        $cart = $list['transaction_subtotal'] + $list['transaction_shipment'] + $list['transaction_service'] + $list['transaction_tax'] - $list['transaction_discount'];

        $list['transaction_carttotal'] = $cart;

        $order = Setting::where('key', 'transaction_grand_total_order')->value('value');
        $exp   = explode(',', $order);
        $exp2  = explode(',', $order);

        foreach ($exp as $i => $value) {
            if ($exp[$i] == 'subtotal') {
                unset($exp[$i]);
                unset($exp2[$i]);
                continue;
            }

            if ($exp[$i] == 'tax') {
                $exp[$i]  = 'transaction_tax';
                $exp2[$i] = 'transaction_tax';
                array_push($label, 'Tax');
                array_push($label2, 'Tax');
            }

            if ($exp[$i] == 'service') {
                $exp[$i]  = 'transaction_service';
                $exp2[$i] = 'transaction_service';
                array_push($label, 'Service Fee');
                array_push($label2, 'Service Fee');
            }

            if ($exp[$i] == 'shipping') {
                if ($list['trasaction_type'] == 'Pickup Order') {
                    unset($exp[$i]);
                    unset($exp2[$i]);
                    continue;
                } else {
                    $exp[$i]  = 'transaction_shipment';
                    $exp2[$i] = 'transaction_shipment';
                    array_push($label, 'Delivery Cost');
                    array_push($label2, 'Delivery Cost');
                }
            }

            if ($exp[$i] == 'discount') {
                $exp2[$i] = 'transaction_discount';
                array_push($label2, 'Discount');
                unset($exp[$i]);
                continue;
            }

            if (stristr($exp[$i], 'empty')) {
                unset($exp[$i]);
                unset($exp2[$i]);
                continue;
            }
        }

        if ($list['trasaction_payment_type'] == 'Balance') {
            $log = LogBalance::where('id_reference', $list['id_transaction'])->where('source', 'Transaction')->where('balance', '<', 0)->first();
            if ($log['balance'] < 0) {
                $list['balance'] = $log['balance'];
                $list['check']   = 'tidak topup';
            } else {
                $list['balance'] = $list['transaction_grandtotal'] - $log['balance'];
                $list['check']   = 'topup';
            }
        }

        if ($list['trasaction_payment_type'] == 'Manual') {
            $payment         = TransactionPaymentManual::with('manual_payment_method.manual_payment')->where('id_transaction', $list['id_transaction'])->first();
            $list['payment'] = $payment;
        }

        if ($list['trasaction_payment_type'] == 'Midtrans' || $list['trasaction_payment_type'] == 'Balance') {
            //cek multi payment
            $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get();
            if ($multiPayment) {
                foreach ($multiPayment as $dataPay) {
                    if ($dataPay['type'] == 'Balance') {
                        $paymentBalance = TransactionPaymentBalance::find($dataPay['id_payment']);
                        if ($paymentBalance) {
                            $list['balance'] = -$paymentBalance['balance_nominal'];
                        }
                    } else {
                        $payment = TransactionPaymentMidtran::find($dataPay['id_payment']);
                    }
                }
                if (isset($payment)) {
                    $list['payment'] = $payment;
                }
            } else {
                if ($list['trasaction_payment_type'] == 'Balance') {
                    $paymentBalance = TransactionPaymentBalance::where('id_transaction', $list['id_transaction'])->first();
                    if ($paymentBalance) {
                        $list['balance'] = -$paymentBalance['balance_nominal'];
                    }
                }

                if ($list['trasaction_payment_type'] == 'Midtrans') {
                    $paymentMidtrans = TransactionPaymentMidtran::where('id_transaction', $list['id_transaction'])->first();
                    if ($paymentMidtrans) {
                        $list['payment'] = $paymentMidtrans;
                    }
                }
            }
        }

        if ($list['trasaction_payment_type'] == 'Offline') {
            $payment         = TransactionPaymentOffline::where('id_transaction', $list['id_transaction'])->get();
            $list['payment'] = $payment;
        }

        array_splice($exp, 0, 0, 'transaction_subtotal');
        array_splice($label, 0, 0, 'Cart Total');

        array_splice($exp2, 0, 0, 'transaction_subtotal');
        array_splice($label2, 0, 0, 'Cart Total');

        array_values($exp);
        array_values($label);

        array_values($exp2);
        array_values($label2);

        $imp         = implode(',', $exp);
        $order_label = implode(',', $label);

        $imp2         = implode(',', $exp2);
        $order_label2 = implode(',', $label2);

        $detail = [];

        $qrTest = '';

        if ($list['trasaction_type'] == 'Pickup Order') {
            $detail = TransactionPickup::where('id_transaction', $list['id_transaction'])->with('transaction_pickup_go_send')->first();
            $qrTest = $detail['order_id'];
        } elseif ($list['trasaction_type'] == 'Delivery') {
            $detail = TransactionShipment::with('city.province')->where('id_transaction', $list['id_transaction'])->first();
        }

        $list['detail']      = $detail;
        $list['order']       = $imp;
        $list['order_label'] = $order_label;

        $list['order_v2']       = $imp2;
        $list['order_label_v2'] = $order_label2;

        $list['date'] = $list['transaction_date'];
        $list['type'] = 'trx';

        $list['kind'] = $list['trasaction_type'];

        $warning    = 0;
        $takenLabel = '';

        if ($detail['reject_at'] != null) {
            $statusPickup = 'Reject';
        } elseif ($detail['taken_at'] != null) {
            $statusPickup = 'Taken';
            $warning      = 1;
            $takenLabel   = $this->convertMonth($detail['taken_at']);
        } elseif ($detail['ready_at'] != null) {
            $statusPickup = 'Ready';
        } elseif ($detail['receive_at'] != null) {
            $statusPickup = 'On Going';
        } else {
            $statusPickup = 'Pending';
        }

        if (isset($success)) {
            $list['success'] = 1;

        }

        // $qrCode = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data='.$qrTest;
        $qrCode     = 'https://chart.googleapis.com/chart?chl=' . $qrTest . '&chs=250x250&cht=qr&chld=H%7C0';
        $qrCode     = html_entity_decode($qrCode);
        $list['qr'] = $qrCode;

        $settingService = Setting::where('key', 'service')->first();
        $settingTax     = Setting::where('key', 'tax')->first();

        $list['valueService'] = 100 * $settingService['value'];
        $list['valueTax']     = 100 * $settingTax['value'];
        $list['status']       = $statusPickup;
        $list['warning']      = $warning;
        $list['taken_label']  = $takenLabel;

        return response()->json(MyHelper::checkGet($list));
    }

    public function convertMonth($date)
    {
        if (date('m', strtotime($date)) == '01') {
            $month = 'Januari';
        } elseif (date('m', strtotime($date)) == '02') {
            $month = 'Februari';
        } elseif (date('m', strtotime($date)) == '03') {
            $month = 'Maret';
        } elseif (date('m', strtotime($date)) == '04') {
            $month = 'April';
        } elseif (date('m', strtotime($date)) == '05') {
            $month = 'Mei';
        } elseif (date('m', strtotime($date)) == '06') {
            $month = 'Juni';
        } elseif (date('m', strtotime($date)) == '07') {
            $month = 'Juli';
        } elseif (date('m', strtotime($date)) == '08') {
            $month = 'Agustus';
        } elseif (date('m', strtotime($date)) == '09') {
            $month = 'September';
        } elseif (date('m', strtotime($date)) == '10') {
            $month = 'Oktober';
        } elseif (date('m', strtotime($date)) == '11') {
            $month = 'November';
        } elseif (date('m', strtotime($date)) == '12') {
            $month = 'Desember';
        }

        $day  = date('d', strtotime($date));
        $year = date('Y', strtotime($date));

        $time = date('H:i', strtotime($date));

        return $day . ' ' . $month . ' ' . $year . ' ' . $time;
    }

    public function detailWebview(DetailOrder $request)
    {
        $post = $request->json()->all();

        if (!isset($post['transaction_date'])) {
            $post['transaction_date'] = date('Y-m-d');
        }

        if (empty($check)) {
            $list = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
                ->where('order_id', $post['order_id'])
                ->whereIn('transaction_payment_status', ['Pending', 'Completed'])
                ->whereDate('transaction_date', date('Y-m-d', strtotime($post['transaction_date'])))
                ->first();

            if (!$list) {
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Data Order Not Found'],
                ]);
            }

            if ($list['reject_at'] != null) {
                $statusPickup = 'Reject';
            } elseif ($list['taken_at'] != null) {
                $statusPickup = 'Taken';
            } elseif ($list['ready_at'] != null) {
                $statusPickup = 'Ready';
            } elseif ($list['receive_at'] != null) {
                $statusPickup = 'On Going';
            } else {
                $statusPickup = 'Pending';
            }

            $dataEncode = [
                'order_id' => $list->order_id,
                'receipt'  => $list->transaction_receipt_number,
            ];

            $encode = json_encode($dataEncode);
            $base   = base64_encode($encode);

            $send = [
                'status' => 'success',
                'result' => [
                    'status'         => $statusPickup,
                    'date'           => $list->transaction_date,
                    'reject_at'      => $list->reject_at,
                    'id_transaction' => $list->id_transaction,
                    'url'            => env('API_URL') . '/transaction/web/view/outletapp?data=' . $base,
                ],
            ];

            return response()->json($send);
        }

    }

    public function acceptOrder(DetailOrder $request)
    {
        $post   = $request->json()->all();
        $outlet = $request->user();

        $order = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
            ->where('order_id', $post['order_id'])
            ->whereDate('transaction_date', date('Y-m-d'))
            ->first();

        if (!$order) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Data Transaction Not Found'],
            ]);
        }

        if ($order->id_outlet != $request->user()->id_outlet) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Data Transaction Outlet Does Not Match'],
            ]);
        }

        if ($order->reject_at != null) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Order Has Been Rejected'],
            ]);
        }

        if ($order->receive_at != null) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Order Has Been Received'],
            ]);
        }

        DB::beginTransaction();

        $pickup = TransactionPickup::where('id_transaction', $order->id_transaction)->update(['receive_at' => date('Y-m-d H:i:s')]);

        if ($pickup) {
            //send notif to customer
            $user = User::find($order->id_user);
            $send = app($this->autocrm)->SendAutoCRM('Order Accepted', $user['phone'], [
                "outlet_name"      => $outlet['outlet_name'],
                'id_transaction'   => $order->id_transaction,
                "id_reference"     => $order->transaction_receipt_number . ',' . $order->id_outlet,
                "transaction_date" => $order->transaction_date]
            );
            if ($send != true) {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Failed Send notification to customer'],
                ]);
            }

            DB::commit();
        }

        return response()->json(MyHelper::checkUpdate($pickup));
    }

    public function SetReady(DetailOrder $request)
    {
        $post   = $request->json()->all();
        $outlet = $request->user();

        $order = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
            ->where('order_id', $post['order_id'])
            ->whereDate('transaction_date', date('Y-m-d'))
            ->first();

        if (!$order) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Data Transaction Not Found'],
            ]);
        }

        if ($order->id_outlet != $request->user()->id_outlet) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Data Transaction Outlet Does Not Match'],
            ]);
        }

        if ($order->reject_at != null) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Order Has Been Rejected'],
            ]);
        }

        if ($order->receive_at == null) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Order Has Not Been Accepted'],
            ]);
        }

        if ($order->ready_at != null) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Order Has Been Marked as Ready'],
            ]);
        }

        DB::beginTransaction();
        $pickup = TransactionPickup::where('id_transaction', $order->id_transaction)->update(['ready_at' => date('Y-m-d H:i:s')]);

        // sendPoint delivery after status delivered only
        if ($pickup && $order->pickup_by == 'Customer' && $order->cashback_insert_status != 1) {
            //send notif to customer
            $user = User::find($order->id_user);

            $newTrx    = Transaction::with('user.memberships', 'outlet', 'productTransaction', 'transaction_vouchers','promo_campaign_promo_code','promo_campaign_promo_code.promo_campaign')->where('id_transaction', $order->id_transaction)->first();
            $checkType = TransactionMultiplePayment::where('id_transaction', $order->id_transaction)->get()->toArray();
            $column    = array_column($checkType, 'type');
            
            $use_referral = optional(optional($newTrx->promo_campaign_promo_code)->promo_campaign)->promo_type == 'Referral';

            if (!in_array('Balance', $column) || $use_referral) {

                $promo_source = null;
                if ($newTrx->id_promo_campaign_promo_code || $newTrx->transaction_vouchers) {
                    if ($newTrx->id_promo_campaign_promo_code) {
                        $promo_source = 'promo_code';
                    } elseif (($newTrx->transaction_vouchers[0]->status ?? false) == 'success') {
                        $promo_source = 'voucher_online';
                    }
                }

                if (app($this->trx)->checkPromoGetPoint($promo_source) || $use_referral) {
                    $savePoint = app($this->getNotif)->savePoint($newTrx);
                    // return $savePoint;
                    if (!$savePoint) {
                        DB::rollback();
                        return response()->json([
                            'status'   => 'fail',
                            'messages' => ['Transaction failed'],
                        ]);
                    }
                }

            }

            $newTrx->update(['cashback_insert_status' => 1]);
            $checkMembership = app($this->membership)->calculateMembership($user['phone']);
            DB::commit();
            $send = app($this->autocrm)->SendAutoCRM('Order Ready', $user['phone'], [
                "outlet_name"      => $outlet['outlet_name'],
                'id_transaction'   => $order->id_transaction,
                "id_reference"     => $order->transaction_receipt_number . ',' . $order->id_outlet,
                "transaction_date" => $order->transaction_date,
            ]);
            if ($send != true) {
                // DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Failed Send notification to customer'],
                ]);
            }
        }
        DB::commit();
        // return  $pickup = TransactionPickup::where('id_transaction', $order->id_transaction)->first();
        return response()->json(MyHelper::checkUpdate($pickup));
    }

    public function takenOrder(DetailOrder $request)
    {
        $post   = $request->json()->all();
        $outlet = $request->user();

        $order = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
            ->where('order_id', $post['order_id'])
            ->whereDate('transaction_date', date('Y-m-d'))
            ->first();

        if (!$order) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Data Transaction Not Found'],
            ]);
        }

        if ($order->id_outlet != $request->user()->id_outlet) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Data Transaction Outlet Does Not Match'],
            ]);
        }

        if ($order->reject_at != null) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Order Has Been Rejected'],
            ]);
        }

        if ($order->receive_at == null) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Order Has Not Been Accepted'],
            ]);
        }

        if ($order->ready_at == null) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Order Has Not Been Marked as Ready'],
            ]);
        }

        if ($order->taken_at != null) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Order Has Been Taken'],
            ]);
        }

        DB::beginTransaction();

        $pickup = TransactionPickup::where('id_transaction', $order->id_transaction)->update(['taken_at' => date('Y-m-d H:i:s')]);
        $order->show_rate_popup = 1;
        $order->save();
        if ($pickup) {
            //send notif to customer
            $user = User::find($order->id_user);
            $send = app($this->autocrm)->SendAutoCRM('Order Taken', $user['phone'], [
                "outlet_name"      => $outlet['outlet_name'],
                'id_transaction'   => $order->id_transaction,
                "id_reference"     => $order->transaction_receipt_number . ',' . $order->id_outlet,
                "transaction_date" => $order->transaction_date,
            ]);
            if ($send != true) {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Failed Send notification to customer'],
                ]);
            }

            AchievementCheck::dispatch(['id_transaction' => $order->id_transaction])->onConnection('achievement');

            DB::commit();
        }

        return response()->json(MyHelper::checkUpdate($pickup));
    }

    public function profile(Request $request)
    {
        $outlet                    = $request->user();
        $profile['outlet_name']    = $outlet['outlet_name'];
        $profile['outlet_code']    = $outlet['outlet_code'];
        $profile['outlet_address'] = $outlet['outlet_address'];
        $profile['outlet_phone']   = $outlet['outlet_phone'];
        $profile['status']         = 'success';

        //save token outlet
        $post = $request->json()->all();
        if (isset($post['device_id']) && isset($post['device_token'])) {
            $cek = OutletToken::where('device_id', $post['device_id'])->first();
            if ($cek) {
                $saveToken = OutletToken::where('device_id', $post['device_id'])->update(['token' => $post['device_token'], 'id_outlet' => $outlet['id_outlet']]);
            } else {
                $saveToken = OutletToken::create(['device_id' => $post['device_id'], 'token' => $post['device_token'], 'id_outlet' => $outlet['id_outlet']]);
            }
        }

        return response()->json($profile);
    }

    public function productSoldOut(ProductSoldOut $request)
    {
        $post        = $request->json()->all();
        $outlet      = $request->user();
        $user_outlet = $request->user_outlet;
        $otp         = $request->outlet_app_otps;
        $updated     = 0;
        $date_time   = date('Y-m-d H:i:s');
        if ($post['sold_out']) {
            $found = ProductPrice::where('id_outlet', $outlet['id_outlet'])
                ->whereIn('id_product', $post['sold_out'])
                ->where('product_stock_status', '<>', 'Sold Out');
            $x = $found->get()->toArray();
            foreach ($x as $product) {
                $create = ProductStockStatusUpdate::create([
                    'id_product'        => $product['id_product'],
                    'id_user'           => null,
                    'user_type'         => 'seeds',
                    'user_name'         => $user_outlet['name'],
                    'user_email'        => $user_outlet['email'],
                    'id_outlet'         => $outlet->id_outlet,
                    'date_time'         => $date_time,
                    'new_status'        => 'Sold Out',
                    'id_outlet_app_otp' => null,
                ]);
            }
            $updated += $found->update(['product_stock_status' => 'Sold Out']);
        }
        if ($post['available']) {
            $found = ProductPrice::where('id_outlet', $outlet['id_outlet'])
                ->whereIn('id_product', $post['available'])
                ->where('product_stock_status', '<>', 'Available');
            $x = $found->get()->toArray();
            foreach ($x as $product) {
                $create = ProductStockStatusUpdate::create([
                    'id_product'        => $product['id_product'],
                    'id_user'           => null,
                    'user_type'         => 'seeds',
                    'user_name'         => $user_outlet['name'],
                    'user_email'        => $user_outlet['email'],
                    'id_outlet'         => $outlet->id_outlet,
                    'date_time'         => $date_time,
                    'new_status'        => 'Available',
                    'id_outlet_app_otp' => null,
                ]);
            }
            $updated += $found->update(['product_stock_status' => 'Available']);
        }
        return [
            'status' => 'success',
            'result' => ['updated' => $updated],
        ];
    }
    /**
     * return list category group by brand
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function listCategory(Request $request)
    {
        $outlet = $request->user();
        $sub    = BrandProduct::select('id_brand', 'id_product', 'id_product_category')->distinct();
        $data   = DB::query()->fromSub($sub, 'brand_product')->select(\DB::raw('brand_product.id_brand,brand_product.id_product_category,count(*) as total_product,sum(case product_stock_status when "Sold Out" then 1 else 0 end) total_sold_out,product_category_name'))
            ->join('product_categories', 'product_categories.id_product_category', '=', 'brand_product.id_product_category')
            ->join('products', function ($query) {
                $query->on('brand_product.id_product', '=', 'products.id_product')
                    ->groupBy('products.id_product');
            })
        // product availbale in outlet
            ->join('product_prices', 'product_prices.id_product', '=', 'products.id_product')
            ->where('product_prices.id_outlet', '=', $outlet['id_outlet'])
        // brand produk ada di outlet
            ->whereColumn('brand_outlet.id_outlet', '=', 'product_prices.id_outlet')
            ->join('brand_outlet', 'brand_outlet.id_brand', '=', 'brand_product.id_brand')
            ->where(function ($query) {
                $query->where('product_prices.product_visibility', '=', 'Visible')
                    ->orWhere(function ($q) {
                        $q->whereNull('product_prices.product_visibility')
                            ->where('products.product_visibility', 'Visible');
                    });
            })
            ->where('product_prices.product_status', '=', 'Active')
            ->whereNotNull('product_prices.product_price')
            ->groupBy('brand_product.id_brand', 'brand_product.id_product_category')
            ->orderBy('product_category_order')
            ->get()->toArray();
        $result = MyHelper::groupIt($data, 'id_brand', null, function ($key, &$val) {
            $brand = Brand::select('id_brand', 'name_brand', 'order_brand')
                ->where([
                    'id_brand'         => $key,
                    'brand_active'     => 1,
                    'brand_visibility' => 1,
                ])->first();
            $brand['categories'] = $val;
            $val                 = $brand;
            return $key;
        });
        usort($result, function ($a, $b) {
            return $a['order_brand'] <=> $b['order_brand'];
        });
        return MyHelper::checkGet(array_values($result));
    }
    /**
     * Return only list product based on selected brand and category
     * @param string $value [description]
     */
    public function productList(ListProduct $request)
    {
        $outlet            = $request->user();
        $post              = $request->json()->all();
        $post['id_outlet'] = $outlet['id_outlet'];
        $products          = Product::select([
            'products.id_product', 'products.product_code', 'products.product_name', 'product_prices.product_stock_status',
        ])
            ->join('brand_product', 'brand_product.id_product', '=', 'products.id_product')
            ->where('brand_product.id_brand', '=', $post['id_brand'])
            ->where('brand_product.id_product_category', '=', $post['id_product_category'])
        // produk tersedia di outlet
            ->join('product_prices', 'product_prices.id_product', '=', 'products.id_product')
            ->where('product_prices.id_outlet', '=', $post['id_outlet'])
        // brand produk ada di outlet
            ->where('brand_outlet.id_outlet', '=', $post['id_outlet'])
            ->join('brand_outlet', 'brand_outlet.id_brand', '=', 'brand_product.id_brand')
            ->where(function ($query) {
                $query->where('product_prices.product_visibility', '=', 'Visible')
                    ->orWhere(function ($q) {
                        $q->whereNull('product_prices.product_visibility')
                            ->where('products.product_visibility', 'Visible');
                    });
            })
            ->where('product_prices.product_status', '=', 'Active')
            ->whereNotNull('product_prices.product_price')
            ->groupBy('products.id_product')
            ->orderBy('products.position')
            ->orderBy('products.id_product');
        if ($request->page) {
            $data = $products->paginate()->toArray();
            if (empty($data['data'])) {
                return MyHelper::checkGet($data['data']);
            }
            return MyHelper::checkGet($data);
        } else {
            return MyHelper::checkGet($products->get()->toArray());
        }
    }

    /**
     * Return list product and groub by its category
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function listProduct(Request $request)
    {
        $outlet       = $request->user();
        $listCategory = ProductCategory::join('products', 'product_categories.id_product_category', 'products.id_product_category')
            ->join('product_prices', 'product_prices.id_product', 'products.id_product')
            ->where('id_outlet', $outlet['id_outlet'])
            ->where('product_prices.product_visibility', '=', 'Visible')
            ->where('product_prices.product_status', '=', 'Active')
            ->with('product_category')
        // ->select('id_product_category', 'product_category_name')
            ->get();

        $result      = [];
        $idParent    = [];
        $idParent2   = [];
        $categorized = [];
        foreach ($listCategory as $i => $category) {
            $dataCategory = [];
            $dataProduct  = [];
            if (isset($category['product_category']['id_product_category'])) {
                //masukin ke array result
                $position = array_search($category['product_category']['id_product_category'], $idParent);
                if (!is_integer($position)) {

                    $dataProduct['id_product']           = $category['id_product'];
                    $dataProduct['product_code']         = $category['product_code'];
                    $dataProduct['product_name']         = $category['product_name'];
                    $dataProduct['product_stock_status'] = $category['product_stock_status'];

                    $child['id_product_category']   = $category['id_product_category'];
                    $child['product_category_name'] = $category['product_category_name'];
                    $child['products'][]            = $dataProduct;

                    $dataCategory['id_product_category']   = $category['product_category']['id_product_category'];
                    $dataCategory['product_category_name'] = $category['product_category']['product_category_name'];
                    $dataCategory['child_category'][]      = $child;

                    $categorized[] = $dataCategory;
                    $idParent[]    = $category['product_category']['id_product_category'];
                    $idParent2[][] = $category['id_product_category'];
                } else {
                    $positionChild = array_search($category['id_product_category'], $idParent2[$position]);
                    if (!is_integer($positionChild)) {
                        //masukin product ke child baru
                        $idParent2[$position][] = $category['id_product_category'];

                        $dataCategory['id_product_category']   = $category['id_product_category'];
                        $dataCategory['product_category_name'] = $category['product_category_name'];

                        $dataProduct['id_product']           = $category['id_product'];
                        $dataProduct['product_code']         = $category['product_code'];
                        $dataProduct['product_name']         = $category['product_name'];
                        $dataProduct['product_stock_status'] = $category['product_stock_status'];

                        $dataCategory['products'][]                 = $dataProduct;
                        $categorized[$position]['child_category'][] = $dataCategory;

                    } else {
                        //masukin product child yang sudah ada
                        $dataProduct['id_product']           = $category['id_product'];
                        $dataProduct['product_code']         = $category['product_code'];
                        $dataProduct['product_name']         = $category['product_name'];
                        $dataProduct['product_stock_status'] = $category['product_stock_status'];

                        $categorized[$position]['child_category'][$positionChild]['products'][] = $dataProduct;
                    }
                }
            } else {
                $position = array_search($category['id_product_category'], $idParent);
                if (!is_integer($position)) {
                    $dataProduct['id_product']           = $category['id_product'];
                    $dataProduct['product_code']         = $category['product_code'];
                    $dataProduct['product_name']         = $category['product_name'];
                    $dataProduct['product_stock_status'] = $category['product_stock_status'];

                    $dataCategory['id_product_category']   = $category['id_product_category'];
                    $dataCategory['product_category_name'] = $category['product_category_name'];
                    $dataCategory['products'][]            = $dataProduct;

                    $categorized[] = $dataCategory;
                    $idParent[]    = $category['id_product_category'];
                    $idParent2[][] = [];
                } else {
                    $idParent2[$position][] = $category['id_product_category'];

                    $dataProduct['id_product']           = $category['id_product'];
                    $dataProduct['product_code']         = $category['product_code'];
                    $dataProduct['product_name']         = $category['product_name'];
                    $dataProduct['product_stock_status'] = $category['product_stock_status'];

                    $categorized[$position]['products'][] = $dataProduct;
                }

            }

        }

        // $uncategorized = ProductPrice::join('products', 'product_prices.id_product', 'products.id_product')
        //                                 ->whereIn('products.id_product', function($query){
        //                                     $query->select('id_product')->from('products')->whereNull('id_product_category');
        //                                 })->where('id_outlet', $outlet['id_outlet'])
        //                                 ->select('products.id_product', 'product_code', 'product_name', 'product_stock_status')->get();

        $result['categorized'] = $categorized;
        // $result['uncategorized'] = $uncategorized;
        return response()->json(MyHelper::checkGet($result));
    }

    public function rejectOrder(DetailOrder $request)
    {
        $post = $request->json()->all();

        $outlet = $request->user();

        $order = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
            ->where('order_id', $post['order_id'])
            ->whereDate('transaction_date', date('Y-m-d'))
            ->first();

        if (!$order) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Data Transaction Not Found'],
            ]);
        }

        if ($order->id_outlet != $request->user()->id_outlet) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Data Transaction Outlet Does Not Match'],
            ]);
        }

        if ($order->ready_at) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Order Has Been Ready'],
            ]);
        }

        if ($order->taken_at) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Order Has Been Taken'],
            ]);
        }

        if ($order->reject_at) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Order Has Been Rejected'],
            ]);
        }

        DB::beginTransaction();

        if (!isset($post['reason'])) {
            $post['reason'] = null;
        }

        $pickup = TransactionPickup::where('id_transaction', $order->id_transaction)->update([
            'reject_at'     => date('Y-m-d H:i:s'),
            'reject_reason' => $post['reason'],
        ]);

        if ($pickup) {
            $getLogFraudDay = FraudDetectionLogTransactionDay::whereRaw('Date(fraud_detection_date) ="' . date('Y-m-d', strtotime($order->transaction_date)) . '"')
                ->where('id_user', $order->id_user)
                ->first();
            if ($getLogFraudDay) {
                $checkCount = $getLogFraudDay['count_transaction_day'] - 1;
                if ($checkCount <= 0) {
                    $delLogTransactionDay = FraudDetectionLogTransactionDay::where('id_fraud_detection_log_transaction_day', $getLogFraudDay['id_fraud_detection_log_transaction_day'])
                        ->delete();
                } else {
                    $updateLogTransactionDay = FraudDetectionLogTransactionDay::where('id_fraud_detection_log_transaction_day', $getLogFraudDay['id_fraud_detection_log_transaction_day'])->update([
                        'count_transaction_day' => $checkCount,
                        'updated_at'            => date('Y-m-d H:i:s'),
                    ]);
                }

            }

            $getLogFraudWeek = FraudDetectionLogTransactionWeek::where('fraud_detection_week', date('W', strtotime($order->transaction_date)))
                ->where('fraud_detection_week', date('Y', strtotime($order->transaction_date)))
                ->where('id_user', $order->id_user)
                ->first();
            if ($getLogFraudWeek) {
                $checkCount = $getLogFraudWeek['count_transaction_week'] - 1;
                if ($checkCount <= 0) {
                    $delLogTransactionWeek = FraudDetectionLogTransactionWeek::where('id_fraud_detection_log_transaction_week', $getLogFraudWeek['id_fraud_detection_log_transaction_week'])
                        ->delete();
                } else {
                    $updateLogTransactionWeek = FraudDetectionLogTransactionWeek::where('id_fraud_detection_log_transaction_week', $getLogFraudWeek['id_fraud_detection_log_transaction_week'])->update([
                        'count_transaction_week' => $checkCount,
                        'updated_at'             => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            //refund ke balance
            // if($order['trasaction_payment_type'] == "Midtrans"){
            $multiple = TransactionMultiplePayment::where('id_transaction', $order->id_transaction)->get()->toArray();
            if ($multiple) {
                foreach ($multiple as $pay) {
                    if ($pay['type'] == 'Balance') {
                        $payBalance = TransactionPaymentBalance::find($pay['id_payment']);
                        if ($payBalance) {
                            $refund = app($this->balance)->addLogBalance($order['id_user'], $point = $payBalance['balance_nominal'], $order['id_transaction'], 'Rejected Order Point', $order['transaction_grandtotal']);
                            if ($refund == false) {
                                DB::rollback();
                                return response()->json([
                                    'status'   => 'fail',
                                    'messages' => ['Insert Cashback Failed'],
                                ]);
                            }
                        }
                    } elseif ($pay['type'] == 'Ovo') {
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
                    } else {
                        $point = 0;
                        $payMidtrans = TransactionPaymentMidtran::find($pay['id_payment']);
                        if ($payMidtrans) {
                            if(Configs::select('is_active')->where('config_name','refund midtrans')->pluck('is_active')->first()){
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
                    $user = User::where('id', $order['id_user'])->first()->toArray();
                    $send = app($this->autocrm)->SendAutoCRM('Rejected Order Point Refund', $user['phone'],
                        [
                            'outlet_name'      => $outlet['outlet_name'],
                            'id_transaction'   => $order['id_transaction'],
                            'transaction_date' => $order['transaction_date'],
                            'receipt_number'   => $order['transaction_receipt_number'],
                            'received_point'   => (string) $point,
                        ]
                    );
                    if ($send != true) {
                        DB::rollback();
                        return response()->json([
                            'status'   => 'fail',
                            'messages' => ['Failed Send notification to customer'],
                        ]);
                    }
                }
            } else {
                $payMidtrans = TransactionPaymentMidtran::where('id_transaction', $order['id_transaction'])->first();
                $payOvo      = TransactionPaymentOvo::where('id_transaction', $order['id_transaction'])->first();
                $payIpay     = TransactionPaymentIpay88::where('id_transaction', $order['id_transaction'])->first();
                if ($payMidtrans) {
                    $point = 0;
                    if(Configs::select('is_active')->where('config_name','refund midtrans')->pluck('is_active')->first()){
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
                } elseif ($payOvo) {
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
                } else {
                    $payBalance = TransactionPaymentBalance::where('id_transaction', $order['id_transaction'])->first();
                    if ($payBalance) {
                        $refund = app($this->balance)->addLogBalance($order['id_user'], $point = $payBalance['balance_nominal'], $order['id_transaction'], 'Rejected Order Point', $order['transaction_grandtotal']);
                        if ($refund == false) {
                            DB::rollback();
                            return response()->json([
                                'status'   => 'fail',
                                'messages' => ['Insert Cashback Failed'],
                            ]);
                        }
                    }
                }
                //send notif to customer
                $user = User::where('id', $order['id_user'])->first()->toArray();
                $send = app($this->autocrm)->SendAutoCRM('Rejected Order Point Refund', $user['phone'],
                    [
                        "outlet_name"      => $outlet['outlet_name'],
                        "transaction_date" => $order['transaction_date'],
                        'id_transaction'   => $order['id_transaction'],
                        'receipt_number'   => $order['transaction_receipt_number'],
                        'received_point'   => (string) $point,
                    ]
                );
                if ($send != true) {
                    DB::rollback();
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => ['Failed Send notification to customer'],
                    ]);
                }

                $send = app($this->autocrm)->SendAutoCRM('Order Reject', $user['phone'], [
                    "outlet_name"      => $outlet['outlet_name'],
                    "id_reference"     => $order->transaction_receipt_number . ',' . $order->id_outlet,
                    "transaction_date" => $order->transaction_date,
                    'id_transaction'   => $order->id_transaction,
                ]);
                if ($send != true) {
                    DB::rollback();
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => ['Failed Send notification to customer'],
                    ]);
                }
            }
            // }

            $checkMembership = app($this->membership)->calculateMembership($user['phone']);

        }
        DB::commit();

        return response()->json(MyHelper::checkUpdate($pickup));
    }

    public function listSchedule(Request $request)
    {
        $schedules = $request->user()->outlet_schedules()->get();
        return MyHelper::checkGet($schedules);
    }

    public function updateSchedule(Request $request)
    {
        $post = $request->json()->all();
        DB::beginTransaction();
        $id_outlet   = $request->user()->id_outlet;
        $user_outlet = $request->user_outlet;
        $otp         = $request->outlet_app_otps;
        $date_time   = date('Y-m-d H:i:s');
        foreach ($post['schedule'] as $value) {
            $old      = OutletSchedule::select('id_outlet_schedule', 'id_outlet', 'day', 'open', 'close', 'is_closed')->where(['id_outlet' => $id_outlet, 'day' => $value['day']])->first();
            $old_data = $old ? $old->toArray() : [];
            if ($old) {
                $save = $old->update($value);
                $new  = $old;
                if (!$save) {
                    DB::rollBack();
                    return response()->json(['status' => 'fail']);
                }
            } else {
                $new = OutletSchedule::create([
                    'id_outlet' => $id_outlet,
                    'day'       => $value['day'],
                ] + $value);
                if (!$new) {
                    DB::rollBack();
                    return response()->json(['status' => 'fail']);
                }
            }
            $new_data = $new->toArray();
            unset($new_data['created_at']);
            unset($new_data['updated_at']);
            if (array_diff($new_data, $old_data)) {
                $create = OutletScheduleUpdate::create([
                    'id_outlet'          => $id_outlet,
                    'id_outlet_schedule' => $new_data['id_outlet_schedule'],
                    'id_user'            => $user_outlet->id_user_outlet,
                    'id_outlet_app_otp'  => $otp->id_outlet_app_otp,
                    'user_type'          => 'user_outlets',
                    'date_time'          => $date_time,
                    'old_data'           => $old_data ? json_encode($old_data) : null,
                    'new_data'           => json_encode($new_data),
                ]);
            }
        }
        DB::commit();
        return response()->json(['status' => 'success']);
    }

    public function history(Request $request)
    {
        $trx_date       = $request->json('trx_date');
        $trx_status     = $request->json('trx_status');
        $trx_type       = $request->json('trx_type');
        $keyword        = $request->json('search_order_id');
        $perpage        = $request->json('perpage');
        $request_number = $request->json('request_number') ?: 'thousand';
        $data           = Transaction::select(\DB::raw('transactions.id_transaction,order_id,DATE_FORMAT(transaction_date, "%Y-%m-%d") as trx_date,DATE_FORMAT(transaction_date, "%H:%i") as trx_time,transaction_receipt_number,count(*) as total_products,transaction_grandtotal'))
            ->where('transactions.id_outlet', $request->user()->id_outlet)
            ->where('trasaction_type', 'Pickup Order')
            ->join('transaction_pickups', 'transactions.id_transaction', '=', 'transaction_pickups.id_transaction')
            ->whereDate('transaction_date', $trx_date)
            ->join('transaction_products', 'transaction_products.id_transaction', '=', 'transactions.id_transaction')
            ->groupBy('transactions.id_transaction');

        if ($trx_status == 'taken') {
            $data->where('transaction_payment_status', 'Completed')
                ->where(function ($query) {
                    $query->whereNotNull('taken_at')
                        ->orWhereNotNull('taken_by_system_at');
                });
        } elseif ($trx_status == 'rejected') {
            $data->where('transaction_payment_status', 'Completed')
                ->whereNotNull('reject_at');
        } elseif ($trx_status == 'unpaid') {
            $data->where('transaction_payment_status', 'Pending')
                ->whereNull('taken_at')
                ->whereNull('taken_by_system_at')
                ->whereNull('reject_at');
        } else {
            $data->where('transaction_payment_status', 'Completed')
                ->where(function ($query) {
                    $query->whereNotNull('taken_at')
                        ->orWhereNotNull('taken_by_system_at')
                        ->orWhereNotNull('reject_at');
                });
        }

        if ($trx_type == 'Delivery') {
            $data->where('pickup_by', 'GO-SEND');
        } elseif ($trx_type == 'Pickup Order') {
            $data->where('pickup_by', 'Customer');
        }

        if ($keyword) {
            $data->where('order_id', 'like', "%$keyword%");
        }

        if ($request->page) {
            $return = $data->paginate($perpage ?: 15)->toArray();
            if (!$return['data']) {
                $return = [];
            } elseif ($request_number) {
                $return['data'] = array_map(function ($var) use ($request_number) {
                    $var['transaction_grandtotal'] = MyHelper::requestNumber($var['transaction_grandtotal'], $request_number);
                    return $var;
                }, $return['data']);
            }
        } else {
            $return = $data->get()->toArray();
            $return = array_map(function ($var) use ($request_number) {
                $var['transaction_grandtotal'] = MyHelper::requestNumber($var['transaction_grandtotal'], $request_number);
                return $var;
            }, $return);
        }
        return MyHelper::checkGet($return);
    }

    public function stockSummary(Request $request)
    {
        $outlet = $request->user();
        $date   = $request->json('date') ?: date('Y-m-d');
        $data   = ProductStockStatusUpdate::distinct()->select(\DB::raw('id_product_stock_status_update,brand_product.id_brand,CONCAT(user_type,",",COALESCE(id_user,""),",",COALESCE(user_name,"")) as user,DATE_FORMAT(date_time, "%H:%i") as time,product_name,new_status as old_status,new_status,new_status as to_available'))
            ->join('products', 'products.id_product', '=', 'product_stock_status_updates.id_product')
            ->join('brand_product', 'products.id_product', '=', 'brand_product.id_product')
            ->where('id_outlet', $outlet->id_outlet)
            ->whereDate('date_time', $date)
            ->orderBy('date_time', 'desc')
            ->get();
        $grouped = [];
        foreach ($data as $value) {
            $grouped[$value->user . '#' . $value->time . '#' . $value->id_brand][] = $value;
        }
        $result = [];
        foreach ($grouped as $key => $var) {
            [$name, $time, $id_brand] = explode('#', $key);
            if (!isset($result[$id_brand]['name_brand'])) {
                $result[$id_brand]['name_brand'] = Brand::select('name_brand')->where('id_brand', $id_brand)->pluck('name_brand')->first();
            }
            $result[$id_brand]['updates'][] = [
                'name'    => $name,
                'time'    => $time,
                'summary' => array_map(function ($vrb) {
                    return [
                        'product_name' => $vrb['product_name'],
                        'old_status'   => $vrb['old_status'],
                        'new_status'   => $vrb['new_status'],
                        'to_available' => $vrb['to_available'],
                    ];}, $var),
            ];
        }
        return MyHelper::checkGet(array_values($result));
    }

    public function requestOTP(Request $request)
    {
        if (!in_array($request->feature, ['Update Stock Status', 'Update Schedule', 'Create Holiday', 'Update Holiday', 'Delete Holiday'])) {
            return [
                'status'   => 'fail',
                'messages' => 'Invalid requested feature',
            ];
        }
        $outlet = $request->user();
        $post   = $request->json()->all();
        $users  = UserOutlet::where(['id_outlet' => $outlet->id_outlet, 'outlet_apps' => '1'])->get();
        if (count($users) === 0) {
            return MyHelper::checkGet([], 'User Outlet Apps empty');
        }
        $status = false;
        foreach ($users as $user) {
            $pinnya = rand(1000, 9999);
            $pin    = password_hash($pinnya, PASSWORD_BCRYPT);
            $create = OutletAppOtp::create([
                'id_user_outlet' => $user->id_user_outlet,
                'id_outlet'      => $outlet->id_outlet,
                'feature'        => $post['feature'],
                'pin'            => $pin,
            ]);
            $send = app($this->autocrm)->SendAutoCRM('Outlet App Request PIN', $user->phone, [
                'outlet_name' => $outlet->outlet_name,
                'outlet_code' => $outlet->outlet_code,
                'feature'     => $post['feature'],
                'admin_name'  => $user->name,
                'pin'         => $pinnya,
            ]);
            if (!$status && ($create && $send)) {
                $status = true;
            }
        }
        if (!$status) {
            return MyHelper::checkGet([], 'Failed send PIN');
        }
        return ['status' => 'success'];
    }

    public function bookDelivery(Request $request)
    {
        $post = $request->json()->all();
        $trx  = Transaction::where(['transaction_payment_status' => 'Completed'])->find($request->id_transaction);
        if (!$trx) {
            return MyHelper::checkGet($trx, 'Transaction Not Found');
        }
        switch (strtolower($request->type)) {
            case 'gosend':
                $result = $this->bookGoSend($trx);
                break;

            default:
                $result = ['status' => 'fail', 'messages' => ['Invalid booking type']];
                break;
        }
        return response()->json($result);
    }

    public function bookGoSend($trx,$fromRetry = false)
    {
        $trx->load('transaction_pickup', 'transaction_pickup.transaction_pickup_go_send', 'outlet');
        if (!($trx['transaction_pickup']['transaction_pickup_go_send']['id_transaction_pickup_go_send'] ?? false)) {
            return [
                'status'   => 'fail',
                'messages' => ['Transaksi tidak menggunakan GO-SEND'],
            ];
        }
        if ($trx['transaction_pickup']['transaction_pickup_go_send']['go_send_id'] && !in_array(strtolower($trx['transaction_pickup']['transaction_pickup_go_send']['latest_status']), ['cancelled', 'no_driver'])) {
            return [
                'status'   => 'fail',
                'messages' => ['Pengiriman sudah dipesan'],
            ];
        }
        //create booking GO-SEND
        $origin['name']      = $trx['outlet']['outlet_name'];
        $origin['phone']     = $trx['outlet']['outlet_phone'];
        $origin['latitude']  = $trx['outlet']['outlet_latitude'];
        $origin['longitude'] = $trx['outlet']['outlet_longitude'];
        $origin['address']   = $trx['outlet']['outlet_address'] . '. ' . $trx['transaction_pickup']['transaction_pickup_go_send']['origin_note'];
        $origin['note']      = $trx['transaction_pickup']['transaction_pickup_go_send']['origin_note'];

        $destination['name']      = $trx['transaction_pickup']['transaction_pickup_go_send']['destination_name'];
        $destination['phone']     = $trx['transaction_pickup']['transaction_pickup_go_send']['destination_phone'];
        $destination['latitude']  = $trx['transaction_pickup']['transaction_pickup_go_send']['destination_latitude'];
        $destination['longitude'] = $trx['transaction_pickup']['transaction_pickup_go_send']['destination_longitude'];
        $destination['address']   = $trx['transaction_pickup']['transaction_pickup_go_send']['destination_address'];
        $destination['note']      = $trx['transaction_pickup']['transaction_pickup_go_send']['destination_note'];

        $packageDetail = Setting::where('key', 'go_send_package_detail')->first();

        //update id from go-send
        $updateGoSend = TransactionPickupGoSend::find($trx['transaction_pickup']['transaction_pickup_go_send']['id_transaction_pickup_go_send']);
        $maxRetry = Setting::select('value')->where('key', 'booking_delivery_max_retry')->pluck('value')->first()?:5;
        if ($fromRetry && $updateGoSend->retry_count >= $maxRetry) {
            return ['status'  => 'fail', 'messages' => ['Retry reach limit']];
        }

        if ($packageDetail) {
            $packageDetail = str_replace('%order_id%', $trx['transaction_pickup']['order_id'], $packageDetail['value']);
        } else {
            $packageDetail = "Order " . $trx['transaction_pickup']['order_id'];
        }

        $booking = GoSend::booking($origin, $destination, $packageDetail, $trx['transaction_receipt_number']);
        if (isset($booking['status']) && $booking['status'] == 'fail') {
            return $booking;
        }

        if (!isset($booking['id'])) {
            return ['status' => 'fail', 'messages' => $booking['messages'] ?? ['failed booking GO-SEND']];
        }
        $ref_status = [
            'Finding Driver' => 'confirmed',
            'Driver Allocated' => 'allocated',
            'Enroute Pickup' => 'out_for_pickup',
            'Item Picked by Driver' => 'picked',
            'Enroute Drop' => 'out_for_delivery',
            'Cancelled' => 'cancelled',
            'Completed' => 'delivered',
            'Rejected' => 'rejected',
            'Driver not found' => 'no_driver',
            'On Hold' => 'on_hold',
        ];
        $status = GoSend::getStatus($booking['orderNo'], true);
        $status['status'] = $ref_status[$status['status']] ?? $status['status'];
        $dataSave     = [
            'id_transaction'                => $trx['id_transaction'],
            'id_transaction_pickup_go_send' => $trx['transaction_pickup']['transaction_pickup_go_send']['id_transaction_pickup_go_send'],
            'status'                        => $status['status'] ?? 'Finding Driver',
            'go_send_order_no'              => $booking['orderNo']
        ];
        GoSend::saveUpdate($dataSave);
        if ($updateGoSend) {
            $updateGoSend->go_send_id        = $booking['id'];
            $updateGoSend->go_send_order_no  = $booking['orderNo'];
            $updateGoSend->latest_status     = $status['status'] ?? null;
            $updateGoSend->driver_id         = $status['driverId'] ?? null;
            $updateGoSend->driver_name       = $status['driverName'] ?? null;
            $updateGoSend->driver_phone      = $status['driverPhone'] ?? null;
            $updateGoSend->driver_photo      = $status['driverPhoto'] ?? null;
            $updateGoSend->vehicle_number    = $status['vehicleNumber'] ?? null;
            $updateGoSend->live_tracking_url = $status['liveTrackingUrl'] ?? null;
            $updateGoSend->retry_count = $fromRetry?($updateGoSend->retry_count+1):0;
            $updateGoSend->save();

            if (!$updateGoSend) {
                return ['status' => 'fail', 'messages' => ['failed update Transaction GO-SEND']];
            }
        }
        return ['status' => 'success'];
    }

    public function refreshDeliveryStatus(Request $request)
    {
        $trx = Transaction::where('transactions.id_transaction', $request->id_transaction)->join('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')->where('pickup_by', 'GO-SEND')->first();
        if (!$trx) {
            return MyHelper::checkGet($trx, 'Transaction Not Found');
        }
        switch (strtolower($request->type)) {
            case 'gosend':
                $trxGoSend = TransactionPickupGoSend::where('id_transaction_pickup', $trx['id_transaction_pickup'])->first();
                if (!$trxGoSend) {
                    return MyHelper::checkGet($trx, 'Transaction GoSend Not Found');
                }
                $ref_status = [
                    'Finding Driver' => 'confirmed',
                    'Driver Allocated' => 'allocated',
                    'Enroute Pickup' => 'out_for_pickup',
                    'Item Picked by Driver' => 'picked',
                    'Enroute Drop' => 'out_for_delivery',
                    'Cancelled' => 'cancelled',
                    'Completed' => 'delivered',
                    'Rejected' => 'rejected',
                    'Driver not found' => 'no_driver',
                    'On Hold' => 'on_hold',
                ];
                $status = GoSend::getStatus($trx['transaction_receipt_number']);
                $status['status'] = $ref_status[$status['status']]??$status['status'];
                if($status['receiver_name'] ?? '') {
                    $toUpdate['receiver_name'] = $status['receiver_name'];
                }
                if ($status['status'] ?? false) {
                    $toUpdate = ['latest_status' => $status['status']];
                    if ($status['liveTrackingUrl'] ?? false) {
                        $toUpdate['live_tracking_url'] = $status['liveTrackingUrl'];
                    }
                    if ($status['driverId'] ?? false) {
                        $toUpdate['driver_id'] = $status['driverId'];
                    }
                    if ($status['driverName'] ?? false) {
                        $toUpdate['driver_name'] = $status['driverName'];
                    }
                    if ($status['driverPhone'] ?? false) {
                        $toUpdate['driver_phone'] = $status['driverPhone'];
                    }
                    if ($status['driverPhoto'] ?? false) {
                        $toUpdate['driver_photo'] = $status['driverPhoto'];
                    }
                    if ($status['vehicleNumber'] ?? false) {
                        $toUpdate['vehicle_number'] = $status['vehicleNumber'];
                    }
                    if (!in_array(strtolower($status['status']), ['allocated', 'no_driver', 'cancelled']) && strpos(env('GO_SEND_URL'), 'integration')) {
                        $toUpdate['driver_id']      = '00510001';
                        $toUpdate['driver_phone']   = '08111251307';
                        $toUpdate['driver_name']    = 'Anton Lucarus';
                        $toUpdate['driver_photo']   = 'http://beritatrans.com/cms/wp-content/uploads/2020/02/images4-553x400.jpeg';
                        $toUpdate['vehicle_number'] = 'AB 2641 XY';
                    }
                    $trxGoSend->update($toUpdate);
                    if (in_array(strtolower($status['status']), ['completed', 'delivered'])) {
                        // sendPoint delivery after status delivered only
                        if ($trx->cashback_insert_status != 1) {
                            //send notif to customer
                            $user = User::find($trx->id_user);

                            $newTrx    = Transaction::with('user.memberships', 'outlet', 'productTransaction', 'transaction_vouchers','promo_campaign_promo_code','promo_campaign_promo_code.promo_campaign')->where('id_transaction', $trx->id_transaction)->first();
                            $checkType = TransactionMultiplePayment::where('id_transaction', $trx->id_transaction)->get()->toArray();
                            $column    = array_column($checkType, 'type');
                            
                            $use_referral = optional(optional($newTrx->promo_campaign_promo_code)->promo_campaign)->promo_type == 'Referral';

                            if (!in_array('Balance', $column) || $use_referral) {

                                $promo_source = null;
                                if ($newTrx->id_promo_campaign_promo_code || $newTrx->transaction_vouchers) {
                                    if ($newTrx->id_promo_campaign_promo_code) {
                                        $promo_source = 'promo_code';
                                    } elseif (($newTrx->transaction_vouchers[0]->status ?? false) == 'success') {
                                        $promo_source = 'voucher_online';
                                    }
                                }

                                if (app($this->trx)->checkPromoGetPoint($promo_source) || $use_referral) {
                                    $savePoint = app($this->getNotif)->savePoint($newTrx);
                                    // return $savePoint;
                                    if (!$savePoint) {
                                        DB::rollback();
                                        return response()->json([
                                            'status'   => 'fail',
                                            'messages' => ['Transaction failed'],
                                        ]);
                                    }
                                }

                            }

                            $newTrx->update(['cashback_insert_status' => 1]);
                            $checkMembership = app($this->membership)->calculateMembership($user['phone']);
                            DB::commit();
                            $send = app($this->autocrm)->SendAutoCRM('Order Ready', $user['phone'], [
                                "outlet_name"      => $outlet['outlet_name'],
                                'id_transaction'   => $trx->id_transaction,
                                "id_reference"     => $trx->transaction_receipt_number . ',' . $trx->id_outlet,
                                "transaction_date" => $trx->transaction_date,
                            ]);
                            if ($send != true) {
                                // DB::rollback();
                                return response()->json([
                                    'status'   => 'fail',
                                    'messages' => ['Failed Send notification to customer'],
                                ]);
                            }
                        }
                        $arrived_at = date('Y-m-d H:i:s', ($status['orderArrivalTime']??false)?strtotime($status['orderArrivalTime']):time());
                        TransactionPickup::where('id_transaction', $trx->id_transaction)->update(['arrived_at' => $arrived_at]);
                        $dataSave = [
                            'id_transaction'                => $trx['id_transaction'],
                            'id_transaction_pickup_go_send' => $trxGoSend['id_transaction_pickup_go_send'],
                            'status'                        => $status['status'] ?? 'on_going',
                            'go_send_order_no'              => $status['orderNo'] ?? ''
                        ];
                        GoSend::saveUpdate($dataSave);
                    } elseif (in_array(strtolower($status['status']), ['cancelled', 'rejected', 'no_driver'])) {
                        $trxGoSend->update([
                            'live_tracking_url' => null,
                            'driver_id' => null,
                            'driver_name' => null,
                            'driver_phone' => null,
                            'driver_photo' => null,
                            'vehicle_number' => null,
                            'receiver_name' => null
                        ]);
                        $dataSave = [
                            'id_transaction'                => $trx['id_transaction'],
                            'id_transaction_pickup_go_send' => $trxGoSend['id_transaction_pickup_go_send'],
                            'status'                        => $status['status'] ?? 'on_going',
                            'go_send_order_no'              => $status['orderNo'] ?? ''
                        ];
                        GoSend::saveUpdate($dataSave);
                        $this->bookGoSend($trx, true);
                    } else {
                        $dataSave = [
                            'id_transaction'                => $trx['id_transaction'],
                            'id_transaction_pickup_go_send' => $trxGoSend['id_transaction_pickup_go_send'],
                            'status'                        => $status['status'] ?? 'on_going',
                            'go_send_order_no'              => $status['orderNo'] ?? ''
                        ];
                        GoSend::saveUpdate($dataSave);
                    }
                }
                return MyHelper::checkGet($trxGoSend);
                break;

            default:
                return ['status' => 'fail', 'messages' => ['Invalid delivery type']];
                break;
        }
    }

    public function cancelDelivery(Request $request)
    {
        $trx = Transaction::where('transactions.id_transaction', $request->id_transaction)->join('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')->where('pickup_by', 'GO-SEND')->first();
        if (!$trx) {
            return MyHelper::checkGet($trx, 'Transaction Not Found');
        }
        $trx->load('transaction_pickup_go_send');
        $orderNo = $trx->transaction_pickup_go_send->go_send_order_no;
        if (!$orderNo) {
            return [
                'status'   => 'fail',
                'messages' => ['Go-Send Pickup not found'],
            ];
        }
        $cancel = GoSend::cancelOrder($orderNo, $trx->transaction_receipt_number);
        if (($cancel['status'] ?? false) == 'fail') {
            return $cancel;
        }
        if (($cancel['statusCode'] ?? false) == '200') {
            $trx->transaction_pickup_go_send->latest_status = 'Cancelled';
            $trx->transaction_pickup_go_send->cancel_reason = $request->reason;
            $trx->transaction_pickup_go_send->save();
            return ['status' => 'success'];
        }
    }

    public function transactionDetail(TransactionDetail $request)
    {
        $id = $request->json('id_transaction');

        $list = Transaction::where([['transactions.id_transaction', $id]])->leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')->with(
            // 'user.city.province',
            'user',
            'productTransaction.product.product_category',
            'productTransaction.modifiers',
            'productTransaction.product.product_photos',
            'productTransaction.product.product_discounts',
            'transaction_payment_offlines',
            'transaction_vouchers.deals_voucher.deal',
            'promo_campaign_promo_code.promo_campaign',
            'transaction_pickup_go_send',
            'outlet.city')->first();
        if (!$list) {
            return MyHelper::checkGet([], 'empty');
        }
        $list                        = $list->toArray();
        $label                       = [];
        $label2                      = [];
        $product_count               = 0;
        $list['product_transaction'] = MyHelper::groupIt($list['product_transaction'], 'id_brand', null, function ($key, &$val) use (&$product_count) {
            $product_count += array_sum(array_column($val, 'transaction_product_qty'));
            $brand = Brand::select('name_brand')->find($key);
            if (!$brand) {
                return 'No Brand';
            }
            return $brand->name_brand;
        });
        $cart = $list['transaction_subtotal'] + $list['transaction_shipment'] + $list['transaction_service'] + $list['transaction_tax'] - $list['transaction_discount'];

        $list['transaction_carttotal']  = $cart;
        $list['transaction_item_total'] = $product_count;

        $order = Setting::where('key', 'transaction_grand_total_order')->value('value');
        $exp   = explode(',', $order);
        $exp2  = explode(',', $order);

        foreach ($exp as $i => $value) {
            if ($exp[$i] == 'subtotal') {
                unset($exp[$i]);
                unset($exp2[$i]);
                continue;
            }

            if ($exp[$i] == 'tax') {
                $exp[$i]  = 'transaction_tax';
                $exp2[$i] = 'transaction_tax';
                array_push($label, 'Tax');
                array_push($label2, 'Tax');
            }

            if ($exp[$i] == 'service') {
                $exp[$i]  = 'transaction_service';
                $exp2[$i] = 'transaction_service';
                array_push($label, 'Service Fee');
                array_push($label2, 'Service Fee');
            }

            if ($exp[$i] == 'shipping') {
                if ($list['trasaction_type'] == 'Pickup Order') {
                    unset($exp[$i]);
                    unset($exp2[$i]);
                    continue;
                } else {
                    $exp[$i]  = 'transaction_shipment';
                    $exp2[$i] = 'transaction_shipment';
                    array_push($label, 'Delivery Cost');
                    array_push($label2, 'Delivery Cost');
                }
            }

            if ($exp[$i] == 'discount') {
                $exp2[$i] = 'transaction_discount';
                array_push($label2, 'Discount');
                unset($exp[$i]);
                continue;
            }

            if (stristr($exp[$i], 'empty')) {
                unset($exp[$i]);
                unset($exp2[$i]);
                continue;
            }
        }

        switch ($list['trasaction_payment_type']) {
            case 'Balance':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get()->toArray();
                if ($multiPayment) {
                    foreach ($multiPayment as $keyMP => $mp) {
                        switch ($mp['type']) {
                            case 'Balance':
                                $log = LogBalance::where('id_reference', $mp['id_transaction'])->first();
                                if ($log['balance'] < 0) {
                                    $list['balance'] = $log['balance'];
                                    $list['check'] = 'tidak topup';
                                } else {
                                    $list['balance'] = $list['transaction_grandtotal'] - $log['balance'];
                                    $list['check'] = 'topup';
                                }
                                $list['payment'][] = [
                                    'name'      => 'Balance',
                                    'amount'    => $list['balance']
                                ];
                                break;
                            case 'Manual':
                                $payment = TransactionPaymentManual::with('manual_payment_method.manual_payment')->where('id_transaction', $list['id_transaction'])->first();
                                $list['payment'] = $payment;
                                $list['payment'][] = [
                                    'name'      => 'Cash',
                                    'amount'    => $payment['payment_nominal']
                                ];
                                break;
                            case 'Midtrans':
                                $payMidtrans = TransactionPaymentMidtran::find($mp['id_payment']);
                                $payment['name']      = strtoupper(str_replace('_', ' ', $payMidtrans->payment_type)).' '.strtoupper($payMidtrans->bank);
                                $payment['amount']    = $payMidtrans->gross_amount;
                                $list['payment'][] = $payment;
                                break;
                            case 'Ovo':
                                $payment = TransactionPaymentOvo::find($mp['id_payment']);
                                $payment['name']    = 'OVO';
                                $list['payment'][] = $payment;
                                break;
                            case 'Ipay88':
                                $PayIpay = TransactionPaymentIpay88::find($mp['id_payment']);
                                $payment['name']    = $PayIpay->payment_method;
                                $payment['amount']    = $PayIpay->amount / 100;
                                $list['payment'][] = $payment;
                                break;
                            case 'Offline':
                                $payment = TransactionPaymentOffline::where('id_transaction', $list['id_transaction'])->get();
                                foreach ($payment as $key => $value) {
                                    $list['payment'][$key] = [
                                        'name'      => $value['payment_bank'],
                                        'amount'    => $value['payment_amount']
                                    ];
                                }
                                break;
                            default:
                                $list['payment'][] = [
                                    'name'      => null,
                                    'amount'    => null
                                ];
                                break;
                        }
                    }
                } else {
                    $log = LogBalance::where('id_reference', $list['id_transaction'])->first();
                    if ($log['balance'] < 0) {
                        $list['balance'] = $log['balance'];
                        $list['check'] = 'tidak topup';
                    } else {
                        $list['balance'] = $list['transaction_grandtotal'] - $log['balance'];
                        $list['check'] = 'topup';
                    }
                    $list['payment'][] = [
                        'name'      => 'Balance',
                        'amount'    => $list['balance']
                    ];
                }
                break;
            case 'Manual':
                $payment = TransactionPaymentManual::with('manual_payment_method.manual_payment')->where('id_transaction', $list['id_transaction'])->first();
                $list['payment'] = $payment;
                $list['payment'][] = [
                    'name'      => 'Cash',
                    'amount'    => $payment['payment_nominal']
                ];
                break;
            case 'Midtrans':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get();
                $payment = [];
                foreach($multiPayment as $dataKey => $dataPay){
                    if($dataPay['type'] == 'Midtrans'){
                        $payMidtrans = TransactionPaymentMidtran::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']      = strtoupper(str_replace('_', ' ', $payMidtrans->payment_type)).' '.strtoupper($payMidtrans->bank);
                        $payment[$dataKey]['amount']    = $payMidtrans->gross_amount;
                    }else{
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey] = $dataPay;
                        $list['balance'] = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']          = 'Balance';
                        $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                    }
                }
                $list['payment'] = $payment;
                break;
            case 'Ovo':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get();
                $payment = [];
                foreach($multiPayment as $dataKey => $dataPay){
                    if($dataPay['type'] == 'Ovo'){
                        $payment[$dataKey] = TransactionPaymentOvo::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']    = 'OVO';
                    }else{
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey] = $dataPay;
                        $list['balance'] = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']          = 'Balance';
                        $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                    }
                }
                $list['payment'] = $payment;
                break;
            case 'Ipay88':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get();
                $payment = [];
                foreach($multiPayment as $dataKey => $dataPay){
                    if($dataPay['type'] == 'IPay88'){
                        $PayIpay = TransactionPaymentIpay88::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']    = $PayIpay->payment_method;
                        $payment[$dataKey]['amount']    = $PayIpay->amount / 100;
                    }else{
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey] = $dataPay;
                        $list['balance'] = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']          = 'Balance';
                        $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                    }
                }
                $list['payment'] = $payment;
                break;
            case 'Offline':
                $payment = TransactionPaymentOffline::where('id_transaction', $list['id_transaction'])->get();
                foreach ($payment as $key => $value) {
                    $list['payment'][$key] = [
                        'name'      => $value['payment_bank'],
                        'amount'    => $value['payment_amount']
                    ];
                }
                break;
            default:
                $list['payment'][] = [
                    'name'      => null,
                    'amount'    => null
                ];
                break;
        }

        array_splice($exp, 0, 0, 'transaction_subtotal');
        array_splice($label, 0, 0, 'Cart Total');

        array_splice($exp2, 0, 0, 'transaction_subtotal');
        array_splice($label2, 0, 0, 'Cart Total');

        array_values($exp);
        array_values($label);

        array_values($exp2);
        array_values($label2);

        $imp         = implode(',', $exp);
        $order_label = implode(',', $label);

        $imp2         = implode(',', $exp2);
        $order_label2 = implode(',', $label2);

        $detail = [];

        $pickupType = $list['trasaction_type'];
        if ($list['trasaction_type'] == 'Pickup Order') {
            $detail = TransactionPickup::where('id_transaction', $list['id_transaction'])->first()->toArray();
            if ($detail) {
                $qr = $detail['order_id'] . strtotime($list['transaction_date']);

                $qrCode = 'https://chart.googleapis.com/chart?chl=' . $qr . '&chs=250x250&cht=qr&chld=H%7C0';
                $qrCode = html_entity_decode($qrCode);

                $newDetail = [];
                foreach ($detail as $key => $value) {
                    $newDetail[$key] = $value;
                    if ($key == 'order_id') {
                        $newDetail['order_id_qrcode'] = $qrCode;
                    }
                }

                $detail = $newDetail;

                if ($detail['pickup_by'] == 'GO-SEND') {
                    $pickupType = 'Delivery';
                }
            }
        } elseif ($list['trasaction_type'] == 'Delivery') {
            $detail = TransactionShipment::with('city.province')->where('id_transaction', $list['id_transaction'])->first();
        }

        $list['detail']      = $detail;
        $list['order']       = $imp;
        $list['order_label'] = $order_label;

        $list['order_v2']       = $imp2;
        $list['order_label_v2'] = $order_label2;

        $list['date'] = $list['transaction_date'];
        $list['type'] = 'trx';

        $result = [
            'id_transaction'              => $list['id_transaction'],
            'user_name'                   => $list['user']['name'],
            'user_phone'                  => $list['user']['phone'],
            'transaction_receipt_number'  => $list['transaction_receipt_number'],
            'transaction_date'            => date('d M Y H:i', strtotime($list['transaction_date'])),
            'trasaction_type'             => $pickupType,
            'transaction_grandtotal'      => MyHelper::requestNumber($list['transaction_grandtotal'], '_CURRENCY'),
            'transaction_subtotal'        => MyHelper::requestNumber($list['transaction_subtotal'], '_CURRENCY'),
            'transaction_discount'        => MyHelper::requestNumber($list['transaction_discount'], '_CURRENCY'),
            'transaction_cashback_earned' => MyHelper::requestNumber($list['transaction_cashback_earned'], '_POINT'),
            'trasaction_payment_type'     => $list['trasaction_payment_type'],
            'transaction_payment_status'  => $list['transaction_payment_status'],
            'rejectable'                  => 0,
            'outlet'                      => [
                'outlet_name'    => $list['outlet']['outlet_name'],
                'outlet_address' => $list['outlet']['outlet_address'],
                'call'           => $list['outlet']['call'],
            ],
        ];

        if ($list['trasaction_payment_type'] != 'Offline') {
            $result['detail'] = [
                'order_id_qrcode' => $list['detail']['order_id_qrcode'],
                'order_id'        => $list['detail']['order_id'],
                'pickup_type'     => $list['detail']['pickup_type'],
                'pickup_date'     => date('d F Y', strtotime($list['detail']['pickup_at'])),
                'pickup_time'     => ($list['detail']['pickup_type'] == 'right now') ? 'RIGHT NOW' : date('H : i', strtotime($list['detail']['pickup_at'])),
            ];
            if (isset($list['transaction_payment_status']) && $list['transaction_payment_status'] == 'Cancelled') {
                $result['transaction_status']      = 0;
                $result['transaction_status_text'] = 'ORDER ANDA DIBATALKAN';
            } elseif (isset($list['transaction_payment_status']) && $list['transaction_payment_status'] == 'Pending') {
                $result['transaction_status']      = 6;
                $result['transaction_status_text'] = 'MENUNGGU PEMBAYARAN';
            } elseif ($list['detail']['reject_at'] != null) {
                $result['transaction_status']      = 0;
                $result['transaction_status_text'] = 'ORDER ANDA DITOLAK';
            } elseif ($list['detail']['taken_by_system_at'] != null) {
                $result['transaction_status']      = 1;
                $result['transaction_status_text'] = 'ORDER SELESAI';
            } elseif ($list['detail']['taken_at'] != null) {
                $result['transaction_status']      = 2;
                $result['transaction_status_text'] = 'ORDER SUDAH DIAMBIL';
            } elseif ($list['detail']['ready_at'] != null) {
                $result['transaction_status']      = 3;
                $result['transaction_status_text'] = 'ORDER SUDAH SIAP';
            } elseif ($list['detail']['receive_at'] != null) {
                $result['transaction_status']      = 4;
                $result['transaction_status_text'] = 'ORDER SEDANG DIPROSES';
            } else {
                $result['transaction_status']      = 5;
                $result['transaction_status_text'] = 'ORDER PENDING';
                $result['rejectable']              = 1;
            }

            if ($list['transaction_pickup_go_send']) {
                $result['delivery_info'] = [
                    'driver'            => null,
                    'delivery_status'   => '',
                    'delivery_address'  => $list['transaction_pickup_go_send']['destination_address']?:'',
                    'booking_status'    => 0,
                    'cancelable'        => 1,
                    'go_send_order_no'  => $list['transaction_pickup_go_send']['go_send_order_no']?:'',
                    'live_tracking_url' => $list['transaction_pickup_go_send']['live_tracking_url']?:'',
                ];
                if ($list['transaction_pickup_go_send']['go_send_id']) {
                    $result['delivery_info']['booking_status'] = 1;
                }
                switch (strtolower($list['transaction_pickup_go_send']['latest_status'])) {
                    case 'finding driver':
                    case 'confirmed':
                        $result['delivery_info']['delivery_status'] = 'Driver belum ditemukan';
                        $result['transaction_status_text']          = 'SEDANG MENCARI DRIVER';
                        $result['rejectable']                       = 0;
                        break;
                    case 'driver allocated':
                    case 'allocated':
                        $result['delivery_info']['delivery_status'] = 'Driver ditemukan';
                        $result['transaction_status_text']          = 'DRIVER DITEMUKAN';
                        $result['delivery_info']['driver']          = [
                            'driver_id'      => $list['transaction_pickup_go_send']['driver_id']?:'',
                            'driver_name'    => $list['transaction_pickup_go_send']['driver_name']?:'',
                            'driver_phone'   => $list['transaction_pickup_go_send']['driver_phone']?:'',
                            'driver_photo'   => $list['transaction_pickup_go_send']['driver_photo']?:'',
                            'vehicle_number' => $list['transaction_pickup_go_send']['vehicle_number']?:'',
                        ];
                        $result['rejectable']                       = 0;
                        break;
                    case 'enroute pickup':
                    case 'out_for_pickup':
                        $result['delivery_info']['delivery_status'] = 'Driver dalam perjalanan menuju Outlet';
                        $result['transaction_status_text']          = 'DRIVER SEDANG MENUJU OUTLET';
                        $result['delivery_info']['driver']          = [
                            'driver_id'      => $list['transaction_pickup_go_send']['driver_id']?:'',
                            'driver_name'    => $list['transaction_pickup_go_send']['driver_name']?:'',
                            'driver_phone'   => $list['transaction_pickup_go_send']['driver_phone']?:'',
                            'driver_photo'   => $list['transaction_pickup_go_send']['driver_photo']?:'',
                            'vehicle_number' => $list['transaction_pickup_go_send']['vehicle_number']?:'',
                        ];
                        $result['delivery_info']['cancelable'] = 1;
                        $result['rejectable']                  = 0;
                        break;
                    case 'enroute drop':
                    case 'out_for_delivery':
                        $result['delivery_info']['delivery_status'] = 'Driver mengantarkan pesanan';
                        $result['transaction_status_text']          = 'PROSES PENGANTARAN';
                        $result['delivery_info']['driver']          = [
                            'driver_id'      => $list['transaction_pickup_go_send']['driver_id']?:'',
                            'driver_name'    => $list['transaction_pickup_go_send']['driver_name']?:'',
                            'driver_phone'   => $list['transaction_pickup_go_send']['driver_phone']?:'',
                            'driver_photo'   => $list['transaction_pickup_go_send']['driver_photo']?:'',
                            'vehicle_number' => $list['transaction_pickup_go_send']['vehicle_number']?:'',
                        ];
                        $result['delivery_info']['cancelable'] = 0;
                        $result['rejectable']                  = 0;
                        break;
                    case 'completed':
                    case 'delivered':
                        $result['transaction_status_text']          = 'ORDER SUDAH DIAMBIL';
                        $result['delivery_info']['delivery_status'] = 'Pesanan sudah diterima Customer';
                        $result['delivery_info']['driver']          = [
                            'driver_id'      => $list['transaction_pickup_go_send']['driver_id']?:'',
                            'driver_name'    => $list['transaction_pickup_go_send']['driver_name']?:'',
                            'driver_phone'   => $list['transaction_pickup_go_send']['driver_phone']?:'',
                            'driver_photo'   => $list['transaction_pickup_go_send']['driver_photo']?:'',
                            'vehicle_number' => $list['transaction_pickup_go_send']['vehicle_number']?:'',
                        ];
                        $result['delivery_info']['cancelable'] = 0;
                        break;
                    case 'cancelled':
                        $result['delivery_info']['booking_status'] = 0;
                        $result['transaction_status_text']         = 'SEDANG MENCARI DRIVER';
                        $result['delivery_info']['cancelable']     = 0;
                        $result['rejectable']              = 1;
                        break;
                    case 'driver not found':
                    case 'no_driver':
                        $result['delivery_info']['booking_status']  = 0;
                        $result['transaction_status_text']          = 'SEDANG MENCARI DRIVER';
                        $result['delivery_info']['delivery_status'] = 'Driver tidak ditemukan';
                        $result['delivery_info']['cancelable']      = 0;
                        $result['rejectable']              = 1;
                        break;
                }
            }
        }

        $discount = 0;
        $quantity = 0;
        $keynya   = 0;
        foreach ($list['product_transaction'] as $keyTrx => $valueTrx) {
            $result['product_transaction'][$keynya]['brand'] = $keyTrx;
            foreach ($valueTrx as $keyProduct => $valueProduct) {
                $quantity                                                                                        = $quantity + $valueProduct['transaction_product_qty'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_qty']       = $valueProduct['transaction_product_qty'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_subtotal']  = MyHelper::requestNumber($valueProduct['transaction_product_subtotal'], '_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_sub_item']  = '@' . MyHelper::requestNumber($valueProduct['transaction_product_subtotal'] / $valueProduct['transaction_product_qty'], '_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_modifier_subtotal'] = MyHelper::requestNumber($valueProduct['transaction_modifier_subtotal'], '_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_note']      = $valueProduct['transaction_product_note'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_discount']  = $valueProduct['transaction_product_discount'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_name']       = $valueProduct['product']['product_name'];
                $discount                                                                                        = $discount + $valueProduct['transaction_product_discount'];
                foreach ($valueProduct['modifiers'] as $keyMod => $valueMod) {
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'][$keyMod]['product_modifier_name']  = $valueMod['text'];
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'][$keyMod]['product_modifier_qty']   = $valueMod['qty'];
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'][$keyMod]['product_modifier_price'] = MyHelper::requestNumber($valueMod['transaction_product_modifier_price'], '_CURRENCY');
                }
            }
            $keynya++;
        }

        $result['payment_detail'][] = [
            'name'   => 'Subtotal',
            'desc'   => $quantity . ' items',
            'amount' => MyHelper::requestNumber($list['transaction_subtotal'], '_CURRENCY'),
        ];

        $p = 0;
        if (!empty($list['transaction_vouchers'])) {
            foreach ($list['transaction_vouchers'] as $valueVoc) {
                $result['promo']['code'][$p++] = $valueVoc['deals_voucher']['voucher_code'];
                $result['payment_detail'][]    = [
                    'name'        => 'Discount',
                    'desc'        => $valueVoc['deals_voucher']['voucher_code'],
                    "is_discount" => 1,
                    'amount'      => MyHelper::requestNumber($discount, '_CURRENCY'),
                ];
            }
        }

        if (!empty($list['promo_campaign_promo_code'])) {
            $result['promo']['code'][$p++] = $list['promo_campaign_promo_code']['promo_code'];
            $result['payment_detail'][]    = [
                'name'        => 'Discount',
                'desc'        => $list['promo_campaign_promo_code']['promo_code'],
                "is_discount" => 1,
                'amount'      => MyHelper::requestNumber($discount, '_CURRENCY'),
            ];
        }

        $result['promo']['discount'] = $discount;
        $result['promo']['discount'] = MyHelper::requestNumber($discount, '_CURRENCY');

        if ($list['trasaction_payment_type'] != 'Offline') {
            if ($list['transaction_payment_status'] == 'Cancelled') {
                $result['detail']['detail_status'][] = [
                    'text' => 'Your order has been canceled',
                    'date' => date('d F Y H:i', strtotime($list['void_date'])),
                ];
            }
            if ($list['detail']['reject_at'] != null) {
                $result['detail']['detail_status'][] = [
                    'text'   => 'Order rejected',
                    'date'   => date('d F Y H:i', strtotime($list['detail']['reject_at'])),
                    'reason' => $list['detail']['reject_reason'],
                ];
            }
            if ($list['detail']['taken_by_system_at'] != null) {
                $result['detail']['detail_status'][] = [
                    'text' => 'Your order has been done by system',
                    'date' => date('d F Y H:i', strtotime($list['detail']['taken_by_system_at'])),
                ];
            }
            if ($list['detail']['taken_at'] != null) {
                $result['detail']['detail_status'][] = [
                    'text' => 'Your order has been taken',
                    'date' => date('d F Y H:i', strtotime($list['detail']['taken_at'])),
                ];
            }
            if ($list['detail']['ready_at'] != null) {
                $result['detail']['detail_status'][] = [
                    'text' => 'Your order is ready ',
                    'date' => date('d F Y H:i', strtotime($list['detail']['ready_at'])),
                ];
            }
            if ($list['detail']['receive_at'] != null) {
                $result['detail']['detail_status'][] = [
                    'text' => 'Your order has been received',
                    'date' => date('d F Y H:i', strtotime($list['detail']['receive_at'])),
                ];
            }
            $result['detail']['detail_status'][] = [
                'text' => 'Your order awaits confirmation ',
                'date' => date('d F Y H:i', strtotime($list['transaction_date'])),
            ];
        }

        foreach ($list['payment'] as $key => $value) {
            if ($value['name'] == 'Balance') {
                $result['transaction_payment'][$key] = [
                    'name'       => (env('POINT_NAME')) ? env('POINT_NAME') : $value['name'],
                    'is_balance' => 1,
                    'amount'     => MyHelper::requestNumber($value['amount'], '_POINT'),
                ];
            } else {
                $result['transaction_payment'][$key] = [
                    'name'   => $value['name'],
                    'amount' => MyHelper::requestNumber($value['amount'], '_CURRENCY'),
                ];
            }
        }

        return response()->json(MyHelper::checkGet($result));
    }
    public function outletNotif($data, $id_outlet)
    {
        $outletToken = OutletToken::where('id_outlet', $id_outlet)->get();
        $subject     = $data['subject'] ?? 'Update Status';
        $stringBody  = $data['string_body'] ?? '';
        unset($data['subject']);
        unset($data['string_body']);
        if (env('PUSH_NOTIF_OUTLET') == 'fcm') {
            $tokens = $outletToken->pluck('token')->toArray();
            if (!empty($tokens)) {
                $subject = $subject;
                $push    = PushNotificationHelper::sendPush($tokens, $subject, $stringBody, null, $data);
            }
        } else {
            $dataArraySend = [];

            foreach ($outletToken as $key => $value) {
                $dataOutletSend = [
                    'to'    => $value['token'],
                    'title' => $subject,
                    'body'  => $stringBody,
                    'data'  => $data,
                ];

                array_push($dataArraySend, $dataOutletSend);

            }

            $curl = MyHelper::post('https://exp.host/--/api/v2/push/send', null, $dataArraySend);
            if (!$curl) {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Send notif failed'],
                ]);
            }
        }

        return true;
    }
    public function listHoliday(Request $request)
    {
        $outlet  = $request->user();
        $holiday = OutletHoliday::distinct()
            ->select(DB::raw('outlet_holidays.id_holiday,date_holidays.id_date_holiday,holiday_name,yearly,date_holidays.date,GROUP_CONCAT(date_edit.date order by date_edit.date) as date_edit'))
            ->where('id_outlet', $outlet->id_outlet)
            ->join('holidays', 'holidays.id_holiday', '=', 'outlet_holidays.id_holiday')
            ->join('date_holidays', 'date_holidays.id_holiday', '=', 'holidays.id_holiday')
            ->join('date_holidays as date_edit', 'date_edit.id_holiday', '=', 'holidays.id_holiday')
            ->where(function ($q) {
                $q->where('yearly', '1')->orWhereDate('date_holidays.date', '>=', date('Y-m-d'));
            })
            ->orderByRaw('CASE WHEN (DATE_FORMAT(date_holidays.`date`,"%m-%d") < DATE_FORMAT(NOW(),"%m-%d")) OR (holidays.yearly = "0" AND YEAR(date_holidays.`date`) > YEAR(NOW())) THEN 1 ELSE 0 END')
            ->orderByRaw('DATE_FORMAT(date_holidays.`date`,"%m-%d")')
            ->orderByRaw('YEAR(date_holidays.`date`)')
            ->groupBy('outlet_holidays.id_holiday', 'date_holidays.date');
        if ($request->page) {
            $result = $holiday->paginate()->toArray();
            $toMod  = &$result['data'];
        } else {
            $result = $holiday->get()->toArray();
            $toMod  = &$result;
        }
        foreach ($toMod as &$value) {
            $value['date_edit']   = array_values(array_unique(explode(',', $value['date_edit'])));
            $value['date_pretty'] = MyHelper::indonesian_date_v2($value['date'], $value['yearly'] ? 'd F' : 'd F Y');
        }
        return MyHelper::checkGet($result);
    }
    public function createHoliday(HolidayUpdate $request)
    {
        $post    = $request->json('holiday');
        $outlet  = $request->user();
        $holiday = [
            'holiday_name' => $post['holiday_name'],
            'yearly'       => $post['yearly'],
        ];

        DB::beginTransaction();
        $insertHoliday = Holiday::create($holiday);

        if ($insertHoliday) {
            $dateHoliday = [];
            if (!is_array($post['date_holiday'])) {
                $post['date_holiday'] = [$post['date_holiday']];
            }
            $date = array_unique($post['date_holiday']);

            foreach ($date as $value) {
                if (!$holiday['yearly'] && $value < date('Y-m-d')) {
                    DB::rollBack();
                    return [
                        'status'   => 'fail',
                        'messages' => ['Tanggal yang dimasukkan sudah terlewati']];
                }
                $dataDate = [
                    'id_holiday' => $insertHoliday['id_holiday'],
                    'date'       => date('Y-m-d', strtotime($value)),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                array_push($dateHoliday, $dataDate);
            }

            $insertDateHoliday = DateHoliday::insert($dateHoliday);

            if ($insertDateHoliday) {
                $dataOutlet = [
                    'id_holiday' => $insertHoliday['id_holiday'],
                    'id_outlet'  => $outlet->id_outlet,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                $insertOutletHoliday = OutletHoliday::create($dataOutlet);

                if ($insertOutletHoliday) {
                    DB::commit();
                    return response()->json(MyHelper::checkCreate($insertOutletHoliday));

                } else {
                    DB::rollBack();
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => [
                            'Data is invalid !!!',
                        ],
                    ]);
                }
            } else {
                DB::rollBack();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => [
                        'Data is invalid !!!',
                    ],
                ]);
            }

        } else {
            DB::rollBack();
            return response()->json([
                'status'   => 'fail',
                'messages' => [
                    'Data is invalid !!!',
                ],
            ]);
        }
    }
    public function updateHoliday(HolidayUpdate $request)
    {
        $post       = $request->json('holiday');
        $outlet     = $request->user();
        $id_holiday = $post['id_holiday'];
        $holiday    = [
            'holiday_name' => $post['holiday_name'],
            'yearly'       => $post['yearly'] ?? 0,
        ];

        DB::beginTransaction();
        $insertHoliday = Holiday::find($id_holiday);
        if (!$insertHoliday) {
            return MyHelper::checkGet([], 'Holiday not found');
        }
        $insertHoliday->update($holiday);
        DateHoliday::where('id_holiday', $id_holiday)->delete();
        if ($insertHoliday) {
            $dateHoliday = [];
            if (!is_array($post['date_holiday'])) {
                $post['date_holiday'] = [$post['date_holiday']];
            }
            $date = array_unique($post['date_holiday']);

            foreach ($date as $value) {
                if (!$holiday['yearly'] && $value < date('Y-m-d')) {
                    DB::rollBack();
                    return [
                        'status'   => 'fail',
                        'messages' => ['Tanggal yang dimasukkan sudah terlewati']];
                }
                $dataDate = [
                    'id_holiday' => $insertHoliday['id_holiday'],
                    'date'       => date('Y-m-d', strtotime($value)),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                array_push($dateHoliday, $dataDate);
            }

            $insertDateHoliday = DateHoliday::insert($dateHoliday);

            $dataOutlet = [
                'id_holiday' => $insertHoliday['id_holiday'],
                'id_outlet'  => $outlet->id_outlet,
            ];

            $insertOutletHoliday = OutletHoliday::updateOrCreate($dataOutlet);

            if ($insertOutletHoliday) {
                DB::commit();
                return response()->json(MyHelper::checkCreate($insertOutletHoliday));

            } else {
                DB::rollBack();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => [
                        'Data is invalid !!!',
                    ],
                ]);
            }

        } else {
            DB::rollBack();
            return response()->json([
                'status'   => 'fail',
                'messages' => [
                    'Data is invalid !!!',
                ],
            ]);
        }
    }
    public function deleteHoliday(Request $request)
    {
        $id_date_holiday = $request->id_date_holiday;
        $id_holiday      = $request->id_holiday;
        if ($id_date_holiday) {
            $date_holiday = DateHoliday::where('id_date_holiday', $id_date_holiday)->first();
            if (!$date_holiday) {
                return MyHelper::checkDelete(false);
            }
            // count
            $count_date = DateHoliday::where('id_holiday', $date_holiday->id_holiday)->count();
            if ($count_date > 1) {
                return MyHelper::checkDelete($date_holiday->delete());
            } else {
                $id_holiday = $date_holiday->id_holiday;
            }
        }
        if ($id_holiday) {
            $delete = Holiday::where(['holidays.id_holiday' => $id_holiday, 'id_outlet' => $request->user()->id_outlet])->join('outlet_holidays', 'outlet_holidays.id_holiday', '=', 'holidays.id_holiday')->delete();
            return MyHelper::checkDelete($delete);
        }
        return MyHelper::checkGet([], 'Holiday not found');
    }
}
