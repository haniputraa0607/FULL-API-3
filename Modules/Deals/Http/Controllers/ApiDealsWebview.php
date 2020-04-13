<?php

namespace Modules\Deals\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Deal;
use App\Lib\MyHelper;
use Illuminate\Support\Facades\Auth;
use Route;

use Modules\Deals\Http\Requests\Deals\ListDeal;

class ApiDealsWebview extends Controller
{
    // deals detail webview
    public function dealsDetail(Request $request)
    {
        $deals = Deal::with('brand', 'outlets.city', 'deals_content.deals_content_details')->where('id_deals', $request->id_deals)->get()->toArray()[0];
        
        $deals['outlet_by_city'] = [];

        if (!empty($deals['outlets'])) {
            $kota = array_column($deals['outlets'], 'city');
            $kota = array_values(array_map("unserialize", array_unique(array_map("serialize", $kota))));

            foreach ($kota as $k => $v) {
                if ($v) {
                    $kota[$k]['outlet'] = [];
                    foreach ($deals['outlets'] as $outlet) {
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

            $deals['outlet_by_city'] = $kota;
        }
        
        unset($deals['outlets']);
        $point = Auth::user()->balance;
        
        $deals['deals_image'] = env('S3_URL_API') . $deals['deals_image'];
        $response = [
            'status' => 'success',
            'result' => 
                $deals
        ];
        $response['button_text'] = 'BELI';
        
        $result = [
            'id_deals'                      => $deals['id_deals'],
            'deals_type'                    => $deals['deals_type'],
            'deals_status'                  => $deals['deals_status'],
            'deals_voucher_type'            => $deals['deals_voucher_price_type'],
            'deals_voucher_use_point'       => (($deals['deals_voucher_price_cash'] - $point) <= 0) ? MyHelper::requestNumber(0,'_POINT') : MyHelper::requestNumber($deals['deals_voucher_price_cash'] - $point,'_POINT'),
            'deals_voucher_point_now'       => MyHelper::requestNumber($point,'_POINT'),
            'deals_voucher_avaliable_point' => (($point - $deals['deals_voucher_price_cash']) <= 0) ? MyHelper::requestNumber(0,'_POINT') : MyHelper::requestNumber($point - $deals['deals_voucher_price_cash'],'_POINT'),
            'deals_voucher_point_success'   => (($deals['deals_voucher_price_cash'] - $point) <= 0) ? 'enable' : 'disable',
            'deals_image'                   => $deals['deals_image'],
            'deals_start'                   => $deals['deals_start'],
            'deals_end'                     => $deals['deals_end'],
            'deals_voucher'                 => ($deals['deals_voucher_type'] == 'Unlimited') ? 'Unlimited' : $deals['deals_total_voucher'] - $deals['deals_total_claimed'] . '/' . $deals['deals_total_voucher'],
            'deals_title'                   => $deals['deals_title'],
            'deals_second_title'            => $deals['deals_second_title'],
            'deals_description'             => $deals['deals_description'],
            'deals_button'                  => 'Claim',
            'time_server'                   => date('Y-m-d H:i:s'),
            'time_to_end'                   => strtotime($deals['deals_end']) - time(),
            'button_text'                   => 'Get',
            'payment_message'               => 'Are you sure want to claim Free Voucher Offline x Online Limited voucher ?',
            'payment_success_message'       => 'Claim Voucher Success ! Do you want to use it now ?'
        ];
        if ($deals['deals_voucher_price_cash'] != "") {
            $result['deals_price'] = MyHelper::requestNumber($deals['deals_voucher_price_cash'], '_CURRENCY');
        } elseif ($deals['deals_voucher_price_point']) {
            $result['deals_price'] = MyHelper::requestNumber($deals['deals_voucher_price_point'],'_POINT') . " points";
        } else {
            $result['deals_price'] = "Free";
        }
        
        $i = 0;
        foreach ($deals['deals_content'] as $keyContent => $valueContent) {
            if (!empty($valueContent['deals_content_details'])) {
                $result['deals_content'][$keyContent]['title'] = $valueContent['title'];
                foreach ($valueContent['deals_content_details'] as $key => $value) {
                    // $result['deals_content'][$keyContent]['detail'][$key] = $value['content'];
                    $content[$key] = $value['content'];
                }
                $result['deals_content'][$keyContent]['detail'] = implode('', $content);
                $i++;
            }
        }

        $result['deals_content'][$i]['title'] = 'Available at';
        $result['deals_content'][$i]['is_outlet'] = 1;
        foreach ($deals['outlet_by_city'] as $keyCity => $valueCity) {
            if (isset($valueCity['city_name'])) {
                foreach ($valueCity['outlet'] as $keyOutlet => $valueOutlet) {
                    // $result['deals_content'][$i]['detail'][$keyOutlet] = $valueOutlet['outlet_name'];
                    $valTheOutlet[$keyOutlet] = '<li style="line-height: 12px;">' .$deals['brand']['name_brand']. ' - ' . $valueOutlet['outlet_name'] . '</li>';
                }
                $city[$keyCity] = strtoupper($valueCity['city_name']) . '<br>' . implode('', $valTheOutlet);
                $result['deals_content'][$i]['detail'] = '<ol>'.implode('', $city).'</ol>';
            }
        }

        return response()->json(MyHelper::checkGet($result));
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
