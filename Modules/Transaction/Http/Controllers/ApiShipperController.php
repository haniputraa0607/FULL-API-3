<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\LogApiGosend;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionPickupGoSend;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionShipment;
use App\Http\Models\User;
use App\Lib\GoSend;
use App\Lib\Shipper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;

use DB;
use App\Http\Models\Product;
use Modules\Merchant\Entities\Merchant;
use Modules\Transaction\Entities\LogShipper;
use Modules\Transaction\Entities\TransactionShipmentTrackingUpdate;
use Modules\UserRating\Entities\UserRatingLog;

class ApiShipperController extends Controller
{
    public function updateTrackingTransaction(){
        $getTransaction = Transaction::join('transaction_shipments', 'transaction_shipments.id_transaction', 'transactions.id_transaction')
            ->whereIn('transaction_status', ['On Delivery'])
            ->whereNotNull('transaction_shipments.order_id')
            ->select('transaction_shipments.*')->get()->toArray();

        foreach ($getTransaction as $data){
            $shipper = new Shipper();
            if(strtolower(env('APP_ENV')) == 'production'){
                $getDetail = $shipper->sendRequest('Get Order Detail', 'GET', 'order/'.$data['order_id'], []);
            }else{
                $getDetail = '{"status":"success","response":{"metadata":{"path":"/v3/order/226DGQX6PVXQZ?%3Aorder_id=226DGQX6PVXQZ&","http_status_code":200,"http_status":"OK","timestamp":1655092153},"data":{"consignee":{"name":"Kimi","phone_number":"62811223344","email":""},"consigner":{"name":"Alkaline","phone_number":"628108080806","email":"invalid.email+p628108080806@shipper.id"},"origin":{"id":4492153,"stop_id":50,"address":"Jl. huru hara no 98 RT 011 RW 0202","direction":"","postcode":"12940","area_id":4711,"area_name":"Karet Kuningan","suburb_id":482,"suburb_name":"Setia Budi","city_id":41,"city_name":"Jakarta Selatan","province_id":6,"province_name":"DKI Jakarta","country_id":228,"country_name":"INDONESIA","lat":"-6.2197608","lng":"106.8266873","email_address":"","company_name":""},"destination":{"id":0,"stop_id":4327,"address":"Jl. muja muju no 101","direction":"","postcode":"12940","area_id":4711,"area_name":"Karet Kuningan","suburb_id":482,"suburb_name":"Setia Budi","city_id":41,"city_name":"Jakarta Selatan","province_id":6,"province_name":"DKI Jakarta","country_id":228,"country_name":"INDONESIA","lat":"-6.2197608","lng":"106.8266873","email_address":"","company_name":""},"external_id":"","order_id":"226DGQX6PVXQZ","courier":{"name":"JNE","rate_id":4,"rate_name":"CTC","amount":27000,"use_insurance":true,"insurance_amount":192,"cod":false,"min_day":1,"max_day":2},"package":{"weight":3,"length":2,"width":4,"height":4,"volume_weight":0.005333333333333333,"package_type":2,"items":[{"id":751464,"name":"Serum 10(Merah 500 ML)","price":10000,"qty":5},{"id":751465,"name":"Serum 10","price":9000,"qty":3}],"international":{"custom_declaration":{"additional_document":[],"document_number":"","tax_document":""},"description_item":"","destination_packet":"","item_type":"","made_in":"","quantity":0,"reason":"","unit":""}},"payment_type":"cash","driver":{"name":"","phone":"","vehicle_type":"","vehicle_number":""},"label_check_sum":"3836815c2c012134044ea98713c0eed6","creation_date":"2022-06-13T02:35:43Z","last_updated_date":"2022-06-13T02:37:36Z","awb_number":"","trackings":[{"shipper_status":{"code":1000,"name":"Paket sedang dipersiapkan","description":"Paket sedang dipersiapkan"},"logistic_status":{"code":99,"name":"Order Masuk ke sistem","description":"Data order sudah masuk ke sistem"},"created_date":"'.date('Y-m-d H:i:s').'"},{"shipper_status":{"code":1020,"name":"Sedang Dijemput","description":"Paket sedang dijemput driver kurir"},"created_date":"'.date('Y-m-d H:i:s').'"},{"shipper_status":{"code":1040,"name":"Paket Siap Dikirim","description":"Paket sudah siap dikirim "},"created_date":"'.date('Y-m-d H:i:s').'"},{"shipper_status":{"code":1180,"name":"Paket Dalam Perjalanan Bersama kurir","description":"Order Dalam Perjalanan dengan"},"created_date":"'.date('Y-m-d H:i:s').'"},{"shipper_status":{"code":2000,"name":"Paket Terkirim","description":"Paket sudah diterima"},"created_date":"'.date('Y-m-d H:i:s').'"}],"is_active":true,"is_hubless":false,"pickup_code":"P2206062FVA","pickup_time":"","shipment_status":{"name":"Order Masuk ke sistem","description":"Data order sudah masuk ke sistem","code":1,"updated_by":"SHIPPER_DRIVERSVC","updated_date":"2022-06-13T02:37:36Z","track_url":"","reason":"","created_date":"2022-06-13T02:35:43Z"},"proof_of_delivery":{"photo":"","signature":""}}}}';
                $getDetail = (array)json_decode($getDetail);
                $getDetail['response'] = (array)$getDetail['response'];
                $getDetail['response']['data'] = (array)$getDetail['response']['data'];
            }

            if(empty($getDetail['response']['data']['trackings'])){
                continue;
            }

            $trackings = $getDetail['response']['data']['trackings'];
            foreach ($trackings as $t){
                $t = (array)$t;
                $dtShipper = (array)$t['shipper_status'];
                if($dtShipper['code'] != 1000){
                    TransactionShipmentTrackingUpdate::updateOrCreate(
                        [
                            'tracking_code' => $dtShipper['code'],
                            'id_transaction' => $data['id_transaction']
                        ],
                        [
                            'id_transaction' => $data['id_transaction'],
                            'shipment_order_id' => $data['order_id'],
                            'tracking_code' => $dtShipper['code'],
                            'tracking_description' => (empty($dtShipper['description']) ? $dtShipper['name']: $dtShipper['description']),
                            'tracking_date_time' => date('Y-m-d H:i:s', strtotime($t['created_date'])),
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    if($dtShipper['code'] == 1040){
                        Transaction::where('id_transaction', $data['id_transaction'])->update(['transaction_status' => 'On Delivery']);
                    }

                    if(in_array($dtShipper['code'], [2000, 3000, 2010])){
                        $receiveat = (!empty($body['status_date']) ? date('Y-m-d H:i:s', strtotime($body['status_date'])) : date('Y-m-d H:i:s'));
                        TransactionShipment::where('id_transaction', $data['id_transaction'])->update(['receive_at' => $receiveat]);
                    }
                }
            }
        }

        return 'success';
    }

    public function updateStatuShipment(Request $request){
        $body = $request->json()->all();

        $transaction = Transaction::join('transaction_shipments', 'transactions.id_transaction', 'transaction_shipments.id_transaction')
                    ->where('transaction_receipt_number', $body['external_id'])->first();

        $dataLog= [
            'subject' => 'Webhook',
            'id_transaction' => $transaction['id_transaction'],
            'request' => json_encode($body),
            'request_url' => url()->current(),
            'response' => null
        ];
        LogShipper::create($dataLog);

        if (empty($transaction)) {
            DB::rollback();
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Transaction not found']
            ]);
        }

        if($transaction['transaction_status'] == 'Completed'){
            return response()->json(['status' => 'success']);
        }

        $shipper = $body['external_status'];
        if($shipper['code'] != 1000){
            TransactionShipmentTrackingUpdate::updateOrCreate(
                [
                    'tracking_code' => $shipper['code'],
                    'id_transaction' => $transaction['id_transaction']
                ],
                [
                    'id_transaction' => $transaction['id_transaction'],
                    'shipment_order_id' => $body['order_id'],
                    'tracking_code' => $shipper['code'],
                    'tracking_description' => (empty($shipper['description']) ? $shipper['name']: $shipper['description']),
                    'tracking_date_time' => date('Y-m-d H:i:s', strtotime($body['status_date'])),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            if(in_array($shipper['code'], [1040, 1041, 1042])){
                Transaction::where('id_transaction', $transaction['id_transaction'])->update(['transaction_status' => 'On Delivery']);
            }

            if(in_array($shipper['code'], [2000, 3000, 2010])){
                $receiveat = (!empty($body['status_date']) ? date('Y-m-d H:i:s', strtotime($body['status_date'])) : date('Y-m-d H:i:s'));
                TransactionShipment::where('id_transaction', $transaction['id_transaction'])->update(['receive_at' => $receiveat]);
            }
        }

        return response()->json(['status' => 'success']);
    }

    function completedTransaction($transaction){
        $updateCompleted = Transaction::where('id_transaction', $transaction['id_transaction'])->update(['transaction_status' => 'Completed', 'show_rate_popup' => '1']);

        if($updateCompleted){
            //insert balance merchant
            $merchant = Merchant::where('id_outlet', $transaction['id_outlet'])->first();
            $idMerchant = $merchant['id_merchant']??null;
            $nominal = $transaction['transaction_grandtotal'] - $transaction['transaction_shipment'] + $transaction['discount_charged_central'];
            $dt = [
                'id_merchant' => $idMerchant,
                'id_transaction' => $transaction['id_transaction'],
                'balance_nominal' => $nominal,
                'source' => 'Transaction Completed'
            ];
            app('Modules\Merchant\Http\Controllers\ApiMerchantTransactionController')->insertBalanceMerchant($dt);

            $trxProduct = TransactionProduct::where('id_transaction', $transaction['id_transaction'])->pluck('id_product')->toArray();

            foreach ($trxProduct as $id_product){
                $countBestSaller = Product::where('id_product', $id_product)->first()['product_count_transaction']??0;
                Product::where('id_product', $id_product)->update(['product_count_transaction' =>$countBestSaller + 1]);

                UserRatingLog::updateOrCreate([
                    'id_user' => $transaction['id_user'],
                    'id_transaction' => $transaction['id_transaction'],
                    'id_product' => $id_product
                ],[
                    'refuse_count' => 0,
                    'last_popup' => date('Y-m-d H:i:s', time() - MyHelper::setting('popup_min_interval', 'value', 900))
                ]);
            }

            $countTrxMerchant = $merchant['merchant_count_transaction']??0;
            Merchant::where('id_merchant', $idMerchant)->update(['merchant_count_transaction' => $countTrxMerchant+1]);
        }

        return true;
    }

    public function cronCompletedReceivedOrder(){
        $maxDay = Setting::where('key', 'transaction_maximum_date_auto_completed')->first()['value']??2;
        $maxDay = (int)$maxDay;
        $currentDate = date('Y-m-d H:i:s');
        $dateQuery = date('Y-m-d', strtotime($currentDate. ' - '.$maxDay.' days'));

        $getTransaction = Transaction::join('transaction_shipments', 'transaction_shipments.id_transaction', 'transactions.id_transaction')
            ->whereIn('transaction_status', ['On Delivery'])
            ->whereNotNull('transaction_shipments.order_id')
            ->whereNotNull('transaction_shipments.receive_at')
            ->whereDate('transaction_shipments.receive_at', '<=', $dateQuery)
            ->select('transactions.*')->get()->toArray();

        foreach ($getTransaction as $dt){
            $this->completedTransaction($dt);
        }

        return true;
    }
}
