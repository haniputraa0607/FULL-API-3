<?php

namespace Modules\PromoCampaign\Lib;

use Modules\PromoCampaign\Entities\PromoCampaign;
use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\PromoCampaign\Entities\PromoCampaignReport;
use Modules\PromoCampaign\Entities\UserReferralCode;
use Modules\PromoCampaign\Entities\PromoCampaignReferralTransaction;
use Modules\PromoCampaign\Entities\PromoCampaignReferral;
use App\Http\Models\Product;
use App\Http\Models\ProductModifier;
use App\Http\Models\UserDevice;
use App\Http\Models\User;
use App\Http\Models\Transaction;
use App\Http\Models\Setting;
use App\Http\Models\Deal;
use App\Http\Models\DealsUser;
use App\Http\Models\Outlet;
use App\Http\Models\ProductModifierPrice;
use App\Http\Models\ProductModifierGlobalPrice;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\Product\Entities\ProductDetail;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantGroupDetail;
use Modules\ProductVariant\Entities\ProductVariantGroupSpecialPrice;
use Modules\Brand\Entities\BrandOutlet;
use Modules\Brand\Entities\BrandProduct;
use Modules\Brand\Entities\Brand;
use Modules\Product\Entities\ProductModifierGroupPivot;
use Modules\ProductVariant\Entities\ProductVariantPivot;
use App\Lib\MyHelper;
use Modules\IPay88\Lib\IPay88;
use Modules\PromoCampaign\Lib\PromoCampaignToolsV1;

