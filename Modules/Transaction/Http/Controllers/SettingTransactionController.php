<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use App\Http\Models\Outlet;

use DB;

class SettingTransactionController extends Controller
{
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

        $productDis = $this->countDis($post);
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

    public function countDis($data)
    {
        $discountTotal = 0;
        $discount = 0;
        $totalAllDiscount = 0;
        $countSemen = 0;
        $discountFormula = $this->convertFormula('discount');
        // return $discountFormula;
        foreach ($data['item'] as $keyData => $valueData) {
            $product = Product::with('product_discounts')->where('id_product', $valueData['id_product'])->first();
            if (empty($product)) {
                DB::rollback();
                return response()->json([
                    'status' => 'fail', 
                    'messages' => ['Product Not Found']
                ]);
            }

            $priceProduct = ProductPrice::where('id_product', $valueData['id_product'])->where('id_outlet', $data['id_outlet'])->first();
            if (empty($priceProduct)) {
                DB::rollback();
                return response()->json([
                    'status' => 'fail', 
                    'messages' => ['Product Price Not Found']
                ]);
            }

            if (count($product['product_discounts']) > 0) {
                foreach ($product['product_discounts'] as $keyDiscount => $valueDiscount) {
                    if (!empty($valueDiscount['discount_percentage'])) {
                        $jat = $valueDiscount['discount_percentage'];

                        $count = $priceProduct['product_price'] * $jat / 100;
                    } else {
                        $count = $valueDiscount['discount_nominal'];
                    }

                    $now = date('Y-m-d');
                    $time = date('H:i:s');
                    $day = date('l');

                    if ($now < $valueDiscount['discount_start']) {
                        $count = 0;
                    }

                    if ($now > $valueDiscount['discount_end']) {
                        $count = 0;
                    }

                    if ($time < $valueDiscount['discount_time_start']) {
                        $count = 0;
                    }

                    if ($time > $valueDiscount['discount_time_end']) {
                        $count = 0;
                    }

                    if (strpos($valueDiscount['discount_days'], $day) === false) {
                        $count = 0;
                    }

                    $discountTotal = $valueData['qty'] * $count;
                    $countSemen += $discountTotal;
                    $discountTotal = 0;
                }
            }
        }

        return $countSemen;
    }

