<?php

namespace App\Lib;

use App\Http\Models\LogApiGosend;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionPickupGoSend;
use App\Http\Models\TransactionPickupGoSendUpdate;
use App\Http\Models\User;
use Modules\Autocrm\Entities\AutoresponseCodeList;
use App\Http\Models\Autocrm;

class GoSend
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public static function getShipmentMethod()
    {
        return Setting::select('value')->where('key', 'gosend_use_sameday')->pluck('value')->first() ? 'SameDay' : 'Instant';
    }

    public static function booking($origin, $destination, $item, $storeOrderId = "", $insurance = null)
    {
        if (env('GO_SEND_URL') == '' || env('GO_SEND_CLIENT_ID') == '' || env('GO_SEND_PASS_KEY') == '') {
            return [
                'status'   => 'fail',
                'messages' => ['GO-SEND key has not been set'],
            ];
        }

        $url = env('GO_SEND_URL') . 'gokilat/v10/booking';

        $header = [
            'Client-ID' => env('GO_SEND_CLIENT_ID'),
            'Pass-Key'  => env('GO_SEND_PASS_KEY'),
        ];

        $post['paymentType']         = 3;
        $post['deviceToken']         = "";
        $post['collection_location'] = "pickup";
        $post['shipment_method']     = self::getShipmentMethod();

        $post['routes'][0]['originName']         = "";
        $post['routes'][0]['originNote']         = $origin['note'];
        $post['routes'][0]['originContactName']  = 'JIWA+';
        $post['routes'][0]['originContactPhone'] = $origin['phone'];
        $post['routes'][0]['originLatLong']      = $origin['latitude'] . ',' . $origin['longitude'];
        $post['routes'][0]['originAddress']      = $origin['address'] . '. ' . $origin['note'];

        $post['routes'][0]['destinationName']         = "";
        $post['routes'][0]['destinationNote']         = "";
        $post['routes'][0]['destinationContactName']  = $destination['name'];
        $post['routes'][0]['destinationContactPhone'] = $destination['phone'];
        $post['routes'][0]['destinationLatLong']      = $destination['latitude'] . ',' . $destination['longitude'];
        $post['routes'][0]['destinationAddress']      = $destination['address'] . ', Note : ' . $destination['note'];

        $post['routes'][0]['item'] = $item;

        $post['routes'][0]['storeOrderId']     = $storeOrderId;
        $post['routes'][0]['insuranceDetails'] = $insurance;
        $token                                 = MyHelper::post($url, null, $post, 0, $header, $status_code, $response_header);
        try {
            LogApiGosend::create([
                'type'              => 'booking',
                'id_reference'      => $storeOrderId,
                'request_url'       => $url,
                'request_method'    => 'POST',
                'request_parameter' => json_encode($post),
                'request_header'    => json_encode($header),
                'response_body'     => json_encode($token),
                'response_header'   => json_encode($response_header),
                'response_code'     => $status_code,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed write log to LogApiGosend: ' . $e->getMessage());
        }
        if (!($token['id'] ?? false)) {
            $token['messages'] = array_merge(($response_header['Error-Message'] ?? []), ['Failed booking GO-SEND']);
        }
        return $token;
    }

    public static function getStatus($storeOrderId, $useOrderId = false)
    {
        if (env('GO_SEND_URL') == '' || env('GO_SEND_CLIENT_ID') == '' || env('GO_SEND_PASS_KEY') == '') {
            return [
                'status'   => 'fail',
                'messages' => ['GO-SEND key has not been set'],
            ];
        }

        $header = [
            'Client-ID' => env('GO_SEND_CLIENT_ID'),
            'Pass-Key'  => env('GO_SEND_PASS_KEY'),
        ];
        if (!$useOrderId) {
            // pakai orderno dulu soalnya kalau pakai storeOrderId sering internal server error
            $orderno = TransactionPickupGoSend::select('go_send_order_no')->join('transaction_pickups', 'transaction_pickups.id_transaction_pickup', '=', 'transaction_pickup_go_sends.id_transaction_pickup')->join('transactions', 'transactions.id_transaction', '=', 'transaction_pickups.id_transaction')->where('transaction_receipt_number', $storeOrderId)->pluck('go_send_order_no')->first();
            $url   = env('GO_SEND_URL') . 'gokilat/v10/booking/orderno/' . $orderno;
            // $url   = env('GO_SEND_URL') . 'gokilat/v10/booking/storeOrderId/' . $storeOrderId;
        } else {
            // storeOrderId is n=gosend order no
            $url   = env('GO_SEND_URL') . 'gokilat/v10/booking/orderno/' . $storeOrderId;
        }
        $token = MyHelper::get($url, null, $header, $status_code, $response_header);
        try {
            LogApiGosend::create([
                'type'              => 'get_status',
                'id_reference'      => $storeOrderId,
                'request_url'       => $url,
                'request_method'    => 'GET',
                'request_header'    => json_encode($header),
                'request_parameter' => null,
                'response_header'   => json_encode($response_header),
                'response_body'     => json_encode($token),
                'response_code'     => $status_code,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed write log to LogApiGosend: ' . $e->getMessage());
        }
        return $token;
    }

    public static function cancelOrder($orderNo, $id_reference)
    {
        if (env('GO_SEND_URL') == '' || env('GO_SEND_CLIENT_ID') == '' || env('GO_SEND_PASS_KEY') == '') {
            return [
                'status'   => 'fail',
                'messages' => ['GO-SEND key has not been set'],
            ];
        }

        $header = [
            'Client-ID' => env('GO_SEND_CLIENT_ID'),
            'Pass-Key'  => env('GO_SEND_PASS_KEY'),
        ];

        $url  = env('GO_SEND_URL') . 'gokilat/v10/booking/cancel';
        $post = [
            'orderNo' => $orderNo,
        ];
        $response = MyHelper::put($url, null, $post, 0, $header, $status_code, $response_header);
        try {
            LogApiGosend::create([
                'type'              => 'cancel',
                'id_reference'      => $id_reference,
                'request_url'       => $url,
                'request_method'    => 'PUT',
                'request_header'    => json_encode($header),
                'request_parameter' => json_encode($post),
                'response_header'   => json_encode($response_header),
                'response_body'     => json_encode($response),
                'response_code'     => $status_code,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed write log to LogApiGosend: ' . $e->getMessage());
        }
        if (!$response) {
            return [
                'status'   => 'fail',
                'messages' => $response_header['Error-Message'] ?? 'Something went wrong',
            ];
        }
        return $response;
    }

    public static function getPrice($origin, $destination)
    {
        if (env('GO_SEND_URL') == '' || env('GO_SEND_CLIENT_ID') == '' || env('GO_SEND_PASS_KEY') == '') {
            return [
                'status'   => 'fail',
                'messages' => ['GO-SEND key has not been set'],
            ];
        }

        $header = [
            'Client-ID' => env('GO_SEND_CLIENT_ID'),
            'Pass-Key'  => env('GO_SEND_PASS_KEY'),
        ];

        $url   = env('GO_SEND_URL') . 'gokilat/v10/calculate/price?origin=' . $origin['latitude'] . ',' . $origin['longitude'] . '8&destination=' . $destination['latitude'] . ',' . $destination['longitude'] . '&paymentType=3';
        $token = MyHelper::get($url, null, $header, $status_code, $response_header);

        try {
            LogApiGosend::create([
                'type'              => 'get_price',
                'id_reference'      => null,
                'request_url'       => $url,
                'request_method'    => 'GET',
                'request_header'    => json_encode($header),
                'request_parameter' => null,
                'response_header'   => json_encode($response_header),
                'response_body'     => json_encode($token),
                'response_code'     => $status_code,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed write log to LogApiGosend: ' . $e->getMessage());
        }

        return $token;
    }

    public static function checkKey()
    {
        if (env('GO_SEND_URL') == '' || env('GO_SEND_CLIENT_ID') == '' || env('GO_SEND_PASS_KEY') == '') {
            return [
                'status'   => 'fail',
                'messages' => ['GO-SEND key has not been set'],
            ];
        }
        return true;
    }
    /**
     * save shipment update to database
     * @param  Array $dataUpdate
     * [
     *         'id_transaction' => 21,
     *         'id_transaction_pickup_go_send' => 23,
     *         'status' => 'on_hold',
     *         'description' => 'Hujan deras'
     * ]
     * @return void
     */
    public static function saveUpdate($dataUpdate)
    {
        if (!$dataUpdate['status'] ?? false) {
            return false;
        }
        $found = TransactionPickupGoSendUpdate::where(['id_transaction_pickup_go_send' => $dataUpdate['id_transaction_pickup_go_send'], 'go_send_order_no' => $dataUpdate['go_send_order_no'], 'status' => $dataUpdate['status']])->first();
        $ref_status = [
            'confirmed' => 'Finding Driver',
            'allocated' => 'Driver Allocated',
            'out_for_pickup' => 'Enroute Pickup',
            'picked' => 'Item Picked by Driver',
            'out_for_delivery' => 'Enroute Drop',
            'cancelled' => 'Cancelled',
            'delivered' => 'Completed',
            'rejected' => 'Rejected',
            'no_driver' => 'Driver not found',
            'on_hold' => 'On Hold'
        ];

        $ref_status2 = array_flip($ref_status);

        if (!$found) {
            $trx_pickup = TransactionPickup::where('id_transaction', $dataUpdate['id_transaction'])->first();
            $trx = Transaction::where('id_transaction', $dataUpdate['id_transaction'])->first();

            $outlet_title = [
                'confirmed' => 'Info Pesanan Delivery',
                'allocated' => 'Driver ditemukan',
                'out_for_pickup' => 'Driver sedang menuju ke outlet',
                'picked' => 'Info Pesanan Delivery',
                'out_for_delivery' => 'Driver sedang menuju ke lokasi tujuan',
                'cancelled' => 'Pengantaran dibatalkan driver',
                'delivered' => 'Pengantaran ' . $trx_pickup->order_id . ' berhasil',
                'rejected' => 'Pengantaran ' . $trx_pickup->order_id . ' gagal',
                'no_driver' => 'Driver tidak ditemukan',
                'on_hold' => 'Info Pesanan Delivery'
            ];

            $outlet_message = [
                'confirmed' => 'Mencari Driver',
                'allocated' => 'Segera persiapkan pesanan',
                'out_for_pickup' => 'Apa pesanan sudah siap?',
                'picked' => 'Driver mengambil Pesanan',
                'out_for_delivery' => 'Pesanan ' . $trx_pickup->order_id . ' sudah siap?',
                'cancelled' => 'Segera ambil tindakan',
                'delivered' => 'Pessanan sudah diterima customer',
                'rejected' => 'Tunggu, pesanan akan segera kembalikan ke outlet',
                'no_driver' => 'Pilih tindakan selanjutnya',
                'on_hold' => 'Driver terkendala saat pengantaran'
            ];

            if ($dataUpdate['status'] == 'delivered') {
                $trx_pickup->update(['show_confirm' => '1']);
                $trx->update(['show_rate_popup' => '1']);
            }
            $outlet  = Outlet::where('id_outlet', $trx->id_outlet)->first();
            $user = User::where('id', $trx->id_user)->first();
            $phone = $user['phone'];
            $userName = $user['name'];
            $dataPush = [
                'type' => 'trx',
                'subject' => ($outlet_title[$dataUpdate['status']] ?? 'Info Pesanan Delivery'),
                'string_body' => ($outlet_message[$dataUpdate['status']] ?? $dataUpdate['status']),
                'status' => $dataUpdate['status'],
                'id_transaction' => $trx->id_transaction,
                'id_reference' => $trx->id_transaction,
                'order_id' => $trx_pickup->order_id
            ];
            app("Modules\OutletApp\Http\Controllers\ApiOutletApp")->outletNotif($dataPush, $outlet->id_outlet);

            $delivery_status = ($ref_status2[$dataUpdate['status']] ?? $dataUpdate['status']);

            $replacer = [
                'confirmed'             => 'Pesanan Diterima!',
                'out_for_pickup'        => 'Mohon menunggu ya!',
                'out_for_delivery'      => 'Sudah siap menikmati pesananmu?',
                'cancelled'             => 'Pesananmu tidak dapat diambil oleh driver',
                'delivered'             => 'Terima kasih sudah pesan di JIWA+!',
                'no_driver'             => 'Driver belum berhasil ditemukan',
            ];
            $replacer_content = [
                'confirmed'             => 'Mohon tunggu, pesanan sedang dipersiapkan',
                'out_for_pickup'        => 'Driver-mu sedang menuju ke outlet',
                'out_for_delivery'      => 'Driver sedang menuju ke tempatmu',
                'cancelled'             => 'Mohon tunggu konfirmasi dari outlet',
                'delivered'             => 'Selamat menikmati Kak %name%',
                'no_driver'             => 'Mohon tunggu konfirmasi dari outlet',
            ];
            if ($replacer[$delivery_status] ?? false) {
                if ($delivery_status == 'delivered') {
                    $getAvailableCodeCrm = Autocrm::where('autocrm_title', 'Order Taken With Code')->first();
                    $code = null;
                    $idCode = null;

                    if (
                        !empty($getAvailableCodeCrm) &&
                        ($getAvailableCodeCrm['autocrm_email_toogle'] == 1 || $getAvailableCodeCrm['autocrm_sms_toogle'] == 1 ||
                            $getAvailableCodeCrm['autocrm_push_toogle'] == 1 || $getAvailableCodeCrm['autocrm_inbox_toogle'] == 1)
                    ) {
                        $getAvailableCode = app('Modules\Autocrm\Http\Controllers\ApiAutoresponseWithCode')->getAvailableCode($trx->id_transaction);
                        $code = $getAvailableCode['autoresponse_code'] ?? null;
                        $idCode = $getAvailableCode['id_autoresponse_code_list'] ?? null;
                    }

                    if (!empty($code)) {
                        $send = app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM('Order Taken Delivery With Code', $phone, [
                            'id_reference'    => $trx->id_transaction,
                            'id_transaction'    => $trx->id_transaction,
                            'receipt_number'  => $trx->transaction_receipt_number,
                            'outlet_code'     => $outlet->outlet_code,
                            'outlet_name'     => $outlet->outlet_name,
                            'delivery_status' => $replacer[$delivery_status] ?? $delivery_status,
                            'order_id'        => $trx_pickup->order_id,
                            'code'            => $code
                        ]);

                        $updateCode = AutoresponseCodeList::where('id_autoresponse_code_list', $idCode)->update(['id_user' => $trx->id_user, 'id_transaction' => $trx->id_transaction]);
                        if ($updateCode) {
                            app('Modules\Autocrm\Http\Controllers\ApiAutoresponseWithCode')->stopAutoresponse($idCode);
                        }
                    } else {
                        $autocrm = app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM(
                            'Delivery Status Update',
                            $phone,
                            [
                                'id_reference'              => $trx->id_transaction,
                                'id_transaction'            => $trx->id_transaction,
                                'receipt_number'            => $trx->transaction_receipt_number,
                                'outlet_code'               => $outlet->outlet_code,
                                'outlet_name'               => $outlet->outlet_name,
                                'delivery_status_title'     => $replacer[$delivery_status] ?? $delivery_status,
                                'delivery_status_content'   => $replacer_content[$delivery_status] ?? $delivery_status,
                                'order_id'                  => $trx_pickup->order_id,
                                'name'                      => ucwords($userName)
                            ]
                        );
                    }
                } else {
                    $autocrm = app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM(
                        'Delivery Status Update',
                        $phone,
                        [
                            'id_reference'              => $trx->id_transaction,
                            'id_transaction'            => $trx->id_transaction,
                            'receipt_number'            => $trx->transaction_receipt_number,
                            'outlet_code'               => $outlet->outlet_code,
                            'outlet_name'               => $outlet->outlet_name,
                            'delivery_status_title'     => $replacer[$delivery_status] ?? $delivery_status,
                            'delivery_status_content'   => $replacer_content[$delivery_status] ?? $delivery_status,
                            'order_id'                  => $trx_pickup->order_id,
                            'name'                      => ucwords($userName)
                        ]
                    );
                }
            }
            if ($delivery_status == 'rejected') {
                $autocrm = app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM(
                    'Delivery Rejected',
                    $phone,
                    [
                        'id_reference'              => $trx->id_transaction,
                        'id_transaction'            => $trx->id_transaction,
                        'receipt_number'            => $trx->transaction_receipt_number,
                        'outlet_code'               => $outlet->outlet_code,
                        'outlet_name'               => $outlet->outlet_name,
                        'order_id'                  => $trx_pickup->order_id,
                        'name'                      => ucwords($userName)
                    ]
                );
            }

            TransactionPickupGoSendUpdate::create($dataUpdate);
        }
    }
}
