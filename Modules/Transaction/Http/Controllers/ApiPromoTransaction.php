<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Brand\Entities\BrandOutlet;
use Modules\PromoCampaign\Entities\PromoCampaign;
use Modules\PromoCampaign\Entities\PromoCampaignBrand;
use Modules\PromoCampaign\Entities\PromoCampaignDiscountBillProduct;
use Modules\PromoCampaign\Entities\PromoCampaignOutlet;
use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\PromoCampaign\Entities\PromoCampaignProductDiscount;
use Modules\PromoCampaign\Entities\PromoCampaignProductDiscountRule;
use Modules\PromoCampaign\Entities\PromoCampaignTierDiscountProduct;
use Modules\PromoCampaign\Entities\PromoCampaignTierDiscountRule;
use Modules\PromoCampaign\Entities\PromoCampaignBuyxgetyProductRequirement;
use Modules\PromoCampaign\Entities\PromoCampaignBuyxgetyRule;
use Modules\PromoCampaign\Entities\PromoCampaignHaveTag;
use Modules\PromoCampaign\Entities\PromoCampaignTag;
use Modules\PromoCampaign\Entities\PromoCampaignReport;
use Modules\PromoCampaign\Entities\UserReferralCode;
use Modules\PromoCampaign\Entities\UserPromo;

;
use Modules\Transaction\Entities\TransactionPromo;
use Modules\PromoCampaign\Entities\PromoCampaignShipmentMethod;
use Modules\PromoCampaign\Entities\PromoCampaignPaymentMethod;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;

use Modules\ProductVariant\Entities\ProductGroup;

use App\Http\Models\User;
use App\Http\Models\Configs;
use App\Http\Models\Campaign;
use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use App\Http\Models\Setting;
use App\Http\Models\Voucher;
use App\Http\Models\Treatment;
use App\Http\Models\Deal;
use App\Http\Models\DealsUser;
use App\Http\Models\DealsPromotionTemplate;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionVoucher;
use App\Http\Models\OauthAccessToken;

use Modules\Transaction\Entities\TransactionProductPromo;

use Modules\PromoCampaign\Lib\PromoCampaignTools;
use App\Lib\MyHelper;
use App\Jobs\GeneratePromoCode;
use App\Lib\TemporaryDataManager;
use Lcobucci\JWT\Parser;
use DB;
use Hash;

