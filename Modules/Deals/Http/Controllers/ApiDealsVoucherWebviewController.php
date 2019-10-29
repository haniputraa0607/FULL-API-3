<?php

namespace Modules\Deals\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;

class ApiDealsVoucherWebviewController extends Controller
{
    public function voucherDetail(Request $request, $id_deals_user)
    {
        $bearer = $request->header('Authorization');
        if ($bearer == "") {
            return abort(404);
        }

        $post['id_deals_user'] = $id_deals_user;
        $post['used'] = 0;

        $action = MyHelper::postCURLWithBearer('api/voucher/me?log_save=0', $post, $bearer);

        if ($action['status'] != 'success') {
            return [
                'status' => 'fail',
                'messages' => ['Deals is not found']
            ];
        } else {
            $data['voucher'] = $action['result'];
        }

        return view('deals::webview.voucher.voucher_detail_v3', $data);
    }

    public function voucherDetailV2(Request $request, $id_deals_user)
    {
        $bearer = $request->header('Authorization');

        if ($bearer == "") {
            return abort(404);
        }

        $post['id_deals_user'] = $id_deals_user;
        $post['used'] = 0;

        $action = MyHelper::postCURLWithBearer('api/voucher/me?log_save=0', $post, $bearer);
        
        if ($action['status'] != 'success') {
            return [
                'status' => 'fail',
                'messages' => ['Deals is not found']
            ];
        } else {
            $data['voucher'] = $action['result'];
        }
        
        usort($data['voucher']['data'][0]['deal_voucher']['deal']['outlet_by_city'], function($a, $b) {
            return $a['city_name'] <=> $b['city_name'];
        });
        
        for ($i = 0; $i < count($data['voucher']['data'][0]['deal_voucher']['deal']['outlet_by_city']); $i++) {
            usort($data['voucher']['data'][0]['deal_voucher']['deal']['outlet_by_city'][$i]['outlet'] ,function($a, $b) {
                return $a['outlet_name'] <=> $b['outlet_name'];
            });
        }

        return view('deals::webview.voucher.voucher_detail_v4', $data);
    }

    // display detail voucher after used
    public function voucherUsed(Request $request, $id_deals_user)
    {
        $bearer = $request->header('Authorization');

        if ($bearer == "") {
            return abort(404);
        }

        $post['id_deals_user'] = $id_deals_user;
        $post['used'] = 1;

        $action = MyHelper::postCURLWithBearer('api/voucher/me?log_save=0', $post, $bearer);

        if ($action['status'] != 'success') {
            return [
                'status' => 'fail',
                'messages' => ['Deals is not found']
            ];
        } else {
            $data['voucher'] = $action['result'];
        }

        return view('deals::webview.voucher.voucher_detail', $data);
    }
}
