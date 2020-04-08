<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\LogApiGosend;
use App\Http\Models\TransactionPickupGoSend;

class ApiGosendController extends Controller
{
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
        $post = $request->json()->all();
        $tpg = TransactionPickupGoSend::where('go_send_id',$post['booking_id']??'')->first();
        if(!$tpg){
            $response_code = 404;
            $response_body = ['status'=>'fail','messages'=>['Transaction Not Found']];
        }else{
            if($post['booking_id']??false){
                $response_code = 200;
                $tpg->update(['latest_status' => $post['status']]);
                $response_body = ['status'=>'success','messages'=>['Success update']];
            }else{
                $response_code = 400;
                $response_body = ['status'=>'fail','messages'=>['booking_id is required']];
            }
        }
        try {
            LogApiGosend::create([
                'type' => 'webhook',
                'id_reference' => $post['booking_id']??'',
                'request_url' => url()->current(),
                'request_method' => $request->method(),
                'request_parameter' => json_encode($post),
                'request_header' => json_encode($request->header()),
                'response_body' => $response_body,
                'response_header' => null,
                'response_code' => $response_code
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed write log to LogApiGosend: '.$e->getMessage());
        }
        return response()->json($response_body,$response_code);
    }
}
