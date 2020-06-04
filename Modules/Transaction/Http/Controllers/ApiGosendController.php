<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\LogApiGosend;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionPickupGoSend;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\User;
use App\Lib\GoSend;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use DB;

class ApiGosendController extends Controller
{
    public function __construct()
    {
        $this->autocrm    = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->getNotif   = "Modules\Transaction\Http\Controllers\ApiNotification";
        $this->membership = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->trx        = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->outlet_app = "Modules\OutletApp\Http\Controllers\ApiOutletApp";
    }
    /**
     * Update latest status from gosend
     * @return Response
     */
    public function updateStatus(Request $request)
    {
        /**
        {
        "entity_id": "string",
        "type": "COMPLETED",
        "event_date": "1486105171000",
        "event_id": "72d82762-8bcc-4412-a180-c71ffcef011a",
        "partner_id": "some-partner-id",
        "booking_id": "123456",
        "status": "confirmed",
        "cancelled_by": "driver",
        "booking_type": "instant",
        "driver_name": "string",
        "driver_phone": "string",
        "driver_phone2": "string",
        "driver_phone3": "string",
        "driver_photo_url": "string",
        "receiver_name": "string",
        "total_distance_in_kms": 0,
        "pickup_eta": "1486528706000-1486528707000",
        "delivery_eta": "1486528708000-1486528709000",
        "price": 0,
        "cancellation_reason": "string",
        "attributes": {
        "key1": "string",
        "key2": "string"
        },
        "liveTrackingUrl": "http://gjk.io/abcd"
        }
         **/
        $auth = $request->header('Authorization');
        $preset_auth = Setting::select('value')->where('key','gosend_auth_token')->pluck('value')->first();
        if($preset_auth && $auth !== $preset_auth){
            return response()->json(['status'=>'fail','messages'=>'Invalid Token'],401);
        }
        $post = $request->json()->all();
        $tpg  = TransactionPickupGoSend::where('go_send_order_no', $post['booking_id'] ?? '')->first();
        if (!$tpg) {
            $response_code = 404;
            $response_body = ['status' => 'fail', 'messages' => ['Transaction Not Found']];
        } else {
            $id_transaction = TransactionPickup::select('id_transaction')->where('id_transaction_pickup', $tpg->id_transaction_pickup)->pluck('id_transaction')->first();
            if ($post['booking_id'] ?? false) {
                $ref_status = [
                    'confirmed'        => 'Finding Driver', //
                    'allocated'        => 'Driver Allocated',
                    'out_for_pickup'   => 'Enroute Pickup', //
                    'picked'           => 'Item Picked by Driver',
                    'out_for_delivery' => 'Enroute Drop', //
                    'cancelled'        => 'Cancelled', //
                    'delivered'        => 'Completed', //
                    'rejected'         => 'Rejected',
                    'no_driver'        => 'Driver not found', //
                    'on_hold'          => 'On Hold',
                ];
                $response_code = 200;
                $toUpdate      = ['latest_status' => $post['status']];
                if($post['receiver_name'] ?? '') {
                    $toUpdate['receiver_name'] = $post['receiver_name'];
                }
                if (!in_array(strtolower($post['status']), ['confirmed', 'no_driver', 'cancelled']) && (empty($tpg->live_tracking_url) || empty($tpg->driver_id) || empty($tpg->driver_name) || empty($tpg->driver_phone) || empty($tpg->driver_photo) || empty($tpg->vehicle_number))) {
                    if ($post['live_tracking_url'] ?? false) {
                        $toUpdate['live_tracking_url'] = $post['live_tracking_url'];
                    }
                    if ($post['driver_name'] ?? false) {
                        $toUpdate['driver_name'] = $post['driver_name'];
                    }
                    if ($post['driver_phone'] ?? false) {
                        $toUpdate['driver_phone'] = $post['driver_phone'];
                    }
                    if ($post['driver_photo_url'] ?? false) {
                        $toUpdate['driver_photo'] = $post['driver_photo_url'];
                    }
                    $tpg->update($toUpdate);
                    // request booking detail because some data not available from webhook request
                    $status = GoSend::getStatus($post['booking_id'], true);
                    if ($status['receiverName'] ?? false) {
                        $toUpdate['receiver_name'] = $status['receiverName'];
                    }
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
                    if(strpos(env('GO_SEND_URL'), 'integration')){
                        $toUpdate['driver_id']      = $toUpdate['driver_id']??'00510001';
                        $toUpdate['driver_phone']   = $toUpdate['driver_phone']??'08111251307';
                        $toUpdate['driver_name']    = $toUpdate['driver_name']??'Anton Lucarus';
                        $toUpdate['driver_photo']   = $toUpdate['driver_photo']??'http://beritatrans.com/cms/wp-content/uploads/2020/02/images4-553x400.jpeg';
                        $toUpdate['vehicle_number'] = $toUpdate['vehicle_number']??'AB 2641 XY';                        
                    }
                } elseif (strtolower($post['status']) == 'confirmed'){
                    $toUpdate['driver_id']      = null;
                    $toUpdate['driver_phone']   = null;
                    $toUpdate['driver_name']    = null;
                    $toUpdate['driver_photo']   = null;
                    $toUpdate['vehicle_number'] = null;                        
                }
                $tpg->update($toUpdate);
                $trx = Transaction::where('transactions.id_transaction', $id_transaction)->join('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')->where('pickup_by', 'GO-SEND')->first();
                if (in_array(strtolower($post['status']), ['completed', 'delivered'])) {
                    // sendPoint delivery after status delivered only
                    if ($trx->cashback_insert_status != 1) {
                        //send notif to customer
                        $user = User::find($trx->id_user);
                        $trx->load('user.memberships', 'outlet', 'productTransaction', 'transaction_vouchers','promo_campaign_promo_code','promo_campaign_promo_code.promo_campaign');
                        $newTrx    = $trx;
                        $checkType = TransactionMultiplePayment::where('id_transaction', $trx->id_transaction)->get()->toArray();
                        $column    = array_column($checkType, 'type');
                        $outlet    = $trx->outlet;
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
                    $status = GoSend::getStatus($post['booking_id'], true);
                    $arrived_at = date('Y-m-d H:i:s', ($status['orderArrivalTime']??false)?strtotime($status['orderArrivalTime']):time());
                    TransactionPickup::where('id_transaction', $trx->id_transaction)->update(['arrived_at' => $arrived_at]);
                    $dataSave       = [
                        'id_transaction'                => $id_transaction,
                        'id_transaction_pickup_go_send' => $tpg['id_transaction_pickup_go_send'],
                        'status'                        => $post['status'],
                        'go_send_order_no'              => $post['booking_id']
                    ];
                    GoSend::saveUpdate($dataSave);
                } elseif (in_array(strtolower($post['status']), ['cancelled', 'rejected', 'no_driver'])) {
                    $tpg->update([
                        'live_tracking_url' => null,
                        'driver_id' => null,
                        'driver_name' => null,
                        'driver_phone' => null,
                        'driver_photo' => null,
                        'vehicle_number' => null,
                        'receiver_name' => null
                    ]);
                    $dataSave       = [
                        'id_transaction'                => $id_transaction,
                        'id_transaction_pickup_go_send' => $tpg['id_transaction_pickup_go_send'],
                        'status'                        => $post['status'],
                        'go_send_order_no'              => $post['booking_id']
                    ];
                    GoSend::saveUpdate($dataSave);
                    app($this->outlet_app)->bookGoSend($trx, true);
                }else{
                    $dataSave       = [
                        'id_transaction'                => $id_transaction,
                        'id_transaction_pickup_go_send' => $tpg['id_transaction_pickup_go_send'],
                        'status'                        => $post['status'],
                        'go_send_order_no'              => $post['booking_id']
                    ];
                    GoSend::saveUpdate($dataSave);
                }
                $trx     = Transaction::where('id_transaction', $id_transaction)->first();
                $outlet  = Outlet::where('id_outlet', $trx->id_outlet)->first();
                $phone   = User::select('phone')->where('id', $trx->id_user)->pluck('phone')->first();
                $autocrm = app($this->autocrm)->SendAutoCRM('Delivery Status Update', $phone,
                    [
                        'id_reference'    => $id_transaction,
                        'receipt_number'  => $trx->receipt_number,
                        'outlet_code'     => $outlet->outlet_code,
                        'outlet_name'     => $outlet->outlet_name,
                        'delivery_status' => $ref_status[$post['status']] ?? 'Finding Driver',
                    ]
                );
                $response_body = ['status' => 'success', 'messages' => ['Success update']];
            } else {
                $response_code = 400;
                $response_body = ['status' => 'fail', 'messages' => ['booking_id is required']];
            }
        }
        try {
            LogApiGosend::create([
                'type'              => 'webhook',
                'id_reference'      => $post['booking_id'] ?? '',
                'request_url'       => url()->current(),
                'request_method'    => $request->method(),
                'request_parameter' => json_encode($post),
                'request_header'    => json_encode($request->header()),
                'response_body'     => json_encode($response_body),
                'response_header'   => null,
                'response_code'     => $response_code,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed write log to LogApiGosend: ' . $e->getMessage());
        }
        return response()->json($response_body, $response_code);
    }
}
