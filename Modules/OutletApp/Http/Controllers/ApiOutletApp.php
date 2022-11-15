<?php

namespace Modules\OutletApp\Http\Controllers;

use App\Http\Models\Configs;
use App\Http\Models\DateHoliday;
use App\Http\Models\Holiday;
use App\Http\Models\LogBalance;
use App\Http\Models\Outlet;
use App\Http\Models\OutletHoliday;
use App\Http\Models\OutletSchedule;
use App\Http\Models\OutletToken;
use App\Http\Models\City;
use App\Http\Models\Product;
use App\Http\Models\ProductCategory;
use App\Http\Models\ProductModifier;
use App\Http\Models\ProductPrice;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionProductModifier;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\TransactionPaymentOffline;
use App\Http\Models\TransactionPaymentOvo;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionPickupGoSend;
use App\Http\Models\TransactionPickupWehelpyou;
use App\Http\Models\User;
use App\Http\Models\UserOutlet;
use App\Http\Models\PaymentMethod;
use App\Http\Models\PaymentMethodOutlet;
use App\Lib\GoSend;
use App\Lib\Midtrans;
use App\Lib\Ovo;
use App\Lib\MyHelper;
use App\Lib\PushNotificationHelper;
use App\Lib\WeHelpYou;
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
use Modules\Plastic\Entities\PlasticTypeOutlet;
use Modules\Product\Entities\ProductDetail;
use App\Http\Models\ProductModifierDetail;
use Modules\Product\Entities\ProductStockStatusUpdate;
use Modules\Product\Entities\ProductModifierGroup;
use Modules\Product\Entities\ProductModifierStockStatusUpdate;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantGroupDetail;
use Modules\ProductVariant\Entities\ProductVariantPivot;
use Modules\ProductVariant\Entities\TransactionProductVariant;
use Modules\SettingFraud\Entities\FraudDetectionLogTransactionDay;
use Modules\SettingFraud\Entities\FraudDetectionLogTransactionWeek;
use Modules\Shift\Entities\Shift;
use Modules\Shift\Entities\UserOutletApp;
use Modules\Transaction\Entities\TransactionBundlingProduct;
use Modules\Transaction\Http\Requests\TransactionDetail;
use Carbon\Carbon;
use Modules\ShopeePay\Entities\TransactionPaymentShopeePay;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\Product\Entities\ProductGlobalPrice;
use App\Http\Models\TransactionPickupGoSendUpdate;
use Modules\OutletApp\Entities\ProductModifierGroupInventoryBrand;
use App\Http\Models\Autocrm;
use Modules\Autocrm\Entities\AutoresponseCodeList;

use function foo\func;

