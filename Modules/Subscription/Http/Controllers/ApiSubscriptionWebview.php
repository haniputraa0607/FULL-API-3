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
        if($subs){
            $response = [
                'status' => 'success',
                'result' => [
                    'webview_url' => env('APP_URL') ."api/webview/subscription/". $subs['id_subscription'],
                    'button_text' => 'BELI'
                ]
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
        
        return [
            'status' => 'success',
            'messages' => ['Subscription found']
        ];
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
        return $action;
        if ($action['status'] != 'success') {
            return [
                'status' => 'fail',
                'messages' => ['Subscription is not found']
            ];
        } else {
            $data['deals'] = $action['result'];
        }

        return view('deals::webview.deals.deals_claim', $data);
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