class ApiPromoTransaction extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->online_trx = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->voucher = "Modules\Deals\Http\Controllers\ApiDealsVoucher";
        $this->fraud = "Modules\SettingFraud\Http\Controllers\ApiFraud";
        $this->promo_campaign = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
        $this->subscription_use = "Modules\Subscription\Http\Controllers\ApiSubscriptionUse";
    }

    public function availableVoucher()
    {
        $user = request()->user();
        if (!$user) {
            return [];
        }

        $voucher = DealsUser::where('id_user', $user->id)
            ->whereIn('paid_status', ['Free', 'Completed'])
            ->whereNull('used_at')
            ->with(['dealVoucher', 'dealVoucher.deal', 'dealVoucher.deal.outlets.city', 'dealVoucher.deal.outlets.city'])
            ->where('deals_users.voucher_expired_at', '>', date('Y-m-d H:i:s'))
            ->orderBy('deals_users.is_used', 'desc')
            ->orderBy('deals_users.voucher_expired_at', 'asc')
            ->limit(5)
            ->get()
            ->toArray();

        $result = array_map(function ($var) {
            return [
                'id_deals' => $var['deal_voucher']['id_deals'],
                'voucher_expired_at' => $var['voucher_expired_at'],
                'id_deals_voucher' => $var['id_deals_voucher'],
                'id_deals_user' => $var['id_deals_user'],
                'deals_title' => $var['deal_voucher']['deal']['deals_title'],
                'deals_second_title' => $var['deal_voucher']['deal']['deals_second_title'],
                'url_deals_image' => $var['deal_voucher']['deal']['url_deals_image'],
                'is_used' => $var['is_used'],
                'date_expired_indo' => MyHelper::adjustTimezone($var['voucher_expired_at'], $user->user_time_zone_utc ?? 7, 'd F Y', true),
                'time_expired_indo' => 'pukul ' . date('H:i', strtotime($var['voucher_expired_at'])),
                'text' => null,
                'is_error' => false
            ];
        }, $voucher);

        return $result;
    }

    public function getScope()
    {
        $bearerToken = request()->bearerToken();
        $tokenId = (new Parser())->parse($bearerToken)->getHeader('jti');
        $getOauth = OauthAccessToken::find($tokenId);
        $scopeUser = str_replace(str_split('[]""'), "", $getOauth['scopes']);

        return $scopeUser;
    }

    public function failResponse($msg = null)
    {
        $res['status'] = 'fail';
        if (!empty($msg)) {
            if (!is_array($msg)) {
                $msg = [$msg];
            }
            $res['messages'] = $msg;
        }
        return $res;
    }

    public function serviceTrxToPromo($svcTrx)
    {
        $svcPromo = [
            'outlet-service' => 'Outlet Service',
            'home-service' => 'Home Service',
            'shop' => 'Online Shop',
            'academy' => 'Academy'
        ];

        return $svcPromo[$svcTrx] ?? $svcTrx;
    }

    public function promoName($promoSource)
    {
        $promoName = [
            'deals' => 'Voucher',
            'promo_campaign' => 'Kode promo',
            'subscription' => 'Subscription'
        ];

        return $promoName[$promoSource] ?? $promoSource;
    }

    public function applyPromoCheckout($data)
    {
        $user = request()->user();
        $sharedPromoTrx = TemporaryDataManager::create('promo_trx');
        $continueCheckOut = $data['available_checkout'];
        $data['available_checkout'] = $continueCheckOut;

        $userPromo = UserPromo::where('id_user', $user->id)->where('promo_use_in', 'Product')->get()->keyBy('promo_type');

        if ($userPromo->isEmpty()) {
            return $data;
        }

        $resPromoCode = null;
        $codeType = null;
        $codeErr = [];
        $codePayment = [];
        if (isset($userPromo['promo_campaign'])) {
            $promoCode = $this->validatePromoCode($userPromo['promo_campaign']->id_reference, null);

            if ($promoCode['status'] == 'fail') {
                $codeErr = $promoCode['messages'];
            } else {
                $promoCampaign = $promoCode['result']->promo_campaign;
                $sharedPromoTrx['promo_campaign'] = $promoCampaign;
                $sharedPromoTrx['promo_campaign']['promo_code'] = $promoCode['result']->promo_code;
                $sharedPromoTrx['promo_campaign']['id_promo_campaign_promo_code'] = $promoCode['result']->id_promo_campaign_promo_code;
                $codePayment = PromoCampaignPaymentMethod::where('id_promo_campaign', $promoCampaign['id_promo_campaign'])->pluck('payment_method')->toArray();
                $codeType = $promoCampaign->promo_type;
            }
        }

        $validPayment = $data['available_payment'] ?? [];
        if (!empty($codePayment)) {
            foreach ($data['available_payment'] ?? [] as $key => $payment) {
                if (!in_array($payment['payment_method'], $codePayment)) {
                    unset($validPayment[$key]);
                }
            }
        }
        $validPayment = array_values($validPayment);

        if (isset($data['subtotal'])) {
            $sharedPromoTrx['subtotal'] = $data['subtotal'];
        }

        $totalAllDisc = 0;
        $discDeliveryAll = 0;
        if (isset($userPromo['promo_campaign'])) {
            if (!empty($promoCampaign)) {
                //check product rule
                $checkItemPromo = $this->checkRuleItem($data, $promoCampaign);
                if (!$checkItemPromo['status']) {
                    $codeErr = $checkItemPromo['message'];
                }

                //check brand outlet
                $allOutlet = array_unique(array_column($data['items'], 'id_outlet'));
                $promoBrands = PromoCampaignBrand::where('id_promo_campaign', $promoCampaign->id_promo_campaign)->pluck('id_brand')->toArray();
                $outletBrand = BrandOutlet::whereIn('id_outlet', $allOutlet)->pluck('id_brand')->toArray();
                $checkBrand     = array_diff($promoBrands, $outletBrand);

                $statusCheckBrand = true;
                if (!empty($promoBrands) && $promoCampaign->brand_rule == 'or' && count($checkBrand) == count($promoBrands)) {
                    $statusCheckBrand = false;
                } elseif (!empty($promoBrands) && $promoCampaign->brand_rule == 'and' && !empty($checkBrand)) {
                    $statusCheckBrand = false;
                }

                if (!$statusCheckBrand) {
                    $codeErr = ['Promo tidak dapat digunakan di outlet ini'];
                }
            }

            foreach ($data['items'] as $key => $dt) {
                if (empty($codeErr)) {
                    $applyCode = $this->applyPromoCode($userPromo['promo_campaign']->id_reference, $dt);
                    $codeErr = $applyCode['messages'] ?? $codeErr;
                }

                $err = $codeErr ?? [];
                $resPromoCode = [
                    'promo_code'        => $sharedPromoTrx['promo_campaign']['promo_code'] ?? null,
                    'title'             => $applyCode['result']['title'] ?? $promoCode['result']->promo_code ?? null,
                    'text'              => array_map(function ($v) {
                        return trim(strip_tags($v));
                    }, $err),
                    'remove_text'       => 'Batalkan penggunaan ' . ($sharedPromoTrx['promo_campaign']['promo_title'] ?? null)
                ];

                $resPromo = $applyCode['result'] ?? null;
                $discItemStatus = 0;
                if (!empty($resPromo['promo_type']) && ($resPromo['promo_type'] == 'Product discount' || $resPromo['promo_type'] == 'Tier discount')) {
                    $sharedPromo = TemporaryDataManager::create('promo_trx');

                    $itemsFinal = [];
                    if (!empty($sharedPromo['items'])) {
                        $discItemStatus = 1;
                        foreach ($sharedPromo['items'] as $index => $it) {
                            $explode = explode('-', $index);
                            if ($explode[0] == $dt['id_outlet'] && $it['is_promo'] == 1) {
                                $newPrice = $it['new_price'];
                                $totalDiscount = $it['total_discount'];
                                $baseDiscount = $it['base_discount'];
                                $qtyDiscount = $it['qty_discount'];
                                unset($it['new_price']);
                                unset($it['total_discount']);
                                unset($it['base_discount']);
                                unset($it['qty_discount']);
                                unset($it['discount']);
                                unset($it['not_get_discount']);

                                $it['product_price_after_discount'] = $newPrice;
                                $it['product_price_after_discount_text'] = 'Rp ' . number_format($newPrice, 0, ",", ".");
                                $it['product_price_subtotal_after_discount'] = $it['product_price_subtotal'] - $totalDiscount;
                                $it['product_price_subtotal_after_discount_text'] = 'Rp ' . number_format($it['product_price_subtotal_after_discount'], 0, ",", ".");
                                $it['total_discount'] = $totalDiscount;
                                $it['base_discount_each_item'] = $baseDiscount;
                                $it['qty_discount'] = $qtyDiscount;

                                $itemsFinal[] = $it;
                            } else {
                                unset($it['promo_qty']);
                                unset($it['new_price']);
                                unset($it['total_discount']);
                                unset($it['base_discount']);
                                unset($it['qty_discount']);
                                unset($it['discount']);
                                unset($it['not_get_discount']);

                                $itemsFinal[] = $it;
                            }
                        }
                    }
                }

                $disc = $resPromo['discount'] ?? 0;
                $discDelivery = $resPromo['discount_delivery'] ?? 0;
                $discAll = $disc + $discDelivery;
                $subTotalPromo = $dt['subtotal'] - $discAll;
                $data['items'][$key]['items'] = $itemsFinal ?? $dt['items'];
                $data['items'][$key]['items_subtotal'] = $dt['items_subtotal'];

                if ($disc > 0) {
                    $data['items'][$key]['discount'] = $discAll;
                    $data['items'][$key]['discount_text'] = 'Rp -' . number_format($discAll, 0, ",", ".");
                    $data['items'][$key]['discount_bill_status'] = ($discItemStatus == 0 && empty($discDelivery) ? 1 : 0);
                }

                if ($discDelivery > 0) {
                    $data['items'][$key]['discount_delivery'] = $discAll;
                    $data['items'][$key]['discount_delivery_text'] = 'Rp -' . number_format($discAll, 0, ",", ".");
                }

                if (!empty($discAll)) {
                    $data['items'][$key]['subtotal_promo'] = $subTotalPromo;
                    $data['items'][$key]['subtotal_promo_text'] = 'Rp ' . number_format($subTotalPromo, 0, ",", ".");
                }

                $data['items'][$key]['id_promo_campaign_promo_code'] = $resPromo['id_promo_campaign_promo_code'] ?? null;
                $totalAllDisc = $totalAllDisc + $discAll;
                $discDeliveryAll = $discDeliveryAll + $discDelivery;
            }

            $resPromoCode['is_error'] = ($totalAllDisc <= 0 ? true : false);
            $resPromoCode['text'] = ($totalAllDisc <= 0 ? $resPromoCode['text'] : []);
            $continueCheckOut = ($totalAllDisc <= 0 ? false : true);
        }

        if ($codeType == 'Discount bill') {
            if ($promoCampaign['promo_campaign_discount_bill_rules']['discount_type'] == 'Percent') {
                $maxDiscountBill = $promoCampaign['promo_campaign_discount_bill_rules']['max_percent_discount'] ?? null;
            } else {
                $maxDiscountBill = $promoCampaign['promo_campaign_discount_bill_rules']['discount_value'] ?? null;
            }
            if (!empty($maxDiscountBill) && $totalAllDisc > $maxDiscountBill) {
                $totalAllDisc = 0;
                $subtotal = $data['subtotal'];
                $lastDiscount = $maxDiscountBill;
                $count = count($data['items']);

                foreach ($data['items'] as $key => $dt) {
                    $index = $key + 1;
                    $sub = $dt['subtotal'];
                    $finalDiscount = 0;

                    foreach ($dt['items'] as $item) {
                        $discItem = (int)($item['product_price_subtotal'] / $subtotal * $maxDiscountBill);
                        $finalDiscount = $finalDiscount + $discItem;
                    }

                    if ($count == $index) {
                        $finalDiscount = $lastDiscount;
                    } else {
                        $lastDiscount = $lastDiscount - $finalDiscount;
                    }

                    if ($discItem > 0) {
                        $data['items'][$key]['discount'] = $finalDiscount;
                        $data['items'][$key]['discount_text'] = 'Rp -' . number_format($finalDiscount, 0, ",", ".");
                        $subTotalPromo = $sub - $finalDiscount;
                        $data['items'][$key]['subtotal_promo'] = $subTotalPromo;
                        $data['items'][$key]['subtotal_promo_text'] = 'Rp ' . number_format($subTotalPromo, 0, ",", ".");
                    }

                    $totalAllDisc = $totalAllDisc + $finalDiscount;
                }
            }
        } elseif ($codeType == 'Discount delivery') {
            if ($promoCampaign['promo_campaign_discount_delivery_rules']['discount_type'] == 'Percent') {
                $maxDiscountDelivery = $promoCampaign['promo_campaign_discount_delivery_rules']['max_percent_discount'] ?? null;
            } else {
                $maxDiscountDelivery = $promoCampaign['promo_campaign_discount_delivery_rules']['discount_value'] ?? null;
            }

            if (!empty($maxDiscountDelivery) && $totalAllDisc > $maxDiscountDelivery) {
                $totalAllDisc = 0;
                $subtotal = $data['total_delivery'];
                $lastDiscount = $maxDiscountDelivery;
                $count = count($data['items']);

                foreach ($data['items'] as $key => $dt) {
                    $index = $key + 1;
                    $sub = $dt['subtotal'];
                    $deliveryPrice = $dt['delivery_price']['shipment_price'] ?? 0;
                    if ($deliveryPrice > 0) {
                        $discItem = (int)($deliveryPrice / $subtotal * $maxDiscountDelivery);

                        if ($count == $index) {
                            $discItem = $lastDiscount;
                        } else {
                            $lastDiscount = $lastDiscount - $discItem;
                        }

                        if ($discItem > 0) {
                            $data['items'][$key]['discount_delivery'] = $discItem;
                            $data['items'][$key]['discount_delivery_text'] = 'Rp -' . number_format($discItem, 0, ",", ".");
                            $subTotalPromo = $sub - $discItem;
                            $data['items'][$key]['subtotal_promo'] = $subTotalPromo;
                            $data['items'][$key]['subtotal_promo_text'] = 'Rp ' . number_format($subTotalPromo, 0, ",", ".");
                        }

                        $discDeliveryAll = $discDeliveryAll + $discItem;
                        $totalAllDisc = $totalAllDisc + $discItem;
                    }
                }
            }
        }

        if (!empty($resPromoCode) && $resPromoCode['is_error'] && empty($resPromoCode['text']) && $codeType == 'Discount delivery') {
            $resPromoCode['text'] = ['Silahkan pilih layanan pengiriman yang diinginkan'];
        }

        $data['promo_code'] = $resPromoCode;
        if ($totalAllDisc > 0) {
            $data['summary_order'][] = [
                'name' => 'Diskon' . ($discDeliveryAll > 0 ? ' Pengiriman' : ''),
                'is_discount' => 1,
                'value' => '-Rp ' . number_format($totalAllDisc, 0, ",", ".")
            ];
            $data['total_discount'] = $totalAllDisc;
            $data['grandtotal'] = $data['grandtotal'] - $totalAllDisc;
            $data['grandtotal_text'] = 'Rp ' . number_format($data['grandtotal'], 0, ",", ".");
        }
        $data['available_payment'] = $validPayment;
        $data['available_checkout'] = $continueCheckOut;
        return $data;
    }

    public function applyPromoCheckoutConsultation($data)
    {
        $user = request()->user();
        $sharedPromoTrx = TemporaryDataManager::create('promo_trx');
        $userPromo = UserPromo::where('id_user', $user->id)->where('promo_use_in', 'Consultation')->get()->keyBy('promo_type');

        if ($userPromo->isEmpty()) {
            return $data;
        }

        $resPromoCode = null;
        $codeErr = [];
        $codePayment = [];
        if (isset($userPromo['promo_campaign'])) {
            $promoCode = $this->validatePromoCode($userPromo['promo_campaign']->id_reference, $data['doctor']['id_doctor'] ?? $data['id_doctor']);

            if ($promoCode['status'] == 'fail') {
                $codeErr = $promoCode['messages'];
            } else {
                $promoCampaign = $promoCode['result']->promo_campaign;
                $sharedPromoTrx['promo_campaign'] = $promoCampaign;
                $sharedPromoTrx['promo_campaign']['promo_code'] = $promoCode['result']->promo_code;
                $sharedPromoTrx['promo_campaign']['id_promo_campaign_promo_code'] = $promoCode['result']->id_promo_campaign_promo_code;
                $codePayment = PromoCampaignPaymentMethod::where('id_promo_campaign', $promoCampaign['id_promo_campaign'])->pluck('payment_method')->toArray();
                $codeType = $promoCampaign->promo_type;
            }
        }

        if (!empty($codePayment)) {
            $validPayment = [];
            foreach ($data['available_payment'] as $payment) {
                if (!in_array($payment['payment_method'], $codePayment)) {
                    $payment['status'] = 0;
                    continue;
                }
                if (!empty($payment['status'])) {
                    $validPayment[] = $payment['payment_method'];
                }
            }
            $codePayment = $validPayment;
            if (empty($validPayment)) {
                $codeErr = 'Metode pembayaran tidak tersedia';
            }

            $promoPayment = $codePayment;

            foreach ($data['available_payment'] as &$payment) {
                if (!in_array($payment['payment_method'], $promoPayment)) {
                    $payment['status'] = 0;
                }
            }
        }

        $totalAllDisc = 0;
        if (isset($userPromo['promo_campaign'])) {
            if (empty($codeErr)) {
                $applyCode = $this->applyPromoCode($userPromo['promo_campaign']->id_reference, $data);
                $codeErr = $applyCode['messages'] ?? $codeErr;
            }

            $err = $applyCode['result']['text'] ?? $codeErr;
            $resPromoCode = [
                'promo_code'        => $sharedPromoTrx['promo_campaign']['promo_code'] ?? null,
                'title'             => $applyCode['result']['title'] ?? null,
                'text'              => array_map(function ($v) {
                    return trim(strip_tags($v));
                }, $err),
                'remove_text'       => 'Batalkan penggunaan ' . ($sharedPromoTrx['promo_campaign']['promo_title'] ?? null)
            ];

            $resPromo = $applyCode['result'] ?? null;
            $disc = $resPromo['discount'] ?? 0;
            $totalAllDisc = $disc;
            $data['id_promo_campaign_promo_code'] = $resPromo['id_promo_campaign_promo_code'] ?? null;

            if (!empty($resPromoCode)) {
                $resPromoCode['is_error'] = (!empty($codeErr) ? true : false);
            }
        }

        $data['promo_code'] = $resPromoCode;
        if ($totalAllDisc > 0) {
            $data['payment_detail'][] = [
                'name' => 'Diskon',
                'is_discount' => 1,
                'value' => '-Rp ' . number_format($totalAllDisc, 0, ",", ".")
            ];
            $data['total_discount'] = $totalAllDisc;
            $data['grandtotal'] = $data['grandtotal'] - $totalAllDisc;
            $data['grandtotal_text'] = 'Rp ' . number_format($data['grandtotal'], 0, ",", ".");
        }
        return $data;
    }

    public function checkRuleItem($data, $promo)
    {
        //all id product in cart
        $allIDItem = [];
        foreach ($data['items'] as $dt) {
            $column = array_column($dt['items'], 'id_product');
            $allIDItem = array_merge($allIDItem, $column);
        }

        switch ($promo->promo_type) {
            case 'Product discount':
                $productFromPromo   = PromoCampaignProductDiscount::where('id_promo_campaign', $promo->id_promo_campaign)->pluck('id_product')->toArray();
                break;

            case 'Tier discount':
                $productFromPromo   = PromoCampaignTierDiscountProduct::where('id_promo_campaign', $promo->id_promo_campaign)->pluck('id_product')->toArray();
                break;

            case 'Discount bill':
                $productFromPromo   = PromoCampaignDiscountBillProduct::where('id_promo_campaign', $promo->id_promo_campaign)->pluck('id_product')->toArray();
                break;

            default:
                $productFromPromo = [];
                break;
        }

        $check = array_diff($productFromPromo, $allIDItem);
        $statusCanUse = true;
        if (!empty($productFromPromo) && $promo->product_rule == 'or' && count($check) == count($productFromPromo)) {
            $statusCanUse = false;
            $msg = ['Promo dapat digunakan dengan membeli produk bertanda khusus'];
        } elseif (!empty($productFromPromo) && $promo->product_rule == 'and' && !empty($check)) {
            $statusCanUse = false;
            $msg = ['Promo dapat digunakan dengan membeli semua produk bertanda khusus'];
        }

        return [
            'status' => $statusCanUse,
            'message' => $msg ?? ''
        ];
    }

    public function applyDeals($id_deals_user, $data = [])
    {
        $sharedPromoTrx = TemporaryDataManager::create('promo_trx');
        $deals = $sharedPromoTrx['deals'];

        $validateGlobalRules = $this->validateGlobalRules('deals', $deals, $data);
        if ($validateGlobalRules['status'] == 'fail') {
            return $validateGlobalRules;
        }

        $getDiscount = $this->getDiscount('deals', $deals, $data);
        if ($getDiscount['status'] == 'fail') {
            return $getDiscount;
        }

        $validateGlobalRulesAfter = $this->validateGlobalRulesAfter('deals', $deals, $data);
        if ($validateGlobalRulesAfter['status'] == 'fail') {
            return $validateGlobalRulesAfter;
        }

        $getProduct = app($this->promo_campaign)->getProduct('deals', $deals);
        $desc = app($this->promo_campaign)->getPromoDescription('deals', $deals, $getProduct['product'] ?? '');

        $res = [
            'id_deals' => $deals->id_deals,
            'id_deals_user' => $id_deals_user,
            'title' => $deals->deals_title,
            'discount' => $getDiscount['result']['discount'] ?? 0,
            'discount_delivery' => $getDiscount['result']['discount_delivery'] ?? 0,
            'promo_type' => $getDiscount['result']['promo_type'],
            'text' => [$desc],
            'promo_source' => 'deals'
        ];

        return MyHelper::checkGet($res);
    }

    public function validateDeals($id_deals_user)
    {
        $dealsUser = DealsUser::find($id_deals_user);
        if (!$dealsUser) {
            return $this->failResponse('Voucher tidak ditemukan');
        }

        if ($dealsUser['id_user'] != request()->user()->id) {
            return $this->failResponse('Voucher tidak tersedia');
        }

        if ($dealsUser['used_at']) {
            return $this->failResponse('Voucher sudah pernah digunakan');
        }

        if ($dealsUser['voucher_expired_at'] < date('Y-m-d H:i:s')) {
            return $this->failResponse('Voucher sudah melewati batas waktu penggunaan');
        }

        if (!empty($dealsUser['voucher_active_at']) && $dealsUser['voucher_active_at'] > date('Y-m-d H:i:s')) {
            $dateStart = MyHelper::adjustTimezone($dealsUser['voucher_active_at'], null, 'l, d F Y H:i', true);
            return $this->failResponse('Voucher mulai dapat digunakan pada ' . $dateStart);
        }

        return MyHelper::checkGet($dealsUser);
    }

    public function applyPromoCode($id_code, $data = [])
    {
        $sharedPromoTrx = TemporaryDataManager::create('promo_trx');
        $promoCampaign = $sharedPromoTrx['promo_campaign'];

        $validateGlobalRules = $this->validateGlobalRules('promo_campaign', $promoCampaign, $data);
        if ($validateGlobalRules['status'] == 'fail') {
            return $validateGlobalRules;
        }

        $getDiscount = $this->getDiscount('promo_campaign', $promoCampaign, $data);
        if ($getDiscount['status'] == 'fail') {
            return $getDiscount;
        }

        $validateGlobalRulesAfter = $this->validateGlobalRulesAfter('promo_campaign', $promoCampaign, $data);
        if ($validateGlobalRulesAfter['status'] == 'fail') {
            return $validateGlobalRulesAfter;
        }

        $getProduct = app($this->promo_campaign)->getProduct('promo_campaign', $promoCampaign);
        $desc = app($this->promo_campaign)->getPromoDescription('promo_campaign', $promoCampaign, $getProduct['product'] ?? '');

        $res = [
            'id_promo_campaign' => $promoCampaign->id_promo_campaign,
            'id_promo_campaign_promo_code' => $promoCampaign->id_promo_campaign_promo_code,
            'promo_code' => $promoCampaign->promo_code,
            'title' => $promoCampaign->promo_title,
            'discount' => $getDiscount['result']['discount'] ?? 0,
            'discount_delivery' => $getDiscount['result']['discount_delivery'] ?? 0,
            'promo_type' => $getDiscount['result']['promo_type'],
            'text' => [$desc],
            'promo_source' => 'promo_campaign'
        ];

        return MyHelper::checkGet($res);
    }

    public function validatePromoCode($id_code, $id_doctor = null)
    {
        $promoCode = PromoCampaignPromoCode::find($id_code);

        if (!$promoCode) {
            return $this->failResponse('Kode promo tidak ditemukan');
        }

        $promo = $promoCode->promo_campaign;
        if (!$promo) {
            return $this->failResponse('Promo tidak ditemukan');
        }

        if (!empty($id_doctor) && $promo->promo_use_in != 'Consultation') {
            return $this->failResponse('Promo tidak bisa digunakan untuk transaksi konsultasi');
        }

        if (empty($id_doctor) && $promo->promo_use_in == 'Consultation') {
            return $this->failResponse('Promo hanya bisa digunakan pada transaksi konsultasi');
        }

        if (!$promo->step_complete || !$promo->user_type) {
            return $this->failResponse('Terdapat kesalahan pada promo');
        }

        $pct = new PromoCampaignTools();
        $user = request()->user();
        if ($promo->user_type == 'New user') {
            $check = Transaction::where('id_user', '=', $user->id)
                ->where('transaction_payment_status', 'Completed')
                ->whereNull('reject_at')
                ->first();
            if ($check) {
                return $this->failResponse('Promo hanya berlaku untuk pengguna baru');
            }
        } elseif ($promo->user_type == 'Specific user') {
            $validPhone = explode(',', $promo->specific_user);
            if (!in_array($user->phone, $validPhone)) {
                return $this->failResponse('Promo tidak berlaku untuk akun Anda');
            }
        }

        if ($promo->code_type == 'Single') {
            if ($promo->limitation_usage) {
                $usedCode = PromoCampaignReport::where('id_promo_campaign', $promo->id_promo_campaign)->where('id_user', $user->id)->distinct()->count('id_transaction_group');
                if ($usedCode >= $promo->limitation_usage) {
                    return $this->failResponse('Promo tidak tersedia');
                }
            }
        } else {
            $used_by_other_user = PromoCampaignReport::where('id_promo_campaign', $promo->id_promo_campaign)
                ->where('id_user', '!=', $user->id)
                ->where('id_promo_campaign_promo_code', $id_code)
                ->first();

            if ($used_by_other_user) {
                return $this->failResponse('Promo tidak berlaku untuk akun Anda');
            }

            $used_code = PromoCampaignReport::where('id_promo_campaign', $promo->id_promo_campaign)
                ->where('id_user', $user->id)
                ->where('id_promo_campaign_promo_code', $id_code)
                ->distinct()->count('id_transaction_group');

            if ($code_limit = $promo->code_limit) {
                if ($used_code >= $code_limit) {
                    return $this->failResponse('Promo tidak tersedia');
                }
            }

            if ($promo->user_limit && !$used_code) {
                $used_diff_code = PromoCampaignReport::where('id_promo_campaign', $promo->id_promo_campaign)
                    ->where('id_user', $user->id)
                    ->distinct()
                    ->count('id_promo_campaign_promo_code', 'id_promo_campaign_promo_code');

                if ($used_diff_code >= $promo->user_limit) {
                    return $this->failResponse('Promo tidak tersedia');
                }
            }
        }

        if ($promo['date_end'] < date('Y-m-d H:i:s')) {
            return $this->failResponse('Kode promo sudah melewati batas waktu penggunaan');
        }

        if (!empty($promo['date_start']) && $promo['date_start'] > date('Y-m-d H:i:s')) {
            $dateStart = MyHelper::adjustTimezone($promo['date_start'], null, 'l, d F Y H:i', true);
            return $this->failResponse('Kode promo mulai dapat digunakan pada ' . $dateStart);
        }

        return MyHelper::checkGet($promoCode);
    }

    public function validateGlobalRules($promoSource, $promoQuery, $data)
    {
        $promo = $promoQuery;
        $sharedPromoTrx = TemporaryDataManager::create('promo_trx');
        $promoName = $this->promoName($promoSource);
        $pct = new PromoCampaignTools();

        if (!empty($data['delivery_price']['shipment_code']) || !empty($data['transaction_shipments']['shipment_courier_code'])) {
            $shipment = $data['delivery_price']['shipment_code'] ?? $data['transaction_shipments']['shipment_courier_code'];
            $promoShipment = $promo->{$promoSource . '_shipment_method'}->pluck('shipment_method');
            $checkShipment = $pct->checkShipmentRule($promo->is_all_shipment ?? 0, $shipment, $promoShipment);
            if (!$checkShipment) {
                return $this->failResponse($promoName . ' tidak dapat digunakan untuk pengiriman ini');
            }
        }

        $id_outlet = $data['id_outlet'];
        $promoBrand = $promo->{$promoSource . '_brands'}->pluck('id_brand')->toArray();
        $promoOutlet = $promo->{$promoSource . '_outlets'};
        $outlet = $pct->checkOutletBrandRule($id_outlet, $promo->is_all_outlet ?? 0, $promoOutlet, $promoBrand);

        if (!$outlet) {
            return $this->failResponse($promoName . ' tidak dapat digunakan di outlet ini');
        }

        if (request()->payment_detail) {
            $promoPayment = $promo->{$promoSource . '_payment_method'}->pluck('payment_method');
            $checkPayment = $pct->checkPaymentRule($promo->is_all_payment ?? 0, request()->payment_detail, $promoPayment);
            if (!$checkPayment) {
                return $this->failResponse($promoName . ' tidak dapat digunakan untuk metode pembayaran ini');
            }
        }

        return ['status' => 'success'];
    }

    public function validateGlobalRulesAfter($promoSource, $promoQuery, $data)
    {
        $promo = $promoQuery;
        $sharedPromoTrx = TemporaryDataManager::create('promo_trx');
        if (isset($sharedPromoTrx['subtotal'])) {
            if ($sharedPromoTrx['subtotal'] < $promo->min_basket_size) {
                $min_basket_size = MyHelper::requestNumber($promo->min_basket_size, '_CURRENCY');
                return $this->failResponse('Pembelian minimum ' . $min_basket_size);
            }
        }

        return ['status' => 'success'];
    }

    public function getDiscount($promoSource, $promoQuery, $data)
    {
        $promo = $promoQuery;
        $promoName = $this->promoName($promoSource);
        switch ($promo->promo_type) {
            case 'Product discount':
                return $this->productDiscount($promoSource, $promoQuery, $data);
                break;

            case 'Tier discount':
                return $this->tierDiscount($promoSource, $promoQuery, $data);
                break;

            case 'Buy X Get Y':
                return $this->bxgyDiscount($promoSource, $promoQuery, $data);
                break;

            case 'Discount bill':
                return $this->billDiscount($promoSource, $promoQuery, $data);
                break;

            case 'Discount delivery':
                return $this->deliveryDiscount($promoSource, $promoQuery, $data);
                break;

            default:
                return $this->failResponse($promoName . ' tidak ditemukan');
                break;
        }
    }

    public function productDiscount($promoSource, $promoQuery, $data)
    {
        $promo          = $promoQuery;
        $pct            = new PromoCampaignTools();
        $promo_rules    = $promo->{$promoSource . '_product_discount_rules'};
        $promo_product  = $promo->{$promoSource . '_product_discount'}->toArray();
        $promo_brand    = $promo->{$promoSource . '_brands'}->pluck('id_brand')->toArray();
        $product_name   = $pct->getProductName($promo_product, $promo->product_rule);
        $shared_promo   = TemporaryDataManager::create('promo_trx');
        $promo_item     = $data['items'];
        $discount       = 0;

        if ($promo_rules->is_all_product) {
            $promo_product = "*";
        }

        $get_promo_product = $pct->getPromoProduct($promo_item, $promo_brand, $promo_product, $promoQuery['product_type']);
        $product = $get_promo_product['product'];

        if (!$product) {
            $message = $pct->getMessage('error_product_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b>.';
            $message = MyHelper::simpleReplace($message, ['product' => $product_name]);
            return $this->failResponse($message);
        }

        // get max promo qty for 1 product
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

            $promo_qty_each = $max_promo_qty;

            if ($max_promo_qty == 0 || (isset($promo_rules->max_product) && $promo_rules->max_product < $max_promo_qty)) {
                $promo_qty_each = $promo_rules->max_product;
            }
        } else {
            $promo_qty_each = $promo_rules->max_product;
        }

        $product_per_price = [];
        foreach ($product as $p) {
            $product_qty = $p['qty'];
            if (isset($p['new_price'])) {
                $qty_discount = $p['qty_discount'];
                $index = $p['new_price'] . '-' . $p['id_brand'] . '-' . $p['id_product'] . '-' . $p['id_transaction_product'];
                $product_per_price[$index] = $p;
                $product_per_price[$index]['qty'] = $p['qty_discount'];
                $product_per_price[$index]['qty'] = $p['qty_discount'];

                $product_qty -= $p['qty_discount'];
                if ($product_qty < 1) {
                    continue;
                }
            }

            $index = $data['id_outlet'] . '-' . $p['product_price'] . '-' . ($p['id_brand'] ?? 'brand') . '-' . $p['id_product'];
            if (isset($product_per_price[$index])) {
                $product_per_price[$index]['qty'] += $product_qty;
                continue;
            }

            $product_per_price[$index] = $p;
            $product_per_price[$index]['qty'] = $product_qty;
            $product_per_price[$index]['new_price'] = $p['product_price'];
        }

        // sort by most expensive product price
        uasort($product_per_price, function ($a, $b) {
            return $b['new_price'] - $a['new_price'];
        });

        foreach ($product_per_price as $k => $p) {
            if (!empty($promo_qty_each)) {
                $brand = $p['id_brand'] ?? 'brand';
                if (!isset($qty_each[$brand][$p['id_product']])) {
                    $qty_each[$brand][$p['id_product']] = $promo_qty_each;
                }

                if ($qty_each[$brand][$p['id_product']] < 0) {
                    $qty_each[$brand][$p['id_product']] = 0;
                }

                if ($qty_each[$brand][$p['id_product']] > $p['qty']) {
                    $promo_qty = $p['qty'];
                } else {
                    $promo_qty = $qty_each[$brand][$p['id_product']];
                }

                $qty_each[$brand][$p['id_product']] -= $p['qty'];
            } else {
                $promo_qty = $p['qty'];
            }
            $product_per_price[$k]['promo_qty'] = $promo_qty;
        }

        foreach ($product_per_price as $key => &$item) {
            $discount += $this->discountPerItem($item, $promo_rules);
        }

        if ($discount <= 0) {
            $message = $pct->getMessage('error_product_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b>.';
            $message = MyHelper::simpleReplace($message, ['product' => 'produk tertentu']);

            return $this->failResponse($message);
        }

        $shared_promo['items'] = $product_per_price;

        return MyHelper::checkGet([
            'discount'  => $discount,
            'promo_type' => $promo->promo_type
        ]);
    }

    public function tierDiscount($promoSource, $promoQuery, $data)
    {
        $promo          = $promoQuery;
        $pct            = new PromoCampaignTools();
        $promo_rules    = $promo->{$promoSource . '_tier_discount_rules'};
        $promo_product  = $promo->{$promoSource . '_tier_discount_product'}->toArray();
        $promo_brand    = $promo->{$promoSource . '_brands'}->pluck('id_brand')->toArray();
        $product_name   = $pct->getProductName($promo_product, $promo->product_rule);
        $shared_promo   = TemporaryDataManager::create('promo_trx');
        $promo_item     = $data['items'];
        $discount       = 0;

        // get min max required for error message
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

        if ($promo_rules[0]->is_all_product) {
            $promo_product = "*";
        }

        $get_promo_product = $pct->getPromoProduct($promo_item, $promo_brand, $promo_product, $promoQuery['product_type']);
        $product = $get_promo_product['product'];
        $total_product = $get_promo_product['total_product'];

        if (!$product) {
            $minmax = ($min_qty != $max_qty ? "$min_qty sampai $max_qty" : $min_qty) . " item";
            $message = $pct->getMessage('error_tier_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b> sebanyak %minmax%.';
            $message = MyHelper::simpleReplace($message, ['product' => $product_name, 'minmax' => $minmax]);

            return $this->failResponse($message);
        }


        // sum total quantity of same product
        $item_get_promo = []; // include brand
        $item_promo = []; // only product/item
        foreach ($product as $key => $value) {
            $brand = $value['id_brand'] ?? 'brand';
            if (isset($item_promo[$value['id_product']])) {
                $item_promo[$value['id_product']] += $value['qty'];
            } else {
                $item_promo[$value['id_product']] = $value['qty'];
            }

            if (isset($item_get_promo[$brand . '-' . $value['id_product']])) {
                $item_get_promo[$brand . '-' . $value['id_product']] += $value['qty'];
            } else {
                $item_get_promo[$brand . '-' . $value['id_product']] = $value['qty'];
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
                $valBrand = $val['id_brand'] ?? 'brand';

                foreach ($promo_rules as $key2 => $rule) {
                    if ($min_qty === null || $rule->min_qty < $min_qty) {
                        $min_qty = $rule->min_qty;
                    }

                    if ($max_qty === null || $rule->max_qty > $max_qty) {
                        $max_qty = $rule->max_qty;
                    }

                    if ($rule->min_qty > $item_get_promo[$valBrand . '-' . $val['id_product']]) {
                        if (empty($temp_rule_key[$key])) {
                            $req_valid = false;
                            break;
                        } else {
                            continue;
                        }
                    }
                    $temp_rule_key[$key][]  = $key2;
                }

                if ($item_get_promo[$valBrand . '-' . $val['id_product']] < $promo_qty_each || $promo_qty_each == 0) {
                    $promo_qty_each = $item_get_promo[$valBrand . '-' . $val['id_product']];
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
            $message = $pct->getMessage('error_tier_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b> sebanyak %minmax%.';
            $message = MyHelper::simpleReplace($message, ['product' => $product_name, 'minmax' => $minmax]);

            return $this->failResponse($message);
        }

        $product_per_price = [];
        foreach ($product as $p) {
            $pBrand = $p['id_brand'] ?? 'brand';
            $product_qty = $p['qty'];
            if (isset($p['new_price'])) {
                $qty_discount = $p['qty_discount'];
                $index = $p['new_price'] . '-' . $pBrand . '-' . $p['id_product'] . '-' . $p['id_transaction_product'];
                $product_per_price[$index] = $p;
                $product_per_price[$index]['qty'] = $p['qty_discount'];
                $product_per_price[$index]['qty'] = $p['qty_discount'];

                $product_qty -= $p['qty_discount'];
                if ($product_qty < 1) {
                    continue;
                }
            }

            $index = $data['id_outlet'] . '-' . $p['product_price'] . '-' . $pBrand . '-' . $p['id_product'] . '-' . ($p['id_transaction_product'] ?? '');
            if (isset($product_per_price[$index])) {
                $product_per_price[$index]['qty'] += $product_qty;
                continue;
            }

            $product_per_price[$index] = $p;
            $product_per_price[$index]['qty'] = $product_qty;
            $product_per_price[$index]['new_price'] = $p['product_price'];
        }

        // sort by most expensive product price
        uasort($product_per_price, function ($a, $b) {
            return $b['new_price'] - $a['new_price'];
        });

        // get max qty of product that can get promo
        $total_promo_qty = $promo_rule->max_qty < $total_product ? $promo_rule->max_qty : $total_product;
        foreach ($product_per_price as $k => $p) {
            $pBrand = $p['id_brand'] ?? 'brand';
            if (!empty($promo_qty_each)) {
                if (!isset($qty_each[$pBrand][$p['id_product']])) {
                    $qty_each[$pBrand][$p['id_product']] = $promo_qty_each;
                }

                if ($qty_each[$pBrand][$p['id_product']] < 0) {
                    $qty_each[$pBrand][$p['id_product']] = 0;
                }

                if ($qty_each[$pBrand][$p['id_product']] > $p['qty']) {
                    $promo_qty = $p['qty'];
                } else {
                    $promo_qty = $qty_each[$pBrand][$p['id_product']];
                }

                $qty_each[$pBrand][$p['id_product']] -= $p['qty'];
            } else {
                if ($total_promo_qty < 0) {
                    $total_promo_qty = 0;
                }

                if ($total_promo_qty > $p['qty']) {
                    $promo_qty = $p['qty'];
                } else {
                    $promo_qty = $total_promo_qty;
                }

                $total_promo_qty -= $promo_qty;
            }

            $product_per_price[$k]['promo_qty'] = $promo_qty;
        }

        // count discount
        $product_id = array_column($product, 'id_product');
        foreach ($product_per_price as $key => &$item) {
            if (!in_array($item['id_brand'] ?? null, $promo_brand) && !empty($promo_brand)) {
                continue;
            }

            if (in_array($item['id_product'], $product_id)) {
                // add discount
                $discount += $this->discountPerItem($item, $promo_rule);
            }
        }

        $shared_promo['items'] = $product_per_price;

        return MyHelper::checkGet([
            'discount'  => $discount,
            'promo_type' => $promo->promo_type
        ]);
    }

    public function bxgyDiscount($promoSource, $promoQuery, $data)
    {
        return $this->failResponse('Promo belum tersedia');
    }

    public function billDiscount($promoSource, $promoQuery, $data)
    {
        // load required relationship
        $promo          = $promoQuery;
        $pct            = new PromoCampaignTools();
        $promo_rules    = $promo->{$promoSource . '_discount_bill_rules'};
        $promo_product  = $promo->{$promoSource . '_discount_bill_products'}->toArray();
        $promo_brand    = $promo->{$promoSource . '_brands'}->pluck('id_brand')->toArray();
        $product_name   = $pct->getProductName($promo_product, $promo->product_rule);
        $promo_item     = $data['items'] ?? [];
        $discount       = 0;

        $allProductStatus = $promo_rules->is_all_product ?? 1;
        if ($allProductStatus) {
            $promo_product = "*";
        }

        if ($promoQuery['promo_use_in'] == 'Product') {
            $get_promo_product = $pct->getPromoProduct($promo_item, $promo_brand, $promo_product, $promoQuery['product_type']);
            $product = $get_promo_product['product'];

            if (!$product) {
                $message = $pct->getMessage('error_product_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b>.';
                $message = MyHelper::simpleReplace($message, ['product' => $product_name]);
                return $this->failResponse($message);
            }
        }

        $total_price = $data['subtotal'];
        $discountType = $promo_rules->discount_type ?? 'Nominal';
        if ($discountType == 'Percent') {
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
            $message = $pct->getMessage('error_product_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b>.';
            $message = MyHelper::simpleReplace($message, ['product' => 'produk tertentu']);
            return $this->failResponse($message);
            ;
        }

        return MyHelper::checkGet([
            'discount'  => $discount,
            'promo_type' => $promo->promo_type
        ]);
    }

    public function deliveryDiscount($promoSource, $promoQuery, $data)
    {
        $promo          = $promoQuery;
        $pct            = new PromoCampaignTools();
        $promo_rules    = $promo->{$promoSource . '_discount_delivery_rules'};
        $promo_brand    = $promo->{$promoSource . '_brands'}->pluck('id_brand')->toArray();
        $shared_promo   = TemporaryDataManager::create('promo_trx');
        $delivery_fee   = $data['delivery_price']['shipment_price'] ?? $data['transaction_shipment'] ?? 0;
        $discount       = 0;

        $discount_type  = $promo_rules->discount_type;
        $discount_value = $promo_rules->discount_value;
        $discount_max   = $promo_rules->max_percent_discount;

        $promo_item     = $data['items'];
        $total_product = 0;
        foreach ($promo_item as $key => $trx) {
            $notGetDiscountStatus = false;
            if (!empty($promoQuery['product_type']) && $promoQuery['product_type'] != 'single + variant') {
                if ($promoQuery['product_type'] == 'single' && !empty($trx['id_product_variant_group'])) {
                    $notGetDiscountStatus = true;
                } elseif ($promoQuery['product_type'] == 'variant' && empty($trx['id_product_variant_group'])) {
                    $notGetDiscountStatus = true;
                }
            }

            $product[$key]['not_get_discount'] = $notGetDiscountStatus;
            if (!$notGetDiscountStatus) {
                $total_product += $trx['qty'];
            }
        }

        if ($total_product <= 0) {
            $msg = '';
            if ($promoQuery['product_type'] == 'single') {
                $msg = 'product tanpa variant';
            } elseif ($promoQuery['product_type'] == 'variant') {
                $msg = 'product dengan variant';
            }

            $message = $pct->getMessage('error_product_discount')['value_text'] = 'Promo hanya berlaku jika membeli ' . $msg . '.';
            return $this->failResponse($message);
        }

        if ($promo_rules) {
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
        }

        return MyHelper::checkGet([
            'discount_delivery' => $discount,
            'promo_type' => $promo->promo_type
        ]);
    }

    public function discountPerItem(&$item, $promo_rules)
    {
        $discount       = 0;
        $prev_discount  = $item['total_discount'] ?? 0;
        $discount_qty   = $item['promo_qty'];
        $product_price  = ($item['new_price'] ?? 0) ?: $item['product_price'];

        $item['total_discount'] = $prev_discount;
        $item['discount']       = 0;
        $item['new_price']      = $product_price;
        $item['base_discount']  = 0;
        $item['is_promo']       = 0;
        $item['qty_discount']   = 0;

        if (empty($discount_qty) || $item['not_get_discount']) {
            return 0;
        }

        if (strtolower($promo_rules->discount_type) == 'nominal') {
            $discount_per_item = $promo_rules->discount_value;
            $discount = $promo_rules->discount_value * $discount_qty;
            $product_price_total = $product_price * $discount_qty;
            if ($discount > $product_price_total) {
                $discount = $product_price_total;
            }

            $item['total_discount'] = $prev_discount + $discount;
            $item['new_price']      = $product_price - $discount_per_item;
            $item['base_discount']  = ($product_price < $promo_rules->discount_value) ? $product_price : $promo_rules->discount_value;
        } else {
            // percent
            $discount_per_item = ($promo_rules->discount_value / 100) * $product_price;
            if (!empty($promo_rules->max_percent_discount) && $discount_per_item > $promo_rules->max_percent_discount) {
                $discount_per_item = $promo_rules->max_percent_discount;
            }
            $discount = (int) ($discount_per_item * $discount_qty);

            $item['total_discount'] = $prev_discount + $discount;
            $item['new_price']      = $product_price - $discount_per_item;
            $item['base_discount']  = $discount_per_item;
        }

        // if new price is negative
        if ($item['new_price'] < 0) {
            $discount               = $product_price * $discount_qty;

            $item['total_discount'] = $prev_discount + $discount;
            $item['new_price']      = 0;
            $item['base_discount']  = $product_price;
        }

        $item['is_promo']       = 1;
        $item['qty_discount']   = $discount_qty;
        $item['discount']       = $discount;
        unset($item['promo_qty']);

        return $discount;
    }

    public function createSharedPromoTrx($dataTrx)
    {
        // get data to calculate promo
        $sharedPromoTrx = TemporaryDataManager::create('promo_trx');


        $items = [];
        // product
        $items = array_merge($items, ($dataTrx['item'] ?? []));
        // product service
        $items = array_merge($items, ($dataTrx['item_service'] ?? []));
        // product academy
        $items = array_merge($items, ($dataTrx['item_academy'] ?? []));
        // transaction products
        $items = array_merge($items, ($dataTrx['transaction_products'] ?? []));

        $promoItems = [];
        $outlet = Outlet::find($dataTrx['outlet']['id_outlet'] ?? $dataTrx['id_outlet']);
        foreach ($items as $val) {
            $productType = 'Product';
            if (
                isset($val['id_user_hair_stylist'])
                || request()->transaction_from == 'home-service'
            ) {
                $productType = 'Service';
            }

            $price = $val['product_price'] ?? $val['transaction_product_price'];
            if ($outlet->is_tax) {
                if ($outlet->outlet_different_price) {
                    $productPrice = ProductSpecialPrice::where(['id_product' => $val['id_product'], 'id_outlet' => $outlet['id_outlet']])->first();
                    if ($productPrice) {
                        $price = $productPrice['product_special_price'];
                    }
                } else {
                    $productPrice = ProductGlobalPrice::where(['id_product' => $val['id_product']])->first();
                    if ($productPrice) {
                        $price = $productPrice['product_global_price'];
                    }
                }
            }

            $promoItems[] = [
                'id_transaction_product' => $val['id_transaction_product'] ?? null,
                'id_product' => $val['id_product'] ?? null,
                'id_brand' => $val['id_brand'],
                'product_price' => $price,
                'product_type' => $val['type'] ?? $productType,
                'qty' => $val['qty'] ?? $val['transaction_product_qty'] ?? 1
            ];
        }

        $sharedPromoTrx['items'] = $sharedPromoTrx['items'] ?? $promoItems;
        $sharedPromoTrx['subtotal'] = $dataTrx['subtotal'] ?? $dataTrx['transaction_subtotal'];
        $sharedPromoTrx['subtotal_promo'] = $sharedPromoTrx['subtotal_promo'] ?? $dataTrx['subtotal'] ?? $dataTrx['transaction_subtotal'];
        $sharedPromoTrx['shipping'] = $dataTrx['shipping'] ?? $dataTrx['transaction_shipment'] ?? 0;
        $sharedPromoTrx['shipping_promo'] = $sharedPromoTrx['shipping_promo'] ?? $dataTrx['transaction_shipment'] ?? 0;
        $sharedPromoTrx['tax'] = $dataTrx['tax'] ?? $dataTrx['transaction_tax'];
        $sharedPromoTrx['service'] = $dataTrx['service'] ?? $dataTrx['transaction_service'] ?? 0;
        $sharedPromoTrx['cashback'] = $dataTrx['cashback'] ?? $dataTrx['transaction_cashback_earned'] ?? 0;
        $sharedPromoTrx['grandtotal'] = $dataTrx['grandtotal'] ?? $dataTrx['transaction_grandtotal'];

        return true;
    }

    public function getSharedPromoTrx()
    {
        $sharedPromoTrx = TemporaryDataManager::create('promo_trx');
        return $sharedPromoTrx;
    }

    public function applyPromoNewTrx($trx)
    {
        $user = request()->user();
        $promoCashback = 'promo_code';
        $cashback = $trx['transaction_cashback_earned'];
        $totalDiscount = (int) abs($trx['transaction_discount']);
        $idPromoCampaign = PromoCampaignPromoCode::where('id_promo_campaign_promo_code', $trx['id_promo_campaign_promo_code'])->first()['id_promo_campaign'] ?? null;
        $dataDiscount = PromoCampaign::where('id_promo_campaign', $idPromoCampaign)->first();
        $chargedCentral = ($dataDiscount['charged_central'] / 100) * $totalDiscount;
        $chargedCentral = round($chargedCentral);
        $chargedOutlet = ($dataDiscount['charged_outlet'] / 100) * $totalDiscount;
        $chargedOutlet = round($chargedOutlet);

        $promoGetPoint = app($this->online_trx)->checkPromoGetPoint($promoCashback);
        $cashback_earned = $promoGetPoint ? $cashback : 0;
        Transaction::where('id_transaction', $trx['id_transaction'])->update([
            'transaction_cashback_earned' => $cashback_earned,
            'discount_charged_central' => $chargedCentral,
            'discount_charged_outlet' => $chargedOutlet
        ]);

        $totalDiscountBill = $trx['transaction_discount_bill'] ?? 0;
        $totalDiscountDelivery = $trx['transaction_discount_delivery'] ?? 0;
        if ($totalDiscountBill > 0 || $totalDiscountDelivery > 0) {
            $totalSubProduct = TransactionProduct::where('id_transaction', $trx['id_transaction'])->sum('transaction_product_subtotal');
            $products = TransactionProduct::where('id_transaction', $trx['id_transaction'])->get()->toArray();
            foreach ($products as $product) {
                if ($totalDiscountBill > 0) {
                    $disc = $product['transaction_product_subtotal'] / $totalSubProduct * $totalDiscountBill;
                } elseif ($totalDiscountDelivery > 0) {
                    $disc = $product['transaction_product_subtotal'] / $totalSubProduct * $totalDiscountDelivery;
                }

                TransactionProduct::where('id_transaction_product', $product['id_transaction_product'])->update([
                    'transaction_product_discount' => $disc,
                    'transaction_product_discount_all' => $disc
                ]);
            }
        }

        if (!empty($dataDiscount)) {
            TransactionPromo::create([
                'id_transaction' => $trx['id_transaction'],
                'promo_name' => $dataDiscount['promo_title'],
                'promo_type' => 'Promo Campaign',
                'id_deals_user' => null,
                'id_promo_campaign_promo_code' => $trx['id_promo_campaign_promo_code'] ?? null,
                'discount_value' => $trx['transaction_discount']
            ]);

            $dataDiscount['id_promo_campaign_promo_code'] = $trx['id_promo_campaign_promo_code'];
            $dataDiscount['user_name'] = $user->name;
            $dataDiscount['user_phone'] = $user->phone;
            $dataDiscount['id_user'] = $user->id;
            if (!empty($trx['id_promo_campaign_promo_code'])) {
                $insertPromo = $this->insertUsedCode($trx, $dataDiscount);
                UserPromo::where('id_user', $user->id)->where('promo_type', 'promo_campaign')->delete();
            }

            if ($insertPromo['status'] == 'fail') {
                return $insertPromo;
            }
        }

        return MyHelper::checkGet($trx);
    }

    public function insertUsedVoucher(Transaction $trx, $dataDiscount)
    {
        $dealsUser = DealsUser::find($dataDiscount['id_deals_user']);
        $dealsUser->update(['used_at' => date('Y-m-d H:i:s'), 'is_used' => 0]);

        $deals = Deal::find($dataDiscount['id_deals']);
        $deals->update(['deals_total_used' => $deals->deals_total_used + 1]);

        $createTrxVoucher = TransactionVoucher::create([
            'id_deals_voucher' => $dealsUser->id_deals_voucher,
            'id_user' => $trx->id_user,
            'id_transaction' => $trx->id_transaction
        ]);

        if (!$createTrxVoucher) {
            return $this->failResponse('Insert Voucher Failed');
        }

        return ['status' => 'success'];
    }

    public function insertUsedCode($trx, $dataDiscount, $fromTriger = 0)
    {
        $deviceId = request()->device_id ?: '';
        $deviceType = request()->device_type ?: null;
        if ($fromTriger == 1) {
            $deviceId = '';
            $deviceType = null;
        }
        $promo_campaign_report = app($this->promo_campaign)->addReport(
            $dataDiscount['id_promo_campaign'],
            $dataDiscount['id_promo_campaign_promo_code'],
            $trx['id_transaction'],
            $trx['id_outlet'],
            $deviceId,
            $deviceType,
            $trx['id_transaction_group']
        );

        if (!$promo_campaign_report) {
            return $this->failResponse('Insert Promo Failed');
        }

        return ['status' => 'success'];
    }

    public function paymentDetailPromo($result)
    {
        $paymentDetail = [];
        if (
            (!empty($result['promo_deals']) && !$result['promo_deals']['is_error'])
            || (!empty($result['promo_code']) && !$result['promo_code']['is_error'])
        ) {
            $paymentDetail[] = [
                'name'          => 'Promo / Discount:',
                "is_discount"   => 0,
                'amount'        => null
            ];

            if (!empty($result['promo_deals']) && !$result['promo_deals']['is_error']) {
                $paymentDetail[] = [
                    'name'          => $result['promo_deals']['title'],
                    "is_discount"   => 1,
                    'amount'        => '-' . number_format(((int) $result['promo_deals']['discount'] ?: $result['promo_deals']['discount_delivery']), 0, ',', '.')
                ];
            }

            if (!empty($result['promo_code']) && !$result['promo_code']['is_error']) {
                $paymentDetail[] = [
                    'name'          => $result['promo_code']['title'],
                    "is_discount"   => 1,
                    'amount'        => '-' . number_format(((int) $result['promo_code']['discount'] ?: $result['promo_code']['discount_delivery']), 0, ',', '.')
                ];
            }
        }

        return $paymentDetail;
    }

    public function ruleTrx($rule)
    {
        $ruleArr = [
            'Product discount' => ['Outlet Service','Home Service','Online Shop','Academy'],
            'Tier discount' => ['Outlet Service','Home Service','Online Shop'],
            // 'Buy X Get Y' => ['Outlet Service','Home Service','Online Shop','Academy'],
            'Discount bill' => ['Outlet Service','Home Service','Online Shop','Academy'],
            'Discount delivery' => ['Online Shop']
        ];

        return $ruleArr[$rule] ?? [];
    }
}