class ApiOutletApp extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->balance          = "Modules\Balance\Http\Controllers\BalanceController";
        $this->getNotif         = "Modules\Transaction\Http\Controllers\ApiNotification";
        $this->membership       = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->trx              = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->promo_campaign   = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
        $this->voucher          = "Modules\Deals\Http\Controllers\ApiDealsVoucher";
        $this->subscription     = "Modules\Subscription\Http\Controllers\ApiSubscriptionVoucher";
        $this->endPoint  = config('url.storage_url_api');
        $this->shopeepay      = "Modules\ShopeePay\Http\Controllers\ShopeePayController";
        $this->outlet           = "Modules\Outlet\Http\Controllers\ApiOutletController";
        $this->autoresponse_code = "Modules\Autocrm\Http\Controllers\ApiAutoresponseWithCode";
        $this->default_driver_photo = config('url.storage_url_api') . "default_image/delivery/driver_default_image.png";
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
            $query = OutletToken::create(['id_outlet' => $outlet['id_outlet'], 'token' => $post['token']]);
            return response()->json(MyHelper::checkUpdate($query));
        }
    }

    public function listOrder(Request $request)
    {
        $post   = $request->json()->all();
        $outlet = $request->user();

        $list = Transaction::leftjoin('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
            ->leftJoin('transaction_products', 'transactions.id_transaction', 'transaction_products.id_transaction')
            ->leftJoin('users', 'users.id', 'transactions.id_user')
            ->select(
                'transactions.id_transaction',
                'transaction_receipt_number',
                'order_id',
                'transaction_date',
                DB::raw('(CASE WHEN pickup_by = "Customer" THEN "Pickup Order" ELSE "Delivery" END) AS transaction_type'),
                'pickup_by',
                'pickup_type',
                'pickup_at',
                'receive_at',
                'ready_at',
                'taken_at',
                'reject_at',
                'taken_by_system_at',
                'transaction_grandtotal',
                DB::raw('sum(transaction_product_qty) as total_item'),
                'users.name'
            )
            ->where('transactions.id_outlet', $outlet->id_outlet)
            ->whereDate('transaction_date', date('Y-m-d'))
            ->where('transaction_payment_status', 'Completed')
            ->where('trasaction_type', 'Pickup Order')
            ->whereNull('void_date')
            ->groupBy('transaction_products.id_transaction');
        switch ($post['sort'] ?? '') {
            case 'oldest':
                $list->orderBy('transaction_date', 'ASC')->orderBy('transactions.id_transaction', 'ASC');
                break;

            case 'newest':
                $list->orderBy('transaction_date', 'DESC')->orderBy('transactions.id_transaction', 'DESC');
                break;

            case 'shortest_pickup_time':
                $list->orderBy('pickup_at', 'ASC')->orderBy('transactions.id_transaction', 'ASC');
                break;

            case 'longest_pickup_time':
                $list->orderBy('pickup_at', 'DESC')->orderBy('transactions.id_transaction', 'DESC');
                break;

            case 'shortest_delivery_time':
                $list->orderBy('pickup_at', 'ASC')->orderBy('transactions.id_transaction', 'ASC');
                break;

            case 'longest_delivery_time':
                $list->orderBy('pickup_at', 'DESC')->orderBy('transactions.id_transaction', 'DESC');
                break;

            default:
                $list->orderBy('pickup_at', 'ASC')
                ->orderBy('transaction_date', 'ASC')
                ->orderBy('transactions.id_transaction', 'ASC');
                break;
        }
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
            if ($dataList['taken_by_system_at'] != null) {
                $dataList['status'] = 'Completed';
                $listCompleted[]    = $dataList;
            } elseif ($dataList['reject_at'] != null) {
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

        $result['unpaid']['count'] = Transaction::where('id_outlet', $request->user()->id_outlet)
            ->where('transaction_payment_status', 'Pending')
            ->whereDate('transaction_date', date('Y-m-d'))
            ->count();

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
            ->where('transactions.id_outlet', $request->user()->id_outlet)
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
                ->where('transactions.id_outlet', $request->user()->id_outlet)
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
                    'url'            => config('url.api_url') . '/transaction/web/view/outletapp?data=' . $base,
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
            ->where('transactions.id_outlet', $outlet->id_outlet)
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


        $pickup = TransactionPickup::where('id_transaction', $order->id_transaction)->update(['receive_at' => date('Y-m-d H:i:s')]);

        if ($pickup) {
           //send notif to customer only for pickup
            if ($order->pickup_by == 'Customer') {
                $user = User::find($order->id_user);
                $send = app($this->autocrm)->SendAutoCRM('Order Accepted', $user['phone'], [
                    "outlet_name"      => $outlet['outlet_name'],
                    'id_transaction'   => $order->id_transaction,
                    "id_reference"     => $order->transaction_receipt_number . ',' . $order->id_outlet,
                    "transaction_date" => $order->transaction_date,
                    'order_id'         => $order->order_id,
                    'receipt_number'   => $order->transaction_receipt_number,
                ]);
                if ($send != true) {
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => ['Failed Send notification to customer'],
                    ]);
                }
            }
            $result = ['status' => 'success'];

            if ($order->pickup_by != 'Customer') {
                switch ($order->pickup_by) {
                    case 'Wehelpyou':
                        $result = $this->bookWehelpyou($order);
                        break;

                    default:
                        $result = $this->bookGoSend($order);
                        break;
                }
            }

            if (($result['status'] ?? false) != 'success') {
                return response()->json([
                    'status'   => 'fail',
                    'messages' => $result['messages'] ?? ['Failed to order GO-SEND'],
                ]);
            }
        }

        return response()->json(MyHelper::checkUpdate($pickup));
    }

    public function SetReady(DetailOrder $request, $autoready = false)
    {
        $post   = $request->json()->all();
        $outlet = $request->user();

        $order = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
            ->where('order_id', $post['order_id'])
            ->whereDate('transaction_date', date('Y-m-d'))
            ->where('transactions.id_outlet', $outlet->id_outlet)
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

        $currentdate = date('Y-m-d H:i');
        $setTime = date('Y-m-d H:i', strtotime($order->pickup_at . ' - 15 minutes'));
        if ($order->pickup_type == 'set time' && $currentdate < $setTime) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Order dapat ditandai siap minimum 15 menit sebelum waktu pengambilan'],
            ]);
        }

        if ($order->pickup_by != 'Customer') {
            switch ($order->pickup_by) {
                case 'Wehelpyou':
                    $pickupWHY = TransactionPickupWehelpyou::where('id_transaction_pickup', $order->id_transaction_pickup)->first();
                    if (
                        !$pickupWHY
                        || !$pickupWHY['latest_status_id']
                        || in_array($pickupWHY['latest_status_id'] ?? false, WeHelpYou::orderEndStatusId())
                    ) {
                        return response()->json([
                            'status'   => 'fail',
                            'messages' => ['Driver belum ditemukan']
                        ]);
                    }
                    break;

                default:
                    $pickup_gosend = TransactionPickupGoSend::where('id_transaction_pickup', $order->id_transaction_pickup)->first();
                    if (!$pickup_gosend || !$pickup_gosend['latest_status'] || in_array($pickup_gosend['latest_status'] ?? false, ['no_driver', 'rejected', 'cancelled', 'confirmed'])) {
                        return response()->json([
                            'status'   => 'fail',
                            'messages' => ['Driver belum ditemukan']
                        ]);
                    }
                    break;
            }
        }

        DB::beginTransaction();
        $pickup = TransactionPickup::where('id_transaction', $order->id_transaction)->update(['ready_at' => date('Y-m-d H:i:s'), 'is_autoready' => $autoready ? 1 : 0]);

        // sendPoint delivery after status delivered only
        if ($pickup && $order->pickup_by == 'Customer' && $order->cashback_insert_status != 1) {
            //send notif to customer
            $user = User::find($order->id_user);

            $newTrx    = Transaction::with('user.memberships', 'outlet', 'productTransaction', 'transaction_vouchers', 'promo_campaign_promo_code', 'promo_campaign_promo_code.promo_campaign')->where('id_transaction', $order->id_transaction)->first();
            $checkType = TransactionMultiplePayment::where('id_transaction', $order->id_transaction)->get()->toArray();
            $column    = array_column($checkType, 'type');

            $use_referral = optional(optional($newTrx->promo_campaign_promo_code)->promo_campaign)->promo_type == 'Referral';
            MyHelper::updateFlagTransactionOnline($newTrx, 'success', $newTrx->user);

            AchievementCheck::dispatch(['id_transaction' => $order->id_transaction, 'phone' => $user['phone']])->onConnection('achievement');
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
                'order_id'         => $order->order_id,
                'receipt_number'   => $order->transaction_receipt_number,
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
            ->where('transactions.id_outlet', $outlet->id_outlet)
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

        if ($order->pickup_by == 'Customer') {
            $order->show_rate_popup = 1;
            \App\Jobs\UpdateQuestProgressJob::dispatch($order->id_transaction)->onConnection('quest');
        }

        $order->save();
        if ($pickup) {
            //send notif to customer
            $user = User::find($order->id_user);
            $getAvailableCodeCrm = Autocrm::where('autocrm_title', 'Order Taken With Code')->first();
            $code = null;
            $idCode = null;

            if (
                $order->pickup_by == 'Customer' && !empty($getAvailableCodeCrm) &&
                ($getAvailableCodeCrm['autocrm_email_toogle'] == 1 || $getAvailableCodeCrm['autocrm_sms_toogle'] == 1 ||
                    $getAvailableCodeCrm['autocrm_push_toogle'] == 1 || $getAvailableCodeCrm['autocrm_inbox_toogle'] == 1)
            ) {
                $getAvailableCode = app($this->autoresponse_code)->getAvailableCode($order->id_transaction);
                $code = $getAvailableCode['autoresponse_code'] ?? null;
                $idCode = $getAvailableCode['id_autoresponse_code_list'] ?? null;
            }

            if (!empty($code)) {
                $send = app($this->autocrm)->SendAutoCRM('Order Taken With Code', $user['phone'], [
                    "outlet_name"      => $outlet['outlet_name'],
                    'id_transaction'   => $order->id_transaction,
                    "id_reference"     => $order->transaction_receipt_number . ',' . $order->id_outlet,
                    "transaction_date" => $order->transaction_date,
                    'order_id'         => $order->order_id,
                    'receipt_number'   => $order->transaction_receipt_number,
                    'code'             => $code
                ]);

                $updateCode = AutoresponseCodeList::where('id_autoresponse_code_list', $idCode)->update(['id_user' => $user['id'], 'id_transaction' => $order->id_transaction]);
                if ($updateCode) {
                    app($this->autoresponse_code)->stopAutoresponse($idCode);
                }
            } else {
                $send = app($this->autocrm)->SendAutoCRM($order->pickup_by == 'Customer' ? 'Order Taken' : 'Order Taken By Driver', $user['phone'], [
                    "outlet_name"      => $outlet['outlet_name'],
                    'id_transaction'   => $order->id_transaction,
                    "id_reference"     => $order->transaction_receipt_number . ',' . $order->id_outlet,
                    "transaction_date" => $order->transaction_date,
                    'order_id'         => $order->order_id,
                    'receipt_number'   => $order->transaction_receipt_number
                ]);
            }

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

    public function profile(Request $request)
    {
        $outlet                    = $request->user();
        $profile['outlet_name']    = $outlet['outlet_name'];
        $profile['outlet_status']  = $outlet['outlet_status'] == "Active" ? "Aktif" : "Tidak Aktif";
        $profile['outlet_code']    = $outlet['outlet_code'];
        $profile['outlet_address'] = $outlet['outlet_address'] ?? '';
        $profile['outlet_phone']   = $outlet['outlet_phone'] ?? '';
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

        $is_modifier = true;
        // product_id = product id or modifier_id 1000 or modifier_group_id id + 100000
        $list_id = array_merge($request->available ?? [], $request->sold_out ?? []);
        foreach ($list_id as $id) {
            if ($id > 100000 && $id % 100000) {
                return $this->modifierGroupSoldOut($request);
                break;
            }
            if ($id % 1000) {
                $is_modifier = false;
                break;
            }
        }

        $updated     = 0;
        if (isset($request->variants) && !empty($request->variants)) {
            $outlet = Outlet::where('id_outlet', $outlet['id_outlet'])->first();
            foreach ($request->variants as $v) {
                if (isset($v['available']) && !empty($v['available'])) {
                    foreach ($v['available'] as $availableProductVariant) {
                        $status = 'Available';
                        $updated = ProductVariantGroupDetail::updateOrCreate(['id_outlet' => $outlet['id_outlet'], 'id_product_variant_group' => $availableProductVariant], ['product_variant_group_stock_status' => $status]);
                    }
                    Product::refreshVariantTree($v['id_product'], $outlet);
                }

                if (isset($v['sold_out']) && !empty($v['sold_out'])) {
                    foreach ($v['sold_out'] as $soldOutProductVariant) {
                        $status = 'Sold Out';
                        $updated = ProductVariantGroupDetail::updateOrCreate(['id_outlet' => $outlet['id_outlet'], 'id_product_variant_group' => $soldOutProductVariant], ['product_variant_group_stock_status' => $status]);
                    }
                    Product::refreshVariantTree($v['id_product'], $outlet);
                }
            }
        }

        if (isset($post['sold_out']) && !empty($post['sold_out'])) {
            $sold = array_unique($post['sold_out']);
            foreach ($sold as $s) {
                $productVariantGroup = ProductVariantGroup::where('id_product', $s)->get()->toArray();
                foreach ($productVariantGroup as $pvg) {
                    $updated = ProductVariantGroupDetail::updateOrCreate(['id_outlet' => $outlet['id_outlet'], 'id_product_variant_group' => $pvg['id_product_variant_group']], ['product_variant_group_stock_status' => 'Sold Out']);
                }
                Product::refreshVariantTree($s, $outlet);
            }
        }

        if (!$is_modifier) {
            $updated     = 0;
            $date_time   = date('Y-m-d H:i:s');
            if ($post['sold_out']) {
                $post['sold_out'] = array_unique($post['sold_out']);
                $found = ProductDetail::where('id_outlet', $outlet['id_outlet'])
                    ->whereIn('id_product', $post['sold_out'])
                    ->where('product_detail_stock_status', '<>', 'Sold Out');
                $x = $found->get()->toArray();
                foreach ($x as $product) {
                    $create = ProductStockStatusUpdate::create([
                        'id_product'        => $product['id_product'],
                        'id_user'           => $user_outlet['id_user'],
                        'user_type'         => $user_outlet['user_type'],
                        'user_name'         => $user_outlet['name'],
                        'user_email'        => $user_outlet['email'],
                        'id_outlet'         => $outlet->id_outlet,
                        'date_time'         => $date_time,
                        'new_status'        => 'Sold Out',
                        'id_outlet_app_otp' => null,
                    ]);
                }
                $updated += $found->update(['product_detail_stock_status' => 'Sold Out']);

                //create detail product
                $newDetail = ProductDetail::where('id_outlet', $outlet['id_outlet'])
                    ->whereIn('id_product', $post['sold_out'])->select('id_product')->get();

                if (count($newDetail) > 0) {
                    $newDetail = $newDetail->pluck('id_product')->toArray();
                    $diff = array_diff($post['sold_out'], $newDetail);
                } else {
                    //all product need to be created in product_detail
                    $diff = $post['sold_out'];
                }
                if (count($diff) > 0) {
                    $insert = [];
                    $insertStatus = [];
                    foreach ($diff as $idProd) {
                        if ($idProd != 0) {
                            $insert[] = [
                                'id_product' => $idProd,
                                'id_outlet'  => $outlet['id_outlet'],
                                'product_detail_stock_status' => 'Sold Out',
                                'product_detail_visibility' => null,
                                'product_detail_status' => 'Active',
                                'created_at' => $date_time,
                                'updated_at' => $date_time
                            ];
                            $insertStatus[] = [
                                'id_product'        => $idProd,
                                'id_user'           => $user_outlet['id_user'],
                                'user_type'         => $user_outlet['user_type'],
                                'user_name'         => $user_outlet['name'],
                                'user_email'        => $user_outlet['email'],
                                'id_outlet'         => $outlet->id_outlet,
                                'date_time'         => $date_time,
                                'new_status'        => 'Sold Out',
                                'id_outlet_app_otp' => null,
                            ];
                        }
                    }
                    $createDetail = ProductDetail::insert($insert);
                    $createStatus = ProductStockStatusUpdate::insert($insertStatus);
                    $updated += $createDetail;
                }
            }
            if ($post['available']) {
                $post['available'] = array_unique($post['available']);
                $found = ProductDetail::where('id_outlet', $outlet['id_outlet'])
                    ->whereIn('id_product', $post['available'])
                    ->where('product_detail_stock_status', '<>', 'Available');
                $x = $found->get()->toArray();
                foreach ($x as $product) {
                    $create = ProductStockStatusUpdate::create([
                        'id_product'        => $product['id_product'],
                        'id_user'           => $user_outlet['id_user'],
                        'user_type'         => $user_outlet['user_type'],
                        'user_name'         => $user_outlet['name'],
                        'user_email'        => $user_outlet['email'],
                        'id_outlet'         => $outlet->id_outlet,
                        'date_time'         => $date_time,
                        'new_status'        => 'Available',
                        'id_outlet_app_otp' => null,
                    ]);
                }
                $updated += $found->update(['product_detail_stock_status' => 'Available']);

                //create detail product
                $newDetail = ProductDetail::where('id_outlet', $outlet['id_outlet'])
                    ->whereIn('id_product', $post['available'])->select('id_product')->get();

                if (count($newDetail) > 0) {
                    $newDetail = $newDetail->pluck('id_product')->toArray();
                    $diff = array_diff($post['available'], $newDetail);
                } else {
                    //all product need to be created in product_detail
                    $diff = $post['available'];
                }

                if (count($diff) > 0) {
                    $insert = [];
                    $insertStatus = [];
                    foreach ($diff as $idProd) {
                        if ($idProd != 0) {
                            $insert[] = [
                                'id_product' => $idProd,
                                'id_outlet'  => $outlet['id_outlet'],
                                'product_detail_stock_status' => 'Available',
                                'product_detail_visibility' => null,
                                'product_detail_status' => 'Active',
                                'created_at' => $date_time,
                                'updated_at' => $date_time
                            ];

                            $insertStatus = [
                                'id_product'        => $idProd,
                                'id_user'           => $user_outlet['id_user'],
                                'user_type'         => $user_outlet['user_type'],
                                'user_name'         => $user_outlet['name'],
                                'user_email'        => $user_outlet['email'],
                                'id_outlet'         => $outlet->id_outlet,
                                'date_time'         => $date_time,
                                'new_status'        => 'Available',
                                'id_outlet_app_otp' => null,
                            ];
                        }
                    }
                    $createDetail = ProductDetail::insert($insert);
                    $createStatus = ProductStockStatusUpdate::insert($insertStatus);
                    $updated += $createDetail;
                }
            }
            return [
                'status' => 'success',
                'result' => ['updated' => $updated],
            ];
        } else {
            $updated     = 0;
            $date_time   = date('Y-m-d H:i:s');
            if ($post['sold_out']) {
                // modifier id = product_id / 1000
                $post['sold_out'] = array_map(function ($val) {
                    return $val / 1000;
                }, array_unique($post['sold_out']));
                $found = ProductModifierDetail::where('id_outlet', $outlet['id_outlet'])
                    ->whereIn('id_product_modifier', $post['sold_out'])
                    ->where('product_modifier_stock_status', '<>', 'Sold Out');
                $x = $found->get()->toArray();
                foreach ($x as $product) {
                    $create = ProductModifierStockStatusUpdate::create([
                        'id_product_modifier' => $product['id_product_modifier'],
                        'id_user'           => $user_outlet['id_user'],
                        'user_type'         => $user_outlet['user_type'],
                        'user_name'         => $user_outlet['name'],
                        'user_email'        => $user_outlet['email'],
                        'id_outlet'         => $outlet->id_outlet,
                        'date_time'         => $date_time,
                        'new_status'        => 'Sold Out',
                        'id_outlet_app_otp' => null,
                    ]);
                }
                $updated += $found->update(['product_modifier_stock_status' => 'Sold Out']);

                //create detail product
                $newDetail = ProductModifierDetail::where('id_outlet', $outlet['id_outlet'])
                    ->whereIn('id_product_modifier', $post['sold_out'])->select('id_product_modifier')->get();

                if (count($newDetail) > 0) {
                    $newDetail = $newDetail->pluck('id_product_modifier')->toArray();
                    $diff = array_diff($post['sold_out'], $newDetail);
                } else {
                    //all product need to be created in product_detail
                    $diff = $post['sold_out'];
                }
                if (count($diff) > 0) {
                    $insert = [];
                    $insertStatus = [];
                    foreach ($diff as $idProd) {
                        if ($idProd != 0) {
                            $insert[] = [
                                'id_product_modifier' => $idProd,
                                'id_outlet'  => $outlet['id_outlet'],
                                'product_modifier_stock_status' => 'Sold Out',
                                'product_modifier_visibility' => null,
                                'product_modifier_status' => 'Active',
                                'created_at' => $date_time,
                                'updated_at' => $date_time
                            ];
                            $insertStatus[] = [
                                'id_product_modifier'        => $idProd,
                                'id_user'           => $user_outlet['id_user'],
                                'user_type'         => $user_outlet['user_type'],
                                'user_name'         => $user_outlet['name'],
                                'user_email'        => $user_outlet['email'],
                                'id_outlet'         => $outlet->id_outlet,
                                'date_time'         => $date_time,
                                'new_status'        => 'Sold Out',
                                'id_outlet_app_otp' => null,
                            ];
                        }
                    }
                    $createDetail = ProductModifierDetail::insert($insert);
                    $createStatus = ProductModifierStockStatusUpdate::insert($insertStatus);
                    $updated += $createDetail;
                }
            }
            if ($post['available']) {
                $post['available'] = array_map(function ($val) {
                    return $val / 1000;
                }, array_unique($post['available']));
                $found = ProductModifierDetail::where('id_outlet', $outlet['id_outlet'])
                    ->whereIn('id_product_modifier', $post['available'])
                    ->where('product_modifier_stock_status', '<>', 'Available');
                $x = $found->get()->toArray();
                foreach ($x as $product) {
                    $create = ProductModifierStockStatusUpdate::create([
                        'id_product_modifier'        => $product['id_product_modifier'],
                        'id_user'           => $user_outlet['id_user'],
                        'user_type'         => $user_outlet['user_type'],
                        'user_name'         => $user_outlet['name'],
                        'user_email'        => $user_outlet['email'],
                        'id_outlet'         => $outlet->id_outlet,
                        'date_time'         => $date_time,
                        'new_status'        => 'Available',
                        'id_outlet_app_otp' => null,
                    ]);
                }
                $updated += $found->update(['product_modifier_stock_status' => 'Available']);

                //create detail product
                $newDetail = ProductModifierDetail::where('id_outlet', $outlet['id_outlet'])
                    ->whereIn('id_product_modifier', $post['available'])->select('id_product_modifier')->get();

                if (count($newDetail) > 0) {
                    $newDetail = $newDetail->pluck('id_product_modifier')->toArray();
                    $diff = array_diff($post['available'], $newDetail);
                } else {
                    //all product need to be created in product_detail
                    $diff = $post['available'];
                }

                if (count($diff) > 0) {
                    $insert = [];
                    $insertStatus = [];
                    foreach ($diff as $idProd) {
                        if ($idProd != 0) {
                            $insert[] = [
                                'id_product_modifier' => $idProd,
                                'id_outlet'  => $outlet['id_outlet'],
                                'product_modifier_stock_status' => 'Available',
                                'product_modifier_visibility' => null,
                                'product_modifier_status' => 'Active',
                                'created_at' => $date_time,
                                'updated_at' => $date_time
                            ];

                            $insertStatus = [
                                'id_product_modifier'        => $idProd,
                                'id_user'           => $user_outlet['id_user'],
                                'user_type'         => $user_outlet['user_type'],
                                'user_name'         => $user_outlet['name'],
                                'user_email'        => $user_outlet['email'],
                                'id_outlet'         => $outlet->id_outlet,
                                'date_time'         => $date_time,
                                'new_status'        => 'Available',
                                'id_outlet_app_otp' => null,
                            ];
                        }
                    }
                    $createDetail = ProductModifierDetail::insert($insert);
                    $createStatus = ProductModifierStockStatusUpdate::insert($insertStatus);
                    $updated += $createDetail;
                }
            }
            return [
                'status' => 'success',
                'result' => ['updated' => $updated],
            ];
        }
    }
    /**
     * return list category group by brand
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function listCategory(Request $request)
    {
        $outlet = $request->user();
        $outlet->load('brand_outlets');
        $sub    = BrandProduct::select('id_brand', 'id_product', 'id_product_category')->distinct();
        $data   = DB::query()->fromSub($sub, 'brand_product')->select(\DB::raw('brand_product.id_brand,brand_product.id_product_category,count(*) as total_product,sum(case product_detail_stock_status when "Sold Out" then 1 else 0 end) total_sold_out,product_category_name'))
            ->join('product_categories', 'product_categories.id_product_category', '=', 'brand_product.id_product_category')
            ->join('products', function ($query) {
                $query->on('brand_product.id_product', '=', 'products.id_product')
                    ->groupBy('products.id_product');
            })
        // product availbale in outlet
            ->leftjoin('product_detail', function ($join) use ($outlet) {
                $join->on('product_detail.id_product', '=', 'products.id_product')
                    ->where('product_detail.id_outlet', '=', $outlet['id_outlet']);
            })
            ->where(function ($query) {
                $query->where('product_detail.product_detail_visibility', '=', 'Visible')
                    ->orWhere(function ($q) {
                        $q->whereNull('product_detail.product_detail_visibility')
                            ->where('products.product_visibility', 'Visible');
                    });
            })
            ->where(function ($query) {
                $query->where('product_detail.product_detail_status', '=', 'Active')
                    ->orWhereNull('product_detail.product_detail_status');
            })
        // brand produk ada di outlet
            ->join('brand_outlet', function ($join) use ($outlet) {
                $join->on('brand_outlet.id_brand', '=', 'brand_product.id_brand')
                    ->where('brand_outlet.id_outlet', '=', $outlet['id_outlet']);
            });
        if ($outlet['outlet_different_price']) {
            $data->join('product_special_price', function ($join) use ($outlet) {
                $join->on('product_special_price.id_product', '=', 'products.id_product')
                    ->where('product_special_price.id_outlet', $outlet['id_outlet']);
            })->whereNotNull('product_special_price.product_special_price');
        } else {
            $data->join('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
                ->whereNotNull('product_global_price.product_global_price');
        }
        $data = $data->groupBy('brand_product.id_brand', 'brand_product.id_product_category')
            ->orderByRaw('CASE WHEN product_category_order IS NULL OR product_category_order = 0 THEN 1 ELSE 0 END')
            ->orderBy('product_category_order')
            ->orderBy('product_categories.id_product_category')
            ->get()->toArray();
        $result = MyHelper::groupIt($data, 'id_brand', null, function ($key, &$val) {
            $brand = Brand::select('id_brand', 'name_brand', 'order_brand')
                ->where([
                    'id_brand'         => $key,
                    'brand_active'     => 1,
                    'brand_visibility' => 1,
                ])->first();
            if (!$brand) {
                return 'no_brand';
            }
            $brand['categories'] = $val;
            $val                 = $brand;
            return $key;
        });
        unset($result['no_brand']);
        usort($result, function ($a, $b) {
            return $a['order_brand'] <=> $b['order_brand'];
        });
        $modifiers = ProductModifier::select(\DB::raw('0 as id_brand, min(product_modifiers.id_product_modifier) as id_product_category, type as product_category_name, count(distinct(product_modifiers.id_product_modifier)) as total_product, 0 as total_sold_out'))
            ->where('modifier_type', '<>', 'Modifier Group')
            ->leftJoin('product_modifier_details', function ($join) use ($outlet) {
                $join->on('product_modifier_details.id_product_modifier', '=', 'product_modifiers.id_product_modifier')
                    ->where('product_modifier_details.id_outlet', $outlet['id_outlet']);
            })
            ->join('product_modifier_inventory_brands', function ($join) use ($outlet) {
                $join->on('product_modifier_inventory_brands.id_product_modifier', 'product_modifiers.id_product_modifier')
                    ->whereIn('id_brand', $outlet->brand_outlets->pluck('id_brand'));
            })
            ->where(function ($q) {
                $q->where('product_modifier_status', 'Active')->orWhereNull('product_modifier_status');
            })
            ->where(function ($query) {
                $query->where('product_modifier_details.product_modifier_visibility', '=', 'Visible')
                        ->orWhere(function ($q) {
                            $q->whereNull('product_modifier_details.product_modifier_visibility')
                            ->where('product_modifiers.product_modifier_visibility', 'Visible');
                        });
            });

        if ($outlet['outlet_different_price']) {
            $modifiers->join('product_modifier_prices', function ($join) use ($outlet) {
                $join->on('product_modifier_prices.id_product_modifier', '=', 'product_modifiers.id_product_modifier')
                    ->where('product_modifier_prices.id_outlet', $outlet['id_outlet']);
            })->whereNotNull('product_modifier_prices.product_modifier_price');
        } else {
            $modifiers->join('product_modifier_global_prices', 'product_modifier_global_prices.id_product_modifier', '=', 'product_modifier_global_prices.id_product_modifier')
                ->whereNotNull('product_modifier_global_prices.product_modifier_price');
        }

        $modifiers = $modifiers->get();

        $result[] = [
            'id_brand' => 0,
            'name_brand' => 'Topping',
            'order_brand' => 999,
            'categories' => $modifiers
        ];

        $modifier_groups = ProductModifier::select(\DB::raw('0 as id_brand, 0 as id_product_category, "Variant No SKU" as product_category_name, count(distinct(product_modifiers.id_product_modifier_group)) as total_product, 0 as total_sold_out'))
            ->where('modifier_type', '=', 'Modifier Group')
            ->leftJoin('product_modifier_details', function ($join) use ($outlet) {
                $join->on('product_modifier_details.id_product_modifier', '=', 'product_modifiers.id_product_modifier')
                    ->where('product_modifier_details.id_outlet', $outlet['id_outlet']);
            })
            ->join('product_modifier_group_inventory_brands', function ($join) use ($outlet) {
                $join->on('product_modifier_group_inventory_brands.id_product_modifier_group', 'product_modifiers.id_product_modifier_group')
                    ->whereIn('id_brand', $outlet->brand_outlets->pluck('id_brand'));
            })
            ->where(function ($q) {
                $q->where('product_modifier_status', 'Active')->orWhereNull('product_modifier_status');
            })
            ->where(function ($query) {
                $query->where('product_modifier_details.product_modifier_visibility', '=', 'Visible')
                        ->orWhere(function ($q) {
                            $q->whereNull('product_modifier_details.product_modifier_visibility')
                            ->where('product_modifiers.product_modifier_visibility', 'Visible');
                        });
            });

        if ($outlet['outlet_different_price']) {
            $modifier_groups->join('product_modifier_prices', function ($join) use ($outlet) {
                $join->on('product_modifier_prices.id_product_modifier', '=', 'product_modifiers.id_product_modifier')
                    ->where('product_modifier_prices.id_outlet', $outlet['id_outlet']);
            })->whereNotNull('product_modifier_prices.product_modifier_price');
        } else {
            $modifier_groups->join('product_modifier_global_prices', 'product_modifier_global_prices.id_product_modifier', '=', 'product_modifier_global_prices.id_product_modifier')
                ->whereNotNull('product_modifier_global_prices.product_modifier_price');
        }

        $modifier_groups = $modifier_groups->get();

        if (!empty($modifier_groups[0]['total_product'])) {
            $result[] = [
                'id_brand' => 0,
                'name_brand' => 'Variant No SKU',
                'order_brand' => 1000,
                'categories' => $modifier_groups
            ];
        }

        return MyHelper::checkGet(array_values($result));
    }
    /**
     * Return only list product based on selected brand and category
     * @param string $value [description]
     */
    public function productList(ListProduct $request)
    {
        $outlet            = $request->user();
        if ($request->id_brand) {
            // product
            $post              = $request->json()->all();
            $post['id_outlet'] = $outlet['id_outlet'];
            $products          = Product::select([
                'products.product_variant_status',
                'products.id_product', 'products.product_code', 'products.product_name',
                DB::raw('(CASE WHEN product_detail.product_detail_stock_status is NULL THEN "Available" ELSE product_detail.product_detail_stock_status END) AS product_stock_status'),
                // 'product_detail.product_detail_stock_status as product_stock_status',
            ])

            // join brand product
                ->join('brand_product', function ($join) use ($post) {
                    $join->on('brand_product.id_product', '=', 'products.id_product')
                        ->where('brand_product.id_brand', '=', $post['id_brand'])
                        ->where('brand_product.id_product_category', '=', $post['id_product_category']);
                })

            // brand produk ada di outlet
                ->join('brand_outlet', function ($join) use ($post) {
                    $join->on('brand_outlet.id_brand', '=', 'brand_product.id_brand')
                        ->where('brand_outlet.id_outlet', '=', $post['id_outlet']);
                })

            // product available (active & visible)
                ->leftJoin('product_detail', function ($join) use ($post) {
                    $join->on('product_detail.id_product', '=', 'products.id_product')
                        ->where('product_detail.id_outlet', '=', $post['id_outlet']);
                })
                ->where(function ($query) {
                    $query->where('product_detail.product_detail_visibility', '=', 'Visible')
                        ->orWhere(function ($q) {
                            $q->whereNull('product_detail.product_detail_visibility')
                                ->where('products.product_visibility', 'Visible');
                        });
                })
                ->where(function ($query) {
                    $query->where('product_detail.product_detail_status', '=', 'Active')
                        ->orWhereNull('product_detail.product_detail_status');
                });

            // has product price
            if ($outlet->outlet_different_price) {
                $products->join('product_special_price', function ($join) use ($post) {
                    $join->on('product_special_price.id_product', '=', 'products.id_product')
                        ->where('product_special_price.id_outlet', '=', $post['id_outlet']);
                })->whereNotNull('product_special_price.product_special_price');
            } else {
                $products->join('product_global_price', 'products.id_product', '=', 'product_global_price.id_product')
                    ->whereNotNull('product_global_price.product_global_price');
            }

            // group by and order
            $products->groupBy('products.id_product')
                ->orderBy('products.position')
                ->orderBy('products.id_product');

            // build response
            if ($request->page) {
                $data = $products->paginate(30)->toArray();
                if (empty($data['data'])) {
                    return MyHelper::checkGet($data['data']);
                } else {
                    foreach ($data['data'] as $key => $dt) {
                        $variants = [];
                        if ($dt['product_variant_status'] == 1) {
                            $variants = ProductVariantGroup::where('id_product', $dt['id_product'])
                                ->where('product_variant_group_visibility', 'Visible')
                                ->select([
                                    'product_variant_groups.id_product', 'product_variant_groups.id_product_variant_group', 'product_variant_groups.product_variant_group_code',
                                    DB::raw('(SELECT GROUP_CONCAT(pv.product_variant_name SEPARATOR ",") FROM product_variant_pivot pvp join product_variants pv on pv.id_product_variant = pvp.id_product_variant where pvp.id_product_variant_group = product_variant_groups.id_product_variant_group) AS product_variant_group_name'),
                                    DB::raw('(CASE
                        WHEN (Select pvgd.product_variant_group_stock_status from product_variant_group_details as pvgd where pvgd.id_product_variant_group = product_variant_groups.id_product_variant_group AND pvgd.id_outlet = ' . $outlet['id_outlet'] . ') is NULL THEN "Available"
                        ELSE (Select pvgd.product_variant_group_stock_status from product_variant_group_details as pvgd where pvgd.id_product_variant_group = product_variant_groups.id_product_variant_group AND pvgd.id_outlet = ' . $outlet['id_outlet'] . ') END) as product_variant_group_stock_status')])->get()->toArray();
                        }

                        $data['data'][$key]['product_variant_group'] = $variants;
                    }
                }
                return MyHelper::checkGet($data);
            } else {
                $data = $products->get()->toArray();
                foreach ($data as $key => $dt) {
                    $variants = [];
                    if ($dt['product_variant_status'] == 1) {
                        $variants = ProductVariantGroup::where('id_product', $dt['id_product'])
                            ->where('product_variant_group_visibility', 'Visible')
                            ->select([
                                'product_variant_groups.id_product', 'product_variant_groups.id_product_variant_group', 'product_variant_groups.product_variant_group_code',
                                DB::raw('(SELECT GROUP_CONCAT(pv.product_variant_name SEPARATOR ",") FROM product_variant_pivot pvp join product_variants pv on pv.id_product_variant = pvp.id_product_variant where pvp.id_product_variant_group = product_variant_groups.id_product_variant_group) AS product_variant_group_name'),
                                DB::raw('(CASE
                        WHEN (Select pvgd.product_variant_group_stock_status from product_variant_group_details as pvgd where pvgd.id_product_variant_group = product_variant_groups.id_product_variant_group AND pvgd.id_outlet = ' . $outlet['id_outlet'] . ') is NULL THEN "Available"
                        ELSE (Select pvgd.product_variant_group_stock_status from product_variant_group_details as pvgd where pvgd.id_product_variant_group = product_variant_groups.id_product_variant_group AND pvgd.id_outlet = ' . $outlet['id_outlet'] . ') END) as product_variant_group_stock_status')])->get()->toArray();
                    }

                    $data[$key]['product_variant_group'] = $variants;
                }
                return MyHelper::checkGet($data);
            }
        } elseif (!$request->id_brand && $request->id_product_category) {
            $outlet->load('brand_outlets');
            // modifiers
            $modifiers = ProductModifier::select(\DB::raw('product_modifiers.id_product_modifier * 1000 as id_product, code as product_code, text as product_name, CASE WHEN product_modifier_stock_status IS NULL THEN "Available" ELSE product_modifier_stock_status END as product_stock_status'))
            ->leftJoin('product_modifier_details', function ($join) use ($outlet) {
                $join->on('product_modifier_details.id_product_modifier', '=', 'product_modifiers.id_product_modifier')
                    ->where('product_modifier_details.id_outlet', $outlet['id_outlet']);
            })
            ->where(function ($q) {
                $q->where('product_modifier_status', 'Active')->orWhereNull('product_modifier_status');
            })
            ->where('modifier_type', '<>', 'Modifier Group')
            ->where(function ($query) {
                $query->where('product_modifier_details.product_modifier_visibility', '=', 'Visible')
                        ->orWhere(function ($q) {
                            $q->whereNull('product_modifier_details.product_modifier_visibility')
                            ->where('product_modifiers.product_modifier_visibility', 'Visible');
                        });
            })
            ->join('product_modifier_inventory_brands', function ($join) use ($outlet) {
                $join->on('product_modifier_inventory_brands.id_product_modifier', 'product_modifiers.id_product_modifier')
                    ->whereIn('id_brand', $outlet->brand_outlets->pluck('id_brand'));
            })
            ->groupBy('product_modifiers.id_product_modifier');
            $modifiers = $modifiers->orderBy('text');

            // build response
            if ($request->page) {
                $data = $modifiers->paginate(30)->toArray();
                if (empty($data['data'])) {
                    return MyHelper::checkGet($data['data']);
                }
                return MyHelper::checkGet($data);
            } else {
                return MyHelper::checkGet($modifiers->get()->toArray());
            }
        } else {
            return $this->listProductModifierGroup($request);
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
            ->where('product_type', 'product')
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

    public function rejectOrder(DetailOrder $request, $dateNow = null)
    {
        $post = $request->json()->all();

        $outlet = $request->user();

        $shared = \App\Lib\TemporaryDataManager::create('reject_order');
        $refund_failed_process_balance = MyHelper::setting('refund_failed_process_balance');

        $order = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
            ->where('order_id', $post['order_id'])
            ->whereDate('transaction_date', $dateNow ?? date('Y-m-d'))
            ->where('transactions.id_outlet', $outlet->id_outlet)
            // join user used by autocrm void failed
            ->leftJoin('users', 'transactions.id_user', 'users.id')
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

        if ($order->pickup_by == 'GO-SEND') {
            $pickup_gosend = TransactionPickupGoSend::where('id_transaction_pickup', $order->id_transaction_pickup)->first();
            if ($pickup_gosend && $pickup_gosend['latest_status'] && !in_array($pickup_gosend['latest_status'] ?? false, ['no_driver', 'rejected', 'cancelled'])) {
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Driver has been booked'],
                    'should_taken' => true,
                ]);
            } else {
                goto reject;
            }
        } elseif ($order->pickup_by == 'Wehelpyou') {
            $pickupWhy = TransactionPickupWehelpyou::where('id_transaction_pickup', $order->id_transaction_pickup)->first();
            if ($pickupWhy && $pickupWhy['latest_status_id'] && !in_array($pickupWhy['latest_status_id'], WeHelpYou::orderEndFailStatusId())) {
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Driver has been booked'],
                    'should_taken' => true,
                ]);
            } else {
                goto reject;
            }
        }

        if ($order->ready_at) {
            if ($order->pickup_by == 'Customer') {
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Order Has Been Ready'],
                ]);
            } elseif ($order->pickup_by == 'Wehelpyou') {
                $pickupWhy = TransactionPickupWehelpyou::where('id_transaction_pickup', $order->id_transaction_pickup)->first();
                $endStatus = WeHelpYou::orderEndStatusId();
                unset($endStatus['Rejected']);
                if ($pickupWhy['latest_status_id'] && !in_array($pickupWhy['latest_status_id'], $endStatus)) {
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => ['Driver has been booked'],
                    ]);
                } else {
                    goto reject;
                }
            } else {
                $pickup_gosend = TransactionPickupGoSend::where('id_transaction_pickup', $order->id_transaction_pickup)->first();
                if ($pickup_gosend['latest_status'] && !in_array($pickup_gosend['latest_status'] ?? false, ['no_driver', 'cancelled'])) {
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => ['Driver has been booked'],
                    ]);
                } else {
                    goto reject;
                }
            }
        }

        if ($order->taken_at) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Order Has Been Taken'],
            ]);
        }

        reject:
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
            'reject_type'   => 'point',
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

            $user = User::where('id', $order['id_user'])->first();
            if (!$user) {
                TransactionPickup::where('id_transaction', $order->id_transaction)->update([
                    'taken_by_system_at' => date('Y-m-d H:i:s'),
                    'reject_at'     => null,
                    'reject_type'   => null,
                    'reject_reason' => null,
                ]);
                \DB::commit();
                return [
                    'status' => 'fail',
                    'messages' => ['User not found']
                ];
            }
            $user = $user->toArray();

            $rejectBalance = false;

            //refund ke balance
            // if($order['trasaction_payment_type'] == "Midtrans"){
            $multiple = TransactionMultiplePayment::where('id_transaction', $order->id_transaction)->get()->toArray();
            $point = 0;
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
                            $rejectBalance = true;
                        }
                    } elseif ($pay['type'] == 'Ovo') {
                        $payOvo = TransactionPaymentOvo::find($pay['id_payment']);
                        if ($payOvo) {
                            $doRefundPayment = Configs::select('is_active')->where('config_name', 'refund ovo')->pluck('is_active')->first();
                            if ($doRefundPayment) {
                                $point = 0;
                                $transaction = TransactionPaymentOvo::where('transaction_payment_ovos.id_transaction', $post['id_transaction'])
                                    ->join('transactions', 'transactions.id_transaction', '=', 'transaction_payment_ovos.id_transaction')
                                    ->first();
                                TransactionPickup::where('id_transaction', $order->id_transaction)->update([
                                    'reject_type'   => 'refund',
                                ]);
                                $refund = Ovo::Void($transaction);
                                if ($refund['response']['responseCode'] != '00') {
                                    $order->update(['failed_void_reason' => $refund['response']['response_description'] ?? '']);
                                    if ($refund_failed_process_balance) {
                                        $doRefundPayment = false;
                                    } else {
                                        $order->update(['need_manual_void' => 1]);
                                        $order2 = clone $order;
                                        $order2->manual_refund = $payOvo['amount'];
                                        $order2->payment_method = 'Ovo';
                                        $order2->payment_reference_number = $payOvo['approval_code'];
                                        if ($shared['reject_batch'] ?? false) {
                                            $shared['void_failed'][] = $order2;
                                        } else {
                                            $variables = [
                                                'detail' => view('emails.failed_refund', ['transaction' => $order2])->render()
                                            ];
                                            app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('Payment Void Failed', $order->phone, $variables, null, true);
                                        }
                                    }
                                }
                            }

                            // don't use elseif / else because in the if block there are conditions that should be included in this process too
                            if (!$doRefundPayment) {
                                TransactionPickup::where('id_transaction', $order->id_transaction)->update([
                                    'reject_type'   => 'point',
                                ]);
                                $refund = app($this->balance)->addLogBalance($order['id_user'], $point = $payOvo['amount'], $order['id_transaction'], 'Rejected Order Ovo', $order['transaction_grandtotal']);
                                if ($refund == false) {
                                    DB::rollback();
                                    return response()->json([
                                        'status'   => 'fail',
                                        'messages' => ['Insert Cashback Failed'],
                                    ]);
                                }
                                $rejectBalance = true;
                            }
                        }
                    } elseif (strtolower($pay['type']) == 'ipay88') {
                        $point = 0;
                        $payIpay = TransactionPaymentIpay88::find($pay['id_payment']);
                        if ($payIpay) {
                            $doRefundPayment = strtolower($payIpay['payment_method']) == 'ovo' && MyHelper::setting('refund_ipay88');
                            if ($doRefundPayment) {
                                $refund = \Modules\IPay88\Lib\IPay88::create()->void($payIpay, 'trx', 'user', $message);
                                TransactionPickup::where('id_transaction', $order['id_transaction'])->update([
                                    'reject_type'   => 'refund',
                                ]);
                                if (!$refund) {
                                    $order->update(['failed_void_reason' => $message ?? '']);
                                    if ($refund_failed_process_balance) {
                                        $doRefundPayment = false;
                                    } else {
                                        $order->update(['need_manual_void' => 1]);
                                        $order2 = clone $order;
                                        $order2->manual_refund = $payIpay['amount'] / 100;
                                        $order2->payment_method = 'Ipay88';
                                        $order2->payment_detail = $payIpay['payment_method'];
                                        $order2->payment_reference_number = $payIpay['trans_id'];
                                        if ($shared['reject_batch'] ?? false) {
                                            $shared['void_failed'][] = $order2;
                                        } else {
                                            $variables = [
                                                'detail' => view('emails.failed_refund', ['transaction' => $order2])->render()
                                            ];
                                            app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('Payment Void Failed', $order->phone, $variables, null, true);
                                        }
                                    }
                                }
                            }

                            // don't use elseif / else because in the if block there are conditions that should be included in this process too
                            if (!$doRefundPayment) {
                                TransactionPickup::where('id_transaction', $order->id_transaction)->update([
                                    'reject_type'   => 'point',
                                ]);
                                $refund = app($this->balance)->addLogBalance($order['id_user'], $point = ($payIpay['amount'] / 100), $order['id_transaction'], 'Rejected Order', $order['transaction_grandtotal']);
                                if ($refund == false) {
                                    DB::rollback();
                                    return response()->json([
                                        'status'   => 'fail',
                                        'messages' => ['Insert Cashback Failed'],
                                    ]);
                                }
                                $rejectBalance = true;
                            }
                        }
                    } elseif (strtolower($pay['type']) == 'shopeepay') {
                        $point = 0;
                        $payShopeepay = TransactionPaymentShopeePay::find($pay['id_payment']);
                        if ($payShopeepay) {
                            $doRefundPayment = MyHelper::setting('refund_shopeepay');
                            if ($doRefundPayment) {
                                $refund = app($this->shopeepay)->refund($payShopeepay['id_transaction'], 'trx', $errors);
                                TransactionPickup::where('id_transaction', $order['id_transaction'])->update([
                                    'reject_type'   => 'refund',
                                ]);
                                if (!$refund) {
                                    $order->update(['failed_void_reason' => implode(', ', $errors ?: [])]);
                                    if ($refund_failed_process_balance) {
                                        $doRefundPayment = false;
                                    } else {
                                        $order->update(['need_manual_void' => 1]);
                                        $order2 = clone $order;
                                        $order2->payment_method = 'ShopeePay';
                                        $order2->manual_refund = $payShopeepay['amount'] / 100;
                                        $order2->payment_reference_number = $payShopeepay['transaction_sn'];
                                        if ($shared['reject_batch'] ?? false) {
                                            $shared['void_failed'][] = $order2;
                                        } else {
                                            $variables = [
                                                'detail' => view('emails.failed_refund', ['transaction' => $order2])->render()
                                            ];
                                            app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('Payment Void Failed', $order->phone, $variables, null, true);
                                        }
                                    }
                                }
                            }

                            // don't use elseif / else because in the if block there are conditions that should be included in this process too
                            if (!$doRefundPayment) {
                                TransactionPickup::where('id_transaction', $order->id_transaction)->update([
                                    'reject_type'   => 'point',
                                ]);
                                $refund = app($this->balance)->addLogBalance($order['id_user'], $point = ($payShopeepay['amount'] / 100), $order['id_transaction'], 'Rejected Order', $order['transaction_grandtotal']);
                                if ($refund == false) {
                                    DB::rollback();
                                    return response()->json([
                                        'status'   => 'fail',
                                        'messages' => ['Insert Cashback Failed'],
                                    ]);
                                }
                                $rejectBalance = true;
                            }
                        }
                    } else {
                        $point = 0;
                        $payMidtrans = TransactionPaymentMidtran::find($pay['id_payment']);
                        if ($payMidtrans) {
                            $doRefundPayment = MyHelper::setting('refund_midtrans');
                            if ($doRefundPayment) {
                                $refund = Midtrans::refund($payMidtrans['vt_transaction_id'], ['reason' => $post['reason'] ?? '']);
                                TransactionPickup::where('id_transaction', $order->id_transaction)->update([
                                    'reject_type'   => 'refund',
                                ]);
                                if ($refund['status'] != 'success') {
                                    $order->update(['failed_void_reason' => implode(', ', $refund['messages'] ?? [])]);
                                    if ($refund_failed_process_balance) {
                                        $doRefundPayment = false;
                                    } else {
                                        $order->update(['need_manual_void' => 1]);
                                        $order2 = clone $order;
                                        $order2->payment_method = 'Midtrans';
                                        $order2->payment_detail = $payMidtrans['payment_type'];
                                        $order2->manual_refund = $payMidtrans['gross_amount'];
                                        $order2->payment_reference_number = $payMidtrans['vt_transaction_id'];
                                        if ($shared['reject_batch'] ?? false) {
                                            $shared['void_failed'][] = $order2;
                                        } else {
                                            $variables = [
                                                'detail' => view('emails.failed_refund', ['transaction' => $order2])->render()
                                            ];
                                            app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('Payment Void Failed', $order->phone, $variables, null, true);
                                        }
                                    }
                                }
                            }

                            // don't use elseif / else because in the if block there are conditions that should be included in this process too
                            if (!$doRefundPayment) {
                                TransactionPickup::where('id_transaction', $order->id_transaction)->update([
                                    'reject_type'   => 'point',
                                ]);
                                $refund = app($this->balance)->addLogBalance($order['id_user'], $point = $payMidtrans['gross_amount'], $order['id_transaction'], 'Rejected Order Midtrans', $order['transaction_grandtotal']);
                                if ($refund == false) {
                                    DB::rollback();
                                    return response()->json([
                                        'status'    => 'fail',
                                        'messages'  => ['Insert Cashback Failed']
                                    ]);
                                }
                                $rejectBalance = true;
                            }
                        }
                    }
                }
            } else {
                $payMidtrans = TransactionPaymentMidtran::where('id_transaction', $order['id_transaction'])->first();
                $payOvo      = TransactionPaymentOvo::where('id_transaction', $order['id_transaction'])->first();
                $payIpay     = TransactionPaymentIpay88::where('id_transaction', $order['id_transaction'])->first();
                if ($payMidtrans) {
                    $point = 0;
                    $doRefundPayment = MyHelper::setting('refund_midtrans');
                    if ($doRefundPayment) {
                        $refund = Midtrans::refund($payMidtrans['vt_transaction_id'], ['reason' => $post['reason'] ?? '']);
                        TransactionPickup::where('id_transaction', $order->id_transaction)->update([
                            'reject_type'   => 'refund',
                        ]);
                        if ($refund['status'] != 'success') {
                            if ($refund_failed_process_balance) {
                                $doRefundPayment = false;
                            } else {
                                $order->update(['need_manual_void' => 1]);
                                $order2 = clone $order;
                                $order2->payment_method = 'Midtrans';
                                $order2->payment_detail = $payMidtrans['payment_type'];
                                $order2->manual_refund = $payMidtrans['gross_amount'];
                                $order2->payment_reference_number = $payMidtrans['vt_transaction_id'];
                                if ($shared['reject_batch'] ?? false) {
                                    $shared['void_failed'][] = $order2;
                                } else {
                                    $variables = [
                                        'detail' => view('emails.failed_refund', ['transaction' => $order2])->render()
                                    ];
                                    app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('Payment Void Failed', $order->phone, $variables, null, true);
                                }
                            }
                        }
                    }

                    // don't use elseif / else because in the if block there are conditions that should be included in this process too
                    if (!$doRefundPayment) {
                        TransactionPickup::where('id_transaction', $order->id_transaction)->update([
                            'reject_type'   => 'point',
                        ]);
                        $refund = app($this->balance)->addLogBalance($order['id_user'], $point = $payMidtrans['gross_amount'], $order['id_transaction'], 'Rejected Order Midtrans', $order['transaction_grandtotal']);
                        if ($refund == false) {
                            DB::rollback();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Insert Cashback Failed']
                            ]);
                        }
                        $rejectBalance = true;
                    }
                } elseif ($payOvo) {
                    $doRefundPayment = Configs::select('is_active')->where('config_name', 'refund ovo')->pluck('is_active')->first();
                    if ($doRefundPayment) {
                        $point = 0;
                        $transaction = TransactionPaymentOvo::where('transaction_payment_ovos.id_transaction', $post['id_transaction'])
                            ->join('transactions', 'transactions.id_transaction', '=', 'transaction_payment_ovos.id_transaction')
                            ->first();
                        $refund = Ovo::Void($transaction);
                        TransactionPickup::where('id_transaction', $order->id_transaction)->update([
                            'reject_type'   => 'refund',
                        ]);
                        if ($refund['status_code'] != '200') {
                            if ($refund_failed_process_balance) {
                                $doRefundPayment = false;
                            } else {
                                $order->update(['need_manual_void' => 1]);
                                $order2 = clone $order;
                                $order2->payment_method = 'Ovo';
                                $order2->manual_refund = $payOvo['amount'];
                                $order2->payment_reference_number = $payOvo['approval_code'];
                                if ($shared['reject_batch'] ?? false) {
                                    $shared['void_failed'][] = $order2;
                                } else {
                                    $variables = [
                                        'detail' => view('emails.failed_refund', ['transaction' => $order2])->render()
                                    ];
                                    app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('Payment Void Failed', $order->phone, $variables, null, true);
                                }
                            }
                        }
                    }

                    // don't use elseif / else because in the if block there are conditions that should be included in this process too
                    if (!$doRefundPayment) {
                        TransactionPickup::where('id_transaction', $order->id_transaction)->update([
                            'reject_type'   => 'point',
                        ]);
                        $refund = app($this->balance)->addLogBalance($order['id_user'], $point = $payOvo['amount'], $order['id_transaction'], 'Rejected Order Ovo', $order['transaction_grandtotal']);
                        if ($refund == false) {
                            DB::rollback();
                            return response()->json([
                                'status'   => 'fail',
                                'messages' => ['Insert Cashback Failed'],
                            ]);
                        }
                        $rejectBalance = true;
                    }
                } elseif ($payIpay) {
                    $point = 0;
                    $doRefundPayment = strtolower($payIpay['payment_method']) == 'ovo' && MyHelper::setting('refund_ipay88');
                    if ($doRefundPayment) {
                        $refund = \Modules\IPay88\Lib\IPay88::create()->void($payIpay);
                        TransactionPickup::where('id_transaction', $order['id_transaction'])->update([
                            'reject_type'   => 'refund',
                        ]);
                        if (!$refund) {
                            if ($refund_failed_process_balance) {
                                $doRefundPayment = false;
                            } else {
                                $order->update(['need_manual_void' => 1]);
                                $order2 = clone $order;
                                $order2->payment_method = 'Ipay88';
                                $order2->payment_detail = $payIpay['payment_method'];
                                $order2->manual_refund = $payIpay['amount'] / 100;
                                $order2->payment_reference_number = $payIpay['trans_id'];
                                if ($shared['reject_batch'] ?? false) {
                                    $shared['void_failed'][] = $order2;
                                } else {
                                    $variables = [
                                        'detail' => view('emails.failed_refund', ['transaction' => $order2])->render()
                                    ];
                                    app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('Payment Void Failed', $order->phone, $variables, null, true);
                                }
                            }
                        }
                    }

                    // don't use elseif / else because in the if block there are conditions that should be included in this process too
                    if (!$doRefundPayment) {
                        TransactionPickup::where('id_transaction', $order->id_transaction)->update([
                            'reject_type'   => 'point',
                        ]);
                        $refund = app($this->balance)->addLogBalance($order['id_user'], $point = ($payIpay['amount'] / 100), $order['id_transaction'], 'Rejected Order', $order['transaction_grandtotal']);
                        if ($refund == false) {
                            DB::rollback();
                            return response()->json([
                                'status'   => 'fail',
                                'messages' => ['Insert Cashback Failed'],
                            ]);
                        }
                        $rejectBalance = true;
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
                        $rejectBalance = true;
                    }
                }
            }
            // }
            // delete promo campaign report
            if ($order->id_promo_campaign_promo_code) {
                $update_promo_report = app($this->promo_campaign)->deleteReport($order->id_transaction, $order->id_promo_campaign_promo_code);
            }
            // return voucher
            $update_voucher = app($this->voucher)->returnVoucher($order->id_transaction);

            // return subscription
            $update_subscription = app($this->subscription)->returnSubscription($order->id_transaction);

            //send notif to customer
            $send = app($this->autocrm)->SendAutoCRM('Order Reject', $user['phone'], [
                "outlet_name"      => $outlet['outlet_name'],
                "id_reference"     => $order->transaction_receipt_number . ',' . $order->id_outlet,
                "transaction_date" => $order->transaction_date,
                'id_transaction'   => $order->id_transaction,
                'order_id'         => $order->order_id,
                'receipt_number'   => $order->transaction_receipt_number,
                'reject_reason'    => $post['reason'] ?? ''
            ]);
            if ($send != true) {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Failed Send notification to customer'],
                ]);
            }

            //send notif point refund
            if ($rejectBalance == true) {
                $send = app($this->autocrm)->SendAutoCRM(
                    'Rejected Order Point Refund',
                    $user['phone'],
                    [
                    "outlet_name"      => $outlet['outlet_name'],
                    "transaction_date" => $order['transaction_date'],
                    'id_transaction'   => $order['id_transaction'],
                    'receipt_number'   => $order['transaction_receipt_number'],
                    'received_point'   => (string) $point,
                    'order_id'         => $order->order_id,
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
        }
        DB::commit();

        return MyHelper::checkUpdate($pickup);
    }

    public function listSchedule(Request $request)
    {
        $schedules = $request->user()->outlet_schedules()->get();
        $timezone = $request->user()->time_zone_utc;
        $city = City::where('id_city', $request->user()->id_city)->with('province')->first();
        //get timezone from province
        if (isset($city['province']['time_zone_utc'])) {
            $timezone = $city['province']['time_zone_utc'];
        }
        foreach ($schedules as $key => $value) {
            $schedules[$key] = app($this->outlet)->getTimezone($value, $timezone);
        }
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
            $timezone = $request->user()->time_zone_utc;
            $city = City::where('id_city', $request->user()->id_city)->with('province')->first();
            //get timezone from province
            if (isset($city['province']['time_zone_utc'])) {
                $timezone = $city['province']['time_zone_utc'];
            }
            $value = $this->setTimezone($value, $timezone);

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
                    'id_user'            => $user_outlet['id_user'],
                    'id_outlet_app_otp'  => $otp->id_outlet_app_otp,
                    'user_type'          => $user_outlet['user_type'],
                    'user_name'          => $user_outlet['name'] ?? '',
                    'user_email'         => $user_outlet['email'] ?? '',
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
        $order_id       = $request->json('order_id');
        $receipt_number = $request->json('receipt_number');
        $search_receipt_number = $request->json('search_receipt_number');
        $search_order_id       = $request->json('search_order_id');
        $min_price      = $request->json('min_price');
        $max_price      = $request->json('max_price');
        $perpage        = $request->json('perpage');
        $request_number = $request->json('request_number') ?: 'thousand_id';
        $data           = Transaction::select(\DB::raw('transactions.id_transaction,order_id,DATE_FORMAT(transaction_date, "%Y-%m-%d") as trx_date,DATE_FORMAT(transaction_date, "%H:%i") as trx_time,transaction_receipt_number,SUM(transaction_product_qty) as total_products,transaction_grandtotal'))
            ->where('transactions.id_outlet', $request->user()->id_outlet)
            ->where('trasaction_type', 'Pickup Order')
            ->join('transaction_pickups', 'transactions.id_transaction', '=', 'transaction_pickups.id_transaction')
            ->join('transaction_products', 'transaction_products.id_transaction', '=', 'transactions.id_transaction')
            ->groupBy('transaction_products.id_transaction');

        if ($trx_date) {
            $data->whereDate('transaction_date', $trx_date);
        }

        if ($order_id) {
            $data->where('transaction_pickups.order_id', $order_id);
        }

        if ($search_order_id) {
            $data->where('order_id', 'like', "%$search_order_id%");
        }

        if ($receipt_number) {
            $data->where('transactions.transaction_receipt_number', $receipt_number);
        }

        if ($search_receipt_number) {
            $data->where('transactions.transaction_receipt_number', 'like', "%$search_receipt_number%");
        }

        if ($min_price) {
            $data->where('transactions.transaction_grandtotal', '>=', $min_price);
        }

        if ($max_price) {
            $data->where('transactions.transaction_grandtotal', '<=', $max_price);
        }

        if ($trx_status == 'taken') {
            $data->where('transaction_payment_status', 'Completed')
                ->whereNull('reject_at')
                ->where(function ($query) {
                    $query->whereNotNull('taken_at')
                        ->orWhereNotNull('taken_by_system_at');
                });
        } elseif ($trx_status == 'rejected') {
            $data->leftJoin('transaction_pickup_go_sends', 'transaction_pickup_go_sends.id_transaction_pickup', 'transaction_pickups.id_transaction_pickup')
                ->leftJoin('transaction_pickup_wehelpyous', 'transaction_pickup_wehelpyous.id_transaction_pickup', 'transaction_pickups.id_transaction_pickup')
                ->where('transaction_payment_status', 'Completed')
                ->where(function ($query) {
                    $query->whereNotNull('reject_at')
                        ->orWhere('transaction_pickup_wehelpyous.latest_status_id', 96)
                        ->orWhere('transaction_pickup_go_sends.latest_status', 'rejected');
                });
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

        switch ($request->sort ?: $request->sort_by) {
            case 'oldest':
                $data->orderBy('transaction_date', 'ASC')->orderBy('transactions.id_transaction', 'ASC');
                break;

            case 'newest':
                $data->orderBy('transaction_date', 'DESC')->orderBy('transactions.id_transaction', 'DESC');
                break;

            case 'price_asc':
                $data->orderBy('transaction_grandtotal', 'ASC')->orderBy('transactions.id_transaction', 'DESC');
                break;

            case 'price_desc':
                $data->orderBy('transaction_grandtotal', 'DESC')->orderBy('transactions.id_transaction', 'DESC');
                break;

            case 'shortest_pickup_time':
                $data->orderBy('pickup_at', 'ASC')->orderBy('transactions.id_transaction', 'ASC');
                break;

            case 'longest_pickup_time':
                $data->orderBy('pickup_at', 'DESC')->orderBy('transactions.id_transaction', 'DESC');
                break;

            case 'shortest_delivery_time':
                $data->orderBy('pickup_at', 'ASC')->orderBy('transactions.id_transaction', 'ASC');
                break;

            case 'longest_delivery_time':
                $data->orderBy('pickup_at', 'DESC')->orderBy('transactions.id_transaction', 'DESC');
                break;

            default:
                $data->orderBy('pickup_at', 'ASC')
                ->orderBy('transaction_date', 'ASC')
                ->orderBy('transactions.id_transaction', 'ASC');
                break;
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
        $data   = ProductStockStatusUpdate::distinct()->select(\DB::raw('id_product_stock_status_update,brand_product.id_brand,CONCAT(COALESCE(user_type,""),",",COALESCE(id_user,""),",",COALESCE(user_name,"")) as user,DATE_FORMAT(date_time, "%H:%i") as time,product_name,new_status as old_status,new_status,new_status as to_available'))
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
                'name'    => $name ?: $outlet['outlet_name'],
                'time'    => $time,
                'summary' => array_map(function ($vrb) {
                    return [
                        'product_name' => $vrb['product_name'],
                        'old_status'   => $vrb['old_status'],
                        'new_status'   => $vrb['new_status'],
                        'to_available' => $vrb['to_available'],
                    ];
                }, $var),
            ];
        }

        $modifier_raws = ProductModifierStockStatusUpdate::distinct()->select(\DB::raw('id_product_modifier_stock_status_update,CONCAT(COALESCE(user_type,""),",",COALESCE(id_user,""),",",COALESCE(user_name,"")) as user,DATE_FORMAT(date_time, "%H:%i") as time, CASE WHEN id_product_modifier_group IS NOT NULL THEN text_detail_trx ELSE text END as product_name,new_status as old_status,new_status,new_status as to_available'))
            ->join('product_modifiers', 'product_modifiers.id_product_modifier', '=', 'product_modifier_stock_status_updates.id_product_modifier')
            ->where('id_outlet', $outlet->id_outlet)
            ->whereDate('date_time', $date)
            ->orderBy('date_time', 'desc')
            ->get();
        $grouped = [];
        foreach ($modifier_raws as $value) {
            $grouped[$value->user . '#' . $value->time][] = $value;
        }

        if ($grouped) {
            $result['0']['name_brand'] = 'Topping';
        }

        foreach ($grouped as $key => $var) {
            [$name, $time] = explode('#', $key);
            $result['0']['updates'][] = [
                'name'    => $name ?: $outlet['outlet_name'],
                'time'    => $time,
                'summary' => array_map(function ($vrb) {
                    return [
                        'product_name' => $vrb['product_name'],
                        'old_status'   => $vrb['old_status'],
                        'new_status'   => $vrb['new_status'],
                        'to_available' => $vrb['to_available'],
                    ];
                }, $var),
            ];
        }

        return MyHelper::checkGet(array_values($result));
    }

    public function requestOTP(Request $request)
    {
        $af = MyHelper::setting('outlet_apps_access_feature', 'value', 'otp');
        if ($af == 'seeds') {
            return MyHelper::checkGet(['status' => 'active','method' => 'auth-email']);
        } elseif ($af == 'otp') {
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
                ], null, false, true);
                if (!$status && ($create && $send)) {
                    $status = true;
                }
            }
            if (!$status) {
                return MyHelper::checkGet([], 'Failed send PIN');
            }
            return MyHelper::checkGet(['status' => 'active','method' => 'OTP']);
        } else {
            return MyHelper::checkGet(['status' => 'inactive']);
        }
    }

    public function bookDelivery(Request $request)
    {
        $post = $request->json()->all();
        $trx  = Transaction::where(['transaction_payment_status' => 'Completed'])->find($request->id_transaction);
        if (!$trx) {
            return MyHelper::checkGet($trx, 'Transaction Not Found');
        }

        $request->type = $trx->shipment_method ?? $request->type;
        switch (strtolower($request->type)) {
            case 'go-send':
            case 'gosend':
                $result = $this->bookGoSend($trx);
                break;

            case 'wehelpyou':
                $result = $this->bookWehelpyou($trx);
                break;

            default:
                $result = ['status' => 'fail', 'messages' => ['Invalid booking type']];
                break;
        }
        return response()->json($result);
    }

    public function bookWehelpyou($trx, $isRetry = false)
    {
        $createOrder = WeHelpYou::bookingDelivery($trx, $isRetry);
        if ($createOrder['status'] == 'fail') {
            return $createOrder;
        }

        return WeHelpYou::updateStatus($trx, $createOrder['result']['poNo']);
    }

    public function bookGoSend($trx, $fromRetry = false)
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

        //update id from go-send
        $updateGoSend = TransactionPickupGoSend::find($trx['transaction_pickup']['transaction_pickup_go_send']['id_transaction_pickup_go_send']);
        if ($fromRetry) {
            $time_limit = 600; // 10 minutes

            if ($updateGoSend->manual_order_no) {
                $firstbook = TransactionPickupGoSendUpdate::select('created_at')->where('go_send_order_no', $updateGoSend->manual_order_no)->orderBy('id_transaction_pickup_go_send_update')->pluck('created_at')->first();
            } else {
                $firstbook = TransactionPickupGoSendUpdate::select('created_at')->where('id_transaction_pickup_go_send', $updateGoSend->id_transaction_pickup_go_send)->orderBy('id_transaction_pickup_go_send_update')->pluck('created_at')->first();
            }

            if ((time() - strtotime($firstbook)) > $time_limit) {
                if (!$updateGoSend->stop_booking_at) {
                    $updateGoSend->update(['stop_booking_at' => date('Y-m-d H:i:s')]);

                    $text_start = 'Driver tidak ditemukan. ';
                    switch ($trx['transaction_pickup']['transaction_pickup_go_send']['latest_status']) {
                        case 'no_driver':
                            $text_start = 'Driver tidak ditemukan.';
                            break;

                        case 'rejected':
                            $text_start = $trx['transaction_pickup']['order_id'] . ' Driver batal mengantar Pesanan.';
                            break;

                        case 'cancelled':
                            $text_start = $trx['transaction_pickup']['order_id'] . ' Driver batal mengambil Pesanan.';
                            break;
                    }
                    // kirim notifikasi
                    $dataNotif = [
                        'subject' => 'Order ' . $trx['transaction_pickup']['order_id'],
                        'string_body' => "$text_start Segera pilih tindakan atau pesanan batal otomatis.",
                        'type' => 'trx',
                        'id_reference' => $trx['id_transaction'],
                        'id_transaction' => $trx['id_transaction']
                    ];
                    $this->outletNotif($dataNotif, $trx->id_outlet);
                }

                return ['status'  => 'fail', 'messages' => ['Retry reach limit']];
            }
        }

        $packageDetail = Setting::where('key', 'go_send_package_detail')->first();

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
            $updateGoSend->retry_count       = $fromRetry ? ($updateGoSend->retry_count + 1) : 0;
            $updateGoSend->manual_order_no   = $fromRetry ? $updateGoSend->manual_order_no : $booking['orderNo'];
            $updateGoSend->stop_booking_at   = null;
            $updateGoSend->save();

            if (!$updateGoSend) {
                return ['status' => 'fail', 'messages' => ['failed update Transaction GO-SEND']];
            }
        }
        return ['status' => 'success'];
    }

    public function refreshDeliveryStatus(Request $request)
    {
        $trx = Transaction::where('transactions.id_transaction', $request->id_transaction)
                ->join('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                ->with(['outlet' => function ($q) {
                    $q->select('id_outlet', 'outlet_name');
                }])
                ->where('pickup_by', '!=', 'Customer')
                ->first();

        if (!$trx) {
            return [
                'status' => 'fail',
                'messages' => [
                    'Transaction not found'
                ]
            ];
        }

        $outlet = $trx->outlet;
        $request->type = $trx->shipment_method ?? $request->type;
        if (!$trx) {
            return MyHelper::checkGet($trx, 'Transaction Not Found');
        }
        switch (strtolower($request->type)) {
            case 'go-send':
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
                $status['status'] = $ref_status[$status['status']] ?? $status['status'];
                if ($status['receiver_name'] ?? '') {
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

                            $newTrx    = Transaction::with('user.memberships', 'outlet', 'productTransaction', 'transaction_vouchers', 'promo_campaign_promo_code', 'promo_campaign_promo_code.promo_campaign')->where('id_transaction', $trx->id_transaction)->first();
                            $checkType = TransactionMultiplePayment::where('id_transaction', $trx->id_transaction)->get()->toArray();
                            $column    = array_column($checkType, 'type');

                            $use_referral = optional(optional($newTrx->promo_campaign_promo_code)->promo_campaign)->promo_type == 'Referral';
                            \App\Jobs\UpdateQuestProgressJob::dispatch($trx->id_transaction)->onConnection('quest');
                            \Modules\OutletApp\Jobs\AchievementCheck::dispatch(['id_transaction' => $trx->id_transaction, 'phone' => $user['phone']])->onConnection('achievement');

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
                        }
                        $arrived_at = date('Y-m-d H:i:s', ($status['orderClosedTime'] ?? false) ? strtotime($status['orderClosedTime']) : time());
                        TransactionPickup::where('id_transaction', $trx->id_transaction)->update(['arrived_at' => $arrived_at]);
                        $dataSave = [
                            'id_transaction'                => $trx['id_transaction'],
                            'id_transaction_pickup_go_send' => $trxGoSend['id_transaction_pickup_go_send'],
                            'status'                        => $status['status'] ?? 'on_going',
                            'go_send_order_no'              => $status['orderNo'] ?? ''
                        ];
                        GoSend::saveUpdate($dataSave);
                    } elseif (in_array(strtolower($status['status']), ['cancelled', 'no_driver'])) {
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
                            'go_send_order_no'              => $status['orderNo'] ?? '',
                            'description'                   => $status['cancelDescription'] ?? null
                        ];
                        GoSend::saveUpdate($dataSave);
                        $this->bookGoSend($trx, true);
                    } elseif (in_array(strtolower($status['status']), ['rejected'])) {
                        $trxGoSend->update(['stop_booking_at' => date('Y-m-d H:i:s')]);
                        $dataSave = [
                            'id_transaction'                => $trx['id_transaction'],
                            'id_transaction_pickup_go_send' => $trxGoSend['id_transaction_pickup_go_send'],
                            'status'                        => $status['status'] ?? 'on_going',
                            'go_send_order_no'              => $status['orderNo'] ?? '',
                            'description'                   => $status['cancelDescription'] ?? null
                        ];
                        GoSend::saveUpdate($dataSave);
                    } else {
                        if (in_array(strtolower($status['status']), ['out_for_delivery'])) {
                            $checkTrxPickup = TransactionPickup::where('id_transaction', $trx->id_transaction)->first();
                            if ($checkTrxPickup) {
                                $checkTrxPickup->update([
                                    'ready_at' => $checkTrxPickup->ready_at ?? date('Y-m-d H:i:s'),
                                    'taken_at' => $checkTrxPickup->taken_at ?? date('Y-m-d H:i:s')
                                ]);
                            }
                        }

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

            case 'wehelpyou':
                $trx->load('transaction_pickup.transaction_pickup_wehelpyou');
                return WeHelpYou::updateStatus($trx, $trx['transaction_pickup']['transaction_pickup_wehelpyou']['poNo']);
                break;

            default:
                return ['status' => 'fail', 'messages' => ['Invalid delivery type']];
                break;
        }
    }

    public function cancelDelivery(Request $request)
    {
        $trx = Transaction::where('transactions.id_transaction', $request->id_transaction)
                ->join('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                ->where('pickup_by', '!=', 'Customer')
                ->first();

        if (!$trx) {
            return MyHelper::checkGet($trx, 'Transaction Not Found');
        }

        switch ($trx->pickup_by) {
            case 'Wehelpyou':
                $trx->load('transaction_pickup_wehelpyou');
                $poNo = $trx->transaction_pickup_wehelpyou->poNo;
                if (!$poNo) {
                    return [
                        'status'   => 'fail',
                        'messages' => ['PO number not found'],
                    ];
                }
                $cancel = WeHelpYou::cancelOrder($poNo);
                if (($cancel['status_code'] ?? false) != '200') {
                    return [
                        'status'   => 'fail',
                        'messages' => ['Cancel order failed']
                    ];
                }

                if (($cancel['status_code'] ?? false) == '200') {
                    $trx->transaction_pickup_wehelpyou->latest_status = 'Cancelled';
                    $trx->transaction_pickup_wehelpyou->cancel_reason = $request->reason;
                    $trx->transaction_pickup_wehelpyou->save();
                    WeHelpYou::updateStatus($trx, $poNo);
                    return ['status' => 'success'];
                } else {
                    return [
                        'status'   => 'fail',
                        'messages' => ['Cancel order failed']
                    ];
                }
                break;

            default:
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
                break;
        }
    }

    public function transactionDetail(TransactionDetail $request)
    {
        $id = $request->json('id_transaction');

        $list = Transaction::where([['transactions.id_transaction', $id]])->leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')->with([
            // 'user.city.province',
            'user',
            'productTransaction.product.product_category',
            'productTransaction.modifiers' => function ($query) {
                $query->orderByRaw('CASE WHEN id_product_modifier_group IS NULL THEN 1 ELSE 0 END');
            },
            'productTransaction.variants' => function ($query) {
                $query->select('id_transaction_product', 'transaction_product_variants.id_product_variant', 'transaction_product_variants.id_product_variant', 'product_variants.product_variant_name', 'transaction_product_variant_price')->join('product_variants', 'product_variants.id_product_variant', '=', 'transaction_product_variants.id_product_variant');
            },
            'productTransaction.product.product_photos',
            'productTransaction.product.product_discounts',
            'transaction_payment_offlines',
            'transaction_vouchers.deals_voucher.deal',
            'promo_campaign_promo_code.promo_campaign',
            'transaction_payment_subscription.subscription_user_voucher.subscription_user.subscription',
            'transaction_pickup_go_send',
            'transaction_pickup_wehelpyou.transaction_pickup_wehelpyou_updates',
            'outlet.city'])
            ->where('transactions.id_outlet', $request->user()->id_outlet)
            ->first();
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
                                $log = LogBalance::where('id_reference', $mp['id_transaction'])->where('source', 'Online Transaction')->first();
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
                                $payment['name']      = strtoupper(str_replace('_', ' ', $payMidtrans->payment_type)) . ' ' . strtoupper($payMidtrans->bank);
                                $payment['amount']    = $payMidtrans->gross_amount;
                                $list['payment'][] = $payment;
                                break;
                            case 'Ovo':
                                $payment = TransactionPaymentOvo::find($mp['id_payment']);
                                $payment['name']    = 'OVO';
                                $list['payment'][] = $payment;
                                break;
                            case 'IPay88':
                                $PayIpay = TransactionPaymentIpay88::find($mp['id_payment']);
                                $payment['name']    = $PayIpay->payment_method;
                                $payment['amount']    = $PayIpay->amount / 100;
                                $list['payment'][] = $payment;
                                break;
                            case 'Shopeepay':
                                $shopeePay = TransactionPaymentShopeePay::find($mp['id_payment']);
                                $payment['name']    = 'ShopeePay';
                                $payment['amount']  = $shopeePay->amount / 100;
                                $list['payment'][]  = $payment;
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
                foreach ($multiPayment as $dataKey => $dataPay) {
                    if ($dataPay['type'] == 'Midtrans') {
                        $payMidtrans = TransactionPaymentMidtran::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']      = strtoupper(str_replace('_', ' ', $payMidtrans->payment_type)) . ' ' . strtoupper($payMidtrans->bank);
                        $payment[$dataKey]['amount']    = $payMidtrans->gross_amount;
                    } else {
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
                foreach ($multiPayment as $dataKey => $dataPay) {
                    if ($dataPay['type'] == 'Ovo') {
                        $payment[$dataKey] = TransactionPaymentOvo::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']    = 'OVO';
                    } else {
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
                foreach ($multiPayment as $dataKey => $dataPay) {
                    if ($dataPay['type'] == 'IPay88') {
                        $PayIpay = TransactionPaymentIpay88::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']    = $PayIpay->payment_method;
                        $payment[$dataKey]['amount']    = $PayIpay->amount / 100;
                    } else {
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey] = $dataPay;
                        $list['balance'] = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']          = 'Balance';
                        $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                    }
                }
                $list['payment'] = $payment;
                break;
            case 'Shopeepay':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get();
                $payment = [];
                foreach ($multiPayment as $dataKey => $dataPay) {
                    if ($dataPay['type'] == 'Shopeepay') {
                        $payShopee = TransactionPaymentShopeePay::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']      = 'ShopeePay';
                        $payment[$dataKey]['amount']    = $payShopee->amount / 100;
                    } else {
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey]              = $dataPay;
                        $list['balance']                = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']      = 'Balance';
                        $payment[$dataKey]['amount']    = $dataPay['balance_nominal'];
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
                $list['payment'] = [];
                break;
        }

        if (!empty($list['transaction_payment_subscription'])) {
            $payment_subscription = abs($list['transaction_payment_subscription']['subscription_nominal']);
            $result['promo_name'] = $list['transaction_payment_subscription']['subscription_user_voucher']['subscription_user']['subscription']['subscription_title'];
            $list['payment'][] = [
                'name'      => 'Subscription',
                'amount'    => $payment_subscription
            ];
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
                if ($detail['pickup_by'] == 'GO-SEND' || $detail['pickup_by'] == 'Wehelpyou') {
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

        $currentdate = date('Y-m-d H:i');
        $setTime = date('Y-m-d H:i', strtotime($list['pickup_at'] . ' - 15 minutes'));
        $allowReady = 1;
        if ($list['pickup_type'] == 'set time' && $currentdate < $setTime) {
            $allowReady = 0;
        }

        $trxType = $list['trasaction_type'];
        if (isset($list['pickup_by']) && ($list['pickup_by'] == 'GO-SEND' || $list['pickup_by'] == 'Wehelpyou')) {
            $trxType = 'Delivery';
        }

        $result = [
            'id_transaction'              => $list['id_transaction'],
            'user_name'                   => $list['user']['name'],
            'user_phone'                  => $list['user']['phone'],
            'transaction_receipt_number'  => $list['transaction_receipt_number'],
            'transaction_date'            => date('d M Y H:i', strtotime($list['transaction_date'])),
            'trasaction_type'             => $trxType,
            'transaction_grandtotal'      => MyHelper::requestNumber($list['transaction_grandtotal'], '_CURRENCY'),
            'transaction_subtotal'        => MyHelper::requestNumber($list['transaction_subtotal'], '_CURRENCY'),
            'transaction_discount'        => MyHelper::requestNumber($list['transaction_discount'], '_CURRENCY'),
            'transaction_cashback_earned' => MyHelper::requestNumber($list['transaction_cashback_earned'], '_POINT'),
            'trasaction_payment_type'     => $list['trasaction_payment_type'],
            'transaction_payment_status'  => $list['transaction_payment_status'],
            'rejectable'                  => 0,
            'allow_ready'                 => $allowReady,
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

            if (empty($list['detail']['pickup_at'])) {
                $proc_time = Setting::where('key', 'processing_time')->first();
                $pickup_at = date('H:i', strtotime('+' . $proc_time->value . ' minutes', strtotime($list['transaction_date'])));
            }
            $result['detail']['pickup_at'] = !empty($list['detail']['pickup_at']) ? date('H:i', strtotime($list['detail']['pickup_at'])) : $pickup_at;

            if (isset($list['transaction_payment_status']) && $list['transaction_payment_status'] == 'Cancelled') {
                $result['transaction_status']      = 0;
                $result['transaction_status_text'] = 'ORDER ANDA DIBATALKAN';
            } elseif (isset($list['transaction_payment_status']) && $list['transaction_payment_status'] == 'Pending') {
                $result['transaction_status']      = 6;
                $result['transaction_status_text'] = 'MENUNGGU PEMBAYARAN';
            } elseif ($list['detail']['reject_at'] != null) {
                $reason = $list['detail']['reject_reason'];
                $ditolak = 'ORDER DITOLAK';
                if (strpos($reason, 'auto reject order') !== false) {
                    $ditolak = 'ORDER DITOLAK OTOMATIS';
                    if (strpos($reason, 'no driver') !== false) {
                        $reason = 'GAGAL MENEMUKAN DRIVER';
                    } elseif (strpos($reason, 'not ready') !== false) {
                        $reason = 'STATUS ORDER TIDAK DIPROSES READY';
                    } else {
                        $reason = 'OUTLET GAGAL MENERIMA ORDER';
                    }
                }
                if ($reason) {
                    $reason = "\n$reason";
                }
                $result['transaction_status']      = 0;
                $result['transaction_status_text'] = "$ditolak$reason";
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

            if ($list['transaction_pickup_go_send'] && !$list['detail']['reject_at']) {
                // $result['transaction_status'] = 5;
                $result['delivery_info'] = [
                    'driver'            => null,
                    'delivery_status'   => '',
                    'delivery_address'  => $list['transaction_pickup_go_send']['destination_address'] ?: '',
                    'delivery_address_note' => $list['transaction_pickup_go_send']['destination_note'] ?: '',
                    'booking_status'    => 0,
                    'cancelable'        => 1,
                    'go_send_order_no'  => $list['transaction_pickup_go_send']['go_send_order_no'] ?: '',
                    'live_tracking_url' => $list['transaction_pickup_go_send']['live_tracking_url'] ?: '',
                ];
                if ($list['transaction_pickup_go_send']['go_send_id']) {
                    $result['delivery_info']['booking_status'] = 1;
                }
                switch (strtolower($list['transaction_pickup_go_send']['latest_status'])) {
                    case 'finding driver':
                    case 'confirmed':
                        $result['delivery_info']['delivery_status'] = 'Sedang mencari driver';
                        $result['delivery_info']['delivery_status_code']   = 1;
                        $result['transaction_status_text']          = 'SEDANG MENCARI DRIVER';
                        $result['rejectable']                       = 0;
                        break;
                    case 'driver allocated':
                    case 'allocated':
                        $result['delivery_info']['delivery_status'] = 'Driver ditemukan';
                        $result['delivery_info']['delivery_status_code']   = 2;
                        $result['transaction_status_text']          = 'DRIVER DITEMUKAN';
                        $result['delivery_info']['driver']          = [
                            'driver_id'      => $list['transaction_pickup_go_send']['driver_id'] ?: '',
                            'driver_name'    => $list['transaction_pickup_go_send']['driver_name'] ?: '',
                            'driver_phone'   => $list['transaction_pickup_go_send']['driver_phone'] ?: 'Data tidak tersedia dari jasa pengiriman',
                            'driver_photo'   => $list['transaction_pickup_go_send']['driver_photo'] ?: $this->default_driver_photo,
                            'vehicle_number' => $list['transaction_pickup_go_send']['vehicle_number'] ?: 'Data tidak tersedia dari jasa pengiriman',
                        ];
                        $result['rejectable']                       = 0;
                        break;
                    case 'enroute pickup':
                    case 'out_for_pickup':
                        $result['delivery_info']['delivery_status'] = 'Driver dalam perjalanan menuju Outlet';
                        $result['delivery_info']['delivery_status_code']   = 2;
                        $result['transaction_status_text']          = 'DRIVER SEDANG MENUJU OUTLET';
                        $result['delivery_info']['driver']          = [
                            'driver_id'      => $list['transaction_pickup_go_send']['driver_id'] ?: '',
                            'driver_name'    => $list['transaction_pickup_go_send']['driver_name'] ?: '',
                            'driver_phone'   => $list['transaction_pickup_go_send']['driver_phone'] ?: 'Data tidak tersedia dari jasa pengiriman',
                            'driver_photo'   => $list['transaction_pickup_go_send']['driver_photo'] ?: $this->default_driver_photo,
                            'vehicle_number' => $list['transaction_pickup_go_send']['vehicle_number'] ?: 'Data tidak tersedia dari jasa pengiriman',
                        ];
                        $result['delivery_info']['cancelable'] = 1;
                        $result['rejectable']                  = 0;
                        break;
                    case 'picked':
                        $result['delivery_info']['delivery_status'] = 'Driver mengambil pesanan di Outlet';
                        $result['delivery_info']['delivery_status_code']   = 2;
                        $result['transaction_status_text']          = 'DRIVER MENGAMBIL PESANAN DI OUTLET';
                        $result['delivery_info']['driver']          = [
                            'driver_id'      => $list['transaction_pickup_go_send']['driver_id'] ?: '',
                            'driver_name'    => $list['transaction_pickup_go_send']['driver_name'] ?: '',
                            'driver_phone'   => $list['transaction_pickup_go_send']['driver_phone'] ?: 'Data tidak tersedia dari jasa pengiriman',
                            'driver_photo'   => $list['transaction_pickup_go_send']['driver_photo'] ?: $this->default_driver_photo,
                            'vehicle_number' => $list['transaction_pickup_go_send']['vehicle_number'] ?: 'Data tidak tersedia dari jasa pengiriman',
                        ];
                        $result['delivery_info']['cancelable'] = 0;
                        $result['rejectable']                  = 0;
                        break;
                    case 'enroute drop':
                    case 'out_for_delivery':
                        $result['delivery_info']['delivery_status'] = 'Driver mengantarkan pesanan';
                        $result['delivery_info']['delivery_status_code']   = 3;
                        if ($list['detail']['ready_at'] != null) {
                            $result['transaction_status_text']          = 'PROSES PENGANTARAN';
                            $result['transaction_status']               = 3;
                        }
                        $result['delivery_info']['driver']          = [
                            'driver_id'      => $list['transaction_pickup_go_send']['driver_id'] ?: '',
                            'driver_name'    => $list['transaction_pickup_go_send']['driver_name'] ?: '',
                            'driver_phone'   => $list['transaction_pickup_go_send']['driver_phone'] ?: 'Data tidak tersedia dari jasa pengiriman',
                            'driver_photo'   => $list['transaction_pickup_go_send']['driver_photo'] ?: $this->default_driver_photo,
                            'vehicle_number' => $list['transaction_pickup_go_send']['vehicle_number'] ?: 'Data tidak tersedia dari jasa pengiriman',
                        ];
                        $result['delivery_info']['cancelable'] = 0;
                        $result['rejectable']                  = 0;
                        break;
                    case 'completed':
                    case 'delivered':
                        if ($list['detail']['ready_at'] == null) {
                            $result['transaction_status'] = 4;
                            $result['transaction_status_text'] = 'ORDER SEDANG DIPROSES';
                        } elseif ($list['detail']['taken_at'] == null) {
                            $result['transaction_status'] = 3;
                            $result['transaction_status_text'] = 'ORDER SUDAH SIAP';
                        } else {
                            $result['transaction_status_text'] = 'ORDER SUDAH DIAMBIL';
                            $result['transaction_status'] = 2;
                        }
                        $result['delivery_info']['delivery_status'] = 'Pesanan sudah diterima Customer';
                        $result['delivery_info']['delivery_status_code']   = 4;
                        $result['delivery_info']['driver']          = [
                            'driver_id'      => $list['transaction_pickup_go_send']['driver_id'] ?: '',
                            'driver_name'    => $list['transaction_pickup_go_send']['driver_name'] ?: '',
                            'driver_phone'   => $list['transaction_pickup_go_send']['driver_phone'] ?: 'Data tidak tersedia dari jasa pengiriman',
                            'driver_photo'   => $list['transaction_pickup_go_send']['driver_photo'] ?: $this->default_driver_photo,
                            'vehicle_number' => $list['transaction_pickup_go_send']['vehicle_number'] ?: 'Data tidak tersedia dari jasa pengiriman',
                        ];
                        $result['delivery_info']['cancelable'] = 0;
                        break;
                    case 'cancelled':
                        $result['delivery_info']['delivery_status_code']   = 0;
                        $result['delivery_info']['booking_status'] = 0;
                        $result['transaction_status_text']         = 'PENGANTARAN DIBATALKAN';
                        $result['delivery_info']['delivery_status'] = 'Pengantaran dibatalkan';
                        $result['delivery_info']['cancelable']     = 0;
                        $result['rejectable']              = ($list['transaction_pickup_go_send']['stop_booking_at']) ? 1 : 0;
                        break;
                    case 'driver not found':
                    case 'no_driver':
                        $result['delivery_info']['delivery_status_code']   = 0;
                        $result['delivery_info']['booking_status']  = 0;
                        $result['transaction_status_text']          = 'DRIVER TIDAK DITEMUKAN';
                        $result['delivery_info']['delivery_status'] = 'Driver tidak ditemukan';
                        $result['delivery_info']['cancelable']      = 0;
                        $result['rejectable']              = ($list['transaction_pickup_go_send']['stop_booking_at']) ? 1 : 0;
                        break;
                }
            } elseif ($list['transaction_pickup_wehelpyou'] && !$list['detail']['reject_at']) {
                // $result['transaction_status'] = 5;
                $result['delivery_info'] = [
                    'driver' => null,
                    'delivery_status' => '',
                    'delivery_address' => $list['transaction_pickup_wehelpyou']['receiver_address'] ?: '',
                    'delivery_address_note' => $list['transaction_pickup_wehelpyou']['receiver_notes'] ?: '',
                    'booking_status' => 0,
                    'cancelable' => 1,
                    'go_send_order_no' => $list['transaction_pickup_wehelpyou']['poNo'] ?: '',
                    'live_tracking_url' => $list['transaction_pickup_wehelpyou']['tracking_live_tracking_url'] ?: ''
                ];
                if ($list['transaction_pickup_wehelpyou']['poNo']) {
                    $result['delivery_info']['booking_status'] = 1;
                }
                switch (strtolower($list['transaction_pickup_wehelpyou']['latest_status'])) {
                    case 'on progress':
                    case 'finding driver':
                        $result['delivery_info']['delivery_status'] = 'Sedang mencari driver';
                        $result['delivery_info']['delivery_status_code']   = 1;
                        $result['transaction_status_text']          = 'SEDANG MENCARI DRIVER';
                        break;
                    case 'driver allocated':
                        $result['delivery_info']['delivery_status'] = 'Driver ditemukan';
                        $result['delivery_info']['delivery_status_code']   = 2;
                        $result['transaction_status_text']          = 'DRIVER DITEMUKAN DAN SEDANG MENUJU OUTLET';
                        $result['delivery_info']['driver']          = [
                            'driver_id'         => 'Data tidak tersedia dari jasa pengiriman',
                            'driver_name'       => $list['transaction_pickup_wehelpyou']['tracking_driver_name'] ?: '',
                            'driver_phone'      => $list['transaction_pickup_wehelpyou']['tracking_driver_phone'] ?: 'Data tidak tersedia dari jasa pengiriman',
                            'driver_whatsapp'   => env('URL_WA') . $list['transaction_pickup_wehelpyou']['tracking_driver_phone'] ?: '',
                            'driver_photo'      => $list['transaction_pickup_wehelpyou']['tracking_photo'] ?: $this->default_driver_photo,
                            'vehicle_number'    => $list['transaction_pickup_wehelpyou']['tracking_vehicle_number'] ?: 'Data tidak tersedia dari jasa pengiriman',
                        ];
                        break;
                    case 'item picked':
                        $result['delivery_info']['delivery_status'] = 'Driver mengambil pesanan di Outlet';
                        $result['delivery_info']['delivery_status_code']   = 2;
                        $result['transaction_status_text']          = 'DRIVER MENGAMBIL PESANAN DI OUTLET';
                        $result['delivery_info']['driver']          = [
                            'driver_id'         => 'Data tidak tersedia dari jasa pengiriman',
                            'driver_name'       => $list['transaction_pickup_wehelpyou']['tracking_driver_name'] ?: '',
                            'driver_phone'      => $list['transaction_pickup_wehelpyou']['tracking_driver_phone'] ?: 'Data tidak tersedia dari jasa pengiriman',
                            'driver_whatsapp'   => env('URL_WA') . $list['transaction_pickup_wehelpyou']['tracking_driver_phone'] ?: '',
                            'driver_photo'      => $list['transaction_pickup_wehelpyou']['tracking_photo'] ?: $this->default_driver_photo,
                            'vehicle_number'    => $list['transaction_pickup_wehelpyou']['tracking_vehicle_number'] ?: 'Data tidak tersedia dari jasa pengiriman',
                        ];
                        $result['delivery_info']['cancelable'] = 1;
                        break;
                    case 'enroute drop':
                        $result['delivery_info']['delivery_status'] = 'Driver mengantarkan pesanan';
                        $result['delivery_info']['delivery_status_code']   = 3;
                        $result['transaction_status_text']          = 'PESANAN SUDAH DI PICK UP OLEH DRIVER DAN SEDANG MENUJU LOKASI #TEMANSEJIWA';
                        $result['transaction_status']               = 3;
                        $result['delivery_info']['driver']          = [
                            'driver_id'         => 'Data tidak tersedia dari jasa pengiriman',
                            'driver_name'       => $list['transaction_pickup_wehelpyou']['tracking_driver_name'] ?: '',
                            'driver_phone'      => $list['transaction_pickup_wehelpyou']['tracking_driver_phone'] ?: 'Data tidak tersedia dari jasa pengiriman',
                            'driver_whatsapp'   => env('URL_WA') . $list['transaction_pickup_wehelpyou']['tracking_driver_phone'] ?: '',
                            'driver_photo'      => $list['transaction_pickup_wehelpyou']['tracking_photo'] ?: $this->default_driver_photo,
                            'vehicle_number'    => $list['transaction_pickup_wehelpyou']['tracking_vehicle_number'] ?: 'Data tidak tersedia dari jasa pengiriman',
                        ];
                        $result['delivery_info']['cancelable'] = 0;
                        break;
                    case 'completed':
                        $result['transaction_status'] = 2;
                        $result['transaction_status_text']          = 'PESANAN TELAH SELESAI DAN DITERIMA';
                        $result['delivery_info']['delivery_status'] = 'Pesanan sudah diterima Customer';
                        $result['delivery_info']['delivery_status_code']   = 4;
                        $result['delivery_info']['driver']          = [
                            'driver_id'         => 'Data tidak tersedia dari jasa pengiriman',
                            'driver_name'       => $list['transaction_pickup_wehelpyou']['tracking_driver_name'] ?: '',
                            'driver_phone'      => $list['transaction_pickup_wehelpyou']['tracking_driver_phone'] ?: 'Data tidak tersedia dari jasa pengiriman',
                            'driver_whatsapp'   => env('URL_WA') . $list['transaction_pickup_wehelpyou']['tracking_driver_phone'] ?: '',
                            'driver_photo'      => $list['transaction_pickup_wehelpyou']['tracking_photo'] ?: $this->default_driver_photo,
                            'vehicle_number'    => $list['transaction_pickup_wehelpyou']['tracking_vehicle_number'] ?: 'Data tidak tersedia dari jasa pengiriman',
                        ];
                        $result['delivery_info']['cancelable'] = 0;
                        break;
                    case 'cancelled, without refund':
                    case 'order failed':
                    case 'cancelled by partner':
                        $result['delivery_info']['booking_status'] = 0;
                        $result['delivery_info']['delivery_status_code'] = 0;
                        $result['delivery_info']['delivery_status'] = 'Pengantaran dibatalkan';
                        $result['delivery_info']['cancelable']     = 0;
                        $result['transaction_status_text']         = 'PENGANTARAN PESANAN TELAH DIBATALKAN';
                        break;
                    case 'rejected':
                        $result['transaction_status'] = 0;
                        $result['delivery_info']['booking_status'] = 0;
                        $result['delivery_info']['delivery_status'] = 'Pengantaran dibatalkan';
                        $result['delivery_info']['delivery_status'] = 'Pengantaran dibatalkan';
                        $result['delivery_info']['cancelable']     = 0;
                        $result['transaction_status_text']         = 'PENGANTARAN PESANAN TELAH DIBATALKAN';
                        break;
                    default:
                        break;
                }
                $result['delivery_info_be'] = [
                    'delivery_address' => $list['transaction_pickup_wehelpyou']['receiver_address'] ?: '',
                    'delivery_address_note' => $list['transaction_pickup_wehelpyou']['receiver_notes'] ?: '',
                ];
            }
        }

        //get item bundling
        $itemBundling = [];
        $itemBundlingPerBrand = [];
        $quantityItemBundling = 0;
        $getBundling   = TransactionBundlingProduct::join('bundling', 'bundling.id_bundling', 'transaction_bundling_products.id_bundling')
            ->where('id_transaction', $id)->get()->toArray();
        foreach ($getBundling as $key => $bundling) {
            $getPriceToping =  $bundling['transaction_bundling_product_subtotal'] / $bundling['transaction_bundling_product_qty'];

            $bundlingProduct = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                ->join('brands', 'brands.id_brand', 'transaction_products.id_brand')
                ->orderBy('order_brand', 'desc')
                ->where('id_transaction_bundling_product', $bundling['id_transaction_bundling_product'])->get()->toArray();

            $products = [];
            $productPerBrand = [];
            $basePriceBundling = 0;
            $subTotalBundlingWithoutModifier = 0;
            $subItemBundlingWithoutModifie = 0;
            foreach ($bundlingProduct as $bp) {
                $quantityItemBundling = $quantityItemBundling + ($bp['transaction_product_bundling_qty'] * $bundling['transaction_bundling_product_qty']);
                $mod = TransactionProductModifier::join('product_modifiers', 'product_modifiers.id_product_modifier', 'transaction_product_modifiers.id_product_modifier')
                    ->whereNull('transaction_product_modifiers.id_product_modifier_group')
                    ->where('id_transaction_product', $bp['id_transaction_product'])
                    ->select('transaction_product_modifiers.id_product_modifier', 'transaction_product_modifiers.text as text', DB::raw('FLOOR(transaction_product_modifier_price * ' . $bp['transaction_product_bundling_qty'] . ' * ' . $bundling['transaction_bundling_product_qty'] . ') as product_modifier_price'))->get()->toArray();
                $variantPrice = TransactionProductVariant::join('product_variants', 'product_variants.id_product_variant', 'transaction_product_variants.id_product_variant')
                    ->where('id_transaction_product', $bp['id_transaction_product'])
                    ->select('product_variants.id_product_variant', 'product_variants.product_variant_name', 'transaction_product_variant_price')->get()->toArray();
                $variantNoPrice =  TransactionProductModifier::join('product_modifiers', 'product_modifiers.id_product_modifier', 'transaction_product_modifiers.id_product_modifier')
                    ->whereNotNull('transaction_product_modifiers.id_product_modifier_group')
                    ->where('id_transaction_product', $bp['id_transaction_product'])
                    ->select('transaction_product_modifiers.id_product_modifier as id_product_variant', 'transaction_product_modifiers.text as product_variant_name', 'transaction_product_modifier_price as transaction_product_variant_price')->get()->toArray();

                $products[] = [
                    'product_name' => $bp['product_name'],
                    'product_note' => $bp['transaction_product_note'],
                    'transaction_product_price' => (int)($bp['transaction_product_price'] + $bp['transaction_variant_subtotal']),
                    'transaction_product_qty' => $bp['transaction_product_bundling_qty'],
                    'modifiers' => $mod,
                    'variants' => array_merge($variantPrice, $variantNoPrice)
                ];

                //set product per brand
                $checkBrand = array_search($bp['id_brand'], array_column($productPerBrand, 'id_brand'));
                if ($checkBrand === false) {
                    $productPerBrand[] = [
                        'id_brand' => $bp['id_brand'],
                        'brand_name' => $bp['name_brand'],
                        'brand_code' => $bp['code_brand'],
                        'products' => [[
                            'id_brand' => $bp['id_brand'],
                            'id_product' => $bp['id_product'],
                            'id_product_variant_group' => $bp['id_product_variant_group'],
                            'product_name' => $bp['product_name'],
                            'product_note' => $bp['transaction_product_note'],
                            'transaction_product_price' => (int)($bp['transaction_product_price'] + $bp['transaction_variant_subtotal']),
                            'transaction_product_qty' => $bp['transaction_product_bundling_qty'],
                            'modifiers' => $mod,
                            'variants' => array_merge($variantPrice, $variantNoPrice)
                        ]]
                    ];
                } else {
                    $productPerBrand[$checkBrand]['products'][] = [
                        'id_brand' => $bp['id_brand'],
                        'id_product' => $bp['id_product'],
                        'id_product_variant_group' => $bp['id_product_variant_group'],
                        'product_name' => $bp['product_name'],
                        'product_note' => $bp['transaction_product_note'],
                        'transaction_product_price' => (int)($bp['transaction_product_price'] + $bp['transaction_variant_subtotal']),
                        'transaction_product_qty' => $bp['transaction_product_bundling_qty'],
                        'modifiers' => $mod,
                        'variants' => array_merge($variantPrice, $variantNoPrice)
                    ];
                }

                $basePriceBundling = $basePriceBundling + (($bp['transaction_product_price'] + $bp['transaction_variant_subtotal']) * $bp['transaction_product_bundling_qty']);
                $subTotalBundlingWithoutModifier = $subTotalBundlingWithoutModifier + (($bp['transaction_product_subtotal'] - ($bp['transaction_modifier_subtotal'] * $bp['transaction_product_bundling_qty'])));
                $subItemBundlingWithoutModifie = $subItemBundlingWithoutModifie + ($bp['transaction_product_bundling_price'] * $bp['transaction_product_bundling_qty']);
            }

            $itemBundling[] = [
                'bundling_name' => $bundling['bundling_name'],
                'bundling_qty' => $bundling['transaction_bundling_product_qty'],
                'bundling_price_no_discount' => $basePriceBundling * $bundling['transaction_bundling_product_qty'],
                'bundling_subtotal' => $subTotalBundlingWithoutModifier * $bundling['transaction_bundling_product_qty'],
                'bundling_sub_item' => '@' . MyHelper::requestNumber($subItemBundlingWithoutModifie, '_CURRENCY'),
                'products' => $products
            ];

            //set bundling per brand
            $checkBundlingPerBrand = array_search($bundling['id_bundling'], array_column($itemBundlingPerBrand, 'id_bundling'));
            if ($checkBundlingPerBrand === false) {
                $itemBundlingPerBrand[] = [
                    'id_bundling' => $bundling['id_bundling'],
                    'bundling_name' => $bundling['bundling_name'],
                    'bundling_qty' => $bundling['transaction_bundling_product_qty'],
                    'bundling_subtotal' => (int)$bundling['transaction_bundling_product_subtotal'],
                    'bundling_sub_item' => '@' . MyHelper::requestNumber($getPriceToping, '_CURRENCY'),
                    'brands' => $productPerBrand
                ];
            } else {
                $dtBrands = $itemBundlingPerBrand[$checkBundlingPerBrand]['brands'];
                foreach ($productPerBrand as $pb) {
                    $checkBundlingProductBrand = array_search($pb['id_brand'], array_column($dtBrands, 'id_brand'));
                    if ($checkBundlingProductBrand === false) {
                        $itemBundlingPerBrand[$checkBundlingPerBrand]['brands'][] = $pb;
                    } else {
                        $mergeProduct = array_merge($pb['products'], $dtBrands[$checkBundlingProductBrand]['products']);
                        $productsBrand = [];
                        foreach ($mergeProduct as $value) {
                            $check = array_search($value['id_product'], array_column($productsBrand, 'id_product'));
                            if ($check === false) {
                                $productsBrand[] = [
                                    'id_brand' => $value['id_brand'],
                                    'id_product' => $value['id_product'],
                                    'id_product_variant_group' => $value['id_product_variant_group'],
                                    'product_name' => $value['product_name'],
                                    'product_note' => $value['product_note'],
                                    'transaction_product_price' => (int)$value['transaction_product_price'],
                                    'transaction_product_qty' => $bundling['transaction_bundling_product_qty'],
                                    'modifiers' => $value['modifiers'],
                                    'variants' => $value['variants']
                                ];
                            } else {
                                $checkModifiers = $productsBrand[$check]['modifiers'];
                                $checkNote = $productsBrand[$check]['product_note'];
                                $checkIdProductVariantGroup = $productsBrand[$check]['id_product_variant_group'];

                                $mergeModifiers = array_merge($checkModifiers, $value['modifiers']);
                                $mergeModifiersUnique = array_map("unserialize", array_unique(array_map("serialize", $mergeModifiers)));

                                if (
                                    $checkIdProductVariantGroup == $value['id_product_variant_group'] &&
                                    count($checkModifiers) == count($value['modifiers']) &&
                                    count($checkModifiers) == count($value['modifiers']) &&
                                    count($mergeModifiersUnique) == count($value['modifiers']) && $checkNote == $value['product_note']
                                ) {
                                    $productsBrand[$check]['transaction_product_qty'] = $productsBrand[$check]['transaction_product_qty'] + $value['transaction_product_qty'];
                                } else {
                                    $productsBrand[] = [
                                        'id_brand' => $value['id_brand'],
                                        'id_product' => $value['id_product'],
                                        'id_product_variant_group' => $value['id_product_variant_group'],
                                        'product_name' => $value['product_name'],
                                        'product_note' => $value['product_note'],
                                        'transaction_product_price' => (int)$value['transaction_product_price'],
                                        'transaction_product_qty' => $value['transaction_product_qty'],
                                        'modifiers' => $value['modifiers'],
                                        'variants' => $value['variants']
                                    ];
                                }
                            }
                        }
                        $itemBundlingPerBrand[$checkBundlingPerBrand]['bundling_qty'] = $itemBundlingPerBrand[$checkBundlingPerBrand]['bundling_qty'] + 1;
                        $itemBundlingPerBrand[$checkBundlingPerBrand]['brands'][$checkBundlingProductBrand]['products'] = $productsBrand;
                    }
                }
            }
        }

        $result['product_bundling_transaction_name'] = 'Bundling';
        $result['product_bundling_transaction_detail'] = $itemBundling;
        $result['product_bundling_transaction_perbrand'] = $itemBundlingPerBrand;
        $result['product_transaction'] = [];

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
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_variant_subtotal']  = MyHelper::requestNumber($valueProduct['transaction_variant_subtotal'], '_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_note']      = $valueProduct['transaction_product_note'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_discount']  = $valueProduct['transaction_product_discount'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_name']       = $valueProduct['product']['product_name'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_price']      = MyHelper::requestNumber($valueProduct['transaction_product_price'], '_CURRENCY');
                $discount                                                                                        = $discount + $valueProduct['transaction_product_discount'];
                $variantsPrice = 0;
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'] = [];
                foreach ($valueProduct['variants'] as $keyMod => $valueMod) {
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'][$keyMod]['product_variant_name']   = $valueMod['product_variant_name'];
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'][$keyMod]['product_variant_price']  = (int)$valueMod['transaction_product_variant_price'];
                    $variantsPrice = $variantsPrice + $valueMod['transaction_product_variant_price'];
                }
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'] = [];
                foreach ($valueProduct['modifiers'] as $keyMod => $valueMod) {
                    if (!empty($valueMod['id_product_modifier_group'])) {
                        $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'][] = [
                            'product_variant_name' => $valueMod['text'],
                            'product_variant_price' => 0,
                            'is_modifier' => 1
                        ];
                    } else {
                        $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'][] = [
                            'product_modifier_name' => $valueMod['text'],
                            'product_modifier_qty' => $valueMod['qty'],
                            'product_modifier_price' => (int)($valueMod['transaction_product_modifier_price'] * $valueProduct['transaction_product_qty'])
                        ];
                    }
                }
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_sub_item']      = '@' . MyHelper::requestNumber($valueProduct['transaction_product_price'] + $variantsPrice, '_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product_variant_group_price'] = (int)($valueProduct['transaction_product_price'] + $variantsPrice);
            }
            $keynya++;
        }

        $result['payment_detail'][] = [
            'name'   => 'Subtotal',
            'desc'   => $quantity + $quantityItemBundling . ' items',
            'amount' => MyHelper::requestNumber($list['transaction_subtotal'], '_CURRENCY'),
        ];

        if ($list['transaction_discount']) {
            $discount = abs($list['transaction_discount']);
            $p = 0;
            if (!empty($list['transaction_vouchers'])) {
                foreach ($list['transaction_vouchers'] as $valueVoc) {
                    $result['promo']['code'][$p++]   = $valueVoc['deals_voucher']['voucher_code'];
                    $result['payment_detail'][] = [
                        'name'          => 'Diskon (Promo)',
                        'desc'          => null,
                        "is_discount"   => 1,
                        'amount'        => '- ' . MyHelper::requestNumber($discount, '_CURRENCY')
                    ];
                }
            }

            if (!empty($list['promo_campaign_promo_code'])) {
                $result['promo']['code'][$p++]   = $list['promo_campaign_promo_code']['promo_code'];
                $result['payment_detail'][] = [
                    'name'          => 'Diskon (Promo)',
                    'desc'          => null,
                    "is_discount"   => 1,
                    'amount'        => '- ' . MyHelper::requestNumber($discount, '_CURRENCY')
                ];
            }

            if (!empty($list['id_subscription_user_voucher']) && !empty($list['transaction_discount'])) {
                $result['payment_detail'][] = [
                    'name'          => 'Subscription (Diskon)',
                    'desc'          => null,
                    "is_discount"   => 1,
                    'amount'        => '- ' . MyHelper::requestNumber($discount, '_CURRENCY')
                ];
            }
        }

        if ($list['transaction_shipment_go_send'] > 0) {
            $result['payment_detail'][] = [
                'name'      => 'Delivery',
                'desc'      => $list['detail']['pickup_by'],
                'amount'    => MyHelper::requestNumber($list['transaction_shipment_go_send'], '_CURRENCY')
            ];
        } elseif ($list['transaction_shipment'] > 0) {
            $getListDelivery = json_decode(MyHelper::setting('available_delivery', 'value_text', '[]'), true) ?? [];
            $shipmentCode = strtolower($list['shipment_method'] . '_' . $list['shipment_courier']);
            if ($list['shipment_method'] == 'GO-SEND') {
                $shipmentCode = 'gosend';
            }

            $search = array_search($shipmentCode, array_column($getListDelivery, 'code'));
            $shipmentName = ($search !== false ? $getListDelivery[$search]['delivery_name'] : strtoupper($list['shipment_courier']));
            $result['payment_detail'][] = [
                'name'      => 'Delivery',
                'desc'      => $shipmentName,
                'amount'    => MyHelper::requestNumber($list['transaction_shipment'], '_CURRENCY')
            ];
        }

        if ($list['transaction_discount_delivery']) {
            $discount = abs($list['transaction_discount_delivery']);
            $p = 0;
            if (!empty($list['transaction_vouchers'])) {
                foreach ($list['transaction_vouchers'] as $valueVoc) {
                    $result['promo']['code'][$p++]   = $valueVoc['deals_voucher']['voucher_code'];
                    $result['payment_detail'][] = [
                        'name'          => 'Diskon (Delivery)',
                        'desc'          => null,
                        "is_discount"   => 1,
                        'amount'        => '- ' . MyHelper::requestNumber($discount, '_CURRENCY')
                    ];
                }
            }

            if (!empty($list['promo_campaign_promo_code'])) {
                $result['promo']['code'][$p++]   = $list['promo_campaign_promo_code']['promo_code'];
                $result['payment_detail'][] = [
                    'name'          => 'Diskon (Delivery)',
                    'desc'          => null,
                    "is_discount"   => 1,
                    'amount'        => '- ' . MyHelper::requestNumber($discount, '_CURRENCY')
                ];
            }

            if (!empty($list['id_subscription_user_voucher']) && !empty($list['transaction_discount_delivery'])) {
                $result['payment_detail'][] = [
                    'name'          => 'Subscription (Delivery)',
                    'desc'          => null,
                    "is_discount"   => 1,
                    'amount'        => '- ' . MyHelper::requestNumber($discount, '_CURRENCY')
                ];
            }
        }

        $result['promo']['discount'] = $discount;
        $result['promo']['discount'] = MyHelper::requestNumber($discount, '_CURRENCY');

        if ($list['trasaction_payment_type'] != 'Offline') {
            if ($list['transaction_payment_status'] == 'Cancelled') {
                $result['detail']['detail_status'][] = [
                    'text' => 'Your order has been canceled',
                    'date' => MyHelper::dateFormatInd($list['void_date'])
                ];
            }
            if ($list['detail']['reject_at'] != null) {
                $result['detail']['detail_status'][] = [
                    'text'   => 'Order rejected',
                    'date' => MyHelper::dateFormatInd($list['detail']['reject_at']),
                    'reason' => $list['detail']['reject_reason'],
                ];
            }
            if ($list['detail']['taken_by_system_at'] != null) {
                $result['detail']['detail_status'][] = [
                    'text' => 'Your order has been done by system',
                    'date' => MyHelper::dateFormatInd($list['detail']['taken_by_system_at'])
                ];
            }
            if ($list['detail']['taken_at'] != null) {
                $result['detail']['detail_status'][] = [
                    'text' => 'Your order has been taken',
                    'date' => MyHelper::dateFormatInd($list['detail']['taken_at'])

                ];
            }
            if ($list['detail']['ready_at'] != null) {
                $result['detail']['detail_status'][] = [
                    'text' => 'Your order is ready ',
                    'date' => MyHelper::dateFormatInd($list['detail']['ready_at'])
                ];
            }
            if ($list['detail']['receive_at'] != null) {
                $result['detail']['detail_status'][] = [
                    'text' => 'Your order has been received',
                    'date' => MyHelper::dateFormatInd($list['detail']['receive_at'])
                ];
            }
            $result['detail']['detail_status'][] = [
                'text' => 'Your order awaits confirmation ',
                'date' => MyHelper::dateFormatInd($list['transaction_date'])
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

    public function transactionDetailV2(TransactionDetail $request)
    {
        $id = $request->json('id_transaction');

        $list = Transaction::where([['transactions.id_transaction', $id]])->leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')->with([
            // 'user.city.province',
            'user',
            'plasticTransaction.product',
            'productTransaction.product.product_category',
            'productTransaction.modifiers' => function ($query) {
                $query->orderByRaw('CASE WHEN id_product_modifier_group IS NULL THEN 1 ELSE 0 END');
            },
            'productTransaction.variants' => function ($query) {
                $query->select('id_transaction_product', 'transaction_product_variants.id_product_variant', 'transaction_product_variants.id_product_variant', 'product_variants.product_variant_name', 'transaction_product_variant_price')->join('product_variants', 'product_variants.id_product_variant', '=', 'transaction_product_variants.id_product_variant');
            },
            'productTransaction.product.product_photos',
            'productTransaction.product.product_discounts',
            'transaction_payment_offlines',
            'transaction_vouchers.deals_voucher.deal',
            'promo_campaign_promo_code.promo_campaign',
            'transaction_payment_subscription.subscription_user_voucher.subscription_user.subscription',
            'transaction_pickup_go_send',
            'transaction_pickup_wehelpyou.transaction_pickup_wehelpyou_updates',
            'outlet.city'])
            ->where('transactions.id_outlet', $request->user()->id_outlet)
            ->first();
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
                                $log = LogBalance::where('id_reference', $mp['id_transaction'])->where('source', 'Online Transaction')->first();
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
                                $payment['name']      = strtoupper(str_replace('_', ' ', $payMidtrans->payment_type)) . ' ' . strtoupper($payMidtrans->bank);
                                $payment['amount']    = $payMidtrans->gross_amount;
                                $list['payment'][] = $payment;
                                break;
                            case 'Ovo':
                                $payment = TransactionPaymentOvo::find($mp['id_payment']);
                                $payment['name']    = 'OVO';
                                $list['payment'][] = $payment;
                                break;
                            case 'IPay88':
                                $PayIpay = TransactionPaymentIpay88::find($mp['id_payment']);
                                $payment['name']    = $PayIpay->payment_method;
                                $payment['amount']    = $PayIpay->amount / 100;
                                $list['payment'][] = $payment;
                                break;
                            case 'Shopeepay':
                                $shopeePay = TransactionPaymentShopeePay::find($mp['id_payment']);
                                $payment['name']    = 'ShopeePay';
                                $payment['amount']  = $shopeePay->amount / 100;
                                $list['payment'][]  = $payment;
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
                foreach ($multiPayment as $dataKey => $dataPay) {
                    if ($dataPay['type'] == 'Midtrans') {
                        $payMidtrans = TransactionPaymentMidtran::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']      = strtoupper(str_replace('_', ' ', $payMidtrans->payment_type)) . ' ' . strtoupper($payMidtrans->bank);
                        $payment[$dataKey]['amount']    = $payMidtrans->gross_amount;
                    } else {
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
                foreach ($multiPayment as $dataKey => $dataPay) {
                    if ($dataPay['type'] == 'Ovo') {
                        $payment[$dataKey] = TransactionPaymentOvo::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']    = 'OVO';
                    } else {
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
                foreach ($multiPayment as $dataKey => $dataPay) {
                    if ($dataPay['type'] == 'IPay88') {
                        $PayIpay = TransactionPaymentIpay88::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']    = $PayIpay->payment_method;
                        $payment[$dataKey]['amount']    = $PayIpay->amount / 100;
                    } else {
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey] = $dataPay;
                        $list['balance'] = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']          = 'Balance';
                        $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                    }
                }
                $list['payment'] = $payment;
                break;
            case 'Shopeepay':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $list['id_transaction'])->get();
                $payment = [];
                foreach ($multiPayment as $dataKey => $dataPay) {
                    if ($dataPay['type'] == 'Shopeepay') {
                        $payShopee = TransactionPaymentShopeePay::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']      = 'ShopeePay';
                        $payment[$dataKey]['amount']    = $payShopee->amount / 100;
                    } else {
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey]              = $dataPay;
                        $list['balance']                = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']      = 'Balance';
                        $payment[$dataKey]['amount']    = $dataPay['balance_nominal'];
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
                $list['payment'] = [];
                break;
        }

        if (!empty($list['transaction_payment_subscription'])) {
            $payment_subscription = abs($list['transaction_payment_subscription']['subscription_nominal']);
            $result['promo_name'] = $list['transaction_payment_subscription']['subscription_user_voucher']['subscription_user']['subscription']['subscription_title'];
            $list['payment'][] = [
                'name'      => 'Subscription',
                'amount'    => $payment_subscription
            ];
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
        $currentdate = date('Y-m-d H:i');
        $setTime = date('Y-m-d H:i', strtotime($list['pickup_at'] . ' - 15 minutes'));
        $allowReady = 1;
        if ($list['pickup_type'] == 'set time' && $currentdate < $setTime) {
            $allowReady = 0;
        }

        $trxType = $list['trasaction_type'];
        if (isset($list['pickup_by']) && ($list['pickup_by'] == 'GO-SEND' || $list['pickup_by'] == 'Wehelpyou')) {
            $trxType = 'Delivery';
        }

        $result = [
            'id_transaction'              => $list['id_transaction'],
            'user_name'                   => $list['user']['name'],
            'user_phone'                  => $list['user']['phone'],
            'transaction_receipt_number'  => $list['transaction_receipt_number'],
            'transaction_date'            => date('d M Y H:i', strtotime($list['transaction_date'])),
            'trasaction_type'             => $trxType,
            'transaction_grandtotal'      => MyHelper::requestNumber($list['transaction_grandtotal'], '_CURRENCY'),
            'transaction_subtotal'        => MyHelper::requestNumber($list['transaction_subtotal'], '_CURRENCY'),
            'transaction_discount'        => MyHelper::requestNumber($list['transaction_discount'], '_CURRENCY'),
            'transaction_cashback_earned' => MyHelper::requestNumber($list['transaction_cashback_earned'], '_POINT'),
            'trasaction_payment_type'     => $list['trasaction_payment_type'],
            'transaction_payment_status'  => $list['transaction_payment_status'],
            'rejectable'                  => 0,
            'allow_ready'                 => $allowReady,
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

            if (empty($list['detail']['pickup_at'])) {
                $proc_time = Setting::where('key', 'processing_time')->first();
                $pickup_at = date('H:i', strtotime('+' . $proc_time->value . ' minutes', strtotime($list['transaction_date'])));
            }
            $result['detail']['pickup_at'] = !empty($list['detail']['pickup_at']) ? date('H:i', strtotime($list['detail']['pickup_at'])) : $pickup_at;

            if (isset($list['transaction_payment_status']) && $list['transaction_payment_status'] == 'Cancelled') {
                $result['transaction_status']      = 0;
                $result['transaction_status_text'] = 'ORDER ANDA DIBATALKAN';
            } elseif (isset($list['transaction_payment_status']) && $list['transaction_payment_status'] == 'Pending') {
                $result['transaction_status']      = 6;
                $result['transaction_status_text'] = 'MENUNGGU PEMBAYARAN';
            } elseif ($list['detail']['reject_at'] != null) {
                $reason = $list['detail']['reject_reason'];
                $ditolak = 'ORDER DITOLAK';
                if (strpos($reason, 'auto reject order') !== false) {
                    $ditolak = 'ORDER DITOLAK OTOMATIS';
                    if (strpos($reason, 'no driver') !== false) {
                        $reason = 'GAGAL MENEMUKAN DRIVER';
                    } elseif (strpos($reason, 'not ready') !== false) {
                        $reason = 'STATUS ORDER TIDAK DIPROSES READY';
                    } else {
                        $reason = 'OUTLET GAGAL MENERIMA ORDER';
                    }
                }
                if ($reason) {
                    $reason = "\n$reason";
                }
                $result['transaction_status']      = 0;
                $result['transaction_status_text'] = "$ditolak$reason";
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

            if ($list['transaction_pickup_go_send'] && !$list['detail']['reject_at']) {
                // $result['transaction_status'] = 5;
                $result['delivery_info'] = [
                    'driver'            => null,
                    'delivery_status'   => '',
                    'delivery_address'  => $list['transaction_pickup_go_send']['destination_address'] ?: '',
                    'delivery_address_note' => $list['transaction_pickup_go_send']['destination_note'] ?: '',
                    'booking_status'    => 0,
                    'cancelable'        => 1,
                    'go_send_order_no'  => $list['transaction_pickup_go_send']['go_send_order_no'] ?: '',
                    'live_tracking_url' => $list['transaction_pickup_go_send']['live_tracking_url'] ?: '',
                ];
                if ($list['transaction_pickup_go_send']['go_send_id']) {
                    $result['delivery_info']['booking_status'] = 1;
                }
                switch (strtolower($list['transaction_pickup_go_send']['latest_status'])) {
                    case 'finding driver':
                    case 'confirmed':
                        $result['delivery_info']['delivery_status'] = 'Sedang mencari driver';
                        $result['delivery_info']['delivery_status_code']   = 1;
                        $result['transaction_status_text']          = 'SEDANG MENCARI DRIVER';
                        $result['rejectable']                       = 0;
                        break;
                    case 'driver allocated':
                    case 'allocated':
                        $result['delivery_info']['delivery_status'] = 'Driver ditemukan';
                        $result['delivery_info']['delivery_status_code']   = 2;
                        $result['transaction_status_text']          = 'DRIVER DITEMUKAN';
                        $result['delivery_info']['driver']          = [
                            'driver_id'      => $list['transaction_pickup_go_send']['driver_id'] ?: '',
                            'driver_name'    => $list['transaction_pickup_go_send']['driver_name'] ?: '',
                            'driver_phone'   => $list['transaction_pickup_go_send']['driver_phone'] ?: 'Data tidak tersedia dari jasa pengiriman',
                            'driver_photo'   => $list['transaction_pickup_go_send']['driver_photo'] ?: $this->default_driver_photo,
                            'vehicle_number' => $list['transaction_pickup_go_send']['vehicle_number'] ?: 'Data tidak tersedia dari jasa pengiriman',
                        ];
                        $result['rejectable']                       = 0;
                        break;
                    case 'enroute pickup':
                    case 'out_for_pickup':
                        $result['delivery_info']['delivery_status'] = 'Driver dalam perjalanan menuju Outlet';
                        $result['delivery_info']['delivery_status_code']   = 2;
                        $result['transaction_status_text']          = 'DRIVER SEDANG MENUJU OUTLET';
                        $result['delivery_info']['driver']          = [
                            'driver_id'      => $list['transaction_pickup_go_send']['driver_id'] ?: '',
                            'driver_name'    => $list['transaction_pickup_go_send']['driver_name'] ?: '',
                            'driver_phone'   => $list['transaction_pickup_go_send']['driver_phone'] ?: 'Data tidak tersedia dari jasa pengiriman',
                            'driver_photo'   => $list['transaction_pickup_go_send']['driver_photo'] ?: $this->default_driver_photo,
                            'vehicle_number' => $list['transaction_pickup_go_send']['vehicle_number'] ?: 'Data tidak tersedia dari jasa pengiriman',
                        ];
                        $result['delivery_info']['cancelable'] = 1;
                        $result['rejectable']                  = 0;
                        break;
                    case 'picked':
                        $result['delivery_info']['delivery_status'] = 'Driver mengambil pesanan di Outlet';
                        $result['delivery_info']['delivery_status_code']   = 2;
                        $result['transaction_status_text']          = 'DRIVER MENGAMBIL PESANAN DI OUTLET';
                        $result['delivery_info']['driver']          = [
                            'driver_id'      => $list['transaction_pickup_go_send']['driver_id'] ?: '',
                            'driver_name'    => $list['transaction_pickup_go_send']['driver_name'] ?: '',
                            'driver_phone'   => $list['transaction_pickup_go_send']['driver_phone'] ?: 'Data tidak tersedia dari jasa pengiriman',
                            'driver_photo'   => $list['transaction_pickup_go_send']['driver_photo'] ?: $this->default_driver_photo,
                            'vehicle_number' => $list['transaction_pickup_go_send']['vehicle_number'] ?: 'Data tidak tersedia dari jasa pengiriman',
                        ];
                        $result['delivery_info']['cancelable'] = 0;
                        $result['rejectable']                  = 0;
                        break;
                    case 'enroute drop':
                    case 'out_for_delivery':
                        $result['delivery_info']['delivery_status'] = 'Driver mengantarkan pesanan';
                        $result['delivery_info']['delivery_status_code']   = 3;
                        if ($list['detail']['ready_at'] != null) {
                            $result['transaction_status_text']          = 'PROSES PENGANTARAN';
                            $result['transaction_status']               = 3;
                        }
                        $result['delivery_info']['driver']          = [
                            'driver_id'      => $list['transaction_pickup_go_send']['driver_id'] ?: '',
                            'driver_name'    => $list['transaction_pickup_go_send']['driver_name'] ?: '',
                            'driver_phone'   => $list['transaction_pickup_go_send']['driver_phone'] ?: 'Data tidak tersedia dari jasa pengiriman',
                            'driver_photo'   => $list['transaction_pickup_go_send']['driver_photo'] ?: $this->default_driver_photo,
                            'vehicle_number' => $list['transaction_pickup_go_send']['vehicle_number'] ?: 'Data tidak tersedia dari jasa pengiriman',
                        ];
                        $result['delivery_info']['cancelable'] = 0;
                        $result['rejectable']                  = 0;
                        $result['already_taken']               = 1;
                        break;
                    case 'completed':
                    case 'delivered':
                        if ($list['detail']['taken_by_system_at'] == null) {
                            if ($list['detail']['ready_at'] == null) {
                                $result['transaction_status'] = 4;
                                $result['transaction_status_text'] = 'ORDER SEDANG DIPROSES';
                            } elseif ($list['detail']['taken_at'] == null) {
                                $result['transaction_status'] = 3;
                                $result['transaction_status_text'] = 'ORDER SUDAH SIAP';
                            } else {
                                $result['transaction_status_text'] = 'ORDER SUDAH DIAMBIL';
                                $result['transaction_status'] = 2;
                            }
                        }
                        $result['delivery_info']['delivery_status'] = 'Pesanan sudah diterima Customer';
                        $result['delivery_info']['delivery_status_code']   = 4;
                        $result['delivery_info']['driver']          = [
                            'driver_id'      => $list['transaction_pickup_go_send']['driver_id'] ?: '',
                            'driver_name'    => $list['transaction_pickup_go_send']['driver_name'] ?: '',
                            'driver_phone'   => $list['transaction_pickup_go_send']['driver_phone'] ?: 'Data tidak tersedia dari jasa pengiriman',
                            'driver_photo'   => $list['transaction_pickup_go_send']['driver_photo'] ?: $this->default_driver_photo,
                            'vehicle_number' => $list['transaction_pickup_go_send']['vehicle_number'] ?: 'Data tidak tersedia dari jasa pengiriman',
                        ];
                        $result['delivery_info']['cancelable'] = 0;
                        break;
                    case 'cancelled':
                        $result['delivery_info']['delivery_status_code']   = 0;
                        $result['delivery_info']['booking_status'] = 0;
                        $result['transaction_status_text']         = 'PENGANTARAN DIBATALKAN';
                        $result['delivery_info']['delivery_status'] = 'Pengantaran dibatalkan';
                        $result['delivery_info']['cancelable']     = 0;
                        $result['rejectable']              = ($list['transaction_pickup_go_send']['stop_booking_at']) ? 1 : 0;
                        break;
                    case 'rejected':
                        $result['delivery_info']['delivery_status_code']   = 0;
                        $result['delivery_info']['booking_status']  = 0;
                        $result['transaction_status_text']          = "PENGANTARAN DIBATALKAN\ndriver tidak dapat mencapai lokasi tujuan";
                        $result['delivery_info']['delivery_status'] = 'Pengantaran dibatalkan';
                        $result['delivery_info']['cancelable']      = 0;
                        $result['rejectable']                       = 0;
                        $result['transaction_status']               = 0;
                        break;
                    case 'on_hold':
                        $result['delivery_info']['delivery_status_code']   = 5;
                        $result['delivery_info']['delivery_status'] = 'Pengiriman sedang ditahan';
                        $result['transaction_status_text']          = 'PENGIRIMAN SEDANG DITAHAN';
                        $result['delivery_info']['driver']          = [
                            'driver_id'         => $list['transaction_pickup_go_send']['driver_id'] ?: '',
                            'driver_name'       => $list['transaction_pickup_go_send']['driver_name'] ?: '',
                            'driver_phone'      => $list['transaction_pickup_go_send']['driver_phone'] ?: '',
                            'driver_whatsapp'   => env('URL_WA') . $list['transaction_pickup_go_send']['driver_phone'] ?: '',
                            'driver_photo'      => $list['transaction_pickup_go_send']['driver_photo'] ?: '',
                            'vehicle_number'    => $list['transaction_pickup_go_send']['vehicle_number'] ?: '',
                        ];
                        $result['delivery_info']['cancelable'] = 0;
                        break;
                    case 'driver not found':
                    case 'no_driver':
                        $result['delivery_info']['delivery_status_code']   = 0;
                        $result['delivery_info']['booking_status']  = 0;
                        $result['transaction_status_text']          = 'DRIVER TIDAK DITEMUKAN';
                        $result['delivery_info']['delivery_status'] = 'Driver tidak ditemukan';
                        $result['delivery_info']['cancelable']      = 0;
                        $result['rejectable']              = ($list['transaction_pickup_go_send']['stop_booking_at']) ? 1 : 0;
                        break;
                }
            } elseif ($list['transaction_pickup_wehelpyou'] && !$list['detail']['reject_at']) {
                // $result['transaction_status'] = 5;
                $result['delivery_info'] = [
                    'driver' => null,
                    'delivery_status' => '',
                    'delivery_address' => $list['transaction_pickup_wehelpyou']['receiver_address'] ?: '',
                    'delivery_address_note' => $list['transaction_pickup_wehelpyou']['receiver_notes'] ?: '',
                    'booking_status' => 0,
                    'cancelable' => 1,
                    'go_send_order_no' => $list['transaction_pickup_wehelpyou']['poNo'] ?: '',
                    'live_tracking_url' => $list['transaction_pickup_wehelpyou']['tracking_live_tracking_url'] ?: ''
                ];
                if ($list['transaction_pickup_wehelpyou']['poNo']) {
                    $result['delivery_info']['booking_status'] = 1;
                }
                switch (strtolower($list['transaction_pickup_wehelpyou']['latest_status_id'])) {
                    case 1:
                    case 11:
                        $result['delivery_info']['delivery_status'] = 'Sedang mencari driver';
                        $result['delivery_info']['delivery_status_code']   = 1;
                        $result['transaction_status_text']          = 'SEDANG MENCARI DRIVER';
                        break;
                    case 8:
                        $result['delivery_info']['delivery_status'] = 'Driver ditemukan';
                        $result['delivery_info']['delivery_status_code']   = 2;
                        $result['transaction_status_text']          = 'DRIVER DITEMUKAN DAN SEDANG MENUJU OUTLET';
                        $result['delivery_info']['driver']          = [
                            'driver_id'         => 'Data tidak tersedia dari jasa pengiriman',
                            'driver_name'       => $list['transaction_pickup_wehelpyou']['tracking_driver_name'] ?: '',
                            'driver_phone'      => $list['transaction_pickup_wehelpyou']['tracking_driver_phone'] ?: 'Data tidak tersedia dari jasa pengiriman',
                            'driver_whatsapp'   => env('URL_WA') . $list['transaction_pickup_wehelpyou']['tracking_driver_phone'] ?: '',
                            'driver_photo'      => $list['transaction_pickup_wehelpyou']['tracking_photo'] ?: $this->default_driver_photo,
                            'vehicle_number'    => $list['transaction_pickup_wehelpyou']['tracking_vehicle_number'] ?: 'Data tidak tersedia dari jasa pengiriman',
                        ];
                        break;
                    case 32:
                        $result['delivery_info']['delivery_status'] = 'Driver mengambil pesanan di Outlet';
                        $result['delivery_info']['delivery_status_code']   = 2;
                        $result['transaction_status_text']          = 'DRIVER MENGAMBIL PESANAN DI OUTLET';
                        $result['delivery_info']['driver']          = [
                            'driver_id'         => 'Data tidak tersedia dari jasa pengiriman',
                            'driver_name'       => $list['transaction_pickup_wehelpyou']['tracking_driver_name'] ?: '',
                            'driver_phone'      => $list['transaction_pickup_wehelpyou']['tracking_driver_phone'] ?: 'Data tidak tersedia dari jasa pengiriman',
                            'driver_whatsapp'   => env('URL_WA') . $list['transaction_pickup_wehelpyou']['tracking_driver_phone'] ?: '',
                            'driver_photo'      => $list['transaction_pickup_wehelpyou']['tracking_photo'] ?: $this->default_driver_photo,
                            'vehicle_number'    => $list['transaction_pickup_wehelpyou']['tracking_vehicle_number'] ?: 'Data tidak tersedia dari jasa pengiriman',
                        ];
                        $result['delivery_info']['cancelable'] = 1;
                        break;
                    case 9:
                        $result['delivery_info']['delivery_status'] = 'Driver mengantarkan pesanan';
                        $result['delivery_info']['delivery_status_code']   = 3;
                        $result['transaction_status_text']          = 'PROSES PENGANTARAN';
                        $result['transaction_status']               = 3;
                        $result['delivery_info']['driver']          = [
                            'driver_id'         => 'Data tidak tersedia dari jasa pengiriman',
                            'driver_name'       => $list['transaction_pickup_wehelpyou']['tracking_driver_name'] ?: '',
                            'driver_phone'      => $list['transaction_pickup_wehelpyou']['tracking_driver_phone'] ?: 'Data tidak tersedia dari jasa pengiriman',
                            'driver_whatsapp'   => env('URL_WA') . $list['transaction_pickup_wehelpyou']['tracking_driver_phone'] ?: '',
                            'driver_photo'      => $list['transaction_pickup_wehelpyou']['tracking_photo'] ?: $this->default_driver_photo,
                            'vehicle_number'    => $list['transaction_pickup_wehelpyou']['tracking_vehicle_number'] ?: 'Data tidak tersedia dari jasa pengiriman',
                        ];
                        $result['delivery_info']['cancelable'] = 0;
                        $result['already_taken']               = 1;
                        break;
                    case 2:
                        $result['transaction_status'] = 2;
                        $result['transaction_status_text']          = 'PESANAN TELAH SELESAI DAN DITERIMA';
                        $result['delivery_info']['delivery_status'] = 'Pesanan sudah diterima Customer';
                        $result['delivery_info']['delivery_status_code']   = 4;
                        $result['delivery_info']['driver']          = [
                            'driver_id'         => 'Data tidak tersedia dari jasa pengiriman',
                            'driver_name'       => $list['transaction_pickup_wehelpyou']['tracking_driver_name'] ?: '',
                            'driver_phone'      => $list['transaction_pickup_wehelpyou']['tracking_driver_phone'] ?: 'Data tidak tersedia dari jasa pengiriman',
                            'driver_whatsapp'   => env('URL_WA') . $list['transaction_pickup_wehelpyou']['tracking_driver_phone'] ?: '',
                            'driver_photo'      => $list['transaction_pickup_wehelpyou']['tracking_photo'] ?: $this->default_driver_photo,
                            'vehicle_number'    => $list['transaction_pickup_wehelpyou']['tracking_vehicle_number'] ?: 'Data tidak tersedia dari jasa pengiriman',
                        ];
                        $result['delivery_info']['cancelable'] = 0;
                        break;
                    case 89:
                    case 90:
                    case 91:
                    case 99:
                        $result['delivery_info']['booking_status'] = 0;
                        $result['delivery_info']['delivery_status_code'] = 0;
                        $result['delivery_info']['delivery_status'] = 'Pengantaran dibatalkan';
                        $result['delivery_info']['cancelable']     = 0;
                        $result['transaction_status_text']         = 'PENGANTARAN PESANAN TELAH DIBATALKAN';
                        break;
                    case 96:
                        $result['transaction_status'] = 0;
                        $result['delivery_info']['booking_status'] = 0;
                        $result['delivery_info']['delivery_status'] = 'Pengantaran dibatalkan';
                        $result['delivery_info']['delivery_status'] = 'Pengantaran dibatalkan';
                        $result['delivery_info']['cancelable']     = 0;
                        $result['delivery_info']['delivery_status_code'] = 0;
                        $result['transaction_status_text']         = 'PENGANTARAN PESANAN TELAH DIBATALKAN';
                        break;
                    default:
                        break;
                }

                if (!empty($list['detail']['receive_at']) && empty($list['transaction_pickup_wehelpyou']['poNo'])) {
                    $result['transaction_status_text'] = 'GAGAL MENCARI DRIVER';
                    $result['delivery_info']['go_send_order_no'] = '-';
                }

                $result['delivery_info_be'] = [
                    'delivery_address' => $list['transaction_pickup_wehelpyou']['receiver_address'] ?: '',
                    'delivery_address_note' => $list['transaction_pickup_wehelpyou']['receiver_notes'] ?: '',
                ];
            }
        }

        $discount = 0;
        $quantity = 0;
        $keynya   = 0;
        $productPerBrand = [];
        foreach ($list['product_transaction'] as $keyTrx => $valueTrx) {
            $result['product_transaction'][$keynya]['brand'] = $keyTrx;
            $forProdBrand = [];
            foreach ($valueTrx as $keyProduct => $valueProduct) {
                $extra_modifier_price = 0;
                $quantity                                                                                        = $quantity + $valueProduct['transaction_product_qty'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_qty']       = $valueProduct['transaction_product_qty'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_subtotal']  = MyHelper::requestNumber($valueProduct['transaction_product_subtotal'], '_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_sub_item']  = '@' . MyHelper::requestNumber($valueProduct['transaction_product_subtotal'] / $valueProduct['transaction_product_qty'], '_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_modifier_subtotal'] = MyHelper::requestNumber($valueProduct['transaction_modifier_subtotal'], '_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_variant_subtotal']  = MyHelper::requestNumber($valueProduct['transaction_variant_subtotal'], '_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_note']      = $valueProduct['transaction_product_note'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_discount']  = $valueProduct['transaction_product_discount'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_name']       = $valueProduct['product']['product_name'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_price']      = MyHelper::requestNumber($valueProduct['transaction_product_price'], '_CURRENCY');
                $discount                                                                                        = $discount + $valueProduct['transaction_product_discount'];
                $variantsPrice = 0;
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'] = [];
                foreach ($valueProduct['variants'] as $keyMod => $valueMod) {
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'][$keyMod]['product_variant_name']   = $valueMod['product_variant_name'];
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'][$keyMod]['product_variant_price']  = (int)$valueMod['transaction_product_variant_price'];
                    $variantsPrice = $variantsPrice + $valueMod['transaction_product_variant_price'];
                }
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'] = [];
                foreach ($valueProduct['modifiers'] as $keyMod => $valueMod) {
                    if (!empty($valueMod['id_product_modifier_group'])) {
                        $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'][] = [
                            'product_variant_name' => $valueMod['text'],
                            'product_variant_price' => 0,
                            'is_modifier' => 1
                        ];
                        $extra_modifier_price += (int) ($valueMod['qty'] * $valueMod['transaction_product_modifier_price']);
                    } else {
                        $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'][] = [
                            'product_modifier_name' => $valueMod['text'],
                            'product_modifier_qty' => $valueMod['qty'],
                            'product_modifier_price' => (int)($valueMod['transaction_product_modifier_price'] * $valueProduct['transaction_product_qty'])
                        ];
                    }
                }
                $variantsPrice += $extra_modifier_price;
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_sub_item']      = '@' . MyHelper::requestNumber($valueProduct['transaction_product_price'] + $variantsPrice, '_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product_variant_group_price'] = (int)($valueProduct['transaction_product_price'] + $variantsPrice);

                $forProdBrand[] = [
                    'bundling_name' => null,
                    'product_name' => $valueProduct['product']['product_name'],
                    'product_note' => $valueProduct['transaction_product_note'],
                    'transaction_product_qty' => $valueProduct['transaction_product_qty'],
                    'product_modifiers' => $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'],
                    'product_variants' => $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'],
                ];
            }
            $productPerBrand[$keyTrx] = $forProdBrand;
            $keynya++;
        }
        //get item bundling
        $bundlingGroup = [];
        $itemBundling = [];
        $quantityItemBundling = 0;
        $productBundlingPerBrand = [];
        $getBundling   = TransactionBundlingProduct::join('bundling', 'bundling.id_bundling', 'transaction_bundling_products.id_bundling')
            ->where('id_transaction', $id)->get()->toArray();
        foreach ($getBundling as $key => $bundling) {
            $bundlingProduct = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                ->join('brands', 'brands.id_brand', 'transaction_products.id_brand')
                ->orderBy('order_brand', 'desc')
                ->where('id_transaction_bundling_product', $bundling['id_transaction_bundling_product'])->get()->toArray();

            $products = [];
            $basePriceBundling = 0;
            $subTotalBundlingWithoutModifier = 0;
            $subItemBundlingWithoutModifie = 0;
            foreach ($bundlingProduct as $bp) {
                $quantityItemBundling = $quantityItemBundling + $bp['transaction_product_qty'];
                $mod = TransactionProductModifier::join('product_modifiers', 'product_modifiers.id_product_modifier', 'transaction_product_modifiers.id_product_modifier')
                    ->whereNull('transaction_product_modifiers.id_product_modifier_group')
                    ->where('id_transaction_product', $bp['id_transaction_product'])
                    ->select('transaction_product_modifiers.id_product_modifier', 'transaction_product_modifiers.text as product_modifier_name', DB::raw('FLOOR(transaction_product_modifier_price * ' . $bp['transaction_product_bundling_qty'] . ' * ' . $bundling['transaction_bundling_product_qty'] . ') as product_modifier_price'))->get()->toArray();
                $variantPrice = TransactionProductVariant::join('product_variants', 'product_variants.id_product_variant', 'transaction_product_variants.id_product_variant')
                    ->where('id_transaction_product', $bp['id_transaction_product'])
                    ->select('product_variants.id_product_variant', 'product_variants.product_variant_name', 'transaction_product_variant_price')->get()->toArray();
                $variantNoPrice =  TransactionProductModifier::join('product_modifiers', 'product_modifiers.id_product_modifier', 'transaction_product_modifiers.id_product_modifier')
                    ->whereNotNull('transaction_product_modifiers.id_product_modifier_group')
                    ->where('id_transaction_product', $bp['id_transaction_product'])
                    ->select('transaction_product_modifiers.id_product_modifier as id_product_variant', 'transaction_product_modifiers.text as product_variant_name', 'transaction_product_modifier_price as transaction_product_variant_price')->get()->toArray();

                $products[] = [
                    'product_name' => $bp['product_name'],
                    'product_note' => $bp['transaction_product_note'],
                    'transaction_product_price' => (int)($bp['transaction_product_price'] + $bp['transaction_variant_subtotal']),
                    'transaction_product_qty' => $bp['transaction_product_bundling_qty'],
                    'modifiers' => $mod,
                    'variants' => array_merge($variantPrice, $variantNoPrice)
                ];

                $bundlingGroup[$bp['name_brand'] . '|' . $bundling['bundling_name']][] =
                    [
                        'product_name' => $bp['product_name'],
                        'product_note' => $bp['transaction_product_note'],
                        'transaction_product_qty' => $bp['transaction_product_bundling_qty'] * $bundling['transaction_bundling_product_qty'],
                        'product_modifiers' => $mod,
                        'product_variants' => array_merge($variantPrice, $variantNoPrice)
                    ];

                $basePriceBundling = $basePriceBundling + (($bp['transaction_product_price'] + $bp['transaction_variant_subtotal']) * $bp['transaction_product_bundling_qty']);
                $subTotalBundlingWithoutModifier = $subTotalBundlingWithoutModifier + (($bp['transaction_product_subtotal'] - ($bp['transaction_modifier_subtotal'] * $bp['transaction_product_bundling_qty'])));
                $subItemBundlingWithoutModifie = $subItemBundlingWithoutModifie + ($bp['transaction_product_bundling_price'] * $bp['transaction_product_bundling_qty']);
            }

            $itemBundling[] = [
                'bundling_name' => $bundling['bundling_name'],
                'bundling_qty' => $bundling['transaction_bundling_product_qty'],
                'bundling_price_no_discount' => $basePriceBundling * $bundling['transaction_bundling_product_qty'],
                'bundling_subtotal' => $subTotalBundlingWithoutModifier * $bundling['transaction_bundling_product_qty'],
                'bundling_sub_item' => '@' . MyHelper::requestNumber($subItemBundlingWithoutModifie, '_CURRENCY'),
                'products' => $products
            ];
        }

        foreach ($bundlingGroup as $key => $bg) {
            $brand = explode('|', $key)[0];
            $merge = $this->mergeBundlingProducts($bg);
            $productBundlingPerBrand[$brand][] = [
                'bundling_name' => explode('|', $key)[1],
                'products' => $merge
            ];
        }
        $nameBrandBundling = Setting::where('key', 'brand_bundling_name')->first();
        $result['name_brand_bundling'] = $nameBrandBundling['value'] ?? 'Bundling';
        $result['product_bundling_transaction_detail'] = $itemBundling;

        $brandProduct = array_keys($productPerBrand);
        $brandProductBundling = array_keys($productBundlingPerBrand);
        $allBrand = array_unique(array_merge($brandProduct, $brandProductBundling));

        $perBrandResult = [];
        foreach ($allBrand as $brand) {
            $perBrandResult[] = [
                'brand' => $brand,
                'item' => $productPerBrand[$brand] ?? [],
                'item_bundling' => $productBundlingPerBrand[$brand] ?? [],
            ];
        }
        $result['product_perbrand'] = $perBrandResult;

        if (!isset($result['product_transaction'])) {
            $result['product_transaction'] = [];
        }

        $result['plastic_transaction_detail'] = [];
        $result['plastic_name'] = '';
        $quantityPlastic = 0;
        if (isset($list['plastic_transaction'])) {
            $result['plastic_name'] = 'Kantong Belanja';
            $subtotal_plastic = 0;
            foreach ($list['plastic_transaction'] as $key => $value) {
                $quantityPlastic = $quantityPlastic + $value['transaction_product_qty'];
                $subtotal_plastic += $value['transaction_product_subtotal'];

                $result['plastic_transaction_detail'][] = [
                    'plastic_name' => $value['product']['product_name'],
                    'plasctic_qty' => $value['transaction_product_qty'],
                    'plastic_base_price' => '@' . MyHelper::requestNumber((int)$value['transaction_product_price'], '_CURRENCY'),
                    'plasctic_subtotal' => MyHelper::requestNumber($value['transaction_product_subtotal'], '_CURRENCY')
                ];
            }

            $result['plastic_transaction'] = [];
            $result['plastic_transaction']['transaction_plastic_total'] = $subtotal_plastic;
        }

        $result['payment_detail'][] = [
            'name'   => 'Subtotal',
            'desc'   => $quantity + $quantityItemBundling + $quantityPlastic . ' items',
            'amount' => MyHelper::requestNumber($list['transaction_subtotal'], '_CURRENCY'),
        ];

        if ($list['transaction_discount']) {
            $discount = abs($list['transaction_discount']);
            $p = 0;
            if (!empty($list['transaction_vouchers'])) {
                foreach ($list['transaction_vouchers'] as $valueVoc) {
                    $result['promo']['code'][$p++]   = $valueVoc['deals_voucher']['voucher_code'];
                    $result['payment_detail'][] = [
                        'name'          => 'Diskon (Promo)',
                        'desc'          => null,
                        "is_discount"   => 1,
                        'amount'        => '- ' . MyHelper::requestNumber($discount, '_CURRENCY')
                    ];
                }
            }

            if (!empty($list['promo_campaign_promo_code'])) {
                $result['promo']['code'][$p++]   = $list['promo_campaign_promo_code']['promo_code'];
                $result['payment_detail'][] = [
                    'name'          => 'Diskon (Promo)',
                    'desc'          => null,
                    "is_discount"   => 1,
                    'amount'        => '- ' . MyHelper::requestNumber($discount, '_CURRENCY')
                ];
            }

            if (!empty($list['id_subscription_user_voucher']) && !empty($list['transaction_discount'])) {
                $result['payment_detail'][] = [
                    'name'          => 'Subscription (Diskon)',
                    'desc'          => null,
                    "is_discount"   => 1,
                    'amount'        => '- ' . MyHelper::requestNumber($discount, '_CURRENCY')
                ];
            }
        }

        if ($list['transaction_shipment_go_send'] > 0) {
            $result['payment_detail'][] = [
                'name'      => 'Delivery',
                'desc'      => $list['detail']['pickup_by'],
                'amount'    => MyHelper::requestNumber($list['transaction_shipment_go_send'], '_CURRENCY')
            ];
        } elseif ($list['transaction_shipment'] > 0) {
            $getListDelivery = json_decode(MyHelper::setting('available_delivery', 'value_text', '[]'), true) ?? [];
            $shipmentCode = strtolower($list['shipment_method'] . '_' . $list['shipment_courier']);
            if ($list['shipment_method'] == 'GO-SEND') {
                $shipmentCode = 'gosend';
            }

            $search = array_search($shipmentCode, array_column($getListDelivery, 'code'));
            $shipmentName = ($search !== false ? $getListDelivery[$search]['delivery_name'] : strtoupper($list['shipment_courier']));
            $result['payment_detail'][] = [
                'name'      => 'Delivery',
                'desc'      => $shipmentName,
                'amount'    => MyHelper::requestNumber($list['transaction_shipment'], '_CURRENCY')
            ];
        }

        if ($list['transaction_discount_delivery']) {
            $discount = abs($list['transaction_discount_delivery']);
            $p = 0;
            if (!empty($list['transaction_vouchers'])) {
                foreach ($list['transaction_vouchers'] as $valueVoc) {
                    $result['promo']['code'][$p++]   = $valueVoc['deals_voucher']['voucher_code'];
                    $result['payment_detail'][] = [
                        'name'          => 'Diskon (Delivery)',
                        'desc'          => null,
                        "is_discount"   => 1,
                        'amount'        => '- ' . MyHelper::requestNumber($discount, '_CURRENCY')
                    ];
                }
            }

            if (!empty($list['promo_campaign_promo_code'])) {
                $result['promo']['code'][$p++]   = $list['promo_campaign_promo_code']['promo_code'];
                $result['payment_detail'][] = [
                    'name'          => 'Diskon (Delivery)',
                    'desc'          => null,
                    "is_discount"   => 1,
                    'amount'        => '- ' . MyHelper::requestNumber($discount, '_CURRENCY')
                ];
            }

            if (!empty($list['id_subscription_user_voucher']) && !empty($list['transaction_discount_delivery'])) {
                $result['payment_detail'][] = [
                    'name'          => 'Subscription (Delivery)',
                    'desc'          => null,
                    "is_discount"   => 1,
                    'amount'        => '- ' . MyHelper::requestNumber($discount, '_CURRENCY')
                ];
            }
        }

        $result['promo']['discount'] = $discount;
        $result['promo']['discount'] = MyHelper::requestNumber($discount, '_CURRENCY');

        if ($list['trasaction_payment_type'] != 'Offline') {
            if ($list['transaction_payment_status'] == 'Cancelled') {
                $result['detail']['detail_status'][] = [
                    'text' => 'Your order has been canceled',
                    'date' => MyHelper::dateFormatInd($list['void_date'])
                ];
            }
            if ($list['detail']['reject_at'] != null) {
                $result['detail']['detail_status'][] = [
                    'text'   => 'Order rejected',
                    'date' => MyHelper::dateFormatInd($list['detail']['reject_at']),
                    'reason' => $list['detail']['reject_reason'],
                ];
            }
            if ($list['detail']['taken_by_system_at'] != null) {
                $result['detail']['detail_status'][] = [
                    'text' => 'Your order has been done by system',
                    'date' => MyHelper::dateFormatInd($list['detail']['taken_by_system_at'])
                ];
            }
            if ($list['detail']['taken_at'] != null) {
                $result['detail']['detail_status'][] = [
                    'text' => 'Your order has been taken',
                    'date' => MyHelper::dateFormatInd($list['detail']['taken_at'])

                ];
            }
            if ($list['detail']['ready_at'] != null) {
                $result['detail']['detail_status'][] = [
                    'text' => 'Your order is ready ',
                    'date' => MyHelper::dateFormatInd($list['detail']['ready_at'])
                ];
            }
            if ($list['detail']['receive_at'] != null) {
                $result['detail']['detail_status'][] = [
                    'text' => 'Your order has been received',
                    'date' => MyHelper::dateFormatInd($list['detail']['receive_at'])
                ];
            }
            $result['detail']['detail_status'][] = [
                'text' => 'Your order awaits confirmation ',
                'date' => MyHelper::dateFormatInd($list['completed_at'] ?: $list['transaction_date'])
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
            ->select(DB::raw('outlet_holidays.id_holiday,date_holidays.id_date_holiday,holiday_name,yearly,date_holidays.date,GROUP_CONCAT(date_edit.date order by date_edit.date) as date_edit,(CASE WHEN COUNT(distinct(oh.id_outlet)) > 1 THEN 1 ELSE 0 END) as read_only'))
            ->where('outlet_holidays.id_outlet', $outlet->id_outlet)
            ->join('holidays', 'holidays.id_holiday', '=', 'outlet_holidays.id_holiday')
            ->join('outlet_holidays as oh', 'oh.id_holiday', '=', 'outlet_holidays.id_holiday')
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
        $insertHoliday = Holiday::select(\DB::raw('holidays.*,(CASE WHEN COUNT(distinct(oh.id_outlet)) > 1 THEN 1 ELSE 0 END) as read_only'))->join('outlet_holidays as oh', 'oh.id_holiday', '=', 'holidays.id_holiday')->groupBy('holidays.id_holiday')->find($id_holiday);
        if (!$insertHoliday) {
            return MyHelper::checkGet([], 'Holiday not found');
        }
        if ($insertHoliday->read_only) {
            return MyHelper::checkGet([], 'This holiday cannot be changed');
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
            $date_holiday = DateHoliday::where('id_date_holiday', $id_date_holiday)->with(['holiday' => function ($q) {
                $q->select(\DB::raw('holidays.*,(CASE WHEN COUNT(distinct(oh.id_outlet)) > 1 THEN 1 ELSE 0 END) as read_only'))->join('outlet_holidays as oh', 'oh.id_holiday', '=', 'holidays.id_holiday')->groupBy('holidays.id_holiday');
            }])->first();
            if (!$date_holiday) {
                return [
                    'status' => 'false',
                    'messages' => [
                        'Holiday not found'
                    ]
                ];
            }
            if ($date_holiday->holiday->read_only) {
                return MyHelper::checkGet([], 'This holiday cannot be deleted');
            };
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
        } elseif ($id_holiday) {
            $holiday = Holiday::select(\DB::raw('holidays.*,(CASE WHEN COUNT(distinct(oh.id_outlet)) > 1 THEN 1 ELSE 0 END) as read_only'))->join('outlet_holidays as oh', 'oh.id_holiday', '=', 'holidays.id_holiday')->groupBy('holidays.id_holiday')->find($id_holiday);
            if ($holiday->read_only) {
                return MyHelper::checkGet([], 'This holiday cannot be deleted');
            };
        }
        if ($id_holiday) {
            $delete = Holiday::where(['holidays.id_holiday' => $id_holiday, 'id_outlet' => $request->user()->id_outlet])->join('outlet_holidays', 'outlet_holidays.id_holiday', '=', 'holidays.id_holiday')->delete();
            return MyHelper::checkDelete($delete);
        }
        return MyHelper::checkGet([], 'Holiday not found');
    }

    public function isOperational($id_outlet)
    {
        $outlet_schedule = Outlet::with(['today' => function ($query) {
            $query->where('is_closed', 0);
        }])->where('id_outlet', $id_outlet)->first()['today'];

        if ($outlet_schedule == null) {
            return ['status' => false, 'message' => 'Outlet is close or not found.'];
        }

        $spare_time['open'] = (int) Setting::where('key', 'spare_open_time')->value('value') ?? 0;
        $spare_time['close'] = (int) Setting::where('key', 'spare_close_time')->value('value') ?? 0;

        $real_time['open']  = date('H:i', strtotime('-' . $spare_time['open'] . ' minutes', strtotime($outlet_schedule['open'])));
        $real_time['close'] = date('H:i', strtotime('+' . $spare_time['close'] . ' minutes', strtotime($outlet_schedule['close'])));

        /* Carbon test */
        // $knownDate = Carbon::createFromTime(22,16,0,'Asia/Jakarta');
        // Carbon::setTestNow($knownDate);
        // $dummyTime = strtotime(Carbon::now()->toTimeString());

        // if($dummyTime < strtotime($real_time['open']) || $dummyTime > strtotime($real_time['close']))
        //     return ['status' => false, 'message' => 'This is not operational time'];
        /* End Carbon Test */

        if (strtotime(date('H:i')) < strtotime($real_time['open']) || strtotime(date('H:i')) > strtotime($real_time['close'])) {
            return ['status' => false, 'message' => 'This is not operational time'];
        }

        return ['status' => true];
    }

    public function start_shift(Request $request)
    {

        // validate request
        $validateRequest = $request->validate([
            'cash_start' => 'required|numeric'
        ]);

        $post = $request->all();
        $user = $request->user();

        /* Check if operational time is set true */
        $is_operational = $this->isOperational($user['id_outlet']);
        if (!$is_operational['status']) {
            return response()->json(['status' => 'fail', 'message' => $is_operational['message']]);
        }

        // find existing shift outlet today
        $outlet_shift = Shift::where('id_outlet', $user['id_outlet'])->whereDate('created_at', date('Y-m-d'));
        $is_outlet_shift_exist_on_curdate = (clone $outlet_shift )->first();
        $is_outlet_shift_closed = (clone $outlet_shift )->whereNotNull('close_time')->first();

        // if this shift is not exist on curdate, continue process to running shift
        if (!$is_outlet_shift_exist_on_curdate) {
            // get this outlet schedule
            $cash_start = $post['cash_start'];
            $open_time = date('Y-m-d H:i:s');

            //save shift
            $save = Shift::create([
                'id_user_outletapp' => $user['id_user_outletapp'], // open by
                'id_outlet' => $user['id_outlet'],
                'open_time' => $open_time,
                'cash_start' => $cash_start
            ]);

            if ($save) {
                return response()->json(['status' => 'success', 'message' => 'Shift started at ' . $open_time, 'id_shift' => $save->id]);
            }
            return response()->json(['status' => 'fail', 'message' => 'Failed to create new shift']);
        }

        if ($is_outlet_shift_closed) {
            return response()->json(['status' => 'fail', 'message' => 'Shift have been ended by this user outlet', 'id_shift' => $is_outlet_shift_closed['id_shift']]);
        }
        return response()->json(['status' => 'fail', 'message' => 'Shift already running', 'id_shift' => $is_outlet_shift_exist_on_curdate['id_shift']]);
    }

    public function end_shift(Request $request)
    {

        // validate request
        $validateRequest = $request->validate([
            'cash_end' => 'required|numeric',
            'id_shift' => 'required'
        ]);

        $post = $request->all();
        $cash_end = $post['cash_end'];
        $id_shift = $post['id_shift'];

        $user = $request->user();

        /* Check if operational time is set true */
        $is_operational = $this->isOperational($user['id_outlet']);
        if (!$is_operational['status']) {
            return response()->json(['status' => 'fail', 'message' => $is_operational['message']]);
        }

        // search for id shift which running today
        $shift = Shift::where('id_shift', $id_shift);

        $is_outlet_shift_opened_by_this_user_outlet = (clone $shift)->where('id_outlet', $user['id_outlet'])->first();
        $is_outlet_shift_opened_on_curdate = (clone $shift)->whereDate('created_at', date('Y-m-d'))->first();
        $is_outlet_shift_not_closed = (clone $shift)->whereNull('close_time')->first();

        // check if shift is really exist in current user outlet
        if ($is_outlet_shift_opened_by_this_user_outlet) {
            // check if shift is really exist on current day
            if ($is_outlet_shift_opened_on_curdate) {
                // check if shift is not yet closed
                if ($is_outlet_shift_not_closed) {
                    $close_time = date('Y-m-d H:i:s');
                    $cash_start = $shift->first()['cash_start'];
                    $cash_difference = $cash_end - $cash_start;

                    $update = Shift::where('id_shift', $id_shift)->update([
                        'cash_end' => $cash_end,
                        'close_time' => $close_time,
                        'cash_difference' => $cash_difference,
                        'id_user_outletapp' => $user['id_user_outletapp'], //close by
                    ]);

                    if ($update) {
                        // Give warning if there is difference in total of transaction offline using CASH & today's cash difference
                        $is_different = $this->check_total_difference_offline_transaction($id_shift);

                        if (isset($is_different['status']) && $is_different['status'] == 'different') {
                            if (\Module::collections()->has('Autocrm')) {
                                $username = $request->user()->username;
                                $autocrm = app($this->autocrm)->SendAutoCRM('Difference in Total Transaction Offline', $username, null, null, false, true);
                            }
                        }

                        return response()->json(['status' => 'success', 'message' => 'Shift ended at ' . $close_time]);
                    }
                    return response()->json(['status' => 'fail', 'message' => 'Failed to update current shift']);
                }
                return response()->json(['status' => 'fail', 'message' => 'Shift have been ended by this user outlet']);
            }
            return response()->json(['status' => 'fail', 'message' => 'Shift is not yet started by this user outlet']);
        }
        return response()->json(['status' => 'fail', 'message' => 'Shift is not owned by this user outlet']);
    }

    public function check_total_difference_offline_transaction($id_shift)
    {
        // Get current data shift
        $shift = Shift::where('id_shift', $id_shift)->first();

        /* Used variables :

            $shift->cash_difference;
            $shift->id_outlet;
            $shift->open_time;
            $shift->close_time;
        */

        /* Conditions :

            Get transaction payment offline where id outlet = this shift id_outlet
            where transasction date between this shift open time & this shift close time
            where transaction is completed
            where transasciton payment offline - payment method = CASH
        */

        $payment = TransactionPaymentOffline::with(
            [
                'transaction' => function ($query) use ($shift) {
                    $open_time = date($shift['open_time']);
                    $close_time = date($shift['close_time']);

                    $query->where('transactions.id_outlet', $shift['id_outlet'])->whereBetween('transactions.transaction_date', [$open_time, $close_time])->where('transactions.transaction_payment_status', 'Completed');
                },

                'payment_method' => function ($query) {
                    $query->where('payment_methods.payment_method_name', 'Cash');
                }
            ]
        )->get()->toArray();

        $payment = array_filter($payment, function ($item) {
            return $item['transaction'] != null && $item['payment_method'] != null;
        });

        $total = 0;
        foreach ($payment as $item) {
            $total += $item['payment_amount'];
        }

        if ($shift['cash_difference'] != $total) {
            return ['status' => 'different', 'difference' => abs($shift['cash_difference'] - $total)];
        }

        return ['status' => 'not different', 'difference' => abs($shift['cash_difference'] - $total)];
    }

    public function listPaymentMethod(Request $request)
    {
        $id_outlet = $request->user()['id_outlet'];

        //get all payment method
        $payment_method = PaymentMethod::select('id_payment_method', 'id_payment_method_category', 'payment_method_name', 'status')
        ->with(['payment_method_category' => function ($query) {
            $query->select('id_payment_method_category', 'payment_method_category_name');
        }])->get()->toArray();

        //get all payment method outlet
        $payment_method_outlet = PaymentMethodOutlet::where('id_outlet', $id_outlet)->get()->toArray();

        //update status outlet
        foreach ($payment_method as $key => $value) {
            foreach ($payment_method_outlet as $key2 => $value) {
                if ($payment_method_outlet[$key2]['id_outlet'] == $id_outlet && $payment_method_outlet[$key2]['id_payment_method'] == $payment_method[$key]['id_payment_method']) {
                    $payment_method[$key]['status'] = $payment_method_outlet[$key2]['status'];
                    break;
                }
            }
            $payment_method[$key]['payment_method_category'] =  $payment_method[$key]['payment_method_category']['payment_method_category_name'];
        }


        return MyHelper::checkGet($payment_method);
    }

    /**
     * Update outlet phone number
     * @param  Request $request
     * @return Response
     */
    public function updatePhone(Request $request)
    {
        $outlet = $request->user();
        if (!$request->phone) {
            return [
                'status'   => 'fail',
                'messages' => ['The phone field is required']
            ];
        }

        $update = $outlet->update(['outlet_phone' => $request->phone]);
        return MyHelper::checkUpdate($update);
    }

    public function splash(Request $request)
    {
        $splash = Setting::where('key', '=', 'default_splash_screen_outlet_apps')->first();
        $duration = Setting::where('key', '=', 'default_splash_screen_outlet_apps_duration')->pluck('value')->first();

        if (!empty($splash)) {
            $splash = $this->endPoint . $splash['value'];
        } else {
            $splash = null;
        }
        $ext = explode('.', $splash);
        $result = [
            'status' => 'success',
            'result' => [
                'splash_screen_url' => $splash . "?update=" . time(),
                'splash_screen_duration' => $duration ?? 5,
                'splash_screen_ext' => '.' . end($ext)
            ]
        ];
        return $result;
    }

    //====================== Product Variant ======================//
    public function listProductVariantGroup(Request $request)
    {
        $post = $request->all();

        $data = ProductVariantGroup::leftJoin('product_variant_group_details as pvgd', 'pvgd.id_product_variant_group', 'product_variant_groups.id_product_variant_group');

        if (isset($post['id_outlet']) && !empty($post['id_outlet'])) {
            $data = $data->where(function ($q) use ($post) {
                $q->where('pvgd.id_outlet', $post['id_outlet']);
                $q->orWhereNull('pvgd.id_product_variant_group_detail');
            });
        }

        if (isset($post['id_product']) && !empty($post['id_product'])) {
            $data = $data->where('product_variant_groups.id_product', $post['id_product']);
        }

        if (isset($post['id_product_variant']) && !empty($post['id_product_variant'])) {
            $data = $data->whereIn('product_variant_groups.id_product_variant_group', function ($query) use ($post) {
                $query->select('pvp2.id_product_variant_group')
                    ->from('product_variant_pivot as pvp2')
                    ->whereIn('pvp2.id_product_variant', $post['id_product_variant']);
            });
        }

        $data = $data->select([
            'product_variant_groups.id_product', 'product_variant_groups.id_product_variant_group', 'product_variant_groups.product_variant_group_code',
            DB::raw('(SELECT GROUP_CONCAT(pv.product_variant_name SEPARATOR ",") FROM product_variant_pivot pvp join product_variants pv on pv.id_product_variant = pvp.id_product_variant where pvp.id_product_variant_group = product_variant_groups.id_product_variant_group) AS product_variant_group_name'),
            DB::raw('(CASE
                        WHEN pvgd.product_variant_group_visibility is NULL THEN product_variant_groups.product_variant_group_visibility
                        ELSE pvgd.product_variant_group_visibility END) as product_variant_group_visibility'),
            DB::raw('(CASE
                        WHEN pvgd.product_variant_group_stock_status is NULL THEN "Sold Out"
                        ELSE pvgd.product_variant_group_stock_status END) as product_variant_group_stock_status')]);


        $data = $data->where(function ($q) {
                        $q->where('pvgd.product_variant_group_status', 'Active')
                            ->orWhereNull('pvgd.product_variant_group_status');
        })
                    ->where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->whereNull('pvgd.product_variant_group_visibility')
                                ->where('product_variant_groups.product_variant_group_visibility', 'Visible');
                        });
                        $q->orWhere(function ($q2) {
                            $q2->whereNotNull('pvgd.product_variant_group_visibility')
                                ->where('pvgd.product_variant_group_visibility', 'Visible');
                        });
                    });

        if (!isset($post['id_product']) || isset($post['page'])) {
            $data = $data->paginate(10)->toArray();
        } else {
            $data = $data->get()->toArray();
        }

        return MyHelper::checkGet($data);
    }

    public function productVariantGroupSoldOut(Request $request)
    {
        $post = $request->all();
        $id_outlet = $post['id_outlet'] ?? $request->user()->id_outlet;

        if (!$id_outlet) {
            return response()->json(['status' => 'fail', 'messages' => 'Outlet ID is required']);
        }

        if (!isset($post['id_product']) && empty($post['id_product'])) {
            return response()->json(['status' => 'fail', 'messages' => 'Product ID is required']);
        }

        if (isset($post['data_stock']) && !empty($post['data_stock'])) {
            foreach ($post['data_stock'] as $dt) {
                if ($dt['product_variant_group_stock_status'] == 1) {
                    $status = 'Available';
                } else {
                    $status = 'Sold Out';
                }

                $updateOrCreate = ProductVariantGroupDetail::updateOrCreate(['id_outlet' => $id_outlet, 'id_product_variant_group' => $dt['id_product_variant_group']], ['product_variant_group_stock_status' => $status]);
            }

            $outlet = Outlet::where('id_outlet', $id_outlet)->first();
            $basePrice = ProductVariantGroup::orderBy('product_variant_group_price', 'asc')->where('id_product', $post['id_product'])->first();
            ProductGlobalPrice::updateOrCreate(['id_product' => $post['id_product']], ['product_global_price' => $basePrice['product_variant_group_price']]);
            Product::refreshVariantTree($post['id_product'], $outlet);
            if ($outlet['outlet_different_price'] == 1) {
                $basePriceDiferrentOutlet = ProductVariantGroup::leftJoin('product_variant_group_special_prices as pgsp', 'pgsp.id_product_variant_group', 'product_variant_groups.id_product_variant_group')
                    ->orderBy('product_variant_group_price', 'asc')
                    ->select(DB::raw('(CASE
                        WHEN pgsp.product_variant_group_price is NOT NULL THEN pgsp.product_variant_group_price
                        ELSE product_variant_groups.product_variant_group_price END)  as product_variant_group_price'))
                    ->where('id_product', $post['id_product'])->where('id_outlet', $id_outlet)->first();
                if ($basePriceDiferrentOutlet) {
                    ProductSpecialPrice::updateOrCreate(['id_outlet' => $id_outlet, 'id_product' => $post['id_product']], ['product_special_price' => $basePriceDiferrentOutlet['product_variant_group_price']]);
                } else {
                    ProductSpecialPrice::updateOrCreate(['id_outlet' => $id_outlet, 'id_product' => $post['id_product']], ['product_special_price' => $basePrice['product_variant_group_price']]);
                }
            }

            return MyHelper::checkUpdate($updateOrCreate);
        } else {
            return response()->json(['status' => 'fail', 'messages' => 'Data for update is empty']);
        }
    }

    public function setTimezone($data, $time_zone_utc)
    {
        $default_time_zone_utc = 7;
        $time_diff = $time_zone_utc - $default_time_zone_utc;

        $data['open'] = date('H:i', strtotime('-' . $time_diff . ' hour', strtotime($data['open'])));
        $data['close'] = date('H:i', strtotime('-' . $time_diff . ' hour', strtotime($data['close'])));

        return $data;
    }

    public function cronDriverNotFound()
    {
        $log = MyHelper::logCron('Driver Not Found Reject Order');
        try {
            $endStatusWehelpyou = Wehelpyou::orderEndFailStatusId();
            // dd(date('Y-m-d H:i:s', strtotime('-30minutes')));
            // dd(date('Y-m-d'));
            $transactions = Transaction::select([
                    DB::raw('
        				CASE WHEN transaction_pickups.pickup_by = "Wehelpyou" 
        					THEN transaction_pickup_wehelpyous.updated_at
        					ELSE transaction_pickup_go_sends.updated_at
        				END AS updated_at,

        				CASE WHEN transaction_pickups.pickup_by = "Wehelpyou" 
        					THEN transaction_pickup_wehelpyous.stop_booking_at
        					ELSE transaction_pickup_go_sends.stop_booking_at
        				END AS stop_booking_at
        			'),
                    'order_id',
                    'transaction_receipt_number',
                    'transactions.id_transaction',
                    'id_outlet',
                    'transaction_date',
                    'transaction_pickups.pickup_by'
                ])->join('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                ->leftJoin('transaction_pickup_go_sends', 'transaction_pickup_go_sends.id_transaction_pickup', '=', 'transaction_pickups.id_transaction_pickup')
                ->leftJoin('transaction_pickup_wehelpyous', 'transaction_pickup_wehelpyous.id_transaction_pickup', '=', 'transaction_pickups.id_transaction_pickup')
                ->whereNull('transaction_pickups.reject_at')
                ->whereDate('transaction_date', date('Y-m-d'))
                ->where([
                    'transaction_payment_status' => 'Completed',
                ])
                ->where(function ($q) {
                    $q->whereNotNull('transaction_pickup_go_sends.stop_booking_at')
                        ->orWhereNotNull('transaction_pickup_wehelpyous.stop_booking_at');
                })
                ->where(function ($q) {
                    $q->where('transaction_pickup_go_sends.latest_status', '<>', 'rejected')
                    ->orWhere('transaction_pickup_wehelpyous.latest_status_id', '<>', '96');
                })
                ->where(function ($q) use ($endStatusWehelpyou) {
                    $q->whereIn('transaction_pickup_go_sends.latest_status', ['no_driver', 'rejected', 'cancelled'])
                    ->orWhereIn('transaction_pickup_wehelpyous.latest_status_id', $endStatusWehelpyou);
                })
                ->with('outlet')
                ->get();
            $processed = [
                'found' => $transactions->count(),
                'cancelled' => 0,
                'failed_cancel' => 0,
                'errors' => [],
            ];
            foreach ($transactions as $transaction) {
                if (date('Y-m-d H:i', time()) == date('Y-m-d H:i', strtotime($transaction['stop_booking_at']))) {
                    continue;
                }
                $difference = floor((time() - strtotime($transaction['stop_booking_at'])) / 60);
                if ($difference < 5) {
                    // kirim notifikasi
                    $dataNotif = [
                        'subject' => 'Order ' . $transaction['order_id'],
                        'string_body' => 'Dalam ' . ( 5 - $difference ) . ' menit, pesanan batal otomatis. Segera pilih tindakan.',
                        'type' => 'trx',
                        'id_reference' => $transaction['id_transaction'],
                        'id_transaction' => $transaction['id_transaction']
                    ];
                    $this->outletNotif($dataNotif, $transaction->id_outlet);
                } else {
                    // reject order
                    $params = [
                        'order_id' => $transaction['order_id'],
                        'reason'   => 'auto reject order by system [no driver]'
                    ];
                    // mocking request object and create fake request
                    $fake_request = new DetailOrder();
                    $fake_request->setJson(new \Symfony\Component\HttpFoundation\ParameterBag($params));
                    $fake_request->merge(['user' => $transaction->outlet]);
                    $fake_request->setUserResolver(function () use ($transaction) {
                        return $transaction->outlet;
                    });

                    $reject = $this->rejectOrder($fake_request);

                    if ($reject['status'] == 'success') {
                        $dataNotif = [
                            'subject' => 'Order Dibatalkan',
                            'string_body' => $transaction['order_id'] . ' - ' . $transaction['transaction_receipt_number'],
                            'type' => 'trx',
                            'id_reference' => $transaction['id_transaction'],
                            'id_transaction' => $transaction['id_transaction']
                        ];
                        $this->outletNotif($dataNotif, $transaction->id_outlet);
                        $processed['cancelled']++;
                    } else {
                        $processed['failed_cancel']++;
                        $processed['errors'][] = $reject['messages'] ?? 'Something went wrong';
                    }
                }
            }

            $log->success($processed);
            return $processed;
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
            return ['status' => 'fail', 'messages' => [$e->getMessage()]];
        }
    }

    public function cronNotReceived()
    {
        $log = MyHelper::logCron('Send Notif Order Not Received/Rejected');
        try {
            $trxs = Transaction::join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
                ->where('transaction_payment_status', 'Completed')
                ->whereDate('transaction_date', date('Y-m-d'))
                ->whereNull('receive_at')
                ->whereNull('reject_at')
                ->pluck('transactions.id_transaction');
            foreach ($trxs as $id_trx) {
                app($this->trx)->outletNotif($id_trx, true);
            }

            $processed = $trxs->count();
            $log->success($processed);
            return $processed;
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
            return ['status' => 'fail', 'messages' => [$e->getMessage()]];
        }
    }

    public function mergeBundlingProducts($items)
    {
        $new_items = [];
        $item_qtys = [];
        $id_custom = [];

        // create unique array
        foreach ($items as $item) {
            $new_item = [
                'product_name' => $item['product_name'],
                'product_note' => $item['product_note'],
                'product_variants' => array_map("unserialize", array_unique(array_map("serialize", array_map(function ($i) {
                    return [
                        'id_product_variant' => $i['id_product_variant'],
                        'product_variant_name' => $i['product_variant_name']
                    ];
                }, $item['product_variants'] ?? [])))),
                'product_modifiers' => array_map(function ($i) {
                    return [
                        "id_product_modifier" => $i['id_product_modifier'],
                        "product_modifier_name" => $i['product_modifier_name']
                    ];
                }, $item['product_modifiers'] ?? []),
            ];
            usort($new_item['product_modifiers'], function ($a, $b) {
                return $a['id_product_modifier'] <=> $b['id_product_modifier'];
            });
            $pos = array_search($new_item, $new_items);
            if ($pos === false) {
                $new_items[] = $new_item;
                $item_qtys[] = $item['transaction_product_qty'];
            } else {
                $item_qtys[$pos] += $item['transaction_product_qty'];
            }
        }
        // update qty
        foreach ($new_items as $key => &$value) {
            $value['transaction_product_qty'] = $item_qtys[$key];
        }

        return $new_items;
    }

    public function listProductPlastic(Request $request)
    {
        $outlet  = $request->user();

        if ($outlet['plastic_used_status'] == 'Inactive') {
            return response()->json(['status' => 'fail', 'messages' => "This outlet don't use plastic"]);
        }

        $plastic_type = PlasticTypeOutlet::join('plastic_type', 'plastic_type.id_plastic_type', 'plastic_type_outlet.id_plastic_type')
                ->groupBy('plastic_type_outlet.id_plastic_type')
                ->where('id_outlet', $outlet['id_outlet'])->orderBy('plastic_type_order', 'asc')->first();
        $plastics = 0;
        $data = [];
        if ($plastic_type['id_plastic_type'] ?? null) {
            $plastics = Product::where('product_type', 'plastic')
                ->leftJoin('product_detail', function ($join) use ($outlet) {
                    $join->on('products.id_product', 'product_detail.id_product')
                        ->where('product_detail.id_outlet', $outlet['id_outlet']);
                })
                ->where(function ($sub) use ($outlet) {
                    $sub->whereNull('product_detail.id_outlet')
                        ->orWhere('product_detail.id_outlet', $outlet['id_outlet']);
                })
                ->where('id_plastic_type', $plastic_type['id_plastic_type'])
                ->where('product_visibility', 'Visible')->select(
                    'products.id_product',
                    'products.product_code',
                    'products.product_name',
                    DB::raw('(CASE WHEN product_detail.product_detail_stock_status is NULL THEN "Available"
                        ELSE product_detail.product_detail_stock_status END) as product_stock_status')
                )->count();

            $data = [[
                'id_plastic_type' => $plastic_type['id_plastic_type'],
                'plastic_type_name' => $plastic_type['plastic_type_name'],
                'total_item' => $plastics
            ]];
        }

        if (!empty($data)) {
            $data = [
                'plastic_name' => 'Kantong Belanja',
                'list' => $data
            ];
        }
        return response()->json(MyHelper::checkGet($data));
    }

    public function detailProductPlastic(Request $request)
    {
        $outlet  = $request->user();
        $post = $request->all();

        if ($outlet['plastic_used_status'] == 'Inactive') {
            return response()->json(['status' => 'fail', 'messages' => "This outlet don't use plastic"]);
        }

        if (!isset($post['id_plastic_type'])) {
            return response()->json(['status' => 'fail', 'messages' => "ID can not be empty"]);
        }

        $plastics = Product::where('product_type', 'plastic')
            ->leftJoin('product_detail', function ($join) use ($outlet) {
                $join->on('products.id_product', 'product_detail.id_product')
                    ->where('product_detail.id_outlet', $outlet['id_outlet']);
            })
            ->where(function ($sub) use ($outlet) {
                $sub->whereNull('product_detail.id_outlet')
                    ->orWhere('product_detail.id_outlet', $outlet['id_outlet']);
            })
            ->where('id_plastic_type', $post['id_plastic_type'])
            ->where('product_visibility', 'Visible')->select(
                'products.id_product',
                'products.product_code',
                'products.product_name',
                DB::raw('(CASE WHEN product_detail.product_detail_stock_status is NULL THEN "Available"
                        ELSE product_detail.product_detail_stock_status END) as product_stock_status')
            );

        if (isset($post['page'])) {
            $plastics = $plastics->paginate(10)->toArray();
        } else {
            $plastics = $plastics->get()->toArray();
        }

        return response()->json(MyHelper::checkGet($plastics));
    }

    public function productPlasticSoldOut(Request $request)
    {
        $post = $request->all();
        $id_outlet = $post['id_outlet'] ?? $request->user()->id_outlet;

        if (!$id_outlet) {
            return response()->json(['status' => 'fail', 'messages' => 'Outlet ID is required']);
        }

        if (empty($post['available']) && empty($post['sold_out'])) {
            return response()->json(['status' => 'fail', 'messages' => 'Data for update is empty']);
        }

        if (isset($post['available']) && !empty($post['available'])) {
            $dataAvailable = array_unique($post['available']);
            foreach ($dataAvailable as $id_product) {
                if ($id_product) {
                    $status = 'Available';
                    $updateOrCreate = ProductDetail::updateOrCreate(['id_outlet' => $id_outlet, 'id_product' => $id_product], ['product_detail_stock_status' => $status]);
                }
            }
        }

        if (isset($post['sold_out']) && !empty($post['sold_out'])) {
            $dataSoldOut = array_unique($post['sold_out']);
            foreach ($dataSoldOut as $id_product) {
                if ($id_product) {
                    $status = 'Sold Out';
                    $updateOrCreate = ProductDetail::updateOrCreate(['id_outlet' => $id_outlet, 'id_product' => $id_product], ['product_detail_stock_status' => $status]);
                }
            }
        }

        return response()->json(MyHelper::checkUpdate($updateOrCreate));
    }

    public function listProductModifierGroup($request)
    {
        $outlet = $request->user();
        $outlet->load('brand_outlets');
        // modifiers
        $modifier_groups = ProductModifier::select(
            \DB::raw(
                'product_modifier_groups.id_product_modifier_group + 100000 as id_product, 
        					product_modifier_groups.product_modifier_group_name as product_name, 

        					product_modifiers.id_product_modifier + 100000 as id_product_variant_group, 
        					product_modifiers.code as product_variant_group_code, 
        					product_modifiers.text_detail_trx as product_variant_group_name, 
        					CASE WHEN product_modifier_stock_status IS NULL THEN "Available" ELSE product_modifier_stock_status END as product_stock_status'
            )
        )
        ->leftJoin('product_modifier_details', function ($join) use ($outlet) {
            $join->on('product_modifier_details.id_product_modifier', '=', 'product_modifiers.id_product_modifier')
                ->where('product_modifier_details.id_outlet', $outlet['id_outlet']);
        })
        ->where(function ($q) {
            $q->where('product_modifier_status', 'Active')->orWhereNull('product_modifier_status');
        })
        ->where('modifier_type', '=', 'Modifier Group')
        ->where(function ($query) {
            $query->where('product_modifier_details.product_modifier_visibility', '=', 'Visible')
                    ->orWhere(function ($q) {
                        $q->whereNull('product_modifier_details.product_modifier_visibility')
                        ->where('product_modifiers.product_modifier_visibility', 'Visible');
                    });
        })
        ->join('product_modifier_group_inventory_brands', function ($join) use ($outlet) {
            $join->on('product_modifier_group_inventory_brands.id_product_modifier_group', 'product_modifiers.id_product_modifier_group')
                ->whereIn('id_brand', $outlet->brand_outlets->pluck('id_brand'));
        })
        ->join('product_modifier_groups', function ($join) use ($outlet) {
            $join->on('product_modifier_groups.id_product_modifier_group', 'product_modifiers.id_product_modifier_group');
        })
        ->groupBy('product_modifiers.id_product_modifier');
        $modifier_groups = $modifier_groups->orderBy('text');

        if ($request->page) {
            $modifier_groups = $modifier_groups->paginate(30)->toArray();
            $data = $modifier_groups['data'];
        } else {
            $modifier_groups = $modifier_groups->get()->toArray();
            $data = $modifier_groups;
        }

        // build response
        $result = [];
        foreach ($data as $key => $val) {
            if (empty($result[$val['id_product']])) {
                $result[$val['id_product']] = [
                    'product_variant_status' => 1,
                    'id_product'            => $val['id_product'],
                    'product_code'          => $val['product_variant_group_code'],
                    'product_name'          => $val['product_name'],
                    'product_stock_status'  => 'Sold Out',
                    'product_variant_group' => []
                ];
            }

            $result[$val['id_product']]['product_variant_group'][] = [
                'id_product' => $val['id_product'],
                'id_product_variant_group' => $val['id_product_variant_group'],
                'product_variant_group_code' => $val['product_variant_group_code'],
                'product_variant_group_name' => $val['product_variant_group_name'],
                'product_variant_group_stock_status' => $val['product_stock_status']
            ];

            if ($val['product_stock_status'] == 'Available') {
                $result[$val['id_product']]['product_stock_status'] = 'Available';
            }
        }

        $result = array_values($result);
        if ($request->page) {
            if (empty($result)) {
                return MyHelper::checkGet($result);
            }
            $modifier_groups['data'] = $result;
            $result = $modifier_groups;
        }

        return MyHelper::checkGet($result);
    }

    public function modifierGroupSoldOut($request)
    {
        $post        = $request->json()->all();
        $outlet      = $request->user();
        $user_outlet = $request->user_outlet;
        $otp         = $request->outlet_app_otps;
        $updated     = 0;
        $date_time   = date('Y-m-d H:i:s');

        if (isset($request->variants) && !empty($request->variants)) {
            $outlet = Outlet::where('id_outlet', $outlet['id_outlet'])->first();
            foreach ($request->variants as $m) {
                if (isset($m['available']) && !empty($m['available'])) {
                    $m['available'] = array_map(function ($val) {
                        return $val - 100000;
                    }, array_unique($m['available']));
                    $found = ProductModifierDetail::where('id_outlet', $outlet['id_outlet'])
                            ->whereIn('id_product_modifier', $m['available'])
                            ->where('product_modifier_stock_status', '<>', 'Available');

                    $x = $found->get()->toArray();
                    foreach ($x as $product) {
                        $create = ProductModifierStockStatusUpdate::create([
                            'id_product_modifier'   => $product['id_product_modifier'],
                            'id_user'               => $user_outlet['id_user'],
                            'user_type'             => $user_outlet['user_type'],
                            'user_name'             => $user_outlet['name'],
                            'user_email'            => $user_outlet['email'],
                            'id_outlet'             => $outlet->id_outlet,
                            'date_time'             => $date_time,
                            'new_status'            => 'Available',
                            'id_outlet_app_otp'     => null,
                        ]);
                    }
                    $updated += $found->update(['product_modifier_stock_status' => 'Available']);

                    //create detail product
                    $newDetail = ProductModifierDetail::where('id_outlet', $outlet['id_outlet'])
                        ->whereIn('id_product_modifier', $m['available'])->select('id_product_modifier')->get();

                    if (count($newDetail) > 0) {
                        $newDetail = $newDetail->pluck('id_product_modifier')->toArray();
                        $diff = array_diff($m['available'], $newDetail);
                    } else {
                        //all product need to be created in product_detail
                        $diff = $m['available'];
                    }

                    if (count($diff) > 0) {
                        $insert = [];
                        $insertStatus = [];
                        foreach ($diff as $idProd) {
                            if ($idProd != 0) {
                                $insert[] = [
                                    'id_product_modifier' => $idProd,
                                    'id_outlet'  => $outlet['id_outlet'],
                                    'product_modifier_stock_status' => 'Available',
                                    'product_modifier_visibility' => null,
                                    'product_modifier_status' => 'Active',
                                    'created_at' => $date_time,
                                    'updated_at' => $date_time
                                ];

                                $insertStatus = [
                                    'id_product_modifier'        => $idProd,
                                    'id_user'           => $user_outlet['id_user'],
                                    'user_type'         => $user_outlet['user_type'],
                                    'user_name'         => $user_outlet['name'],
                                    'user_email'        => $user_outlet['email'],
                                    'id_outlet'         => $outlet->id_outlet,
                                    'date_time'         => $date_time,
                                    'new_status'        => 'Available',
                                    'id_outlet_app_otp' => null,
                                ];
                            }
                        }
                        $createDetail = ProductModifierDetail::insert($insert);
                        $createStatus = ProductModifierStockStatusUpdate::insert($insertStatus);
                        $updated += $createDetail;
                    }
                }

                if (isset($m['sold_out']) && !empty($m['sold_out'])) {
                    $m['sold_out'] = array_map(function ($val) {
                        return $val - 100000;
                    }, array_unique($m['sold_out']));
                    $found = ProductModifierDetail::where('id_outlet', $outlet['id_outlet'])
                        ->whereIn('id_product_modifier', $m['sold_out'])
                        ->where('product_modifier_stock_status', '<>', 'Sold Out');
                    $x = $found->get()->toArray();
                    foreach ($x as $product) {
                        $create = ProductModifierStockStatusUpdate::create([
                            'id_product_modifier'   => $product['id_product_modifier'],
                            'id_user'               => $user_outlet['id_user'],
                            'user_type'             => $user_outlet['user_type'],
                            'user_name'             => $user_outlet['name'],
                            'user_email'            => $user_outlet['email'],
                            'id_outlet'             => $outlet->id_outlet,
                            'date_time'             => $date_time,
                            'new_status'            => 'Sold Out',
                            'id_outlet_app_otp'     => null
                        ]);
                    }
                    $updated += $found->update(['product_modifier_stock_status' => 'Sold Out']);

                    //create detail product
                    $newDetail = ProductModifierDetail::where('id_outlet', $outlet['id_outlet'])
                        ->whereIn('id_product_modifier', $m['sold_out'])->select('id_product_modifier')->get();

                    if (count($newDetail) > 0) {
                        $newDetail = $newDetail->pluck('id_product_modifier')->toArray();
                        $diff = array_diff($m['sold_out'], $newDetail);
                    } else {
                        //all product need to be created in product_detail
                        $diff = $m['sold_out'];
                    }
                    if (count($diff) > 0) {
                        $insert = [];
                        $insertStatus = [];
                        foreach ($diff as $idProd) {
                            if ($idProd != 0) {
                                $insert[] = [
                                    'id_product_modifier' => $idProd,
                                    'id_outlet'  => $outlet['id_outlet'],
                                    'product_modifier_stock_status' => 'Sold Out',
                                    'product_modifier_visibility' => null,
                                    'product_modifier_status' => 'Active',
                                    'created_at' => $date_time,
                                    'updated_at' => $date_time
                                ];
                                $insertStatus[] = [
                                    'id_product_modifier' => $idProd,
                                    'id_user'           => $user_outlet['id_user'],
                                    'user_type'         => $user_outlet['user_type'],
                                    'user_name'         => $user_outlet['name'],
                                    'user_email'        => $user_outlet['email'],
                                    'id_outlet'         => $outlet->id_outlet,
                                    'date_time'         => $date_time,
                                    'new_status'        => 'Sold Out',
                                    'id_outlet_app_otp' => null
                                ];
                            }
                        }
                        $createDetail = ProductModifierDetail::insert($insert);
                        $createStatus = ProductModifierStockStatusUpdate::insert($insertStatus);
                        $updated += $createDetail;
                    }
                }
            }
        }

        return [
            'status' => 'success',
            'result' => ['updated' => $updated]
        ];
    }

    public function insertUserCashback($trx)
    {
        if ($trx->cashback_insert_status != 1) {
            //send notif to customer
            $user = User::find($trx->id_user);

            $newTrx = Transaction::with(
                'user.memberships',
                'outlet',
                'productTransaction',
                'transaction_vouchers',
                'promo_campaign_promo_code',
                'promo_campaign_promo_code.promo_campaign'
            )
                    ->where('id_transaction', $trx->id_transaction)
                    ->first();

            $checkType = TransactionMultiplePayment::where('id_transaction', $trx->id_transaction)->get()->toArray();
            $column    = array_column($checkType, 'type');

            $use_referral = optional(optional($newTrx->promo_campaign_promo_code)->promo_campaign)->promo_type == 'Referral';
            \App\Jobs\UpdateQuestProgressJob::dispatch($trx->id_transaction)->onConnection('quest');
            \Modules\OutletApp\Jobs\AchievementCheck::dispatch(['id_transaction' => $trx->id_transaction, 'phone' => $user['phone']])->onConnection('achievement');

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
                    if (!$savePoint) {
                        return [
                            'status'   => 'fail',
                            'messages' => ['Transaction failed'],
                        ];
                    }
                }
            }

            $newTrx->update(['cashback_insert_status' => 1]);
            $checkMembership = app($this->membership)->calculateMembership($user['phone']);
        }

        return ['status' => 'success'];
    }
}
