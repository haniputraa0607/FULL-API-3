<?php

namespace Modules\Deals\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Deal;

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
                    'webview_url' => env('APP_URL') ."webview/deals/". $deals['id_deals'] ."/". $deals['deals_type'],
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
