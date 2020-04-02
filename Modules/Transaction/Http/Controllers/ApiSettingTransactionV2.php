<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use App\Http\Models\ProductModifier;

use DB;

class ApiSettingTransactionV2 extends Controller
{
    public function setting($value) {
        $setting = Setting::where('key', $value)->first();
        
        if (empty($setting->value)) {
            return response()->json(['Setting Not Found']);
        }

        return $setting->value;
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

    public function taxValue() {
        $tax = $this->setting('tax');
        return $tax;
    }

    public function convertFormula($value) {
        $convert = $this->$value();
        
        return $convert;
    }

    public function countTransaction($value, $data,$discount_promo=[]) {
        $subtotal = isset($data['subtotal']) ? $data['subtotal'] : 0;
        $service  = isset($data['service']) ? $data['service'] : 0;
        $tax      = isset($data['tax']) ? $data['tax'] : 0;
        $shipping = isset($data['shipping']) ? $data['shipping'] : 0;
        $discount = isset($data['discount']) ? $data['discount'] : 0;
        // return $data;
        if ($value == 'subtotal') {
            $dataSubtotal = [];
            foreach (($discount_promo['item']??$data['item']) as $keyData => $valueData) {
                $this_discount=0;
                $this_discount=$valueData['discount']??0;
    
                // if($discount_promo){
                //     foreach ($discount_promo['item']??[] as $disc) {
                //         if($disc['id_product']==$valueData['id_product']){
                //             $this_discount=$disc['discount']??0;
                //         }
                //     }
                // }

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
                        'messages' => ['Price Product Not Found'],
                        'product' => $product['product_name']
                    ]);
                }

                if($productPrice['product_price'] == null || $productPrice['product_price_base'] == null || $productPrice['product_price_tax'] == null){
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Price Product Not Valid'],
                        'product' => $product['product_name']
                    ]);
                }
                $mod_subtotal = 0;
                foreach ($valueData['modifiers'] as $modifier) {
                    $id_product_modifier = is_numeric($modifier)?$modifier:$modifier['id_product_modifier'];
                    $qty_product_modifier = is_numeric($modifier)?1:$modifier['qty'];
                    $mod = ProductModifier::with(['product_modifier_prices'=>function($query) use ($data){
                            $query->select('id_product_modifier_price','id_product_modifier','product_modifier_price');
                            $query->where('id_outlet',$data['id_outlet']);
                        }])
                        ->whereHas('product_modifier_prices',function($query) use ($data){
                            $query->where('id_outlet',$data['id_outlet']);
                            $query->whereNotNull('product_modifier_price');
                        })
                        ->find($id_product_modifier);
                    if(!$mod||!isset($mod['product_modifier_prices'][0]['product_modifier_price'])){
                        DB::rollBack();
                        return [
                            'status' => 'fail',
                            'messages' => ['Modifier not found']
                        ];
                    }
                    $mod = $mod->toArray();
                    $mod_subtotal += $mod['product_modifier_prices'][0]['product_modifier_price']*$qty_product_modifier;
                }
                // $price = $productPrice['product_price_base'] * $valueData['qty'];
                $price = (($productPrice['product_price']+$mod_subtotal) * $valueData['qty'])-$this_discount;
                array_push($dataSubtotal, $price);
            }

            return $dataSubtotal;
        }

        if ($value == 'discount') {
            $discountTotal = 0;
            // $discount = [];
            // $discountFormula = $this->convertFormula('discount');

            // $checkSettingPercent = Setting::where('key', 'discount_percent')->first();
            // $checkSettingNominal = Setting::where('key', 'discount_nominal')->first();
            // $count = 0;

            // if (!empty($checkSettingPercent)) {
            //     if ($checkSettingPercent['value'] != '0' && $checkSettingPercent['value'] != '') {
            //         $count = (eval('return ' . preg_replace('/([a-zA-Z0-9]+)/', '\$$1', $discountFormula) . ';'));
            //     }
            // } else {
            //     if (!empty($checkSettingNominal)) {
            //         if ($checkSettingNominal['value'] != '0' && $checkSettingNominal['value'] != '') {
            //             $count = $checkSettingNominal;
            //         }
            //     }
            // }
            foreach ($data['item'] as $keyData => $valueData) {
                $this_discount=0;
                if($discount_promo){
                    foreach ($discount_promo['item']??[] as $disc) {
                        if($disc['id_product']==$valueData['id_product']){
                            $this_discount=$disc['discount']??0;
                        }
                    }
                }
                $discountTotal += $this_discount;
            }
            return $discountTotal;
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

            //tax dari product price tax
            // $productTax = 0;
            // foreach ($data['item'] as $keyProduct => $valueProduct) {
            //     $checkProduct = Product::where('id_product', $valueProduct['id_product'])->first();
            //     if (empty($checkProduct)) {
            //         DB::rollback();
            //         return response()->json([
            //             'status'    => 'fail',
            //             'messages'  => ['Product Not Found'],
            //             'product' => $checkProduct['product_name']
            //         ]);
            //     }
    
            //     $checkPriceProduct = ProductPrice::where(['id_product' => $checkProduct['id_product'], 'id_outlet' => $data['id_outlet']])->first();
            //     if (empty($checkPriceProduct)) {
            //         return response()->json([
            //             'status'    => 'fail',
            //             'messages'  => ['Price Product Not Valid'],
            //             'product' => $checkProduct['product_name']
            //         ]);
            //     }

            //     if($checkPriceProduct['product_price'] == null || $checkPriceProduct['product_price_base'] == null || $checkPriceProduct['product_price_tax'] == null){
            //         return response()->json([
            //             'status'    => 'fail',
            //             'messages'  => ['Price Product Not Valid'],
            //             'product' => $checkProduct['product_name']
            //         ]);
            //     }

            //     $productTax += $checkPriceProduct['product_price_tax'] * $valueProduct['qty'];
            // }

            // return $productTax;

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

    public function discountProduct($data)
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

    public function getrandomstring($length = 120) {

       global $template;
       settype($template, "string");

       $template = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

       settype($length, "integer");
       settype($rndstring, "string");
       settype($a, "integer");
       settype($b, "integer");

       for ($a = 0; $a <= $length; $a++) {
               $b = rand(0, strlen($template) - 1);
               $rndstring .= $template[$b];
       }

       return $rndstring; 
    }

    public function getrandomnumber($length) {

       global $template;
       settype($template, "string");

       $template = "0987654321";

       settype($length, "integer");
       settype($rndstring, "string");
       settype($a, "integer");
       settype($b, "integer");

       for ($a = 0; $a <= $length; $a++) {
               $b = rand(0, strlen($template) - 1);
               $rndstring .= $template[$b];
       }

       return $rndstring; 
    }
}
