<?php

namespace Modules\Deals\Http\Controllers;

use App\Http\Models\DealsUser;
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

    public function detailVoucher(Request $request)
    {
        $bearer = $request->header('Authorization');

        if ($bearer == "") {
            return abort(404);
        }

        $post['id_deals_user'] = $request->id_deals_user;
        $post['used'] = 0;

        // $action = MyHelper::postCURLWithBearer('api/voucher/me?log_save=0', $post, $bearer);
        $voucher = DealsUser::with(['deals_voucher', 'deals_voucher.deal', 'deals_voucher.deal.brand', 'deals_voucher.deal.deals_content', 'deals_voucher.deal.deals_content.deals_content_details', 'deals_voucher.deal.outlets.city', 'deals_voucher.deal.outlets.city'])
        ->where('id_deals_user', $request->id_deals_user)->get()->toArray()[0];

        if (!empty($voucher['deals_voucher']['deal']['outlets'])) {
            $kota = array_column($voucher['deals_voucher']['deal']['outlets'], 'city');
            $kota = array_values(array_map("unserialize", array_unique(array_map("serialize", $kota))));

            foreach ($kota as $k => $v) {
                if ($v) {
                    $kota[$k]['outlet'] = [];
                    foreach ($voucher['deals_voucher']['deal']['outlets'] as $outlet) {
                        if ($v['id_city'] == $outlet['id_city']) {
                            unset($outlet['pivot']);
                            unset($outlet['city']);

                            array_push($kota[$k]['outlet'], $outlet);
                        }
                    }
                } else {
                    unset($kota[$k]);
                }
            }

            $voucher['deals_voucher']['deal']['outlet_by_city'] = $kota;
        }
        
        usort($voucher['deals_voucher']['deal']['outlet_by_city'], function($a, $b) {
            if(isset($a['city_name']) && isset($b['city_name'])){
                return $a['city_name'] <=> $b['city_name'];
            }
        });

        for ($i = 0; $i < count($voucher['deals_voucher']['deal']['outlet_by_city']); $i++) {
            usort($voucher['deals_voucher']['deal']['outlet_by_city'][$i]['outlet'] ,function($a, $b) {
                return $a['outlet_name'] <=> $b['outlet_name'];
            });
        }

        $voucher['deals_voucher']['deal']['deals_image'] = env('S3_URL_API') . $voucher['deals_voucher']['deal']['deals_image'];

        $data = $voucher;
        
        $result = [
            'deals_image'           => $data['deals_voucher']['deal']['deals_image'],
            'deals_title'           => $data['deals_voucher']['deal']['deals_title'],
            'id_deals_voucher'      => $data['id_deals_voucher'],
            'id_deals_user'         => $data['id_deals_user'],
            'voucher_expired'       => date('d F Y', strtotime($data['voucher_expired_at'])),
            'is_used'               => $data['is_used'],
            'btn_used'              => 'Use Later',
            'is_online'             => $data['deals_voucher']['deal']['is_online'],
            'btn_online'            => 'Use Voucher',
            'is_offline'            => $data['deals_voucher']['deal']['is_offline'],
            'btn_offline'           => 'Redeem to Cashier',
            'header_online_voucher' => 'Online Transaction',
            'title_online_voucher'  => 'Apply promo on this app',
            'header_offline_voucher'=> 'Offline Transaction',
            'title_offline_voucher' => 'Redeem directly at Cashier',
            'button_text'           => 'Redeem',
            'popup_message'         => [
                $data['deals_voucher']['deal']['deals_title'],
                'will be used on the next transaction'
            ],
        ];

        
        $i = 0;
        foreach ($data['deals_voucher']['deal']['deals_content'] as $keyContent => $valueContent) {
            if (!empty($valueContent['deals_content_details'])) {
                $result['deals_content'][$keyContent]['title'] = $valueContent['title'];
                foreach ($valueContent['deals_content_details'] as $key => $value) {
                    $result['deals_content'][$keyContent]['detail'][0]['data'][$key] = $value['content'];
                    // $content[$key] = '<li>'.$value['content'].'</li>';
                }
                // $result['deals_content'][$keyContent]['detail'] = '<ul style="color:#707070;">'.implode('', $content).'</ul>';
                $i++;
            }
        }

        $result['deals_content'][$i]['is_outlet'] = 1;
        $result['deals_content'][$i]['title'] = 'Available at';
        $result['deals_content'][$i]['brand'] = $data['deals_voucher']['deal']['brand']['name_brand'];
        $result['deals_content'][$i]['brand_logo'] = $data['deals_voucher']['deal']['brand']['logo_brand'];

        if($data['deals_voucher']['deal']['custom_outlet_text'] != null){
            $result['deals_content'][$i]['detail'] = $data['deals_voucher']['deal']['custom_outlet_text'];
        }else{
            foreach ($data['deals_voucher']['deal']['outlet_by_city'] as $keyCity => $valueCity) {
                if (isset($valueCity['city_name'])) {
                    $result['deals_content'][$i]['detail'][$keyCity]['city'] = $valueCity['city_name'];
                    foreach ($valueCity['outlet'] as $keyOutlet => $valueOutlet) {
                        $result['deals_content'][$i]['detail'][$keyCity]['outlet'][$keyOutlet] = $valueOutlet['outlet_name'];
                        // $valTheOutlet[$keyOutlet] = '<li style="line-height: 12px;">' . $valueOutlet['outlet_name'] . '</li>';
                    }
                    // $city[$keyCity] = strtoupper($valueCity['city_name']) . '<br><ul style="color:#707070;">' .implode('', $valTheOutlet).'</ul>';
                    // $result['deals_content'][$i]['detail'] = implode('', $city);
                }
            }
        }

        return response()->json(MyHelper::checkGet($result));
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