class PromoCampaignTools
{
    public function __construct()
    {
        $this->user     = "Modules\Users\Http\Controllers\ApiUser";
        $this->promo_campaign       = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
        $this->subscription_use     = "Modules\Subscription\Http\Controllers\ApiSubscriptionUse";
        $this->outlet_group_filter  = "Modules\Outlet\Http\Controllers\ApiOutletGroupFilterController";
    }
    /**
     * validate transaction to use promo campaign
     * @param   int         $id_promo   id promo campaigm
     * @param   array       $trxs       array of item and total transaction
     * @param   array       $error      error message
     * @return  array/boolean     modified array of trxs if can, otherwise false
     */
    public function validatePromo($request, $id_promo, $id_outlet, $trxs, &$errors, $source = 'promo_campaign', &$errorProduct = 0, $delivery_fee = 0, $subtotal_per_brand = [])
    {
        if (!is_numeric($id_promo)) {
            $errors[] = 'Id promo not valid';
            return false;
        }
        if (!is_array($trxs)) {
            $errors[] = 'Transaction data not valid';
            return false;
        }

        if ($source == 'promo_campaign') {
            $promo = PromoCampaign::with('promo_campaign_outlets')->find($id_promo);
            $promo_outlet = $promo->promo_campaign_outlets;
            $promo_outlet_groups = $promo->outlet_groups;
        } elseif ($source == 'deals') {
            $promo = Deal::with('outlets_active')->find($id_promo);
            $promo_outlet = $promo->outlets_active;
            $promo_outlet_groups = $promo->outlet_groups;
        } else {
            $errors[] = 'Promo not found';
            return false;
        }

        if (!$promo) {
            $errors[] = 'Promo not found';
            return false;
        }

        if ($promo->id_brand) {
            $pct = new PromoCampaignToolsV1();
            return $pct->validatePromo($id_promo, $id_outlet, $trxs, $errors, $source, $errorProduct, $delivery_fee);
        }

        $promo_brand = $promo->{$source . '_brands'}->pluck('id_brand')->toArray();
        $outlet = $this->checkOutletBrandRule($id_outlet, $promo->is_all_outlet ?? 0, $promo_outlet, $promo_brand, $promo->brand_rule, $promo_outlet_groups);

        if (!$outlet) {
            $errors[] = 'Promo tidak dapat digunakan di outlet ini.';
            return false;
        }

        if (isset($request['type'])) {
            $promo_shipment = $promo->{$source . '_shipment_method'}->pluck('shipment_method');
            if ($promo->promo_type == 'Discount delivery') {
                if ($request->type == 'Pickup Order') {
                    $errors[] = 'Promo tidak dapat digunakan untuk Pick Up';
                    return false;
                }
                if (count($promo_shipment) == 1 && $promo_shipment[0] == 'Pickup Order') {
                    $promo->is_all_shipment = 1;
                }
            }

            $shipment_method = ($request->type == 'Pickup Order' || $request->type == 'GO-SEND') ? $request->type : $request->courier;
            $check_shipment  = $this->checkShipmentRule($promo->is_all_shipment ?? 0, $shipment_method, $promo_shipment);
            if (!$check_shipment) {
                $errors[] = 'Promo tidak dapat digunakan untuk tipe order ini';
                return false;
            }
        }

        if (isset($request['payment_type']) && (isset($request['payment_id']) || isset($request['payment_detail']))) {
            $promo_payment  = $promo->{$source . '_payment_method'}->pluck('payment_method');
            $payment_method = $this->getPaymentMethod($request['payment_type'], $request['payment_id'], $request['payment_detail']);
            $check_payment  = $this->checkPaymentRule($promo->is_all_payment ?? 0, $payment_method, $promo_payment);

            if (!$check_payment) {
                $errors[] = 'Promo tidak dapat digunakan untuk metode pembayaran ini';
                return false;
            }
        }

        if ((!empty($promo->date_start) && !empty($promo->date_end)) && (strtotime($promo->date_start) > time() || strtotime($promo->date_end) < time())) {
            $errors[] = 'Promo is not valid';
            return false;
        }

        $discount = 0;
        $discount_delivery = 0;

        /*
        * dikomen karena sekarang belum digunakan
        *
        // add product discount if exist
        foreach ($trxs as  $id_trx => &$trx) {
            $product=Product::with(['product_prices' => function($q) use ($id_outlet){
                            $q->where('id_outlet', '=', $id_outlet)
                              ->where('product_status', '=', 'Active')
                              ->where('product_stock_status', '=', 'Available');
                        } ])->find($trx['id_product']);
            //is product available
            if(!$product){
                // product not available
                $errors[]='Product with id '.$trx['id_product'].' could not be found';
                continue;
            }
            $product_discount=$this->getProductDiscount($product)*$trx['qty'];
            $product_price=$product->product_prices[0]->product_price??[];
            // $discount+=$product_discount;
            if($product_discount){
                // $trx['discount']=$product_discount;
                $trx['new_price']=($product_price*$trx['qty'])-$product_discount;
            }
        }
        */

        if ($promo->promo_type != 'Discount delivery') {
            //get all modifier in array
            $mod = [];
            foreach ($trxs as $key => $value) {
                foreach ($value['modifiers'] as $key2 => $value2) {
                    $mod[] = $value2['id_product_modifier'] ?? $value2;
                }
            }
            // remove duplicate modifiers
            $mod = array_flip($mod);
            $mod = array_flip($mod);
            // get all modifier data
            $mod = $this->getAllModifier($mod, $id_outlet);

            // get mod price
            $mod_price = [];
            foreach ($mod as $key => $value) {
                $mod_price[$value['id_product_modifier']] = $value['product_modifier_price'] ?? 0;
            }
        }

        $missing_product_messages = null;
        switch ($promo->promo_type) {
            case 'Product discount':
                // load required relationship
                $promo->load($source . '_product_discount', $source . '_product_discount_rules');
                $promo_rules = $promo[$source . '_product_discount_rules'];
                $promo_product = $promo[$source . '_product_discount']->toArray();
                $max_product = $promo_rules->max_product;
                $qty_promo_available = [];

                $product_name = $this->getProductName($promo_product, $promo->product_rule);

                if (!$promo_rules->is_all_product) {
                    if ($promo[$source . '_product_discount']->isEmpty()) {
                        $errors[] = 'Produk tidak ditemukan';
                        return false;
                    }
                    $promo_product = $promo[$source . '_product_discount']->toArray();
                    $promo_product_count = count($promo_product);

                    $product_error_applied = $this->checkProductErrorApplied($promo_product, $id_outlet, $missing_product_messages);

                    $check_product = $this->checkProductRule($promo, $promo_brand, $promo_product, $trxs);

                    // promo product not available in cart?
                    if (!$check_product) {
                        $message = $this->getMessage('error_product_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b>.';
                        $message = MyHelper::simpleReplace($message, ['product' => $product_name]);
                        $errors[] = $missing_product_messages ?? $message;
                        $errorProduct = $product_error_applied;
                        return false;
                    }
                } else {
                    $promo_product = "*";
                    $product_error_applied = 'all';
                }

                $get_promo_product = $this->getPromoProduct($trxs, $promo_brand, $promo_product);
                $product = $get_promo_product['product'];

                // product not found? buat jaga-jaga kalau sesuatu yang tidak diinginkan terjadi
                if (!$product) {
                    $message = $this->getMessage('error_product_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b>.';
                    $message = MyHelper::simpleReplace($message, ['product' => $product_name]);

                    $errors[] = $missing_product_messages ?? $message;
                    $errorProduct = $product_error_applied;
                    return false;
                }

                // get product price
                foreach ($product as $key => $value) {
                    $product[$key]['price'] = null;
                    $product[$key]['product_price'] = null;
                    $product_price = $this->getProductPrice($id_outlet, $value['id_product'], $value['id_product_variant_group'], $value['id_brand']);
                    if (!$product_price) {
                        $errors[] = 'Produk tidak ditemukan';
                        continue;
                    }
                    $product[$key]['product_price'] = $product_price;
                    $product[$key]['price'] = $product_price['product_price'];
                }

                // sort product by price desc
                uasort($product, function ($a, $b) {
                    return $b['price'] - $a['price'];
                });

                $merge_product = [];
                foreach ($product as $key => $value) {
                    if (isset($merge_product[$value['id_product']])) {
                        $merge_product[$value['id_product']] += $value['qty'];
                    } else {
                        $merge_product[$value['id_product']] = $value['qty'];
                    }
                }

                if ($promo->product_rule == 'and') {
                    $max_promo_qty = 0;
                    foreach ($merge_product as $value) {
                        if ($max_promo_qty == 0 || $max_promo_qty > $value) {
                            $max_promo_qty = $value;
                        }
                    }
                    $promo_qty_each = $max_promo_qty == 0 || (isset($promo_rules->max_product) && $promo_rules->max_product < $max_promo_qty) ? $promo_rules->max_product : $max_promo_qty;
                } else {
                    $promo_qty_each = $promo_rules->max_product;
                }

                // get max qty of product that can get promo
                foreach ($product as $key => $value) {
                    if (!empty($promo_qty_each)) {
                        if (!isset($qty_each[$value['id_brand']][$value['id_product']])) {
                            $qty_each[$value['id_brand']][$value['id_product']] = $promo_qty_each;
                        }

                        if ($qty_each[$value['id_brand']][$value['id_product']] < 0) {
                            $qty_each[$value['id_brand']][$value['id_product']] = 0;
                        }

                        if ($qty_each[$value['id_brand']][$value['id_product']] > $value['qty']) {
                            $promo_qty = $value['qty'];
                        } else {
                            $promo_qty = $qty_each[$value['id_brand']][$value['id_product']];
                        }

                        $qty_each[$value['id_brand']][$value['id_product']] -= $value['qty'];
                    } else {
                        $promo_qty = $value['qty'];
                    }

                    $product[$key]['promo_qty'] = $promo_qty;
                }

                foreach ($trxs as $key => &$trx) {
                    if (!isset($product[$key])) {
                        continue;
                    }

                    $modifier = 0;
                    foreach ($trx['modifiers'] as $key2 => $value2) {
                        $modifier += $mod_price[$value2['id_product_modifier'] ?? $value2] ?? 0;
                    }

                    $trx['promo_qty'] = $product[$key]['promo_qty'];
                    $discount += $this->discount_product($product[$key]['product_price'], $promo_rules, $trx, $modifier);
                }
                if ($discount <= 0) {
                    $message = $this->getMessage('error_product_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b>.';
                    $message = MyHelper::simpleReplace($message, ['product' => 'produk bertanda khusus']);

                    $errors[] = $missing_product_messages ?? $message;
                    $errorProduct = $product_error_applied;
                    return false;
                }
                break;

            case 'Tier discount':
                // load requirement relationship
                $promo->load($source . '_tier_discount_rules', $source . '_tier_discount_product');
                $promo_product = $promo[$source . '_tier_discount_product'];
                $promo_product->load('product');
                if (!$promo_product) {
                    $errors[] = 'Tier discount promo product is not set correctly';
                    return false;
                }

                // get min max required for error message
                $promo_rules = $promo[$source . '_tier_discount_rules'];
                $min_qty = null;
                $max_qty = null;
                foreach ($promo_rules as $rule) {
                    if ($min_qty === null || $rule->min_qty < $min_qty) {
                        $min_qty = $rule->min_qty;
                    }
                    if ($max_qty === null || $rule->max_qty > $max_qty) {
                        $max_qty = $rule->max_qty;
                    }
                }

                $minmax = ($min_qty != $max_qty ? "$min_qty sampai $max_qty" : $min_qty) . " item";
                $promo_product_array = $promo_product->toArray();
                $promo_product_id = array_column($promo_product_array, 'id_product');
                $promo_product_count = count($promo_product);

                $product_name = $this->getProductName($promo_product, $promo->product_rule);

                if (!$promo_rules[0]->is_all_product) {
                    if ($promo[$source . '_tier_discount_product']->isEmpty()) {
                        $errors[] = 'Produk tidak ditemukan';
                        return false;
                    }
                    $promo_product = $promo[$source . '_tier_discount_product']->toArray();
                    $promo_product_count = count($promo_product);

                    $product_error_applied = $this->checkProductErrorApplied($promo_product, $id_outlet, $missing_product_messages);

                    $check_product = $this->checkProductRule($promo, $promo_brand, $promo_product, $trxs);

                    // promo product not available in cart?
                    if (!$check_product) {
                        $message = $this->getMessage('error_tier_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b> sebanyak %minmax%.';
                        $message = MyHelper::simpleReplace($message, ['product' => $product_name, 'minmax' => $minmax]);
                        $errors[] = $missing_product_messages ?? $message;
                        $errorProduct = $product_error_applied;
                        return false;
                    }
                } else {
                    $promo_product = "*";
                    $product_error_applied = 'all';
                }

                $get_promo_product = $this->getPromoProduct($trxs, $promo_brand, $promo_product);
                $product = $get_promo_product['product'];
                $total_product = $get_promo_product['total_product'];

                // product not found? buat jaga-jaga kalau sesuatu yang tidak diinginkan terjadi
                if (!$product) {
                    $minmax = ($min_qty != $max_qty ? "$min_qty sampai $max_qty" : $min_qty) . " item";
                    $message = $this->getMessage('error_tier_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b> sebanyak %minmax%.';
                    $message = MyHelper::simpleReplace($message, ['product' => $product_name, 'minmax' => $minmax]);

                    $errors[] = $missing_product_messages ?? $message;
                    $errorProduct = $product_error_applied;
                    return false;
                }

                // sum total quantity of same product
                $item_get_promo = []; // include brand
                $item_promo = []; // only product/item
                foreach ($product as $key => $value) {
                    if (isset($item_promo[$value['id_product']])) {
                        $item_promo[$value['id_product']] += $value['qty'];
                    } else {
                        $item_promo[$value['id_product']] = $value['qty'];
                    }

                    if (isset($item_get_promo[$value['id_brand'] . '-' . $value['id_product']])) {
                        $item_get_promo[$value['id_brand'] . '-' . $value['id_product']] += $value['qty'];
                    } else {
                        $item_get_promo[$value['id_brand'] . '-' . $value['id_product']] = $value['qty'];
                    }
                }

                //find promo rules
                $promo_rule = null;
                if ($promo->product_rule == "and" && $promo_product != "*") {
                    $req_valid  = true;
                    $rule_key   = [];
                    $promo_qty_each = 0;
                    foreach ($product as $key => &$val) {
                        $min_qty    = null;
                        $max_qty    = null;
                        $temp_rule_key[$key] = [];

                        foreach ($promo_rules as $key2 => $rule) {
                            if ($min_qty === null || $rule->min_qty < $min_qty) {
                                $min_qty = $rule->min_qty;
                            }
                            if ($max_qty === null || $rule->max_qty > $max_qty) {
                                $max_qty = $rule->max_qty;
                            }

                            if ($rule->min_qty > $item_get_promo[$val['id_brand'] . '-' . $val['id_product']]) {
                                if (empty($temp_rule_key[$key])) {
                                    $req_valid = false;
                                    break;
                                } else {
                                    continue;
                                }
                            }
                            $temp_rule_key[$key][]  = $key2;
                        }

                        if ($item_get_promo[$val['id_brand'] . '-' . $val['id_product']] < $promo_qty_each || $promo_qty_each == 0) {
                            $promo_qty_each = $item_get_promo[$val['id_brand'] . '-' . $val['id_product']];
                        }

                        if (!empty($rule_key)) {
                            $rule_key = array_intersect($rule_key, $temp_rule_key[$key]);
                        } else {
                            $rule_key = $temp_rule_key[$key];
                        }

                        if (!$req_valid) {
                            break;
                        }
                    }

                    if ($req_valid && !empty($rule_key)) {
                        $rule_key   = end($rule_key);
                        $promo_rule = $promo_rules[$rule_key];
                        $promo_qty_each = $promo_qty_each > $promo_rule->max_qty ? $promo_rule->max_qty : $promo_qty_each;
                    }
                } else {
                    $min_qty    = null;
                    $max_qty    = null;

                    foreach ($promo_rules as $rule) {
                        if ($min_qty === null || $rule->min_qty < $min_qty) {
                            $min_qty = $rule->min_qty;
                        }
                        if ($max_qty === null || $rule->max_qty > $max_qty) {
                            $max_qty = $rule->max_qty;
                        }

                        if ($rule->min_qty > $total_product) { // total keseluruhan product
                            continue;
                        }
                        $promo_rule = $rule;
                    }
                }

                if (!$promo_rule) {
                    $minmax = ($min_qty != $max_qty ? "$min_qty sampai $max_qty" : $min_qty) . " item";
                    $message = $this->getMessage('error_tier_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b> sebanyak %minmax%.';
                    $message = MyHelper::simpleReplace($message, ['product' => $product_name, 'minmax' => $minmax]);

                    $errors[] = $missing_product_messages ?? $message;
                    $errorProduct = $product_error_applied;
                    return false;
                }

                // get product price
                foreach ($product as $key => $value) {
                    $product[$key]['price'] = null;
                    $product[$key]['product_price'] = null;
                    $product_price = $this->getProductPrice($id_outlet, $value['id_product'], $value['id_product_variant_group'], $value['id_brand']);
                    if (!$product_price) {
                        $errors[] = 'Produk tidak ditemukan';
                        continue;
                    }
                    $product[$key]['product_price'] = $product_price;
                    $product[$key]['price'] = $product_price['product_price'];
                }

                // sort product price desc
                uasort($product, function ($a, $b) {
                    return $b['price'] - $a['price'];
                });

                // get max qty of product that can get promo
                $total_promo_qty = $promo_rule->max_qty < $total_product ? $promo_rule->max_qty : $total_product;
                foreach ($product as $key => $value) {
                    if (!empty($promo_qty_each)) {
                        if ($value['product_type'] == 'variant') {
                            if (!isset($qty_each[$value['id_brand']][$value['id_product']][$value['id_product_variant_group']])) {
                                $qty_each[$value['id_brand']][$value['id_product']][$value['id_product_variant_group']] = $promo_qty_each;
                            }

                            if ($qty_each[$value['id_brand']][$value['id_product']][$value['id_product_variant_group']] < 0) {
                                $qty_each[$value['id_brand']][$value['id_product']][$value['id_product_variant_group']] = 0;
                            }

                            if ($qty_each[$value['id_brand']][$value['id_product']][$value['id_product_variant_group']] > $value['qty']) {
                                $promo_qty = $value['qty'];
                            } else {
                                $promo_qty = $qty_each[$value['id_brand']][$value['id_product']][$value['id_product_variant_group']];
                            }

                            $qty_each[$value['id_brand']][$value['id_product']][$value['id_product_variant_group']] -= $value['qty'];
                        } else {
                            if (!isset($qty_each[$value['id_brand']][$value['id_product']])) {
                                $qty_each[$value['id_brand']][$value['id_product']] = $promo_qty_each;
                            }

                            if ($qty_each[$value['id_brand']][$value['id_product']] < 0) {
                                $qty_each[$value['id_brand']][$value['id_product']] = 0;
                            }

                            if ($qty_each[$value['id_brand']][$value['id_product']] > $value['qty']) {
                                $promo_qty = $value['qty'];
                            } else {
                                $promo_qty = $qty_each[$value['id_brand']][$value['id_product']];
                            }

                            $qty_each[$value['id_brand']][$value['id_product']] -= $value['qty'];
                        }
                    } else {
                        if ($total_promo_qty < 0) {
                            $total_promo_qty = 0;
                        }

                        if ($total_promo_qty > $value['qty']) {
                            $promo_qty = $value['qty'];
                        } else {
                            $promo_qty = $total_promo_qty;
                        }

                        $total_promo_qty -= $promo_qty;
                    }

                    $product[$key]['promo_qty'] = $promo_qty;
                }

                // count discount
                $product_id = array_column($product, 'id_product');
                foreach ($trxs as $key => &$trx) {
                    if (!isset($product[$key])) {
                        continue;
                    }

                    if (!in_array($trx['id_brand'], $promo_brand)) {
                        continue;
                    }

                    $modifier = 0;
                    foreach ($trx['modifiers'] as $key2 => $value2) {
                        $modifier += $mod_price[$value2['id_product_modifier'] ?? $value2] ?? 0;
                    }

                    if (in_array($trx['id_product'], $product_id)) {
                        // add discount
                        $trx['promo_qty'] = $product[$key]['promo_qty'];
                        if ($trx['promo_qty'] == 0) {
                            continue;
                        }
                        $discount += $this->discount_product($product[$key]['product_price'], $promo_rule, $trx, $modifier);
                    }
                }

                break;

            case 'Buy X Get Y':
                // load requirement relationship
                $promo->load($source . '_buyxgety_rules', $source . '_buyxgety_product_requirement');
                $promo_product = $promo[$source . '_buyxgety_product_requirement'];
                $promo_product->load('product');

                if (!$promo_product) {
                    $errors[] = 'Promo tidak ditemukan';
                    return false;
                }

                $product_error_applied = $this->checkProductErrorApplied($promo_product, $id_outlet, $missing_product_messages);

                // sum total quantity of same product
                foreach ($trxs as $key => $value) {
                    if (isset($item_get_promo[$value['id_brand']][$value['id_product']])) {
                        $item_get_promo[$value['id_brand']][$value['id_product']] += $value['qty'];
                    } else {
                        $item_get_promo[$value['id_brand']][$value['id_product']] = $value['qty'];
                    }
                }

                $promo_rules = $promo[$source . '_buyxgety_rules'];

                // get min max for error message
                $min_qty = null;
                $max_qty = null;
                foreach ($promo_rules as $rule) {
                    if ($min_qty === null || $rule->min_qty_requirement < $min_qty) {
                        $min_qty = $rule->min_qty_requirement;
                    }
                    if ($max_qty === null || $rule->max_qty_requirement > $max_qty) {
                        $max_qty = $rule->max_qty_requirement;
                    }
                }

                // promo product not available in cart?
                $minmax = ($min_qty != $max_qty ? "$min_qty sampai $max_qty" : $min_qty) . " item";
                $promo_product_array = $promo_product->toArray();
                $promo_product_id = array_column($promo_product_array, 'id_product');

                // promo product not available in cart?
                $product_name = $this->getProductName($promo_product, $promo->product_rule);


                if (!$promo_rules[0]->is_all_product) {
                    if ($promo[$source . '_buyxgety_product_requirement']->isEmpty()) {
                        $errors[] = 'Produk tidak ditemukan';
                        return false;
                    }
                    $promo_product = $promo[$source . '_buyxgety_product_requirement']->toArray();
                    $promo_product_count = count($promo_product);

                    $check_product = $this->checkProductRule($promo, $promo_brand, $promo_product, $trxs);

                    // promo product not available in cart?
                    if (!$check_product) {
                        $message = $this->getMessage('error_buyxgety_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b> sebanyak %minmax%.';
                        $message = MyHelper::simpleReplace($message, ['product' => $product_name, 'minmax' => $minmax]);

                        $errors[] = $missing_product_messages ?? $message;
                        $errorProduct = $product_error_applied;
                        return false;
                    }
                } else {
                    $promo_product = "*";
                    $product_error_applied = 'all';
                }

                $get_promo_product = $this->getPromoProduct($trxs, $promo_brand, $promo_product);
                $product = $get_promo_product['product'];
                $total_product = $get_promo_product['total_product'];

                // product not found? buat jaga-jaga kalau sesuatu yang tidak diinginkan terjadi
                if (!$product) {
                    $minmax = ($min_qty != $max_qty ? "$min_qty sampai $max_qty" : $min_qty) . " item";
                    $message = $this->getMessage('error_buyxgety_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b> sebanyak %minmax%.';
                    $message = MyHelper::simpleReplace($message, ['product' => $product_name, 'minmax' => $minmax]);

                    $errors[] = $missing_product_messages ?? $message;
                    $errorProduct = $product_error_applied;
                    return false;
                }

                // sum total quantity of same product
                $item_get_promo = []; // include brand
                $item_promo = []; // only product
                foreach ($product as $key => $value) {
                    if (isset($item_promo[$value['id_product']])) {
                        $item_promo[$value['id_product']] += $value['qty'];
                    } else {
                        $item_promo[$value['id_product']] = $value['qty'];
                    }

                    if (isset($item_get_promo[$value['id_brand'] . '-' . $value['id_product']])) {
                        $item_get_promo[$value['id_brand'] . '-' . $value['id_product']] += $value['qty'];
                    } else {
                        $item_get_promo[$value['id_brand'] . '-' . $value['id_product']] = $value['qty'];
                    }
                }

                //find promo
                $promo_rules = $promo[$source . '_buyxgety_rules'];
                $promo_rule = false;
                $min_qty = null;
                $max_qty = null;

                $promo_rule = null;
                if ($promo->product_rule == "and" && $promo_product != "*") {
                    $req_valid  = true;
                    $rule_key   = [];
                    foreach ($product as $key => &$val) {
                        $min_qty    = null;
                        $max_qty    = null;
                        $temp_rule_key[$key] = [];

                        foreach ($promo_rules as $key2 => $rule) {
                            if ($min_qty === null || $rule->min_qty_requirement < $min_qty) {
                                $min_qty = $rule->min_qty_requirement;
                            }
                            if ($max_qty === null || $rule->max_qty_requirement > $max_qty) {
                                $max_qty = $rule->max_qty_requirement;
                            }

                            if ($rule->min_qty_requirement > $item_get_promo[$val['id_brand'] . '-' . $val['id_product']]) {
                                if (empty($temp_rule_key[$key])) {
                                    $req_valid = false;
                                    break;
                                } else {
                                    continue;
                                }
                            }
                            $temp_rule_key[$key][] = $key2;
                        }

                        if (!empty($rule_key)) {
                            $rule_key = array_intersect($rule_key, $temp_rule_key[$key]);
                        } else {
                            $rule_key = $temp_rule_key[$key];
                        }

                        if (!$req_valid) {
                            break;
                        }
                    }
                    if ($req_valid && !empty($rule_key)) {
                        $rule_key   = end($rule_key);
                        $promo_rule = $promo_rules[$rule_key];
                    }
                } else {
                    $min_qty    = null;
                    $max_qty    = null;

                    foreach ($promo_rules as $rule) {
                        if ($min_qty === null || $rule->min_qty_requirement < $min_qty) {
                            $min_qty = $rule->min_qty_requirement;
                        }
                        if ($max_qty === null || $rule->max_qty_requirement > $max_qty) {
                            $max_qty = $rule->max_qty_requirement;
                        }

                        if ($rule->min_qty_requirement > $total_product) { // total keseluruhan product
                            continue;
                        }
                        $promo_rule = $rule;
                    }
                }

                if (!$promo_rule) {
                    $minmax = ($min_qty != $max_qty ? "$min_qty sampai $max_qty" : $min_qty) . " item";
                    $message = $this->getMessage('error_buyxgety_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b> sebanyak %minmax%.';
                    $message = MyHelper::simpleReplace($message, ['product' => $product_name, 'minmax' => $minmax]);

                    $errors[] = $missing_product_messages ?? $message;
                    $errorProduct = $product_error_applied;
                    return false;
                }
                // get product with brand

                $benefit_product = $this->getOneProductV2($id_outlet, $promo_rule->benefit_id_product, $promo_rule->id_brand, 'with_brand', $promo_rule->id_product_variant_group);

                if (!$benefit_product) {
                    $errors[] = "Product benefit tidak ditemukan.";
                    return false;
                }

                $benefit_qty    = $promo_rule->benefit_qty;
                $benefit_value  = $promo_rule->discount_value;
                $benefit_type   = $promo_rule->discount_type;
                $benefit_max_value = $promo_rule->max_percent_discount;
                $benefit_product_price = $this->getProductPrice($id_outlet, $promo_rule->benefit_id_product, $promo_rule->id_product_variant_group, $promo_rule->id_brand);

                $benefit = null;
                $promo_modifier = $promo_rule->{$source . '_buyxgety_product_modifiers'};
                $benefit_modifier = [];
                foreach ($promo_modifier as $value) {
                    $benefit_modifier[] = [
                        'id_product_modifier' => $value['id_product_modifier'],
                        'qty' => 1
                    ];
                }

                $rule = (object) [
                    'max_qty' => $benefit_qty,
                    'discount_type' => $benefit_type,
                    'discount_value' => $benefit_value,
                    'max_percent_discount' => $benefit_max_value
                ];

                if (!$promo_rule->id_product_variant_group) {
                    $benefit_variant = $this->getCheapestVariant($id_outlet, $benefit_product->id_product);
                }

                $extra_modifier_product = ProductModifierGroupPivot::where('id_product', $benefit_product->id_product)
                                        ->leftJoin('product_modifiers', 'product_modifiers.id_product_modifier_group', '=', 'product_modifier_group_pivots.id_product_modifier_group')
                                        ->where('product_modifiers.product_modifier_visibility', 'Visible')
                                        ->get();
                $extra_modifier = [];
                $used_modifier = [];
                foreach ($extra_modifier_product as $key => $value) {
                    if (empty($used_modifier[$value['id_product_modifier_group']])) {
                        $extra_modifier[] = [
                            'id_product_modifier' => $value['id_product_modifier'],
                            'qty' => 1
                        ];

                        $used_modifier[$value['id_product_modifier_group']] = 1;
                    }
                }

                if ($promo_rule->id_product_variant_group || $benefit_variant) {
                    $variant = ProductVariantPivot::where('id_product_variant_group', ($promo_rule->id_product_variant_group ?? $benefit_variant))->get();

                    foreach ($variant as $key => $value) {
                        $extra_modifier_variant = ProductModifierGroupPivot::where('id_product_variant', $value['id_product_variant'])
                                                ->leftJoin('product_modifiers', 'product_modifiers.id_product_modifier_group', '=', 'product_modifier_group_pivots.id_product_modifier_group')
                                                ->where('product_modifiers.product_modifier_visibility', 'Visible')
                                                ->orderBy('product_modifier_order')
                                                ->orderBy('id_product_modifier')
                                                ->first();
                        if ($extra_modifier_variant) {
                            // $extra_modifier[] = $extra_modifier_variant['id_product_modifier'];
                            $extra_modifier[] = [
                                'id_product_modifier' => $extra_modifier_variant['id_product_modifier'],
                                'qty' => 1
                            ];
                        }
                    }
                }

                // add product benefit
                $benefit_item = [
                    'id_custom'     => isset(end($trxs)['id_custom']) ? end($trxs)['id_custom'] + 1 : '',
                    'id_product'    => $benefit_product->id_product,
                    'id_brand'      => $benefit_product->brand->id_brand,
                    'qty'           => $promo_rule->benefit_qty,
                    'is_promo'      => 1,
                    'is_free'       => ($promo_rule->discount_type == "percent" && $promo_rule->discount_value == 100) ? 1 : 0,
                    'modifiers'     => [],
                    'bonus'         => 1,
                    'id_product_variant_group' => $promo_rule->id_product_variant_group,
                    'modifiers' => $benefit_modifier
                ];
                // $benefit_item['id_product']  = $benefit_product->id_product;
                // $benefit_item['id_brand']    = $benefit_product->brands[0]->id_brand??'';
                // $benefit_item['qty']         = $promo_rule->benefit_qty;

                $discount += $this->discount_product($benefit_product_price, $rule, $benefit_item);

                array_push($trxs, $benefit_item);
                break;

            case 'Discount global':
                // load required relationship
                $promo->load('promo_campaign_discount_global_rule');
                $promo_rules = $promo->promo_campaign_discount_global_rule;
                // get jumlah harga
                $total_price = 0;
                foreach ($trxs as $id_trx => &$trx) {
                    $product = Product::with(['product_prices' => function ($q) use ($id_outlet) {
                            $q->where('id_outlet', '=', $id_outlet)
                              ->where('product_status', '=', 'Active')
                              ->where('product_stock_status', '=', 'Available')
                              ->where('product_visibility', '=', 'Visible');
                    } ])->find($trx['id_product']);
                    $qty = $trx['qty'];
                    $total_price += $qty * $product->product_prices[0]->product_price ?? [];
                }
                if ($promo_rules->discount_type == 'Percent') {
                    $discount += ($total_price * $promo_rules->discount_value) / 100;
                } else {
                    if ($promo_rules->discount_value < $total_price) {
                        $discount += $promo_rules->discount_value;
                    } else {
                        $discount += $total_price;
                    }
                    break;
                }
                break;

            case 'Referral':
                $promo->load('promo_campaign_referral');
                $promo_rules = $promo->promo_campaign_referral;
                if ($promo_rules->referred_promo_type == 'Product Discount') {
                    $rule = (object) [
                        'max_qty' => false,
                        'discount_type' => $promo_rules->referred_promo_unit,
                        'discount_value' => $promo_rules->referred_promo_value,
                        'max_percent_discount' => $promo_rules->referred_promo_value_max
                    ];
                    foreach ($trxs as $id_trx => &$trx) {
                        // get product data
                        $product = Product::with(['product_prices' => function ($q) use ($id_outlet) {
                            $q->where('id_outlet', '=', $id_outlet)
                              ->where('product_status', '=', 'Active')
                              ->where('product_stock_status', '=', 'Available');
                        } ])->find($trx['id_product']);
                        $cur_mod_price = 0;
                        foreach ($trx['modifiers'] as $modifier) {
                            $id_product_modifier = is_numeric($modifier) ? $modifier : $modifier['id_product_modifier'];
                            $qty_product_modifier = is_numeric($modifier) ? 1 : $modifier['qty'];
                            $cur_mod_price += ($mod_price[$id_product_modifier] ?? 0) * $qty_product_modifier;
                        }
                        //is product available
                        if (!$product) {
                            // product not available
                            // $errors[]='Product with id '.$trx['id_product'].' could not be found';
                            $errors[] = 'Produk tidak ditemukan';
                            continue;
                        }
                        // add discount
                        $discount += $this->discount_product($product, $rule, $trx, $cur_mod_price);
                    }
                } else {
                    return [
                        'item' => $trxs,
                        'discount' => 0
                    ];
                }
                break;

            case 'Discount bill':
                // load required relationship
                $promo->load($source . '_discount_bill_products', $source . '_discount_bill_rules');
                $promo_rules = $promo[$source . '_discount_bill_rules'];
                $promo_brand_flipped = array_flip($promo_brand);
                $promo_product = $promo[$source . '_discount_bill_products']->toArray();

                $product_name = $this->getProductName($promo_product, $promo->product_rule);

                if (!$promo_rules->is_all_product) {
                    if ($promo[$source . '_discount_bill_products']->isEmpty()) {
                        $errors[] = 'Produk tidak ditemukan';
                        return false;
                    }
                    $promo_product = $promo[$source . '_discount_bill_products']->toArray();
                    $promo_product_count = count($promo_product);

                    $product_error_applied = $this->checkProductErrorApplied($promo_product, $id_outlet, $missing_product_messages);

                    $check_product = $this->checkProductRule($promo, $promo_brand, $promo_product, $trxs);

                    // promo product not available in cart?
                    if (!$check_product && empty($request['bundling_promo'])) {
                        $message = $this->getMessage('error_product_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b>.';
                        $message = MyHelper::simpleReplace($message, ['product' => $product_name]);
                        $errors[] = $missing_product_messages ?? $message;
                        $errorProduct = $product_error_applied;
                        return false;
                    }
                } else {
                    $promo_product = "*";
                    $product_error_applied = 'all';
                }

                $get_promo_product = $this->getPromoProduct($trxs, $promo_brand, $promo_product);
                $product = $get_promo_product['product'];

                // product not found? buat jaga-jaga kalau sesuatu yang tidak diinginkan terjadi
                if (!$product && empty($request['bundling_promo'])) {
                    $message = $this->getMessage('error_product_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b>.';
                    $message = MyHelper::simpleReplace($message, ['product' => $product_name]);

                    $errors[] = $missing_product_messages ?? $message;
                    $errorProduct = $product_error_applied;
                    return false;
                }

                // get jumlah harga
                $total_price = 0;
                foreach ($subtotal_per_brand as $key => $value) {
                    if (!isset($promo_brand_flipped[$key])) {
                        continue;
                    }
                    $total_price += $value;
                }

                if ($promo_rules->discount_type == 'Percent') {
                    $discount += ($total_price * $promo_rules->discount_value) / 100;
                    if (!empty($promo_rules->max_percent_discount) && $discount > $promo_rules->max_percent_discount) {
                        $discount = $promo_rules->max_percent_discount;
                    }
                } else {
                    if ($promo_rules->discount_value < $total_price) {
                        $discount += $promo_rules->discount_value;
                    } else {
                        $discount += $total_price;
                    }
                }
                if ($discount <= 0) {
                    $message = $this->getMessage('error_product_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b>.';
                    $message = MyHelper::simpleReplace($message, ['product' => 'produk bertanda khusus']);

                    $errors[] = $message;
                    $errorProduct = 'all';
                    return false;
                }

                break;

            case 'Discount delivery':
                // load required relationship
                $promo->load($source . '_discount_delivery_rules');
                $promo_rules = $promo[$source . '_discount_delivery_rules'];

                if ($promo_rules) {
                    $discount_delivery = $this->discountDelivery(
                        $delivery_fee,
                        $promo_rules->discount_type,
                        $promo_rules->discount_value,
                        $promo_rules->max_percent_discount
                    );
                }

                break;
        }
        // discount?
        // if($discount<=0){
        //  $errors[]='Does not get any discount';
        //  return false;
        // }
        return [
            'item'      => $trxs,
            'discount'  => $discount,
            'promo_type' => $promo->promo_type,
            'discount_delivery' => $discount_delivery ?? 0
        ];
    }

    /**
     * validate transaction to use promo campaign light version
     * @param   int         $id_promo   id promo campaigm
     * @param   array       $trxs       array of item and total transaction
     * @param   array       $error      error message
     * @return  boolean     true/false
     */

    public static function validatePromoLight($id_promo, $trxs, &$errors)
    {
        /**
         $trxs=[
            {
                id_product:1,
                qty:2
            }
         ]
         */
        if (!is_numeric($id_promo)) {
            $errors[] = 'Id promo not valid';
            return false;
        }
        if (!is_array($trxs)) {
            $errors[] = 'Transaction data not valid';
            return false;
        }
        $promo = PromoCampaign::find($id_promo);
        if (!$promo) {
            $errors[] = 'Promo Campaign not found';
            return false;
        }
        $discount = 0;
        switch ($promo->promo_type) {
            case 'Product discount':
                // load required relationship
                $promo->load('promo_campaign_product_discount', 'promo_campaign_product_discount_rules');
                $promo_rules = $promo->promo_campaign_product_discount_rules;
                if (!$promo_rules->is_all_product) {
                    $promo_product = $promo->promo_campaign_product_discount->toArray();
                } else {
                    $promo_product = "*";
                }
                foreach ($trxs as $id_trx => &$trx) {
                    // is all product get promo
                    if ($promo_rules->is_all_product) {
                        return true;
                    } else {
                        // is product available in promo
                        if (is_array($promo_product) && in_array($trx['id_product'], array_column($promo_product, 'id_product'))) {
                            return true;
                        }
                    }
                }
                return false;
                break;

            case 'Tier discount':
                // load requirement relationship
                $promo->load('promo_campaign_tier_discount_rules', 'promo_campaign_tier_discount_product');
                $promo_product = $promo->promo_campaign_tier_discount_product;
                if (!$promo_product) {
                    $errors[] = 'Tier discount promo product is not set correctly';
                    return false;
                }
                // promo product not available in cart?
                if (!in_array($promo_product->id_product, array_column($trxs, 'id_product'))) {
                    $errors[] = 'Cart doesn\'t contain promoted product';
                    return false;
                }
                //get cart's product to apply promo
                $product = null;
                foreach ($trxs as &$trx) {
                    //is this the cart product we looking for?
                    if ($trx['id_product'] == $promo_product->id_product) {
                        //set reference to this cart product
                        $product=&$trx;
                        // break from loop
                        break;
                    }
                }
                // product not found? buat jaga-jaga kalau sesuatu yang tidak diinginkan terjadi
                if (!$product) {
                    $errors[] = 'Cart doesn\'t contain promoted product';
                    return false;
                }
                return true;
                break;

            case 'Buy X Get Y':
                // load requirement relationship
                $promo->load('promo_campaign_buyxgety_rules', 'promo_campaign_buyxgety_product_requirement');
                $promo_product = $promo->promo_campaign_buyxgety_product_requirement;
                if (!$promo_product) {
                    $errors[] = 'Benefit product is not set correctly';
                    return false;
                }
                // promo product not available in cart?
                if (!in_array($promo_product->id_product, array_column($trxs, 'id_product'))) {
                    $errors[] = 'Requirement product doesnt available in cart';
                    return false;
                }
                //get cart's product to get benefit
                $product = null;
                foreach ($trxs as &$trx) {
                    //is this the cart product we looking for?
                    if ($trx['id_product'] == $promo_product->id_product) {
                        //set reference to this cart product
                        $product=&$trx;
                        // break from loop
                        break;
                    }
                }
                // product not found? buat jaga-jaga kalau sesuatu yang tidak diinginkan terjadi
                if (!$product) {
                    $errors[] = 'Requirement product doesnt available in cart';
                    return false;
                }
                return true;
                break;

            case 'Discount global':
                return true;
                break;
        }
    }

    /**
     * modify $trx set discount to product
     * @param  Product                              $product
     * @param  PromoCampaignProductDiscountRule     $promo_rules
     * @param  Array                                $trx            transaction data
     * @return int discount
     */
    public function discount_product($product, $promo_rules, &$trx, $modifier = null)
    {
        // check discount type
        $discount   = 0;
        $modifier   = 0; // reset all modifier price to 0
        // set quantity of product to apply discount
        $discount_qty = $trx['promo_qty'] ?? $trx['qty'];
        $old = $trx['discount'] ?? 0;
        // is there any max qty set?
        if (($promo_rules->max_qty ?? false) && $promo_rules->max_qty < $discount_qty) {
            $discount_qty = $promo_rules->max_qty;
        }

        // check 'product discount' limit product qty
        if (($promo_rules->max_product ?? false) && $promo_rules->max_product < $discount_qty) {
            $discount_qty = $promo_rules->max_product;
        }

        // check if isset promo qty
        if (isset($trx['promo_qty'])) {
            $discount_qty = $trx['promo_qty'];
            unset($trx['promo_qty']);
        }

        $product_price = ($product['product_price'] ?? $product->product_prices[0]->product_price ?? null) + $modifier;

        if (isset($trx['new_price']) && $trx['new_price']) {
            $product_price = $trx['new_price'] / $trx['qty'];
        }
        if ($promo_rules->discount_type == 'Nominal' || $promo_rules->discount_type == 'nominal') {
            $discount = $promo_rules->discount_value * $discount_qty;
            $product_price_total = $product_price * $discount_qty;
            if ($discount > $product_price_total) {
                $discount = $product_price_total;
            }
            $trx['discount']        = ($trx['discount'] ?? 0) + $discount;
            $trx['new_price']       = ($product_price * $trx['qty']) - $trx['discount'];
            $trx['is_promo']        = 1;
            $trx['base_discount']   = $product_price < $promo_rules->discount_value ? $product_price : $promo_rules->discount_value;
            $trx['qty_discount']    = $discount_qty;
        } else {
            // percent
            $discount_per_product = ($promo_rules->discount_value / 100) * $product_price;
            if ($discount_per_product > $promo_rules->max_percent_discount && !empty($promo_rules->max_percent_discount)) {
                $discount_per_product = $promo_rules->max_percent_discount;
            }
            $discount = (int)($discount_per_product * $discount_qty);
            $trx['discount']        = ($trx['discount'] ?? 0) + $discount;
            $trx['new_price']       = ($product_price * $trx['qty']) - $trx['discount'];
            $trx['is_promo']        = 1;
            $trx['base_discount']   = $discount_per_product;
            $trx['qty_discount']    = $discount_qty;
        }
        if ($trx['new_price'] < 0) {
            $trx['is_promo']        = 1;
            $trx['new_price']       = 0;
            $trx['discount']        = $product_price * $discount_qty;
            $trx['base_discount']   = $product_price;
            $trx['qty_discount']    = $discount_qty;
            $discount               = $trx['discount'] - $old;
        }
        return $discount;
    }

    /**
     * Validate if a user can use promo
     * @param  int      $id_promo id promo campaign
     * @param  int      $id_user  id user
     * @return boolean  true/false
     */
    public function validateUser($id_promo, $id_user, $phone, $device_type, $device_id, &$errors = [], $id_code = null)
    {
        $promo = PromoCampaign::find($id_promo);

        if (!$promo) {
            $errors[] = 'Promo campaign not found';
            return false;
        }
        if (!$promo->step_complete || !$promo->user_type) {
            $errors[] = 'Promo campaign not finished';
            return false;
        }

        if ($promo->promo_type == 'Referral') {
            if (User::find($id_user)->transaction_online) {
                $errors[] = 'Kode promo tidak ditemukan';
                return false;
            }
            if (
                UserReferralCode::where([
                'id_promo_campaign_promo_code' => $id_code,
                'id_user' => $id_user
                ])->exists()
            ) {
                $errors[] = 'Kode promo tidak ditemukan';
                return false;
            }
            $referer = UserReferralCode::where('id_promo_campaign_promo_code', $id_code)
                ->join('users', 'users.id', '=', 'user_referral_codes.id_user')
                ->where('users.is_suspended', '=', 0)
                ->first();
            if (!$referer) {
                $errors[] = 'Kode promo tidak ditemukan';
            }
        }

        //check user
        $user = $this->userFilter($id_user, $promo->user_type, $promo->specific_user, $phone);

        if (!$user) {
            if ($promo->specific_user == 'New user') {
                $errors[] = 'Promo hanya berlaku untuk pengguna baru';
            } else {
                $errors[] = 'Promo tidak berlaku untuk akun Anda';
            }

            return false;
        }

        // use promo code?
        if ($promo->code_type == 'Single') {
            if ($promo->limitation_usage) {
                // limit usage user?
                if (PromoCampaignReport::where('id_promo_campaign', $id_promo)->where('id_user', $id_user)->count() >= $promo->limitation_usage) {
                    $errors[] = 'Promo tidak tersedia';
                    return false;
                }
            }

            // limit usage device
            /*if(PromoCampaignReport::where('id_promo_campaign',$id_promo)->where('device_id',$device_id)->count()>=$promo->limitation_usage){
                $errors[]='Kuota device anda untuk penggunaan kode promo ini telah habis';
                return false;
            }*/
        } else {
            $used_by_other_user = PromoCampaignReport::where('id_promo_campaign', $id_promo)
                                ->where('id_user', '!=', $id_user)
                                ->where('id_promo_campaign_promo_code', $id_code)
                                ->first();
            if ($used_by_other_user) {
                $errors[] = 'Promo tidak berlaku untuk akun Anda';
                return false;
            }

            $used_code = PromoCampaignReport::where('id_promo_campaign', $id_promo)->where('id_user', $id_user)->where('id_promo_campaign_promo_code', $id_code)->count();

            if ($code_limit = $promo->code_limit) {
                if ($used_code >= $code_limit) {
                    $errors[] = 'Promo tidak tersedia';
                    return false;
                }
            }

            if ($promo->user_limit && !$used_code) {
                $used_diff_code = PromoCampaignReport::where('id_promo_campaign', $id_promo)->where('id_user', $id_user)->distinct()->count('id_promo_campaign_promo_code');
                if ($used_diff_code >= $promo->user_limit) {
                    $errors[] = 'Promo tidak tersedia';
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Get product price with product discount
     * @param  Product $product product
     * @return int          new product price
     */
    public function getProductDiscount($product)
    {
        $product->load('discountActive');
        $productItem = $product->toArray();
        $productItem['discountActive'] = $productItem['discount_active'];
        $countSemen = 0;
        if (count($productItem['discountActive']) > 0) {
            $productItem['discount_status'] = 'yes';
        } else {
            $productItem['discount_status'] = 'no';
        }
        if ($productItem['discount_status'] == 'yes') {
            foreach ($productItem['discountActive'] as $row => $dis) {
                if (!empty($dis['discount_percentage'])) {
                    $jat = $dis['discount_percentage'];

                    $count = $productItem['product_prices'][0]['product_price'] ?? [] * $jat / 100;
                } else {
                    $count = $dis['discount_nominal'];
                }

                $now = date('Y-m-d');
                $time = date('H:i:s');
                $day = date('l');

                if ($now < $dis['discount_start']) {
                    $count = 0;
                }

                if ($now > $dis['discount_end']) {
                    $count = 0;
                }

                if ($time < $dis['discount_time_start']) {
                    $count = 0;
                }

                if ($time > $dis['discount_time_end']) {
                    $count = 0;
                }

                if (strpos($dis['discount_days'], $day) === false) {
                    $count = 0;
                }

                $countSemen += $count;
                $count = 0;
            }
        }
        if ($countSemen > ($productItem['product_prices'][0]['product_price'] ?? [])) {
            $countSemen = $productItem['product_prices'][0]['product_price'] ?? [];
        }
        return $countSemen;
    }

    public function userFilter($id_user, $rule, $valid_user, $phone)
    {
        if ($rule == 'New user') {
            $check = Transaction::where('id_user', '=', $id_user)->first();
            if ($check) {
                return false;
            }
        } elseif ($rule == 'Specific user') {
            $valid_user = explode(',', $valid_user);
            if (!in_array($phone, $valid_user)) {
                return false;
            }
        }

        return true;
    }

    public function checkOutletRule($id_outlet, $all_outlet, $outlet = [], $id_brand = null, $brand = [])
    {
        if (isset($id_brand)) {
            if (!empty($brand)) {
                $check_brand = array_search($id_brand, array_column($brand, 'id_brand'));
                if ($check_brand === false) {
                    return false;
                }
            } else {
                $check_brand = Outlet::where('id_outlet', $id_outlet)
                                ->whereHas('brands', function ($q) use ($id_brand) {
                                    $q->where('brand_outlet.id_brand', $id_brand);
                                })
                                ->first();
                if (!$check_brand) {
                    return false;
                }
            }
        }
        if ($all_outlet == '1') {
            return true;
        } else {
            foreach ($outlet as $value) {
                if ($value['id_outlet'] == $id_outlet) {
                    return true;
                }
            }

            return false;
        }
    }

    public function checkOutletBrandRule($id_outlet, $all_outlet, $promo_outlets, $promo_brands, $brand_rule = 'and', $promo_outlet_groups = [])
    {
        if (!is_array($promo_outlets)) {
            $promo_outlets = $promo_outlets->toArray();
        }

        $outlet_brands  = BrandOutlet::where('id_outlet', $id_outlet)->first()['id_brand'] ?? null;

        if (!empty($promo_brands) && !in_array($outlet_brands, $promo_brands)) {
            return false;
        }

        $outlet_by_group_filter = [];
        foreach ($promo_outlet_groups as $val) {
            $temp = app($this->outlet_group_filter)->outletGroupFilter($val['id_outlet_group']);
            $outlet_by_group_filter = array_merge($outlet_by_group_filter, $temp);
        }

        $promo_outlets = array_merge($promo_outlets, $outlet_by_group_filter);

        if ($all_outlet == '1') {
            return true;
        } else {
            foreach ($promo_outlets as $value) {
                if ($value['id_outlet'] == $id_outlet) {
                    return true;
                }
            }

            return false;
        }
    }

    public function getMessage($key)
    {
        $message = Setting::where('key', '=', $key)->first() ?? null;

        return $message;
    }

    public function getRequiredProduct($id_promo, $source = 'promo_campaign')
    {
        if ($source == 'deals') {
            $promo = Deal::where('id_deals', '=', $id_promo)
                    ->with([
                        'deals_product_discount.product' => function ($q) {
                            $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                        },
                        'deals_buyxgety_product_requirement.product' => function ($q) {
                            $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                        },
                        'deals_tier_discount_product.product' => function ($q) {
                            $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                        },
                        'deals_product_discount_rules',
                        'deals_tier_discount_rules',
                        'deals_buyxgety_rules'
                    ])
                    ->first();
        } elseif ($source == 'promo_campaign') {
            $promo = PromoCampaign::where('id_promo_campaign', '=', $id_promo)
                    ->with([
                        'promo_campaign_product_discount.product' => function ($q) {
                            $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                        },
                        'promo_campaign_buyxgety_product_requirement.product' => function ($q) {
                            $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                        },
                        'promo_campaign_tier_discount_product.product' => function ($q) {
                            $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                        },
                        'promo_campaign_product_discount_rules',
                        'promo_campaign_tier_discount_rules',
                        'promo_campaign_buyxgety_rules'
                    ])
                    ->first();
        }

        if ($promo) {
            $promo = $promo->toArray();
            if (($promo[$source . '_product_discount_rules']['is_all_product'] ?? false) == 1) {
                $product = null;
            } elseif (!empty($promo[$source . '_product_discount'])) {
                $product = $promo[$source . '_product_discount'][0]['product'] ?? '';
                if (!empty($promo[$source . '_product_discount'][0]['id_brand'])) {
                    $product['id_brand'] = $promo[$source . '_product_discount'][0]['id_brand'];
                }
            } elseif (!empty($promo[$source . '_tier_discount_product'])) {
                $product = $promo[$source . '_tier_discount_product']['product'] ?? $promo[$source . '_tier_discount_product'][0]['product'] ?? '';
                if (!empty($promo[$source . '_tier_discount_product'][0]['id_brand'])) {
                    $product['id_brand'] = $promo[$source . '_tier_discount_product'][0]['id_brand'];
                }
            } elseif (!empty($promo[$source . '_buyxgety_product_requirement'])) {
                $product = $promo[$source . '_buyxgety_product_requirement']['product'] ?? $promo[$source . '_buyxgety_product_requirement'][0]['product'] ?? '';
                if (!empty($promo[$source . '_buyxgety_product_requirement'][0]['id_brand'])) {
                    $product['id_brand'] = $promo[$source . '_buyxgety_product_requirement'][0]['id_brand'];
                }
            } else {
                $product = null;
            }

            if (!empty($product) && !empty($promo['id_brand'])) {
                $product['id_brand'] = $promo['id_brand'];
            }
            return $product;
        } else {
            return null;
        }
    }

    public function getAllModifier($array_modifier, $id_outlet)
    {
        $different_price = Outlet::select('outlet_different_price')->where('id_outlet', $id_outlet)->pluck('outlet_different_price')->first();

        $mod = ProductModifier::select('product_modifiers.id_product_modifier', 'text', 'product_modifier_stock_status', 'product_modifier_price')
            ->whereIn('product_modifiers.id_product_modifier', $array_modifier)
            ->leftJoin('product_modifier_details', function ($join) use ($id_outlet) {
                $join->on('product_modifier_details.id_product_modifier', '=', 'product_modifiers.id_product_modifier')
                    ->where('product_modifier_details.id_outlet', $id_outlet);
            })
            ->where(function ($q) {
                $q->where('product_modifier_stock_status', 'Available')->orWhereNull('product_modifier_stock_status');
            })
            ->where(function ($q) {
                $q->where('product_modifier_status', 'Active')->orWhereNull('product_modifier_status');
            })
            ->where(function ($query) {
                $query->where('product_modifier_details.product_modifier_visibility', '=', 'Visible')
                        ->orWhere(function ($q) {
                            $q->whereNull('product_modifier_details.product_modifier_visibility')
                            ->where('product_modifiers.product_modifier_visibility', 'Visible');
                        });
            });

        if ($different_price) {
            $mod->join('product_modifier_prices', function ($join) use ($id_outlet) {
                $join->on('product_modifier_prices.id_product_modifier', '=', 'product_modifiers.id_product_modifier');
                $join->where('product_modifier_prices.id_outlet', $id_outlet);
            });
        } else {
            $mod->join('product_modifier_global_prices', function ($join) use ($id_outlet) {
                $join->on('product_modifier_global_prices.id_product_modifier', '=', 'product_modifiers.id_product_modifier');
            });
        }

        $mod = $mod->get();
        if ($mod) {
            return $mod;
        } else {
            return [];
        }
    }

    public function getOneProduct($id_outlet, $id_product, $id_brand, $brand = null)
    {
        $product = Product::where('id_product', $id_product)
                    ->whereHas('brand_category', function ($q) use ($id_brand) {
                        $q->where('id_brand', $id_brand);
                    })
                    ->whereRaw('products.id_product in 
			        (CASE
	                    WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $id_outlet . ' )
	                    is NULL THEN products.id_product
	                    ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_status = "Active" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $id_outlet . ' )
	                END)')
                    ->first();

        $product_detail = ProductDetail::where([
                            'id_product' => $id_product,
                            'id_outlet' => $id_outlet,
                            'product_detail_visibility' => 'Visible',
                            'product_detail_status' => 'Active',
                            'product_detail_stock_status' => 'Available'
                        ])->first();

        if (!$product_detail) {
            $product = false;
        }

        $check_price = $this->getProductPrice($id_outlet, $id_product, $id_product_variant_group = null, $id_brand);

        if (!$check_price) {
            $product = false;
        }

        if ($product && !empty($brand)) {
            $product_brand = Brand::join('brand_product', 'brand_product.id_brand', '=', 'brands.id_brand')
                            ->where('brand_active', '1')
                            ->where('id_product', $id_product)
                            ->first();
            if (!$product_brand) {
                $product = false;
            } else {
                $product->brand = $product_brand;
            }
        }

        return $product;
    }

    public function getOneProductV2($id_outlet, $id_product, $id_brand, $brand = null, $id_product_variant_group = null)
    {
        $product = Product::where('id_product', $id_product)
                    ->whereHas('brand_category', function ($q) use ($id_brand) {
                        $q->where('id_brand', $id_brand);
                    })
                    ->first();
        if (!$product) {
            return false;
        }

        if ($id_product_variant_group) {
            // check product variant
            $product_variant_group_detail = ProductVariantGroupDetail::where('id_product_variant_group', $id_product_variant_group)->where('id_outlet', $id_outlet)->first();

            if ($product_variant_group_detail) {
                if (
                    $product_variant_group_detail['product_variant_group_visibility'] != 'Visible'
                    || $product_variant_group_detail['product_variant_group_status'] != 'Active'
                    || $product_variant_group_detail['product_variant_group_stock_status'] != 'Available'
                ) {
                    return false;
                }
            } else {
                $product_variant_group = ProductVariantGroup::where('id_product_variant_group', $id_product_variant_group)->where('product_variant_group_visibility', 'Visible')->first();

                if (!$product_variant_group) {
                    return false;
                }
            }
        } else {
            // check product
            $product_detail = ProductDetail::where(['id_product' => $id_product, 'id_outlet' => $id_outlet])->first();
            if ($product_detail = false) {
                if (
                    $product_detail['product_detail_visibility'] != 'Visible'
                    || $product_detail['product_detail_status'] != 'Active'
                    || $product_detail['product_detail_stock_status'] != 'Available'
                ) {
                    return false;
                }
            } else {
                $product = Product::where('id_product', $id_product)->where('product_visibility', 'Visible')->first();

                if (!$product) {
                    return false;
                }
            }
        }

        $check_price = $this->getProductPrice($id_outlet, $id_product, $id_product_variant_group = null, $id_brand);

        if (!$check_price) {
            return false;
        }

        if ($product && !empty($brand)) {
            $product_brand = Brand::join('brand_product', 'brand_product.id_brand', '=', 'brands.id_brand')
                            ->where('brand_active', '1')
                            ->where('id_product', $id_product)
                            ->first();
            if (!$product_brand) {
                return false;
            } else {
                $product->brand = $product_brand;
            }
        }

        return $product;
    }

    public function getProductPrice($id_outlet, $id_product, $id_product_variant_group = null, $id_brand = null)
    {
        $different_price = Outlet::select('outlet_different_price')->where('id_outlet', $id_outlet)->pluck('outlet_different_price')->first();

        if ($id_brand) {
            $check_brand = BrandProduct::where('id_brand', $id_brand)->where('id_product', $id_product)->first();
            if (!$check_brand) {
                return false;
            }
        }

        if ($id_product_variant_group) {
            if ($different_price) {
                $productPrice = ProductVariantGroupSpecialPrice::select('product_variant_group_price')
                                ->where('id_product_variant_group', $id_product_variant_group)
                                ->where('id_outlet', $id_outlet)
                                ->first();

                if ($productPrice) {
                    $productPrice['product_price'] = $productPrice['product_variant_group_price'];
                }
            } else {
                $productPrice = ProductVariantGroup::select('product_variant_group_price')->where('id_product_variant_group', $id_product_variant_group)->first();

                if ($productPrice) {
                    $productPrice['product_price'] = $productPrice['product_variant_group_price'];
                }
            }
        } else {
            if ($different_price) {
                $productPrice = ProductSpecialPrice::where(['id_product' => $id_product, 'id_outlet' => $id_outlet])->first()->toArray();
                if ($productPrice) {
                    $productPrice['product_price'] = $productPrice['product_special_price'];
                }
            } else {
                $productPrice = ProductGlobalPrice::where(['id_product' => $id_product])->first()->toArray();
                if ($productPrice) {
                    $productPrice['product_price'] = $productPrice['product_global_price'];
                }
            }
        }

        if ($productPrice && $productPrice['product_price'] === 0) {
            $productPrice = false;
        }
        return $productPrice;
    }

    public function getProductModifierPrice($id_outlet, $id_product_modifier)
    {
        $different_price = Outlet::select('outlet_different_price')->where('id_outlet', $id_outlet)->pluck('outlet_different_price')->first();

        if ($different_price) {
            $modifier_price = ProductModifierPrice::where('id_product_modifier', $id_product_modifier)->where('id_outlet', $id_outlet)->first();
        } else {
            $modifier_price = ProductModifierGlobalPrice::where('id_product_modifier', $id_product_modifier)->first();
        }

        if (!$modifier_price->product_modifier_price) {
            $productPrice = false;
        }

        return $modifier_price->product_modifier_price;
    }

    /**
     * Create referal promo code
     * @param  Integer $id_user user id of user
     * @return boolean       true if success
     */
    public static function createReferralCode($id_user)
    {
        //check user have referral code
        $referral_campaign = PromoCampaign::select('id_promo_campaign')->where('promo_type', 'referral')->first();
        if (!$referral_campaign) {
            return false;
        }
        $check = UserReferralCode::where('id_user', $id_user)->first();
        if ($check) {
            return $check;
        }
        $max_iterate = 1000;
        $iterate = 0;
        $exist = true;
        do {
            $promo_code = MyHelper::createrandom(6, 'PromoCode');
            $exist = PromoCampaignPromoCode::where('promo_code', $promo_code)->exists();
            if ($exist) {
                $promo_code = false;
            };
            $iterate++;
        } while ($exist && $iterate <= $max_iterate);
        if (!$promo_code) {
            return false;
        }
        $create = PromoCampaignPromoCode::create([
            'id_promo_campaign' => $referral_campaign->id_promo_campaign,
            'promo_code' => $promo_code
        ]);
        if (!$create) {
            return false;
        }
        $create2 = UserReferralCode::create([
            'id_promo_campaign_promo_code' => $create->id_promo_campaign_promo_code,
            'id_user' => $id_user
        ]);
        return $create2;
    }
    /**
     * Apply cashback to referrer
     * @param  Transaction $transaction Transaction model
     * @return boolean
     */
    public static function applyReferrerCashback($transaction)
    {
        if (!$transaction['id_promo_campaign_promo_code']) {
            return true;
        }
        $transaction->load('promo_campaign_promo_code', 'promo_campaign_promo_code.promo_campaign');
        $use_referral = ($transaction['promo_campaign_promo_code']['promo_campaign']['promo_type'] ?? false) === 'Referral';
        // apply cashback to referrer
        if ($use_referral) {
            $referral_rule = PromoCampaignReferral::where('id_promo_campaign', $transaction['promo_campaign_promo_code']['id_promo_campaign'])->first();
            $referrer = UserReferralCode::where('id_promo_campaign_promo_code', $transaction['id_promo_campaign_promo_code'])->pluck('id_user')->first();
            if (!$referrer || !$referral_rule) {
                return false;
            }
            $referrer_cashback = 0;
            if ($referral_rule->referrer_promo_unit == 'Percent') {
                $referrer_discount_percent = $referral_rule->referrer_promo_value <= 100 ? $referral_rule->referrer_promo_value : 100;
                $referrer_cashback = $transaction['transaction_grandtotal'] * $referrer_discount_percent / 100;
            } else {
                if ($transaction['transaction_grandtotal'] >= $referral_rule->referred_min_value) {
                    $referrer_cashback = $referral_rule->referrer_promo_value <= $transaction['transaction_grandtotal'] ? $referral_rule->referrer_promo_value : $transaction['transaction_grandtotal'];
                }
            }
            if ($referrer_cashback) {
                $insertDataLogCash = app("Modules\Balance\Http\Controllers\BalanceController")->addLogBalance($referrer, $referrer_cashback, $transaction['id_transaction'], 'Referral Bonus', $transaction['transaction_grandtotal']);
                if (!$insertDataLogCash) {
                    return false;
                }
                PromoCampaignReferralTransaction::where('id_transaction', $transaction['id_transaction'])->update(['referrer_bonus' => $referrer_cashback]);
                $referrer_total_cashback = UserReferralCode::where('id_user', $referrer)->first();
                if ($referrer_total_cashback) {
                    $upData = [
                        'cashback_earned' => $referrer_total_cashback->cashback_earned + $referrer_cashback,
                        'number_transaction' => $referrer_total_cashback->number_transaction + 1
                    ];
                    if (!$referrer_total_cashback->referral_code) {
                        $upData['referral_code'] = PromoCampaignPromoCode::select('promo_code')->where('id_promo_campaign_promo_code', $transaction['id_promo_campaign_promo_code'])->pluck('promo_code')->first();
                    }
                    $up = $referrer_total_cashback->update($upData);
                } else {
                    $up = UserReferralCode::create([
                        'id_user' => $referrer,
                        'referral_code' => PromoCampaignPromoCode::select('promo_code')->where('id_promo_campaign_promo_code', $transaction['id_promo_campaign_promo_code'])->pluck('promo_code')->first(),
                        'number_transaction' => 1,
                        'cashback_earned' => $referrer_cashback
                    ]);
                }
                if (!$up) {
                    return false;
                }
            }
        }
        return true;
    }

    public function removeBonusItem($item)
    {
        foreach ($item as $key => $value) {
            if (!empty($value['bonus'])) {
                unset($item[$key]);
                break;
            }
        }

        return $item;
    }

    public function discountDelivery($delivery_fee, $discount_type, $discount_value, $discount_max)
    {
        $discount = 0;
        if ($discount_type == 'Percent') {
            $discount = ($delivery_fee * $discount_value) / 100;
            if (!empty($discount_max) && $discount > $discount_max) {
                $discount = $discount_max;
            }
        } else {
            if ($discount_value < $delivery_fee) {
                $discount = $discount_value;
            } else {
                $discount = $delivery_fee;
            }
        }

        return $discount;
    }

    public function checkPaymentRule($all_payment, $payment_method, $promo_payment_list)
    {
        if (!is_array($promo_payment_list)) {
            $promo_payment_list = $promo_payment_list->toArray();
        }

        if ($all_payment) {
            return true;
        }

        if (in_array($payment_method, $promo_payment_list)) {
            return true;
        } else {
            return false;
        }
    }

    public function checkShipmentRule($all_shipment, $shipment_method, $promo_shipment_list)
    {
        if (!is_array($promo_shipment_list)) {
            $promo_shipment_list = $promo_shipment_list->toArray();
        }

        if (in_array('GO-SEND', $promo_shipment_list)) {
            $promo_shipment_list[] = 'gosend';
        } elseif (in_array('gosend', $promo_shipment_list)) {
            $promo_shipment_list[] = 'GO-SEND';
        }

        if ($all_shipment) {
            return true;
        }

        foreach ($promo_shipment_list as $shipment) {
            if (strpos($shipment, $shipment_method) !== false) {
                return true;
            }
        }

        return false;
    }

    public function getPaymentMethod($payment_type, $payment_id, $payment_detail)
    {
        // payment_id for ipay88
        // payment_detail for midtrans
        $payment_method = null;
        if ($payment_type == "Ipay88") {
            $payment_method = $this->getPaymentIpay88($payment_id);
        } elseif ($payment_type == "Midtrans") {
            $payment_method =  $payment_detail;
        } elseif ($payment_type == "Shopeepay") {
            $payment_method =  $payment_type;
        }

        return $payment_method;
    }

    public function getPaymentIpay88($payment_id)
    {
        $payment_id = strtoupper($payment_id);
        $ipay88 = new Ipay88();
        $payment_list = $ipay88->payment_id;
        $payment_list['CREDIT_CARD'] = 'Credit Card';
        $payment_list['CREDIT CARD'] = 'Credit Card';
        $payment_list['OVO'] = 'Ovo';

        if (isset($payment_list[$payment_id])) {
            $payment_method = $payment_list[$payment_id];
        } else {
            $payment_method = null;
        }

        return $payment_method;
    }

    public function checkProductRule($promo, $promo_brand, $promo_product, $trxs)
    {
        if (!is_array($promo_product)) {
            $promo_product_array = $promo_product->toArray();
        } else {
            $promo_product_array = $promo_product;
        }
        $promo_product_id = array_column($promo_product_array, 'id_product');
        // merge total quantity of same product
        // $merge_product_array     = [];
        $merge_product_only     = []; // for product only
        $merge_product_variant  = []; // for product + variant
        $merge_product = [];
        foreach ($trxs as $key => $value) {
            if (isset($merge_product_variant[$value['id_brand'] . '-' . $value['id_product'] . '-' . $value['id_product_variant_group']])) {
                $merge_product_variant[$value['id_brand'] . '-' . $value['id_product'] . '-' . $value['id_product_variant_group']] += $value['qty'];
            } else {
                $merge_product_variant[$value['id_brand'] . '-' . $value['id_product'] . '-' . $value['id_product_variant_group']] = $value['qty'];
            }

            $merge_product[$value['id_brand']][$value['id_product']][$value['id_product_variant_group']] = ($merge_product[$value['id_brand']][$value['id_product']][$value['id_product_variant_group']] ?? 0) + $value['qty'];
        }

        // check how many promo product that valid
        $check_product = [];
        foreach ($promo_product_array as $val) {
            foreach ($merge_product_variant as $key => $product_qty) {
                $promo_key = $val['id_brand'] . '-' . $val['id_product'] . '-' . $val['id_product_variant_group'];

                if (!isset($val['id_product_variant_group'])) {
                    if (isset($merge_product[$val['id_brand']][$val['id_product']])) {
                        $check_product[] = $merge_product[$val['id_brand']][$val['id_product']];
                        break;
                    }
                } else {
                    if (isset($merge_product[$val['id_brand']][$val['id_product']][$val['id_product_variant_group']])) {
                        $check_product[] = $merge_product[$val['id_brand']][$val['id_product']][$val['id_product_variant_group']];
                        break;
                    }
                }
            }
        }

        // promo product not available in cart?
        if ($promo->product_rule === 'and') {
            if (count($check_product) != count($promo_product)) {
                return false;
            }
        } elseif ($promo->product_rule === 'or') {
            if (empty($check_product)) {
                return false;
            }
        }

        return true;
    }

    public function getPromoProduct(&$trxs, $promo_brand, $promo_product, $promo_product_type = null)
    {
        if ($promo_product != '*') {
            if (!is_array($promo_product)) {
                $promo_product_array = $promo_product->toArray();
            } else {
                $promo_product_array = $promo_product;
            }
        }

        $product = [];
        $total_product = 0;
        foreach ($trxs as $key => &$trx) {
            if (!in_array($trx['id_brand'] ?? null, $promo_brand) && !empty($promo_brand)) {
                continue;
            }

            $product[$key] = $trx;
            $notGetDiscountStatus = false;
            if (!empty($promo_product_type) && $promo_product_type != 'single + variant') {
                if ($promo_product_type == 'single' && !empty($trx['id_product_variant_group'])) {
                    $notGetDiscountStatus = true;
                } elseif ($promo_product_type == 'variant' && empty($trx['id_product_variant_group'])) {
                    $notGetDiscountStatus = true;
                }
            }

            if (!empty($promo_product_array)) {
                $allProductSelected =  array_column($promo_product_array, 'id_product');
                if (!in_array($trx['id_product'], $allProductSelected)) {
                    $notGetDiscountStatus = true;
                }
            }

            $product[$key]['not_get_discount'] = $notGetDiscountStatus;
            if (!$notGetDiscountStatus) {
                $total_product += $trx['qty'];
            }
        }

        return [
            'product' => (!empty($total_product) ? $product : []),
            'total_product' => $total_product
        ];
    }

    public function getCheapestVariant($id_outlet, $id_product)
    {
        $outlet = Outlet::select('id_outlet', 'outlet_different_price')->where('id_outlet', $id_outlet)->first();
        $variant_list = Product::getVariantTree($id_product, $outlet);
        $result = null;

        if ($variant_list) {
            $variant =  $this->getVariant($variant_list['base_price'], $variant_list['variants_tree']['childs'], $group_price);

            if (isset($variant['id_product_variant_group'])) {
                $result = $variant['id_product_variant_group'];
            }
        }

        return $result;
    }

    public function getVariant($base_price, $variant, &$group_price)
    {
        try {
            foreach ($variant as $key => $value) {
                if (isset($value['variant']['childs'])) {
                    $group_price = self::getVariant($base_price, $value['variant']['childs'], $group_price);
                }

                if (isset($value['product_variant_group_price'])) {
                    if ($value['product_variant_group_price'] == $base_price) {
                        $group_price = [
                            'price' => $value['product_variant_group_price'],
                            'id_product_variant_group' => $value['id_product_variant_group']
                        ];
                        break;
                    }
                }
            }

            return $group_price;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function applyPromoProduct($post, $list_products, $list_product_source, &$promo_error)
    {
        $result = $list_products;

        // set default flag to 0
        if ($list_product_source == 'list_product') {
            foreach ($result as $id_brand => $categories) {
                foreach ($categories as $id_category => $products) {
                    foreach ($products['list'] ?? $products as $key => $value) {
                        if (!is_numeric($id_category)) {
                            $result[$id_brand][$id_category]['list'][$key]['is_promo'] = 0;
                        } else {
                            $result[$id_brand][$id_category][$key]['is_promo'] = 0;
                        }
                    }
                }
            }
        } elseif ($list_product_source == 'list_product2') {
            foreach ($result as $key => $categories) {
                foreach ($categories['list'] as $key2 => $products) {
                    foreach ($products['list'] as $key3 => $product) {
                        $result[$key]['list'][$key2]['list'][$key3]['is_promo'] = 0;
                    }
                }
            }
        } else {
            foreach ($result as $key => $value) {
                $result[$key]['is_promo'] = 0;
            }
        }

        // return data if not using promo
        if ((empty($post['promo_code']) && empty($post['id_deals_user']) && empty($post['id_subscription_user']))) {
            return $result;
        }
        $promo_error = null;
        if (
            (!empty($post['promo_code']) && !empty($post['id_deals_user']) && !empty($post['id_subscription_user']))
            || (!empty($post['promo_code']) && !empty($post['id_deals_user']) && empty($post['id_subscription_user']))
            || (!empty($post['promo_code']) && empty($post['id_deals_user']) && !empty($post['id_subscription_user']))
            || (empty($post['promo_code']) && !empty($post['id_deals_user']) && !empty($post['id_subscription_user']))
        ) {
            $promo_error = 'Promo not valid';
            return $result;
        }

        if (!empty($post['promo_code'])) {
            $code = app($this->promo_campaign)->checkPromoCode($post['promo_code'], 1, 1);
            if (!$code) {
                $promo_error = 'Promo not valid';
                return $result;
            }
            $source             = 'promo_campaign';
            $brands             = $code->promo_campaign->promo_campaign_brands()->pluck('id_brand')->toArray();
            $all_outlet         = $code['promo_campaign']['is_all_outlet'] ?? 0;
            $promo_outlet       = $code['promo_campaign']['promo_campaign_outlets'] ?? [];
            $promo_outlet_group = $code['promo_campaign']['outlet_groups'] ?? [];
            $id_brand_promo     = $code['promo_campaign']['id_brand'] ?? null;
            $brand_rule         = $code['promo_campaign']['brand_rule'] ?? 'and';
            $promo_type         = $code->promo_type;

            // if promo doesn't have product related rule, return data
            if ($code->promo_type != 'Product discount' && $code->promo_type != 'Tier discount' && $code->promo_type != 'Buy X Get Y' && $code->promo_type != 'Discount bill') {
                return $result;
            }
        } elseif (!empty($post['id_deals_user'])) {
            $code = app($this->promo_campaign)->checkVoucher($post['id_deals_user'], 1, 1);
            if (!$code) {
                $promo_error = 'Promo not valid';
                return $result;
            }
            $source             = 'deals';
            $brands             = $code->dealVoucher->deals->deals_brands()->pluck('id_brand')->toArray();
            $all_outlet         = $code['dealVoucher']['deals']['is_all_outlet'] ?? 0;
            $promo_outlet       = $code['dealVoucher']['deals']['outlets_active'] ?? [];
            $promo_outlet_group = $code['dealVoucher']['deals']['outlet_groups'] ?? [];
            $id_brand_promo     = $code['dealVoucher']['deals']['id_brand'] ?? null;
            $brand_rule         = $code['dealVoucher']['deals']['brand_rule'] ?? 'and';
            $promo_type         = $code->dealVoucher->deals->promo_type;

            // if promo doesn't have product related rule, return data
            if (
                $code->dealVoucher->deals->promo_type != 'Product discount'
                && $code->dealVoucher->deals->promo_type != 'Tier discount'
                && $code->dealVoucher->deals->promo_type != 'Buy X Get Y'
                && $code->dealVoucher->deals->promo_type != 'Discount bill'
            ) {
                return $result;
            }
        } elseif (!empty($post['id_subscription_user'])) {
            $code = app($this->subscription_use)->checkSubscription($post['id_subscription_user'], 1, 1, 1);
            if (!$code) {
                $promo_error = 'Promo not valid';
                return $result;
            }
            $source             = 'subscription';
            $brands             = $code->subscription_user->subscription->subscription_brands->pluck('id_brand')->toArray();
            $all_outlet         = $code['subscription_user']['subscription']['is_all_outlet'] ?? 0;
            $promo_outlet       = $code['subscription_user']['subscription']['outlets_active'] ?? [];
            $promo_outlet_group = $code['subscription_user']['subscription']['outlet_groups'] ?? [];
            $id_brand_promo     = $code['subscription_user']['subscription']['id_brand'] ?? null;
            $brand_rule         = $code['subscription_user']['subscription']['brand_rule'] ?? 'and';
            $promo_type         = 'Subscription';
        }

        if (($code['promo_campaign']['date_end'] ?? $code['voucher_expired_at'] ?? $code['subscription_expired_at']) < date('Y-m-d H:i:s')) {
            $promo_error = 'Promo is ended';
            return $result;
        }

        $code = $code->toArray();

        if (!empty($id_brand_promo)) {
            $check_outlet = $this->checkOutletRule($post['id_outlet'], $all_outlet, $promo_outlet, $id_brand_promo);
        } else {
            $check_outlet = $this->checkOutletBrandRule($post['id_outlet'], $all_outlet, $promo_outlet, $brands, $brand_rule, $promo_outlet_group);
        }

        if (!$check_outlet) {
            $promo_error = 'Promo tidak dapat digunakan di outlet ini.';
            return $result;
        }

        $applied_product = app($this->promo_campaign)->getProduct($source, ($code['promo_campaign'] ?? $code['deal_voucher']['deals'] ?? $code['subscription_user']['subscription']))['applied_product'] ?? [];

        if ($list_product_source == 'list_product') {
            // used on list product result, before ordering and check category
            $result = $this->checkListProduct($applied_product, $id_brand_promo, $brands, $result);
        } elseif ($list_product_source == 'search_product') {
            // used on search product
            $result = $this->checkListProduct2($applied_product, $id_brand_promo, $brands, $result);
        } elseif ($list_product_source == 'list_product2') {
            // used on list product, after ordering and check category
            $result = $this->checkListProduct3($applied_product, $brands, $result, $promo_type ?? "");
        }

        return $result;
    }

    public function checkListProduct($applied_product, $id_brand_promo, $brands, $list_product)
    {
        $result = $list_product;

        // set default flag to 0

        $promo_product = [];
        $promo_product_position = 1;
        if (!empty($id_brand_promo)) { // single brand
            foreach ($result as $id_brand => $categories) {
                foreach ($categories as $id_category => $products) {
                    foreach ($products['list'] ?? $products as $key => $product) {
                        if ($product['id_brand'] != $id_brand_promo) {
                            continue;
                        }
                        if ($applied_product == '*') { // all product
                            if (!is_numeric($id_category)) {
                                $result[$id_brand][$id_category]['list'][$key]['is_promo'] = 1;
                            } else {
                                $result[$id_brand][$id_category][$key]['is_promo'] = 1;
                                $promo_product[] = ['position' => $promo_product_position] + $result[$id_brand][$id_category][$key];
                                $promo_product_position++;
                            }
                        } else {
                            if (isset($applied_product['id_product'])) { // single product
                                if ($applied_product['id_product'] == $product['id_product']) {
                                    if (!is_numeric($id_category)) {
                                        $result[$id_brand][$id_category]['list'][$key]['is_promo'] = 1;
                                    } else {
                                        $result[$id_brand][$id_category][$key]['is_promo'] = 1;
                                        $promo_product[] = ['position' => $promo_product_position] + $result[$id_brand][$id_category][$key];
                                        $promo_product_position++;
                                    }
                                }
                            } else { // multiple product
                                foreach ($applied_product as $val) {
                                    if ($val['id_product'] == $product['id_product']) {
                                        if (!is_numeric($id_category)) {
                                            $result[$id_brand][$id_category]['list'][$key]['is_promo'] = 1;
                                        } else {
                                            $result[$id_brand][$id_category][$key]['is_promo'] = 1;
                                            $promo_product[] = ['position' => $promo_product_position] + $result[$id_brand][$id_category][$key];
                                            $promo_product_position++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else { // multi brand
            foreach ($result as $id_brand => $categories) {
                foreach ($categories as $id_category => $products) {
                    foreach ($products['list'] ?? $products as $key => $product) {
                        if ($applied_product == '*') { // all product
                            if (in_array($product['id_brand'], $brands)) {
                                if (!is_numeric($id_category)) {
                                    $result[$id_brand][$id_category]['list'][$key]['is_promo'] = 1;
                                } else {
                                    $result[$id_brand][$id_category][$key]['is_promo'] = 1;
                                    $promo_product[] = ['position' => $promo_product_position] + $result[$id_brand][$id_category][$key];
                                    $promo_product_position++;
                                }
                            }
                        } else {
                            foreach ($applied_product as $val) { // multiple product
                                if ($val['id_brand'] == $product['id_brand'] && $val['id_product'] == $product['id_product']) {
                                    if (!is_numeric($id_category)) {
                                        $result[$id_brand][$id_category]['list'][$key]['is_promo'] = 1;
                                    } else {
                                        $result[$id_brand][$id_category][$key]['is_promo'] = 1;
                                        $promo_product[] = ['position' => $promo_product_position] + $result[$id_brand][$id_category][$key];
                                        $promo_product_position++;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($promo_product)) {
            $new_promo_category['applied_promo'] = [
                'category' => [
                    "product_category_name" => "Promo",
                    "product_category_order" => -2000000,
                    "id_product_category" => null,
                    "url_product_category_photo" => ""
                ],
                'list'  => $promo_product
            ];
            $brand = array_keys($result);
            $brand_list = Brand::select('id_brand', 'name_brand', 'code_brand', 'order_brand')->whereIn('id_brand', $brand)->get()->toArray();
            if ($brand_list) {
                usort($brand_list, function ($a, $b) {
                    return $a['order_brand'] <=> $b['order_brand'];
                });
                $brand_list = array_column($brand_list, 'id_brand');
                foreach ($result as $key => &$value) {
                    if ($key == $brand_list[0]) {
                        $value = $value + $new_promo_category;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    public function checkListProduct2($applied_product, $id_brand_promo, $brands, $list_product)
    {
        $products = $list_product;

        if (!empty($id_brand_promo)) { // single brand
            foreach ($products as $key => $product) {
                if ($product['id_brand'] != $id_brand_promo) {
                    continue;
                }
                if ($applied_product == '*') { // all product
                    $products[$key]['is_promo'] = 1;
                } else {
                    if (isset($applied_product['id_product'])) { // single product
                        if ($applied_product['id_product'] == $product['id_product']) {
                            $products[$key]['is_promo'] = 1;
                        }
                    } else { // multiple product
                        foreach ($applied_product as $val) {
                            if ($val['id_product'] == $product['id_product']) {
                                $products[$key]['is_promo'] = 1;
                            }
                        }
                    }
                }
            }
        } else { // multi brand
            foreach ($products as $key => $product) {
                if ($applied_product == '*') { // all product
                    if (in_array($product['id_brand'], $brands)) {
                        $products[$key]['is_promo'] = 1;
                    }
                } else {
                    foreach ($applied_product as $val) { // multiple product
                        if ($val['id_brand'] == $product['id_brand'] && $val['id_product'] == $product['id_product']) {
                            $products[$key]['is_promo'] = 1;
                        }
                    }
                }
            }
        }

        return $products;
    }

    public function checkListProduct3($applied_product, $brands, $list_product, $promo_type = '')
    {
        $result = $list_product;

        $promo_product = []; // store unique product that get promo
        $flagged_product = []; // to check if product is in promo product or not
        foreach ($result as $key => $categories) {
            foreach ($categories['list'] as $key2 => $products) {
                foreach ($products['list'] as $key3 => $product) {
                    if (is_null($product['id_product'])) {
                        continue;
                    }
//                  if(isset($product['is_promo_bundling']) &&  $product['is_promo_bundling'] == 0) {
//                        continue;
//                    }elseif (isset($product['is_promo_bundling']) &&  $product['is_promo_bundling'] == 1 && ($promo_type != 'Discount bill' || $promo_type != 'Subscription') && $applied_product != '*'){
//                        continue;
//                    }

                    if ($applied_product == '*') { // all product
                        if (in_array($product['id_brand'], $brands)) {
                            $result[$key]['list'][$key2]['list'][$key3]['is_promo'] = 1;
                            if (isset($flagged_product[$product['id_brand']][$product['id_product']])) {
                                continue;
                            }
                            // if promo for all product, doesnt create promo category
                            // $promo_product[] = $result[$key]['list'][$key2]['list'][$key3];
                            $flagged_product[$product['id_brand']][$product['id_product']] = 1;
                        }
                    } else {
                        foreach ($applied_product as $val) { // selected multiple product
                            if ($val['id_brand'] == $product['id_brand'] && $val['id_product'] == $product['id_product']) {
                                $result[$key]['list'][$key2]['list'][$key3]['is_promo'] = 1;
                                if (isset($flagged_product[$product['id_brand']][$product['id_product']])) {
                                    continue;
                                }
                                $promo_product[] = $result[$key]['list'][$key2]['list'][$key3];
                                $flagged_product[$product['id_brand']][$product['id_product']] = 1;
                            }
                        }
                    }
                }
            }
        }

        $brand_get_promo = [];
        /*
        if (!empty($promo_product)) {
            $promo_category = [
                'category' => [
                    "product_category_name" => "Promo",
                    "product_category_order" => -2000000,
                    "id_product_category" => null,
                    "url_product_category_photo" => ""
                ],
                'list'  => $promo_product
            ];

            $promo_brand = [
                "brand" => [
                    "id_brand" => 0,
                    "name_brand" => "Promo",
                    "code_brand" => "0",
                    "order_brand" => 0
                ],
                "list" => [$promo_category]
            ];

            $brand_get_promo[] = $promo_brand;
        }
        */

        $brand_not_get_promo = [];
        $bundling = [];
        foreach ($result as $value) {
            if ($value['brand']['id_brand'] >= 1000) {
                $bundling[] = $value;
            } elseif (isset($flagged_product[$value['brand']['id_brand']])) {
                $brand_get_promo[] = $value;
            } else {
                $brand_not_get_promo[] = $value;
            }
        }
        $result = array_merge($bundling, $brand_get_promo, $brand_not_get_promo);

        return $result;
    }

    public function checkProductErrorApplied($promo_product, $id_outlet, &$missing_product_messages)
    {
        $promo_product_count = count($promo_product);
        if ($promo_product_count == 1) {
            $product_error_applied = 1;
            $check_promo_product_availability = $this->getOneProductV2($id_outlet, $promo_product[0]['id_product'], $promo_product[0]['id_brand']);
            if (!$check_promo_product_availability) {
                $product_error_applied  = 0;
                $missing_product_messages   = 'Promo tidak dapat digunakan di outlet ini karena produk tidak tersedia';
            }
        } else {
            $product_error_applied = 'all';
        }

        return $product_error_applied;
    }

    public function getProductName($promo_product, $product_rule)
    {
        $product_name = '';
        if (count($promo_product) == 1) {
            $promo_product[0]['product_name'] =
            $product = Product::where('id_product', $promo_product[0]['id_product'])->first();
            $product_name = $product->product_name;

            if ($promo_product[0]['id_product_variant_group']) {
                $variant = ProductVariantPivot::join('product_variants', 'product_variants.id_product_variant', 'product_variant_pivot.id_product_variant')
                            ->where('id_product_variant_group', $promo_product[0]['id_product_variant_group'])->pluck('product_variant_name');

                if ($variant->isNotEmpty()) {
                    $variant = implode(' ', $variant->toArray());
                    $product_name = $product_name . ' ' . $variant;
                }
            }
        }

        if (empty($product_name)) {
            if ($product_rule === 'and') {
                $product_name = 'semua produk bertanda khusus';
            } else {
                $product_name = 'produk bertanda khusus';
            }
        }

        return $product_name;
    }

    public function getActivePromoCourier($request, $listDelivery, $query)
    {
        if ($request->promo_code) {
            $promoShipment = $query->promo_campaign->promo_campaign_shipment_method->pluck('shipment_method');
            $isAllShipment = $query->is_all_shipment;
        } elseif ($request->id_deals_user) {
            $promoShipment = $query->dealVoucher->deals->deals_shipment_method->pluck('shipment_method');
            $isAllShipment = $query->dealVoucher->deals->is_all_shipment;
        } elseif ($request->id_subscription_user) {
            $promoShipment = $query->subscription_user->subscription->subscription_shipment_method->pluck('shipment_method');
            $isAllShipment = $query->subscription_user->subscription->is_all_shipment;
        }

        $courier = $request->courier;
        $courierPromo = null;
        foreach ($listDelivery as $delivery) {
            if ($this->checkShipmentRule($isAllShipment, $delivery['code'], $promoShipment)) {
                // $courierPromo = $courierPromo ?? $delivery;
                if (
                    (empty($courier) && $delivery['disable'] == 0)
                    || $delivery['courier'] == $courier
                ) {
                        $courierPromo = $delivery;
                        break;
                }
            }
        }

        return $courierPromo;
    }

    public function reorderSelectedDelivery($listDelivery, $delivery)
    {
        if (!$delivery) {
            return $listDelivery;
        }

        $selected = [];
        foreach ($listDelivery as $key => $val) {
            if ($val['code'] == $delivery['code']) {
                $selected[] = $val;
                unset($listDelivery[$key]);
                break;
            }
        }

        $listDelivery = array_merge($selected, $listDelivery);
        return $listDelivery;
    }

    public function countReferralCashback($id_promo_campaign, $subtotal)
    {
        $referral_rule = PromoCampaignReferral::where('id_promo_campaign', $id_promo_campaign)->first();
        if (!$referral_rule) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Promo referral tidak ditemukan']
            ]);
        }

        $referred_cashback = 0;
        if ($referral_rule->referred_promo_type == 'Cashback') {
            if ($referral_rule->referred_promo_unit == 'Percent') {
                $referred_cashback = $this->countPercentDiscount($referral_rule->referred_promo_value, $subtotal, $referral_rule->referred_promo_value_max);
            } else {
                if ($subtotal >= $referral_rule->referred_min_value) {
                    $referred_cashback = $this->countNominalDiscount($referral_rule->referred_promo_value, $subtotal);
                }
            }
        }

        return MyHelper::checkGet($referred_cashback);
    }

    public function countPercentDiscount($percentValue, $price, $maxDiscount = null)
    {
        $percentValue = $percentValue <= 100 ? $percentValue : 100;
        $discount = $price * $percentValue / 100;
        return ($maxDiscount && $maxDiscount < $discount) ? $maxDiscount : $discount;
    }

    public function countNominalDiscount($nominalDiscount, $price)
    {
        return ($nominalDiscount <= $post['subtotal']) ? $nominalDiscount : $price;
    }
}
