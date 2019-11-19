<?php

namespace Modules\Deals\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Deal;
use App\Lib\MyHelper;
use Route;

use Modules\Deals\Http\Requests\Deals\ListDeal;

class ApiDealsWebview extends Controller
{
    // deals detail webview
    public function dealsDetail(Request $request)
    {
        // return url webview and button text for mobile (native button)
        $deals = Deal::find($request->get('id_deals'));
        if($deals){
            $response = [
                'status' => 'success',
                'result' => [
                    'webview_url' => env('APP_URL') ."api/webview/deals/". $deals['id_deals'] ."/". $deals['deals_type'],
                    'button_text' => 'BELI'
                ]
            ];
        }else{
            $response = [
                'status' => 'fail',
                'messages' => [
                    'Deals Not Found'
                ]
            ];
        }
        return response()->json($response);
    }

    // webview deals detail
    public function webviewDealsDetail(Request $request, $id_deals, $deals_type)
    {
        $bearer = $request->header('Authorization');
        
        if ($bearer == "") {
            return abort(404);
        }

        $post['id_deals'] = $id_deals;
        $post['publish'] = 1;
        $post['deals_type'] = "Deals";
        $post['web'] = 1;
        
        $action = MyHelper::postCURLWithBearer('api/deals/list', $post, $bearer);

        if ($action['status'] != 'success') {
            return [
                'status' => 'fail',
                'messages' => ['Deals is not found']
            ];
        } else {
            $data['deals'] = $action['result'];
        }
        
        usort($data['deals'][0]['outlet_by_city'], function($a, $b) {
            return $a['city_name'] <=> $b['city_name'];
        });
        
        for ($i = 0; $i < count($data['deals'][0]['outlet_by_city']); $i++) {
            usort($data['deals'][0]['outlet_by_city'][$i]['outlet'] ,function($a, $b) {
                return $a['outlet_name'] <=> $b['outlet_name'];
            });
        }
        
        return view('deals::webview.deals.deals_detail', $data);
    }

    public function dealsClaim(Request $request, $id_deals_user)
    {
        $bearer = $request->header('Authorization');

        if ($bearer == "") {
            return abort(404);
        }

        $post['id_deals_user'] = $id_deals_user;

        $action = MyHelper::postCURLWithBearer('api/deals/me', $post, $bearer);

        if ($action['status'] != 'success') {
            return [
                'status' => 'fail',
                'messages' => ['Deals is not found']
            ];
        } else {
            $data['deals'] = $action['result'];
        }

        return view('deals::webview.deals.deals_claim', $data);
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
