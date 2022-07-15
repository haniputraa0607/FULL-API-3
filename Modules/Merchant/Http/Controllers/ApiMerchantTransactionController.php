<?php

namespace Modules\Merchant\Http\Controllers;

use App\Http\Models\City;
use App\Http\Models\LogBalance;
use App\Http\Models\MonthlyReportTrx;
use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\ProductPhoto;
use App\Http\Models\Province;
use App\Http\Models\Subdistricts;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\TransactionProduct;
use App\Http\Models\User;
use App\Lib\Shipper;
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
use Modules\ProductVariant\Entities\ProductVariant;
use Modules\Merchant\Entities\Merchant;
use Modules\Merchant\Entities\MerchantLogBalance;
use Modules\Merchant\Http\Requests\MerchantCreateStep1;
use Modules\Merchant\Http\Requests\MerchantCreateStep2;
use Modules\Outlet\Entities\DeliveryOutlet;
use DB;
use App\Http\Models\Transaction;
use Modules\ProductVariant\Entities\ProductVariantPivot;
use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\Transaction\Entities\TransactionGroup;
use Modules\Transaction\Entities\TransactionShipmentTrackingUpdate;
use Modules\Transaction\Http\Requests\TransactionDetail;
use Modules\UserRating\Entities\UserRating;
use Modules\UserRating\Http\Controllers\ApiUserRatingController;
use Modules\Xendit\Entities\TransactionPaymentXendit;

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
            $ratings = [];

            if($value['transaction_status'] == 'Completed' && $value['show_rate_popup'] == 1){
                $transactions['data'][$key]['transaction_status_code'] = $codeIndo['Unreview']['code']??'';
                $transactions['data'][$key]['transaction_status_text'] = $codeIndo['Unreview']['text']??'';

                $getRatings = UserRating::where('id_transaction', $value['id_transaction'])->get()->toArray();
                foreach ($getRatings as $rating){
                    $currentOption = explode(',', $rating['option_value']);
                    $ratings[] = [
                        "rating_value" => $rating['rating_value'],
                        "suggestion" => $rating['suggestion'],
                        "option_value" => $currentOption
                    ];
                }
            }

            $transactions['data'][$key]['ratings'] = $ratings;
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
                'need_recipe_status' =>  $value['transaction_product_recipe_status'],
                'product_total_price' => 'Rp '. number_format((int)$value['transaction_product_subtotal'],0,",","."),
                'discount_all' => (int)$value['transaction_product_discount_all'],
                'discount_all_text' => 'Rp '.number_format((int)$value['transaction_product_discount_all'],0,",","."),
                'discount_each_product' => (int)$value['transaction_product_base_discount'],
                'discount_each_product_text' => 'Rp '.number_format((int)$value['transaction_product_base_discount'],0,",","."),
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

        if(!empty($transaction['transaction_discount'])){
            $codePromo = PromoCampaignPromoCode::where('id_promo_campaign_promo_code', $transaction['id_promo_campaign_promo_code'])->first()['promo_code']??'';
            $paymentDetail[] = [
                'text' => 'Discount'.(!empty($transaction['transaction_discount_delivery'])? ' Biaya Kirim':'').(!empty($codePromo) ?' ('.$codePromo.')' : ''),
                'value' => 'Rp '. number_format((int)$transaction['transaction_discount'],0,",",".")
            ];
        }

        $trxPaymentMidtrans = TransactionPaymentMidtran::where('id_transaction_group', $transaction['id_transaction_group'])->first();
        $trxPaymentXendit = TransactionPaymentXendit::where('id_transaction_group', $transaction['id_transaction_group'])->first();

        if(!empty($trxPaymentMidtrans)){
            $paymentMethod = $trxPaymentMidtrans['payment_type'].(!empty($trxPaymentMidtrans['bank']) ? ' ('.$trxPaymentMidtrans['bank'].')':'');
        }elseif(!empty($trxPaymentXendit)){
            $paymentMethod = $trxPaymentXendit['type'];
        }

        $address = [
            'destination_name' => $transaction['destination_name'],
            'destination_phone' => $transaction['destination_phone'],
            'destination_address' => $transaction['destination_address'],
            'destination_description' => $transaction['destination_description'],
            'destination_province' => $transaction['province_name'],
            'destination_city' => $transaction['city_name'],
        ];

        $tracking = [];
        $trxTracking = TransactionShipmentTrackingUpdate::where('id_transaction', $id)->orderBy('tracking_date_time', 'desc')->orderBy('id_transaction_shipment_tracking_update', 'desc')->get()->toArray();
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
                'delivery_tracking' => $tracking,
                'pickup_code' => $transaction['shipment_pickup_code'],
                'pickup_time_start' => date('Y-m-d H:i:s', strtotime($transaction['shipment_pickup_time_start'])),
                'pickup_time_end' => date('Y-m-d H:i:s', strtotime($transaction['shipment_pickup_time_end']))
            ],
            'image_recipe' => (empty($transaction['image_recipe']) ? '': config('url.storage_url_api').$transaction['image_recipe']),
            'payment' => $paymentMethod??'',
            'payment_detail' => $paymentDetail,
            'point_receive' => (!empty($transaction['transaction_cashback_earned']) ? 'Mendapatkan +'.number_format((int)$transaction['transaction_cashback_earned'],0,",",".").' Points Dari Transaksi ini' : '')
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

        $description = Setting::where('key', 'delivery_request_description')->first()['value_text']??'';

        return response()->json(['status' => 'success', 'result' => [
            'address' => $address,
            'description' => $description
        ]]);
    }

    public function listTimePickupDelivery(Request $request){
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
            ->where('transactions.id_outlet', $idOutlet)
            ->where('transactions.id_transaction', $post['id_transaction'])
            ->where('transaction_status', 'On Progress')->first();
        if(empty($detail)){
            return response()->json(['status' => 'fail', 'messages' => ['Data order tidak ditemukan']]);
        }

        $timeZoneOutlet = City::join('provinces', 'provinces.id_province', 'cities.id_province')
                ->where('id_city', $detail['id_city'])->first()['time_zone_utc']??null;
        if(empty($timeZoneOutlet)){
            return response()->json(['status' => 'fail', 'messages' => ['Data timezone can not be empty']]);
        }

        $timeZone = [
            7 => 'Asia/Jakarta',
            8 => 'Asia/Makassar',
            9 => 'Asia/Jayapura'
        ];

        $dtRequestTimezone = $timeZone[$timeZoneOutlet];
        $shipper = new Shipper();
        $listTime = $shipper->sendRequest('Pickup Time List', 'GET', 'pickup/timeslot?time_zone='.$dtRequestTimezone, []);

        if(empty($listTime['response']['data']['time_slots'])){
            return response()->json(['status' => 'fail', 'messages' => ['Timeslot not available']]);
        }

        $res = $listTime['response']['data']['time_slots'];
        $result = [];
        foreach ($res as $value){
            $start = date('Y-m-d H:i:s', strtotime($value['start_time']));
            $end = date('Y-m-d H:i:s', strtotime($value['end_time']));

            $result[] = [
                "start_time" => $start,
                "end_time" => $end
            ];
        }

        return response()->json(MyHelper::checkGet($result));
    }


    public function confirmDeliveryTransaction(Request $request){
        $post = $request->json()->all();
        $idUser = $request->user()->id;
        $checkMerchant = Merchant::where('id_user', $idUser)->first();
        if(empty($checkMerchant)){
            return response()->json(['status' => 'fail', 'messages' => ['Data merchant tidak ditemukan']]);
        }
        $idOutlet = $checkMerchant['id_outlet'];

        if(empty($post['id_transaction']) || !isset($post['pickup_status'])){
            return response()->json(['status' => 'fail', 'messages' => ['ID transaction and pickup status can not be empty']]);
        }

        if($post['pickup_status'] == true && (empty($post['pickup_time_start']) || empty($post['pickup_time_end']))){
            return response()->json(['status' => 'fail', 'messages' => ['Pickup time can not be empty']]);
        }

        $detail = Transaction::join('transaction_shipments', 'transaction_shipments.id_transaction', 'transactions.id_transaction')
            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->leftJoin('cities', 'cities.id_city', 'outlets.id_city')
            ->leftJoin('provinces', 'provinces.id_province', 'cities.id_province')
            ->where('transactions.id_outlet', $idOutlet)
            ->where('transactions.id_transaction', $post['id_transaction'])
            ->where('transaction_status', 'On Progress')
            ->first();
        if(empty($detail)){
            return response()->json(['status' => 'fail', 'messages' => ['Data order tidak ditemukan']]);
        }

        if(!empty($detail['order_id']) && !empty($detail['shipment_pickup_code'])){
            return response()->json(['status' => 'fail', 'messages' => ['Sedang menunggu pickup']]);
        }

        $items = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                ->where('id_transaction', $detail['id_transaction'])
                ->get()->toArray();

        $products = [];
        foreach ($items as $value){
            $productName = $value['product_name'];
            if(!empty($value['id_product_variant_group'])){
                $variants = ProductVariant::join('product_variant_pivot', 'product_variant_pivot.id_product_variant', 'product_variants.id_product_variant')
                            ->where('id_product_variant_group', $value['id_product_variant_group'])->pluck('product_variant_name')->toArray();
                $productName = $productName.'('.implode(" ", $variants).')';
            }

            $products[] = [
                "name" => $productName,
                "price" => (int)$value['transaction_product_price'],
                "qty" => $value['transaction_product_qty']
            ];
        }

        $subdistrictOutlet = Subdistricts::where('id_subdistrict', $detail['depart_id_subdistrict'])
            ->join('districts', 'districts.id_district', 'subdistricts.id_district')->first();
        if(empty($subdistrictOutlet)){
            return response()->json(['status' => 'fail', 'messages' => ['Empty district outlet']]);
        }

        $shipper = new Shipper();
        if(empty($detail['order_id'])){
            $latOutlet = $subdistrictOutlet['subdistrict_latitude'];
            $lngOutlet = $subdistrictOutlet['subdistrict_longitude'];

            $subdistrictCustomer = Subdistricts::where('id_subdistrict', $detail['destination_id_subdistrict'])
                ->join('districts', 'districts.id_district', 'subdistricts.id_district')->first();
            if(empty($subdistrictCustomer)){
                return response()->json(['status' => 'fail', 'messages' => ['Empty district customer']]);
            }
            $latCustomer = $subdistrictCustomer['subdistrict_latitude'];
            $lngCustomer = $subdistrictCustomer['subdistrict_longitude'];

            $dtOrderShipment = [
                "external_id" => $detail['transaction_receipt_number'],
                "consignee" => [
                    "name" => $detail['destination_name'],
                    "phone_number" => substr_replace($detail['destination_phone'], '62', 0, 1)
                ],
                "consigner" => [
                    "name" => $detail['depart_name'],
                    "phone_number" => substr_replace($detail['depart_phone'], '62', 0, 1)
                ],
                "courier" => [
                    "cod" => false,
                    "rate_id" => $detail['shipment_rate_id'],
                    "use_insurance" => ($detail['shipment_insurance_use_status'] == 1 ? true : false)
                ],
                "coverage" => "domestic",
                "destination" => [
                    "address" => $detail['destination_address'],
                    "area_id" => $subdistrictCustomer['id_subdistrict_external'],
                    "lat" => $latCustomer,
                    "lng" => $lngCustomer
                ],
                "origin" => [
                    "address" => $detail['depart_address'],
                    "area_id" => $subdistrictOutlet['id_subdistrict_external'],
                    "lat" => $latOutlet,
                    "lng" => $lngOutlet
                ],
                "package" => [
                    "height" => $detail['shipment_total_height'],
                    "width" => $detail['shipment_total_width'],
                    "length" => $detail['shipment_total_length'],
                    "weight" => $detail['shipment_total_weight'],
                    "items" => $products,
                    "price" => $detail['transaction_subtotal'],
                    "package_type" => (int)Setting::where('key', 'default_package_type_delivery')->first()['value']??3
                ],
                "payment_type" => "postpay"
            ];

            $orderDelivery = $shipper->sendRequest('Order', 'POST', 'order', $dtOrderShipment);
            if(empty($orderDelivery['response']['data']['order_id'])){
                return response()->json(['status' => 'fail', 'messages' => ['Failed request to third party']]);
            }

            $devOrder = $orderDelivery['response']['data'];
            $orderID = $devOrder['order_id'];
            TransactionShipment::where('id_transaction', $detail['id_transaction'])
                ->update([
                    'order_id' => $orderID
                ]);
        }else{
            $orderID = $detail['order_id'];
        }

        if($post['pickup_status'] == true){
            //pickup request
            $timeZoneOutlet = City::join('provinces', 'provinces.id_province', 'cities.id_province')
                    ->where('id_city', $detail['id_city'])->first()['time_zone_utc']??null;
            if(empty($timeZoneOutlet)){
                return response()->json(['status' => 'fail', 'messages' => ['Data timezone can not be empty']]);
            }

            $timeZone = [
                7 => 'Asia/Jakarta',
                8 => 'Asia/Makassar',
                9 => 'Asia/Jayapura'
            ];
            $dtPickupShipment = [
                "data" => [
                    "order_activation" => [
                        "order_id" => [
                            $orderID
                        ],
                        "timezone" => $timeZone[$timeZoneOutlet],
                        "start_time" => date("c", strtotime($post['pickup_time_start'])),
                        "end_time" => date("c", strtotime($post['pickup_time_end']))
                    ]
                ]
            ];

            $pickupDelivery = $shipper->sendRequest('Request Pickup', 'POST', 'pickup/timeslot', $dtPickupShipment);
            if(empty($pickupDelivery['response']['data']['order_activations'][0]['pickup_code'])){
                return response()->json(['status' => 'fail', 'messages' => ['Failed request pickup to third party']]);
            }
            $devPickup = $pickupDelivery['response']['data'];

            $pickupCode = $devPickup['order_activations'][0]['pickup_code'];
            $update = TransactionShipment::where('id_transaction', $detail['id_transaction'])
                ->update([
                    'shipment_pickup_time_start' => date('Y-m-d H:i:s', strtotime($post['pickup_time_start'])),
                    'shipment_pickup_time_end' => date('Y-m-d H:i:s', strtotime($post['pickup_time_end'])),
                    'shipment_pickup_code' => $pickupCode
                ]);
        }

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
            'id_district' => $subdistrictOutlet['id_district'],
            'district_name' => $subdistrictOutlet['district_name'],
            'id_subdistrict' => $subdistrictOutlet['id_subdistrict'],
            'subdistrict_name' => $subdistrictOutlet['subdistrict_name'],
            'address' => $detail['outlet_address'],
            'postal_code' => $detail['outlet_postal_code']
        ];

        if($update ?? true){
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
        $trxTracking = TransactionShipmentTrackingUpdate::where('id_transaction', $id)->orderBy('tracking_date_time', 'desc')->orderBy('id_transaction_shipment_tracking_update', 'desc')->get()->toArray();
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
            'merchant_balance_source'        => $data['source'],
            'created_at'                     => date('Y-m-d H:i:s'),
            'updated_at'                     => date('Y-m-d H:i:s')
        ];

        if($balance_nominal < 0){
            $create = MerchantLogBalance::updateOrCreate($LogBalance);
        }else{
            $create = MerchantLogBalance::updateOrCreate(['id_merchant' => $data['id_merchant'], 'merchant_balance_id_reference' => $data['id_transaction'], 'merchant_balance_source' => $data['source']], $LogBalance);
        }


        return $create;
    }
}
