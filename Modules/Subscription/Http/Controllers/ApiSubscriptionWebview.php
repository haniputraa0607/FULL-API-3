<?php

namespace Modules\Subscription\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\FeaturedSubscription;
use Modules\Subscription\Entities\SubscriptionOutlet;
use Modules\Subscription\Entities\SubscriptionUser;
use Modules\Subscription\Entities\SubscriptionUserVoucher;
use App\Http\Models\Setting;
use App\Lib\MyHelper;
use Route;

use Modules\Subscription\Http\Requests\ListSubscription;

class ApiSubscriptionWebview extends Controller
{
    // deals detail webview
    public function subscriptionDetail(Request $request)
    {
        // return url webview and button text for mobile (native button)
        $subs = Subscription::find($request->get('id_subscription'));
        $user = $request->user();
        $curBalance = (int) $user->balance??0;

        if($subs){
            $subs['webview_url'] = env('API_URL') ."api/webview/subscription/". $subs['id_subscription'];
            $subs['button_text'] = 'BELI';
            $subs['button_status'] = 0;

            //text konfirmasi pembelian
            if($subs['subscription_price_type']=='free'){
                //voucher free
                $payment_message = Setting::where('key', 'subscription_payment_messages')->pluck('value_text')->first()??'Kamu yakin ingin membeli subscription ini?';
                $payment_message = MyHelper::simpleReplace($payment_message,['subscription_title'=>$subs['subscription_title']]);
            }elseif($subs['subscription_price_type']=='point'){
                $payment_message = Setting::where('key', 'subscription_payment_messages_point')->pluck('value_text')->first()??'Anda akan menukarkan %point% points anda dengan subscription %subscription_title%?';
                $payment_message = MyHelper::simpleReplace($payment_message,['point'=>$subs['subscription_price_point'],'subscription_title'=>$subs['subscription_title']]);
            }else{
                $payment_message = Setting::where('key', 'subscription_payment_messages_cash')->pluck('value_text')->first()??'Kamu yakin ingin membeli subscription %subscription_title% dengan harga %cash% ?';
                $payment_message = MyHelper::simpleReplace($payment_message,['cash'=> $subs['subscription_price_cash'], 'subscription_title'=>$subs['subscription_title']]);
            }

            $payment_success_message = Setting::where('key', 'subscription_payment_success_messages')->pluck('value_text')->first()??'Anda telah membeli subscription %subscription_title%';
            $payment_success_message = MyHelper::simpleReplace($payment_success_message,['subscription_title'=>$subs['subscription_title']]);


            $subs['payment_message'] = $payment_message??'';
            $subs['payment_success_message'] = $payment_success_message;

            if($subs['subscription_price_type']=='free'&&$subs['subscription_status']=='available'){
                $subs['button_status']=1;
            }else {
                if($subs['subscription_price_type']=='point'){
                    $subs['button_status']= $subs['subscription_price_point']<=$curBalance?1:0;
                    if($subs['subscription_price_point']>$curBalance){
                        $subs['payment_fail_message'] = Setting::where('key', 'payment_fail_messages')->pluck('value_text')->first()??'Mohon maaf, point anda tidak cukup';
                    }
                }else{
                    $subs['button_text'] = 'Beli';
                    if($subs['subscription_status']=='available'){
                        $subs['button_status'] = 1;
                    }
                }
            }
            $response = [
                'status' => 'success',
                'result' => $subs
            ];
        }else{
            $response = [
                'status' => 'fail',
                'messages' => [
                    'Subscription Not Found'
                ]
            ];
        }
        return response()->json($response);
    }

    // webview deals detail
    public function webviewSubscriptionDetail(Request $request, $id_subscription)
    {

        $bearer = $request->header('Authorization');
        
        if ($bearer == "") {
            return abort(404);
        }

        $post['id_subscription'] = $id_subscription;
        $post['publish'] = 1;
        $post['web'] = 1;
        
        $action = MyHelper::postCURLWithBearer('/api/subscription/list', $post, $bearer);
        
        if ($action['status'] != 'success') {
            return [
                'status' => 'fail',
                'messages' => ['Subscription is not found']
            ];
        } else {
            $data['subscription'] = $action['result'];
        }
        
        usort($data['subscription'][0]['outlet_by_city'], function($a, $b) {
            return $a['city_name'] <=> $b['city_name'];
        });
        
        for ($i = 0; $i < count($data['subscription'][0]['outlet_by_city']); $i++) {
            usort($data['subscription'][0]['outlet_by_city'][$i]['outlet'] ,function($a, $b) {
                return $a['outlet_name'] <=> $b['outlet_name'];
            });
        }
        
        return view('subscription::webview.subscription_detail', $data);
    }

    public function mySubscription(Request $request, $id_subscription_user)
    {
        $bearer = $request->header('Authorization');
        
        if ($bearer == "") {
            return abort(404);
        }

        $post['id_subscription_user'] = $id_subscription_user;

        $action = MyHelper::postCURLWithBearer('api/subscription/me', $post, $bearer);
        // dd($action);
        if ($action['status'] != 'success') {
            return [
                'status' => 'fail',
                'messages' => ['Subscription is not found']
            ];
        } else {
            $data['subscription'] = $action['result'];
        }

        // usort($data['subscription']['outlet_by_city'], function($a, $b) {
        //     return $a['city_name'] <=> $b['city_name'];
        // });
        
        // for ($i = 0; $i < count($data['subscription']['outlet_by_city']); $i++) {
        //     usort($data['subscription']['outlet_by_city'][$i]['outlet'] ,function($a, $b) {
        //         return $a['outlet_name'] <=> $b['outlet_name'];
        //     });
        // }
        
        return view('subscription::webview.subscription_me', $data);
    }

    public function subscriptionSuccess(Request $request)
    {
        return response('ok');
    }
    
    // voucher detail webview
    /*public function voucherDetail($id_deals_user)
    {
        // return url webview and button text for mobile (native button)
        $response = [
            'status' => 'success',
            'result' => [
                'webview_url' => env('APP_URL') ."webview/voucher/". $id_deals_user,
                'button_text' => 'INVALIDATE'
            ]
        ];
        return response()->json($response);
    }*/
    
}