    function count($post) {
        $grandTotal = $this->grandTotal();

        foreach ($grandTotal as $keyTotal => $valueTotal) {
            if ($valueTotal == 'subtotal') {
                $post['sub'] = $this->countTransaction($valueTotal, $post);
                if (gettype($post['sub']) != 'array') {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Data not valid']
                    ]);
                }

                $post['subtotal'] = array_sum($post['sub']);
            } elseif ($valueTotal == 'discount') {
                $post['dis'] = $this->countTransaction($valueTotal, $post);

                $post['discount'] = $post['dis'] + $post['dis_sem'];
            } else {
                $post[$valueTotal] = $this->countTransaction($valueTotal, $post);
            }
        }

        return $post;
    }

    public function grandTotal() {
        $grandTotal = $this->setting('transaction_grand_total_order');
        
        $grandTotal = explode(',', $grandTotal);
        foreach ($grandTotal as $key => $value) {
            if (substr($grandTotal[$key], 0, 5) == 'empty') {
                unset($grandTotal[$key]);
            }
        }

        $grandTotal = array_values($grandTotal);
        return $grandTotal;
    }

    public function setting($value) {
        $setting = Setting::where('key', $value)->first();
        
        if (empty($setting->value)) {
            return response()->json(['Setting Not Found']);
        }

        return $setting->value;
    }

     public function countTransaction($value, $data) {
        $subtotal = isset($data['subtotal']) ? $data['subtotal'] : 0;
        $service  = isset($data['service']) ? $data['service'] : 0;
        $tax      = isset($data['tax']) ? $data['tax'] : 0;
        $shipping = isset($data['shipping']) ? $data['shipping'] : 0;
        $discount = isset($data['discount']) ? $data['discount'] : 0;
        // return $data;
        if ($value == 'subtotal') {
            $dataSubtotal = [];
            foreach ($data['item'] as $keyData => $valueData) {
                $product = Product::with('product_discounts', 'product_prices')->where('id_product', $valueData['id_product'])->first();
                if (empty($product)) {
                    DB::rollback();
                    return response()->json([
                        'status' => 'fail', 
                        'messages' => ['Product Not Found']
                    ]);
                }
                
                $productPrice = ProductPrice::where(['id_product' => $valueData['id_product'], 'id_outlet' => $data['id_outlet']])->first();
                if (empty($productPrice)) {
                    DB::rollback();
                    return response()->json([
                        'status' => 'fail',
                        'messages' => ['Price Product Not Found']
                    ]);
                }
        
                $price = $productPrice['product_price'] * $valueData['qty'];
                array_push($dataSubtotal, $price);
            }

            return $dataSubtotal;
        }

        if ($value == 'discount') {
            $discountTotal = 0;
            $discount = [];
            $discountFormula = $this->convertFormula('discount');
            
            $checkSettingPercent = Setting::where('key', 'discount_percent')->first();
            $checkSettingNominal = Setting::where('key', 'discount_nominal')->first();
            $count = 0;

            if (!empty($checkSettingPercent)) {
                if ($checkSettingPercent['value'] != '0' && $checkSettingPercent['value'] != '') {
                    $count = (eval('return ' . preg_replace('/([a-zA-Z0-9]+)/', '\$$1', $discountFormula) . ';'));
                }
            } else {
                if (!empty($checkSettingNominal)) {
                    if ($checkSettingNominal['value'] != '0' && $checkSettingNominal['value'] != '') {
                        $count = $checkSettingNominal;
                    }
                }
            }

            return $count;
        }

        if ($value == 'service') {
            $subtotal = $data['subtotal'];
            $serviceFormula = $this->convertFormula('service');
            $value = $this->serviceValue();

            $count = (eval('return ' . preg_replace('/([a-zA-Z0-9]+)/', '\$$1', $serviceFormula) . ';'));
            return $count;

        }

        if ($value == 'shipping') {
            return $shipping;

        }

        if ($value == 'tax') {
            $subtotal = $data['subtotal'];
            $taxFormula = $this->convertFormula('tax');
            $value = $this->taxValue();
            // return $taxFormula;

            $count = (eval('return ' . preg_replace('/([a-zA-Z0-9]+)/', '\$$1', $taxFormula) . ';'));
            return $count;

        }

        if ($value == 'point') {
            $subtotal = $data['subtotal'];
            $pointFormula = $this->convertFormula('point');
            $value = $this->pointValue();

            $count = floor(eval('return ' . preg_replace('/([a-zA-Z0-9]+)/', '\$$1', $pointFormula) . ';'));
            return $count;

        }

        if ($value == 'cashback') {
            $subtotal = $data['subtotal'];
            $cashbackFormula = $this->convertFormula('cashback');
            $value = $this->cashbackValue();
            $max = $this->cashbackValueMax();

            $count = floor(eval('return ' . preg_replace('/([a-zA-Z0-9]+)/', '\$$1', $cashbackFormula) . ';'));

            return $count;

        }
    }

    public function convertFormula($value) {
        $convert = $this->$value();
        
        return $convert;
    }

    public function discount() {
        $discount = $this->setting('transaction_discount_formula');
        return $discount;
    }

    public function tax() {
        $tax = $this->setting('transaction_tax_formula');

        $tax = preg_replace('/\s+/', '', $tax);
        return $tax;
    }

    public function service() {
        $service = $this->setting('transaction_service_formula');

        $service = preg_replace('/\s+/', '', $service);
        return $service;
    }

    public function point() {
        $point = $this->setting('point_acquisition_formula');

        $point = preg_replace('/\s+/', '', $point);
        return $point;
    }

    public function cashback() {
        $cashback = $this->setting('cashback_acquisition_formula');

        $cashback = preg_replace('/\s+/', '', $cashback);
        return $cashback;
    }

    public function taxValue() {
        $tax = $this->setting('tax');
        return $tax;
    }

    public function pointCount() {
        $point = $this->setting('point_acquisition_formula');
        return $point;
    }

    public function cashbackCount() {
        $cashback = $this->setting('cashback_acquisition_formula');
        return $cashback;
    }

    public function pointValue() {
        $point = $this->setting('point_conversion_value');
        return $point;
    }

    public function cashbackValue() {
        $cashback = $this->setting('cashback_conversion_value');
        return $cashback;
    }

    public function cashbackValueMax() {
        $cashback = $this->setting('cashback_maximum');
        return $cashback;
    }

    public function serviceValue() {
        $service = $this->setting('service');
        return $service;
    }
}
