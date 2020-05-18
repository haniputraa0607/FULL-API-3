<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use App\Http\Models\Outlet;
use App\Lib\MyHelper;

use DB;

class ApiSettingTransaction extends Controller
{
    function __construct() {
        ini_set('max_execution_time', 0);
        date_default_timezone_set('Asia/Jakarta');
        $this->setting_trx   = "Modules\Transaction\Http\Controllers\ApiSettingTransactionV2";
    }

    public function settingTrx(Request $request) {
        $post = $request->json()->all();
        $outlet = Outlet::where('id_outlet', $post['id_outlet'])->first();
        if (empty($outlet)) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Outlet Not Found']
            ]);
        }

        $totalDisProduct = 0;

        $productDis = app($this->setting_trx)->discountProduct($post);
        if ($productDis) {
            $totalDisProduct = $productDis;
        }

        $post['dis_sem'] = $totalDisProduct;

        $count = $this->count($post);
        $order = Setting::where('key', 'transaction_grand_total_order')->value('value');
        $exp   = explode(',', $order);
        
        for ($i=0; $i < count($exp); $i++) { 
            if (substr($exp[$i], 0, 5) == 'empty') {
                unset($exp[$i]);
                continue;
            }

            if (!isset($post['shipping'])) {
                if ($exp[$i] == 'shipping') {
                    unset($exp[$i]);
                    continue;
                }
            }
        }

        if (isset($post['balance'])) {
            array_splice($exp, 1, 0, 'balance');
        }

        array_values($exp);

        $imp = implode(',', $exp);

        $sub = 0;
        $tax = 0;
        $service = 0;
        $dis = 0;
        $ship = 0;
        $balance = 0;

        if (isset($count['subtotal'])) {
            $sub = $count['subtotal'];
        }

        if (isset($count['tax'])) {
            $tax = $count['tax'];
        }

        if (isset($count['service'])) {
            $service = $count['service'];
        }

        if (isset($count['discount'])) {
            $dis = $count['discount'];
        }

        if (isset($count['shipping'])) {
            $ship = $count['shipping'];
        }

        if (isset($post['balance'])) {
            $balance = $post['balance'];
        }

        $total = $sub + $tax + $service - $dis + $ship - $balance;
        if ($total < 1) {
            $total = 0;
        }

        if (isset($post['balance'])) {
            $result = [
                'order'    => $imp,
                'subtotal' => $sub,
                'balance'  => $balance,
                'tax'      => $tax,
                'service'  => $service,
                'discount' => $dis,
                'shipping' => $ship,
                'total'    => $total,
            ];
        } else {
            $result = [
                'order'    => $imp,
                'subtotal' => $sub,
                'tax'      => $tax,
                'service'  => $service,
                'discount' => $dis,
                'shipping' => $ship,
                'total'    => $total,
            ];
        }

        foreach ($result as $key => $value) {
            if (!isset($post['shipping'])) {
                if ($result[$key] == 'shipping') {
                    unset($result[$key]);
                    continue;
                }
            }
        }

        array_values($result);
        
        return response()->json([
            'status' => 'success',
            'result' => $result
        ]);
    }

    function count($post) {
        $grandTotal = app($this->setting_trx)->grandTotal();

        foreach ($grandTotal as $keyTotal => $valueTotal) {
            if ($valueTotal == 'subtotal') {
                $post['sub'] = app($this->setting_trx)->countTransaction($valueTotal, $post);
                if (gettype($post['sub']) != 'array') {
                    $mes = ['Data Not Valid'];

                    if (isset($post['sub']->original['messages'])) {
                        $mes = $post['sub']->original['messages'];

                        if ($post['sub']->original['messages'] == ['Price Product Not Found']) {
                            if (isset($post['sub']->original['product'])) {
                                $mes = ['Price Product Not Found with product '.$post['sub']->original['product'].' at outlet '.$outlet['outlet_name']];
                            }
                        }
                    }

                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $mes
                    ]);
                }

                $post['subtotal'] = array_sum($post['sub']);
                $post['subtotal'] = $post['subtotal'] - $post['dis_sem'];
            } elseif ($valueTotal == 'discount') {
                $post['dis'] = app($this->setting_trx)->countTransaction($valueTotal, $post);
                $mes = ['Data Not Valid'];

                if (isset($post['sub']->original['messages'])) {
                    $mes = $post['sub']->original['messages'];

                    if ($post['sub']->original['messages'] == ['Price Product Not Found']) {
                        if (isset($post['sub']->original['product'])) {
                            $mes = ['Price Product Not Found with product '.$post['sub']->original['product'].' at outlet '.$outlet['outlet_name']];
                        }
                    }

                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $mes
                    ]);
                }

                $post['discount'] = $post['dis'] + $post['dis_sem'];
            } else {
                $post[$valueTotal] = app($this->setting_trx)->countTransaction($valueTotal, $post);
            }
        }

        return $post;
    }
    /**
     * Check credit card payment gateway for transaction
     * @param  Request $request [description]
     * @return Array           [description]
     */
    public function ccPayment(Request $request)
    {
        $pg = Setting::select('value')->where('key','credit_card_payment_gateway')->pluck('value')->first()?:'Ipay88';
        return MyHelper::checkGet($pg);
    }
}
