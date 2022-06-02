<?php

namespace Modules\Merchant\Http\Controllers;

use App\Http\Models\LogBalance;
use App\Http\Models\MonthlyReportTrx;
use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\ProductPhoto;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\TransactionProduct;
use App\Http\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Setting;
use App\Lib\MyHelper;
use Modules\Balance\Http\Controllers\NewTopupController;
use Modules\Disburse\Entities\BankAccount;
use Modules\Disburse\Entities\BankAccountOutlet;
use Modules\Disburse\Entities\BankName;
use App\Http\Models\TransactionShipment;
use Modules\Merchant\Entities\Merchant;
use Modules\Merchant\Entities\MerchantLogBalance;
use Modules\Merchant\Http\Requests\MerchantCreateStep1;
use Modules\Merchant\Http\Requests\MerchantCreateStep2;
use Modules\Outlet\Entities\DeliveryOutlet;
use DB;
use App\Http\Models\Transaction;
use Modules\ProductVariant\Entities\ProductVariantPivot;
use Modules\Transaction\Entities\TransactionGroup;
use Modules\Transaction\Entities\TransactionShipmentTrackingUpdate;
use Modules\Transaction\Http\Requests\TransactionDetail;

class ApiMerchantTransactionController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->product_variant_group = "Modules\ProductVariant\Http\Controllers\ApiProductVariantGroupController";
        $this->online_trx = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->trx = "Modules\Transaction\Http\Controllers\ApiTransaction";
        $this->merchant = "Modules\Merchant\Http\Controllers\ApiMerchantController";
    }

    public function statusCount(Request $request){
        $idUser = $request->user()->id;
        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }

        $order_new = Transaction::where('id_outlet', $checkMerchant['id_outlet'])
                    ->where('transaction_status', 'Pending')->count();
        $order_onprogress = Transaction::where('id_outlet', $checkMerchant['id_outlet'])
            ->where('transaction_status', 'On Progress')->count();
        $order_ondelivery = Transaction::where('id_outlet', $checkMerchant['id_outlet'])
            ->where('transaction_status', 'On Delivery')->count();
        $order_completed = Transaction::where('id_outlet', $checkMerchant['id_outlet'])
            ->where('transaction_status', 'Completed')->count();
        $order_rejected = Transaction::where('id_outlet', $checkMerchant['id_outlet'])
            ->where('transaction_status', 'Rejected')->count();

        $result = [
            'new' => $order_new,
            'on_progress' => $order_onprogress,
            'on_delivery' => $order_ondelivery,
            'completed' => $order_completed,
            'rejected' => $order_rejected
        ];

        return response()->json(MyHelper::checkGet($result));
    }

    public function listTransaction(Request $request){
        $post = $request->json()->all();
        $idUser = $request->user()->id;
        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }
        $idOutlet = $checkMerchant['id_outlet'];
        $codeIndo = [
            'Reject' => [
                'code' => 1,
                'text' => 'Dibatalkan'
            ],
            'Unpaid' => [
                'code' => 2,
                'text' => 'Belum dibayar'
            ],
            'Pending' => [
                'code' => 3,
                'text' => 'Telah dibayar'
            ],
            'On Progress' => [
                'code' => 4,
                'text' => 'Diproses'
            ],
            'On Delivery' => [
                'code' => 5,
                'text' => 'Dikirim'
            ],
            'Completed' => [
                'code' => 6,
                'text' => 'Selesai'
            ],
            'Unreview' => [
                'code' => 7,
                'text' => 'Belum direview'
            ]
        ];

        $transactions = Transaction::leftJoin('transaction_shipments','transaction_shipments.id_transaction','=','transactions.id_transaction')
                        ->leftJoin('cities','transaction_shipments.destination_id_city','=','cities.id_city')
                        ->where('id_outlet', $idOutlet);

        if(!empty($post['status'])){
            $status = ($post['status'] == 'new' ? 'Pending': $post['status']);
            $transactions = $transactions->where('transaction_status', $status);
        }

        if(!empty($post['search_receipt_number'])){
            $transactions = $transactions->where('transaction_receipt_number', 'like', '%'.$post['search_receipt_number'].'%');
        }

        $transactions = $transactions->paginate($post['pagination_total_row']??10)->toArray();

        foreach ($transactions['data']??[] as $key=>$value){
            $product = TransactionProduct::where('id_transaction', $value['id_transaction'])
                ->join('products', 'products.id_product', 'transaction_products.id_product')->first();
            $variant = '';
            if(!empty($product['id_product_variant_group'])){
                $variant = ProductVariantPivot::join('product_variants', 'product_variants.id_product_variant', 'product_variant_pivot.id_product_variant')
                    ->where('id_product_variant_group', $product['id_product_variant_group'])->pluck('product_variant_name')->toArray();
                $variant = implode(', ', $variant);
            }

            $image = ProductPhoto::where('id_product', $product['id_product'])->orderBy('product_photo_order', 'asc')->first()['url_product_photo']??config('url.storage_url_api').'img/default.jpg';
            $transactions['data'][$key] = [
                'id_transaction' => $value['id_transaction'],
                'transaction_receipt_number' => $value['transaction_receipt_number'],
                'transaction_status_code' => $codeIndo[$value['transaction_status']]['code']??'',
                'transaction_status_text' => $codeIndo[$value['transaction_status']]['text']??'',
                'transaction_grandtotal' => $value['transaction_grandtotal'],
                'product_name' => $product['product_name'],
                'product_qty' => $product['transaction_product_qty'],
                'product_image' => (empty($image) ? config('url.storage_url_api').'img/default.jpg': $image),
                'product_variants' => $variant,
                'delivery_city' => $value['city_name'],
                'delivery_method' => strtoupper($value['shipment_courier']),
                'delivery_service' => ucfirst($value['shipment_courier_service']),
                'maximum_date_process' => (!empty($value['transaction_maximum_date_process'])? MyHelper::dateFormatInd($value['transaction_maximum_date_process'], false, false):''),
                'maximum_date_delivery' => (!empty($value['transaction_maximum_date_delivery'])? MyHelper::dateFormatInd($value['transaction_maximum_date_delivery'], false, false):''),
                'estimated_delivery' => '',
                'reject_at' => (!empty($value['transaction_reject_at'])? MyHelper::dateFormatInd($value['transaction_reject_at'], false, false):''),
                'reject_reason' => (!empty($value['transaction_reject_reason'])? $value['transaction_reject_reason']:''),
            ];

            if(!empty($status) && $status == 'completed'){
                $transactions['data'][$key]['transaction_status_code'] = $codeIndo['Unreview']['code']??'';
                $transactions['data'][$key]['transaction_status_text'] = $codeIndo['Unreview']['text']??'';
                $transactions['data'][$key]['rating_value'] = null;
                $transactions['data'][$key]['rating_description'] = null;
            }
        }
        return response()->json(MyHelper::checkGet($transactions));
    }

    public function detailTransaction(TransactionDetail $request){
        $idUser = $request->user()->id;
        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }
        $idOutlet = $checkMerchant['id_outlet'];

        if ($request->json('transaction_receipt_number') !== null) {
            $trx = Transaction::where(['transaction_receipt_number' => $request->json('transaction_receipt_number')])->first();
            if($trx) {
                $id = $trx->id_transaction;
            } else {
                return MyHelper::checkGet([]);
            }
        } else {
            $id = $request->json('id_transaction');
        }

        $codeIndo = [
            'Reject' => [
                'code' => 1,
                'text' => 'Ditolak'
            ],
            'Unpaid' => [
                'code' => 2,
                'text' => 'Belum dibayar'
            ],
            'Pending' => [
                'code' => 3,
                'text' => 'Telah dibayar'
            ],
            'On Progress' => [
                'code' => 4,
                'text' => 'Diproses'
            ],
            'On Delivery' => [
                'code' => 5,
                'text' => 'Dikirim'
            ],
            'Completed' => [
                'code' => 6,
                'text' => 'Selesai'
            ],
            'Unreview' => [
                'code' => 7,
                'text' => 'Belum direview'
            ]
        ];

        $transaction = Transaction::where(['transactions.id_transaction' => $id])
            ->leftJoin('transaction_shipments','transaction_shipments.id_transaction','=','transactions.id_transaction')
            ->leftJoin('cities','transaction_shipments.destination_id_city','=','cities.id_city')
            ->leftJoin('provinces','provinces.id_province','=','cities.id_province')->first();

        if(empty($transaction)){
            return response()->json(MyHelper::checkGet($transaction));
        }

        if($idOutlet != $transaction['id_outlet']){
            return MyHelper::checkGet([]);
        }

        $transactionProducts = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
            ->where('id_transaction', $id)
            ->with(['variants'=> function($query){
                $query->select('id_transaction_product','transaction_product_variants.id_product_variant','transaction_product_variants.id_product_variant','product_variants.product_variant_name', 'transaction_product_variant_price')
                    ->join('product_variants','product_variants.id_product_variant','=','transaction_product_variants.id_product_variant');
            }])
            ->select('transaction_products.*', 'products.product_name')->get()->toArray();

        $products = [];
        foreach ($transactionProducts as $value){
            $image = ProductPhoto::where('id_product', $value['id_product'])->orderBy('product_photo_order', 'asc')->first()['url_product_photo']??config('url.storage_url_api').'img/default.jpg';
            $products[] = [
                'id_product' => $value['id_product'],
                'product_name' => $value['product_name'],
                'product_qty' => $value['transaction_product_qty'],
                'product_total_price' => 'Rp '. number_format((int)$value['transaction_product_subtotal'],0,",","."),
                'note' => $value['transaction_product_note'],
                'variants' => implode(', ', array_column($value['variants'], 'product_variant_name')),
                'image' => $image
            ];
        }

        $paymentDetail = [
            [
                'text' => 'Subtotal',
                'value' => 'Rp '. number_format((int)$transaction['transaction_subtotal'],0,",",".")
            ],
            [
                'text' => 'Biaya Kirim',
                'value' => 'Rp '. number_format((int)$transaction['transaction_shipment'],0,",",".")
            ]
        ];

        $trxPayment = TransactionPaymentMidtran::where('id_transaction_group', $transaction['id_transaction_group'])->first();
        $paymentMethod = $trxPayment['payment_type'].(!empty($trxPayment['bank']) ? ' ('.$trxPayment['bank'].')':'');
        $address = [
            'destination_name' => $transaction['destination_name'],
            'destination_phone' => $transaction['destination_phone'],
            'destination_address' => $transaction['destination_address'],
            'destination_description' => $transaction['destination_description'],
            'destination_province' => $transaction['province_name'],
            'destination_city' => $transaction['city_name'],
        ];

        $tracking = [];
        $trxTracking = TransactionShipmentTrackingUpdate::where('id_transaction', $id)->orderBy('tracking_date_time', 'desc')->get()->toArray();
        foreach ($trxTracking as $value){
            $tracking[] = [
                'date' => MyHelper::dateFormatInd(date('Y-m-d H:i', strtotime($value['tracking_date_time'])), true),
                'description' => $value['tracking_description']
            ];
        }

        if($transaction['transaction_status'] == 'Completed'){
            $transaction['transaction_status'] = 'Unreview';
        }

        $result = [
            'id_transaction' => $id,
            'transaction_receipt_number' => $transaction['transaction_receipt_number'],
            'transaction_status_code' => $codeIndo[$transaction['transaction_status']]['code']??'',
            'transaction_status_text' => $codeIndo[$transaction['transaction_status']]['text']??'',
            'transaction_date' => MyHelper::dateFormatInd(date('Y-m-d H:i', strtotime($transaction['transaction_date'])), true),
            'transaction_products' => $products,
            'address' => $address,
            'transaction_grandtotal' => 'Rp '. number_format((int)$transaction['transaction_grandtotal'],0,",","."),
            'delivery' => [
                'delivery_id' => $transaction['order_id'],
                'delivery_method' => strtoupper($transaction['shipment_courier']),
                'delivery_service' => ucfirst($transaction['shipment_courier_service']),
                'delivery_price' => 'Rp '. number_format((int)$transaction['transaction_shipment'],0,",","."),
                'delivery_tracking' => $tracking
            ],
            'payment' => $paymentMethod,
            'payment_detail' => $paymentDetail,
            'point_receive' => 'Mendapatkan +'.number_format((int)$transaction['transaction_cashback_earned'],0,",",".").' Points Dari Transaksi ini'
        ];

        return response()->json(MyHelper::checkGet($result));
    }

    public function acceptTransaction(Request $request){
        $post = $request->json()->all();
        $idUser = $request->user()->id;
        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }
        $idOutlet = $checkMerchant['id_outlet'];

        if(empty($post['id_transaction']) || empty($post['maximum_date_delivery'])){
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted data']]);
        }

        $transaction = Transaction::where('id_outlet', $idOutlet)
                        ->where('id_transaction', $post['id_transaction'])
                        ->where('transaction_status', 'Pending')->with(['outlet', 'user'])->first();
        if(empty($transaction)){
            return response()->json(['status' => 'fail', 'messages' => ['Data order tidak ditemukan']]);
        }

        $update = Transaction::where('id_transaction', $transaction['id_transaction'])
                ->update(['transaction_status' => 'On Progress', 'transaction_maximum_date_delivery' => date('Y-m-d', strtotime($post['maximum_date_delivery']))]);

        if($update){
            TransactionShipmentTrackingUpdate::create([
                'id_transaction' => $transaction['id_transaction'],
                'tracking_description' => 'Paket sedang dikemas oleh penjual dan akan dikirim',
                'tracking_date_time' => date('Y-m-d H:i:s')
            ]);

            $user = User::where('id', $transaction['id_user'])->first();
            $outlet = Outlet::where('id_outlet', $transaction['id_outlet'])->first();
            app($this->autocrm)->SendAutoCRM('Order Accepted', $user['phone'], [
                "outlet_name"      => $outlet['outlet_name'],
                'id_transaction'   => $transaction['id_transaction'],
                "id_reference"     => $transaction['transaction_receipt_number'] . ',' . $transaction['id_outlet'],
                "transaction_date" => $transaction['transaction_date'],
                'receipt_number'   => $transaction['transaction_receipt_number'],
            ]);
        }

        return response()->json(MyHelper::checkUpdate($update));
    }

    public function rejectTransaction(Request $request){
        $post = $request->json()->all();
        $idUser = $request->user()->id;
        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }
        $idOutlet = $checkMerchant['id_outlet'];

        if(empty($post['id_transaction']) || empty($post['reject_reason'])){
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted data']]);
        }

        $transaction = Transaction::where('id_outlet', $idOutlet)
            ->where('id_transaction', $post['id_transaction'])
            ->where('transaction_status', 'Pending')->first();
        if(empty($transaction)){
            return response()->json(['status' => 'fail', 'messages' => ['Data order tidak ditemukan']]);
        }

        $reject = $transaction->triggerReject($post);

        if(!$reject){
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Reject transaction failed'],
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    public function requestDeliveryTransaction(Request $request){
        $post = $request->json()->all();
        $idUser = $request->user()->id;
        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }
        $idOutlet = $checkMerchant['id_outlet'];

        if(empty($post['id_transaction'])){
            return response()->json(['status' => 'fail', 'messages' => ['ID transaction can not be empty']]);
        }

        $detail = Transaction::join('transaction_shipments', 'transaction_shipments.id_transaction', 'transactions.id_transaction')
            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->leftJoin('cities', 'cities.id_city', 'outlets.id_city')
            ->leftJoin('provinces', 'provinces.id_province', 'cities.id_province')
            ->where('transactions.id_outlet', $idOutlet)
            ->where('transactions.id_transaction', $post['id_transaction'])
            ->where('transaction_status', 'On Progress')->first();
        if(empty($detail)){
            return response()->json(['status' => 'fail', 'messages' => ['Data order tidak ditemukan']]);
        }

        if(!empty($detail['order_id'])){
            return response()->json(['status' => 'fail', 'messages' => ['Sedang menunggu pickup']]);
        }

        $address = [
            'id_province' => $detail['id_province'],
            'province_name' => $detail['province_name'],
            'id_city' => $detail['id_city'],
            'city_name' => $detail['city_name'],
            'address' => $detail['outlet_address'],
            'postal_code' => $detail['outlet_postal_code']
        ];

        $datePickup = MyHelper::dateFormatInd(date('Y-m-d H:i:s', strtotime(date('Y-m-d '.'14:00:00'). ' + 2 days')));
        $pickUpDescription = 'Penjemputan akan dilakukan '.$datePickup.'';
        $description = Setting::where('key', 'delivery_request_description')->first()['value_text']??'';

        return response()->json(['status' => 'success', 'result' => [
            'address' => $address,
            'pickup_description' => $pickUpDescription,
            'description' => $description
        ]]);
    }

    public function confirmDeliveryTransaction(Request $request){
        $post = $request->json()->all();
        $idUser = $request->user()->id;
        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }
        $idOutlet = $checkMerchant['id_outlet'];

        if(empty($post['id_transaction'])){
            return response()->json(['status' => 'fail', 'messages' => ['ID transaction can not be empty']]);
        }

        $detail = Transaction::join('transaction_shipments', 'transaction_shipments.id_transaction', 'transactions.id_transaction')
            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->leftJoin('cities', 'cities.id_city', 'outlets.id_city')
            ->leftJoin('provinces', 'provinces.id_province', 'cities.id_province')
            ->where('transactions.id_outlet', $idOutlet)
            ->where('transactions.id_transaction', $post['id_transaction'])
            ->where('transaction_status', 'On Progress')->first();
        if(empty($detail)){
            return response()->json(['status' => 'fail', 'messages' => ['Data order tidak ditemukan']]);
        }

        if(!empty($detail['order_id'])){
            return response()->json(['status' => 'fail', 'messages' => ['Sedang menunggu pickup']]);
        }

        $orderID = 'D-'.MyHelper::createrandom(7,'Angka').$detail['id_outlet'].MyHelper::createrandom(5,'Angka');
        $update = TransactionShipment::where('id_transaction', $detail['id_transaction'])->update(['order_id' => $orderID]);
        $deliveryList = app($this->merchant)->availableDelivery($detail['id_outlet']);
        $deliveryName = '';
        $deliveryLogo = '';
        foreach ($deliveryList as $value){
            if($value['delivery_method'] == $detail['shipment_courier']){
                $deliveryName =  $value['delivery_name'];
                $deliveryLogo = $value['logo'];
                break;
            }
        }
        $address = [
            'id_province' => $detail['id_province'],
            'province_name' => $detail['province_name'],
            'id_city' => $detail['id_city'],
            'city_name' => $detail['city_name'],
            'address' => $detail['outlet_address'],
            'postal_code' => $detail['outlet_postal_code']
        ];

        if($update){
            TransactionShipmentTrackingUpdate::create([
                'id_transaction' => $detail['id_transaction'],
                'shipment_order_id' => $orderID,
                'tracking_description' => 'Menunggu paket di ambil oleh kurir',
                'tracking_date_time' => date('Y-m-d H:i:s')
            ]);

            return response()->json(['status' => 'success', 'result' => [
                'delivery_id' => $orderID,
                'delivery_name' => $deliveryName,
                'delivery_logo' => $deliveryLogo,
                'address' => $address
            ]]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Failed update']]);
        }
    }

    public function dummyUpdateStatusDelivery(Request $request){
        $post = $request->json()->all();

        if(empty($post['id_transaction']) || empty($post['status_code'])){
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted data']]);
        }

        $detail = Transaction::join('transaction_shipments', 'transaction_shipments.id_transaction', 'transactions.id_transaction')
            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->leftJoin('cities', 'cities.id_city', 'outlets.id_city')
            ->leftJoin('provinces', 'provinces.id_province', 'cities.id_province')
            ->where('transactions.id_transaction', $post['id_transaction'])->first();
        if(empty($detail)){
            return response()->json(['status' => 'fail', 'messages' => ['Data order tidak ditemukan']]);
        }

        $filterCode = [
            1 => 'Paket Anda sudah dibawa oleh kurir',
            2 => 'Paket Anda sedang dalam perjalanan',
            3 => 'Paket Anda sedang diantar kealamat tujuan',
            4 => 'Paket Anda sudah diterima oleh '.$detail['destination_name'],
            5 => 'Paket sudah diterima'
        ];

        if(empty($filterCode[$post['status_code']])){
            return response()->json(['status' => 'fail', 'messages' => ['Status code tidak ditemukan']]);
        }

        if($post['status_code'] == 1){
            Transaction::where('id_transaction', $detail['id_transaction'])->update(['transaction_status' => 'On Delivery']);
        }elseif($post['status_code'] == 5){
            $detail->triggerTransactionCompleted();
        }

        $update = TransactionShipmentTrackingUpdate::create([
            'id_transaction' => $detail['id_transaction'],
            'shipment_order_id' => $detail['order_id'],
            'tracking_description' => $filterCode[$post['status_code']],
            'tracking_date_time' => date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s'). ' + 2 minutes'))
        ]);

        return response()->json(MyHelper::checkUpdate($update));
    }

    public function deliveryTracking(Request $request){
        $idUser = $request->user()->id;
        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }
        $idOutlet = $checkMerchant['id_outlet'];

        if ($request->json('transaction_receipt_number') !== null) {
            $trx = Transaction::where(['transaction_receipt_number' => $request->json('transaction_receipt_number')])->first();
            if($trx) {
                $id = $trx->id_transaction;
            } else {
                return MyHelper::checkGet([]);
            }
        } else {
            $id = $request->json('id_transaction');
        }

        $codeIndo = [
            'Reject' => [
                'code' => 1,
                'text' => 'Ditolak'
            ],
            'Unpaid' => [
                'code' => 2,
                'text' => 'Belum dibayar'
            ],
            'Pending' => [
                'code' => 3,
                'text' => 'Telah dibayar'
            ],
            'On Progress' => [
                'code' => 4,
                'text' => 'Diproses'
            ],
            'On Delivery' => [
                'code' => 5,
                'text' => 'Dikirim'
            ],
            'Completed' => [
                'code' => 6,
                'text' => 'Selesai'
            ],
            'Unreview' => [
                'code' => 7,
                'text' => 'Belum direview'
            ]
        ];

        $transaction = Transaction::where(['transactions.id_transaction' => $id])
            ->leftJoin('transaction_shipments','transaction_shipments.id_transaction','=','transactions.id_transaction')
            ->leftJoin('cities','transaction_shipments.destination_id_city','=','cities.id_city')
            ->leftJoin('provinces','provinces.id_province','=','cities.id_province')->first();

        if(empty($transaction)){
            return response()->json(MyHelper::checkGet($transaction));
        }

        if($idOutlet != $transaction['id_outlet']){
            return MyHelper::checkGet([]);
        }

        $tracking = [];
        $trxTracking = TransactionShipmentTrackingUpdate::where('id_transaction', $id)->orderBy('tracking_date_time', 'desc')->get()->toArray();
        foreach ($trxTracking as $value){
            $tracking[] = [
                'date' => MyHelper::dateFormatInd(date('Y-m-d H:i', strtotime($value['tracking_date_time'])), true),
                'description' => $value['tracking_description']
            ];
        }

        $result = [
            'delivery_id' => $transaction['order_id'],
            'delivery_method' => strtoupper($transaction['shipment_courier']),
            'delivery_service' => ucfirst($transaction['shipment_courier_service']),
            'delivery_price' => 'Rp '. number_format((int)$transaction['transaction_shipment'],0,",","."),
            'delivery_tracking' => $tracking
        ];

        return response()->json(MyHelper::checkGet($result));
    }

    public function insertBalanceMerchant($data){
        $balance_nominal = $data['balance_nominal'];
        $balance_before = MerchantLogBalance::where('id_merchant', $data['id_merchant'])->sum('merchant_balance');
        $balance_after = $balance_before + $balance_nominal;
        $checkLog = MerchantLogBalance::where('merchant_balance_source', 'Completed Transaction')->where('merchant_balance_id_reference', $data['id_transaction'])->where('id_merchant', $data['id_merchant'])->first();
        if($checkLog){
            $balance_before = $checkLog->balance_before;
            if($balance_nominal == $checkLog->balance){
                $balance_after = $checkLog->balance_after;
            }else{
                $balance_after = $balance_before + $balance_nominal;
            }
        }

        $LogBalance = [
            'id_merchant'                    => $data['id_merchant'],
            'merchant_balance'               => $balance_nominal,
            'merchant_balance_before'        => $balance_before,
            'merchant_balance_after'         => $balance_after,
            'merchant_balance_id_reference'  => $data['id_transaction'],
            'merchant_balance_source'        => $data['source']
        ];

        $create = MerchantLogBalance::updateOrCreate(['id_merchant' => $data['id_merchant'], 'merchant_balance_id_reference' => $data['id_transaction'], 'merchant_balance_source' => $data['source']], $LogBalance);

        return $create;
    }
}
