<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\LogApiGosend;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionPickupGoSend;
use App\Http\Models\User;
use App\Lib\GoSend;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class ApiGosendController extends Controller
{
    public function __construct()
    {
        $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
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
            if ($post['booking_id'] ?? false) {
                $status = [
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
                if ($post['liveTrackingUrl'] ?? false) {
                    $toUpdate['live_tracking_url'] = $post['liveTrackingUrl'];
                }
                if ($post['driver_id'] ?? false) {
                    $toUpdate['driver_id'] = $post['driver_id'];
                }
                if ($post['driver_name'] ?? false) {
                    $toUpdate['driver_name'] = $post['driver_name'];
                }
                if ($post['driver_phone'] ?? false) {
                    $toUpdate['driver_phone'] = $post['driver_phone'];
                }
                if ($post['driver_photo'] ?? false) {
                    $toUpdate['driver_photo'] = $post['driver_photo'];
                }
                if ($post['vehicle_number'] ?? false) {
                    $toUpdate['vehicle_number'] = $post['vehicle_number'];
                }
                if (!in_array(strtolower($post['status']), ['confirmed', 'no_driver', 'cancelled']) && strpos(env('GO_SEND_URL'), 'integration')) {
                    $toUpdate['driver_id']      = '00510001';
                    $toUpdate['driver_phone']   = '08111251307';
                    $toUpdate['driver_name']    = 'Anton Lucarus';
                    $toUpdate['driver_photo']   = 'http://beritatrans.com/cms/wp-content/uploads/2020/02/images4-553x400.jpeg';
                    $toUpdate['vehicle_number'] = 'AB 2641 XY';
                }
                $tpg->update($toUpdate);
                $id_transaction = TransactionPickup::select('id_transaction')->where('id_transaction_pickup', $tpg->id_transaction_pickup)->pluck('id_transaction')->first();
                $dataSave       = [
                    'id_transaction'                => $id_transaction,
                    'id_transaction_pickup_go_send' => $tpg['id_transaction_pickup_go_send'],
                    'status'                        => $status[$post['status']] ?? 'Finding Driver',
                ];
                GoSend::saveUpdate($dataSave);
                $trx     = Transaction::where('id_transaction', $id_transaction)->first();
                $outlet  = Outlet::where('id_outlet', $trx->id_outlet)->first();
                $phone   = User::select('phone')->where('id', $trx->id_user)->pluck('phone')->first();
                $autocrm = app($this->autocrm)->SendAutoCRM('Delivery Status Update', $phone,
                    [
                        'id_reference'    => $id_transaction,
                        'receipt_number'  => $trx->receipt_number,
                        'outlet_code'     => $outlet->outlet_code,
                        'outlet_name'     => $outlet->outlet_name,
                        'delivery_status' => $status[$post['status']] ?? 'Finding Driver',
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
