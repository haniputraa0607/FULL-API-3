<?php

namespace Modules\Subscription\Http\Controllers;

use App\Http\Models\Outlet;
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
use App\Http\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Route;

use Modules\Subscription\Http\Requests\ListSubscription;

class ApiSubscriptionWebview extends Controller
{
    // deals detail webview
    public function subscriptionDetail(Request $request)
    {
        // return url webview and button text for mobile (native button)
        $subs = Subscription::with('outlets', 'subscription_content.subscription_content_details')->find($request->get('id_subscription'));
        $user = $request->user();
        $curBalance = (int) $user->balance??0;

        $result = [
            'id_subscription_user'          => $subs['id_subscription_user'],
            'subscription_title'            => $subs['subscription_title'],
            'subscription_sub_title'        => $subs['subscription_sub_title'],
            'subscription_description'      => $subs['subscription_description'],
            'url_subscription_image'        => $subs['url_subscription_image'],
            'subscription_start'            => date('Y-m-d H:i:s', strtotime($subs['subscription_start'])),
            'subscription_end'              => date('Y-m-d H:i:s', strtotime($subs['subscription_end'])),
            'subscription_publish_start'    => date('Y-m-d H:i:s', strtotime($subs['subscription_publish_start'])),
            'subscription_publish_end'      => date('Y-m-d H:i:s', strtotime($subs['subscription_publish_end'])),
            'subscription_price_type'       => $subs['subscription_price_type'],
            'subscription_price_point'      => $subs['subscription_price_point'],
            'subscription_price_cash'       => $subs['subscription_price_cash'],
            'subscription_price_pretty'     => $subs['subscription_price_pretty'],
            'subscription_voucher_total'    => $subs['subscription_voucher_total'],
            'button_text'                   => 'BELI',
            'button_status'                 => 0
        ];

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


        $result['payment_message'] = $payment_message??'';
        $result['payment_success_message'] = $payment_success_message;

        if($subs['subscription_price_type']=='free'&&$subs['subscription_status']=='available'){
            $result['button_status']=1;
        }else {
            if($subs['subscription_price_type']=='point'){
                $result['button_status']= $subs['subscription_price_point']<=$curBalance?1:0;
                if($subs['subscription_price_point']>$curBalance){
                    $result['payment_fail_message'] = Setting::where('key', 'payment_fail_messages')->pluck('value_text')->first()??'Mohon maaf, point anda tidak cukup';
                }
            }else{
                $result['button_text'] = 'Beli';
                if($subs['subscription_status']=='available'){
                    $result['button_status'] = 1;
                }
            }
        }

        $i = 0;
        foreach ($subs['subscription_content'] as $keyContent => $valueContent) {
            if (!empty($valueContent['subscription_content_details'])) {
                $result['subscription_content'][$keyContent]['title'] = $valueContent['title'];
                foreach ($valueContent['subscription_content_details'] as $key => $value) {
                    $result['subscription_content'][$keyContent]['detail'][$key] = $value['content'];
                    // $content[$key] = '<li>'.$value['content'].'</li>';
                }
                // $result['deals_content'][$keyContent]['detail'] = '<ul style="color:#707070;">'.implode('', $content).'</ul>';
                $i++;
            }
        }

        $result['subscription_content'][$i]['is_outlet']    = 1;
        $result['subscription_content'][$i]['title']        = 'Berlaku di';

        if ($subs['is_all_outlet'] == true) {
            $result['subscription_content'][$i]['detail'][] = 'Berlaku untuk semua outlet';
        } else {
            foreach ($subs['outlet_by_city'] as $keyCity => $valueCity) {
                if (isset($valueCity['city_name'])) {
                    $result['subscription_content'][$i]['detail_available'][$keyCity]['city'] = $valueCity['city_name'];
                    foreach ($valueCity['outlet'] as $keyOutlet => $valueOutlet) {
                        $result['subscription_content'][$i]['detail_available'][$keyCity]['outlet'][$keyOutlet] = $valueOutlet['outlet_name'];
                        // $valTheOutlet[$keyOutlet] = '<li style="line-height: 12px;">' . $valueOutlet['outlet_name'] . '</li>';
                    }
                    // $city[$keyCity] = strtoupper($valueCity['city_name']) . '<br><ul style="color:#707070;">' .implode('', $valTheOutlet).'</ul>';
                    // $result['deals_content'][$i]['detail'] = implode('', $city);
                }
            }
        }

        $result['time_server'] = date('Y-m-d H:i:s');

        return response()->json(MyHelper::checkGet($result));
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

    public function mySubscription(Request $request)
    {
        $bearer = $request->header('Authorization');
        if ($bearer == "") {
            return abort(404);
        }

        $subs = SubscriptionUser::with('subscription.outlets', 'subscription_user_vouchers', 'subscription.subscription_content.subscription_content_details')->where('id_subscription_user', $request->id_subscription_user)->first()->toArray();
        
        $result = [
            'id_subscription_user'          => $subs['id_subscription_user'],
            'id_subscription'               => $subs['subscription']['id_subscription'],
            'subscription_title'            => $subs['subscription']['subscription_title'],
            'subscription_sub_title'        => $subs['subscription']['subscription_sub_title'],
            'subscription_description'      => $subs['subscription']['subscription_description'],
            'url_subscription_image'        => $subs['subscription']['url_subscription_image'],
            'subscription_start'            => date('Y-m-d H:i:s', strtotime($subs['subscription']['subscription_start'])),
            'subscription_end'              => date('Y-m-d H:i:s', strtotime($subs['subscription']['subscription_end'])),
            'subscription_publish_start'    => date('Y-m-d H:i:s', strtotime($subs['subscription']['subscription_publish_start'])),
            'subscription_publish_end'      => date('Y-m-d H:i:s', strtotime($subs['subscription']['subscription_publish_end'])),
            'subscription_voucher_total'    => $subs['subscription']['subscription_voucher_total']
        ];
        $result['time_server'] = date('Y-m-d H:i:s');

        $result['subscription_voucher_used'] = 0;
        $voucher = [];
        foreach ($subs['subscription_user_vouchers'] as $key => $value) {
            if (!is_null($value['used_at'])) {
                $getTrx = Transaction::select(DB::raw('transactions.*,sum(transaction_products.transaction_product_qty) item_total'))->leftJoin('transaction_products','transactions.id_transaction','=','transaction_products.id_transaction')->with('outlet')->where('transactions.id_transaction', $value['id_transaction'])->groupBy('transactions.id_transaction')->first();
                $voucher[$key]['used_at']    = $value['used_at'];
                if (is_null($getTrx->outlet)) {
                    $voucher[$key]['outlet']     = '-';
                    $voucher[$key]['item']       = '-';
                } else {
                    $voucher[$key]['outlet']     = $getTrx->outlet->outlet_name;
                    $voucher[$key]['item']       = $getTrx->item_total;
                }
                $result['subscription_voucher_used']    = $result['subscription_voucher_used'] + 1;
            }
        }
        $i = 0;
        $result['subscription_content'][$i]['title']            = 'Voucher';
        $result['subscription_content'][$i]['detail_voucher']   = $voucher;
        $i++;
        foreach ($subs['subscription']['subscription_content'] as $keyContent => $valueContent) {
            if (!empty($valueContent['subscription_content_details'])) {
                $result['subscription_content'][$i]['title'] = $valueContent['title'];
                foreach ($valueContent['subscription_content_details'] as $key => $value) {
                    $result['subscription_content'][$i]['detail'][$key] = $value['content'];
                    // $content[$key] = '<li>'.$value['content'].'</li>';
                }
                // $result['deals_content'][$keyContent]['detail'] = '<ul style="color:#707070;">'.implode('', $content).'</ul>';
                $i++;
            }
        }

        $result['subscription_content'][$i]['is_outlet']    = 1;
        $result['subscription_content'][$i]['title']        = 'Berlaku di';

        if ($subs['subscription']['is_all_outlet'] == true) {
            $result['subscription_content'][$i]['detail'][] = 'Berlaku untuk semua outlet';
        } else {
            foreach ($subs['subscription']['outlet_by_city'] as $keyCity => $valueCity) {
                if (isset($valueCity['city_name'])) {
                    $result['subscription_content'][$i]['detail_available'][$keyCity]['city'] = $valueCity['city_name'];
                    foreach ($valueCity['outlet'] as $keyOutlet => $valueOutlet) {
                        $result['subscription_content'][$i]['detail_available'][$keyCity]['outlet'][$keyOutlet] = $valueOutlet['outlet_name'];
                        // $valTheOutlet[$keyOutlet] = '<li style="line-height: 12px;">' . $valueOutlet['outlet_name'] . '</li>';
                    }
                    // $city[$keyCity] = strtoupper($valueCity['city_name']) . '<br><ul style="color:#707070;">' .implode('', $valTheOutlet).'</ul>';
                    // $result['deals_content'][$i]['detail'] = implode('', $city);
                }
            }
        }
        return response()->json(MyHelper::checkGet($result));
    }

    public function subsLater(Request $request)
    {
        $bearer = $request->header('Authorization');
        if ($bearer == "") {
            return abort(404);
        }

        $subs = SubscriptionUser::with('subscription')->where('id_subscription_user', $request->id_subscription_user)->first()->toArray();
        
        $result = [
            'id_subscription_user'              => $subs['id_subscription_user'],
            'header_text'                       => 'PEMBELIAN BERHASIL',
            'subscription_header_text'          => 'Terima kasih telah membeli',
            'url_subscription_image'            => $subs['subscription']['url_subscription_image'],
            'bought_at'                         => date('Y-m-d H:i:s', strtotime($subs['bought_at'])),
            'subscription_user_receipt_number'  => $subs['subscription_user_receipt_number'],
            'balance_nominal'                   => $subs['balance_nominal']
        ];

        if ($subs['subscription_price_cash'] == 'Free') {
            $result['subscription_price']   = 'Free';
        } else {
            if ($subs['subscription_price_cash'] > 0) {
                $result['subscription_price']   = $subs['subscription_price_cash'];
            } elseif ($subs['subscription_price_point'] > 0) {
                $result['subscription_price']   = $subs['subscription_price_cash'];
            }
        }

        return response()->json(MyHelper::checkGet($result));
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
