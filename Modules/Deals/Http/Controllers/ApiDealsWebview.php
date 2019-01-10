<?php

namespace Modules\Deals\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class ApiDealsWebview extends Controller
{
    // deals detail webview
    /*public function dealsDetail($id_deals, $deals_type)
    {
        // return url webview and button text for mobile (native button)
        $response = [
            'status' => 'success',
            'result' => [
                'webview_url' => env('APP_URL') ."/webview/deals/". $id_deals ."/". $deals_type,
                'button_text' => 'BELI'
            ]
        ];
        return response()->json($response);
    }*/
    
    // voucher detail webview
    /*public function voucherDetail($id_deals_user)
    {
        // return url webview and button text for mobile (native button)
        $response = [
            'status' => 'success',
            'result' => [
                'webview_url' => env('APP_URL') ."/webview/voucher/". $id_deals_user,
                'button_text' => 'INVALIDATE'
            ]
        ];
        return response()->json($response);
    }*/
    
}
