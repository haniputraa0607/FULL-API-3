<?php

namespace Modules\PromoCampaign\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\PromoCampaign\Entities\PromoCampaign;
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

use Modules\Deals\Entities\DealsProductDiscount;
use Modules\Deals\Entities\DealsProductDiscountRule;
use Modules\Deals\Entities\DealsTierDiscountProduct;
use Modules\Deals\Entities\DealsTierDiscountRule;
use Modules\Deals\Entities\DealsBuyxgetyProductRequirement;
use Modules\Deals\Entities\DealsBuyxgetyRule;

use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\SubscriptionUser;
use Modules\Subscription\Entities\SubscriptionUserVoucher;

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

use Modules\PromoCampaign\Http\Requests\Step1PromoCampaignRequest;
use Modules\PromoCampaign\Http\Requests\Step2PromoCampaignRequest;
use Modules\PromoCampaign\Http\Requests\DeletePromoCampaignRequest;
use Modules\PromoCampaign\Http\Requests\ValidateCode;
use Modules\PromoCampaign\Http\Requests\UpdateCashBackRule;
use Modules\PromoCampaign\Http\Requests\CheckUsed;

use Modules\PromoCampaign\Lib\PromoCampaignTools;
use App\Lib\MyHelper;
use App\Jobs\GeneratePromoCode;
use DB;
use Hash;
use Modules\SettingFraud\Entities\DailyCheckPromoCode;
use Modules\SettingFraud\Entities\LogCheckPromoCode;

use Modules\Brand\Entities\BrandProduct;
use Modules\Brand\Entities\BrandOutlet;
use Modules\Outlet\Entities\DeliveryOutlet;

use App\Lib\WeHelpYou;

class ApiPromo extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->online_transaction   = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->voucher   = "Modules\Deals\Http\Controllers\ApiDealsVoucher";
        $this->fraud   = "Modules\SettingFraud\Http\Controllers\ApiFraud";
        $this->promo_campaign       = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
        $this->subscription_use   = "Modules\Subscription\Http\Controllers\ApiSubscriptionUse";
    }

    public function checkUsedPromo(CheckUsed $request)
    {
        $user = auth()->user();
        $datenow = date("Y-m-d H:i:s");
        $remove = 0;
        $available_promo = $this->availablePromo();
        DB::beginTransaction();
        $user_promo = UserPromo::where('id_user', '=', $user->id)->first();
        if (!$user_promo) {
            return response()->json([
                'status' => 'fail',
                'result' => [
                    'total_promo'   => $available_promo
                ]
            ]);
        }
        if ($user_promo->promo_type == 'deals') {
            $promo = app($this->promo_campaign)->checkVoucher(null, null, 1, 1);

            if ($promo) {
                if ($promo->used_at) {
                    $remove = 1;
                } elseif ($promo->voucher_expired_at < $datenow) {
                    $remove = 1;
                } elseif ($promo->voucher_active_at > $datenow) {
                    $remove = 1;
                }
            }
        } elseif ($user_promo->promo_type == 'promo_campaign') {
            $promo = app($this->promo_campaign)->checkPromoCode(null, 1, 1, $user_promo->id_reference, 1);
            if ($promo) {
                if ($promo->date_end < $datenow || $promo->date_start > $datenow) {
                    $remove = 1;
                } else {
                    $pct = new PromoCampaignTools();
                    $validate_user = $pct->validateUser($promo->id_promo_campaign, $user->id, $user->phone, null, $request->device_id, $error, $promo->id_promo_campaign_promo_code);
                    if (!$validate_user) {
                        $remove = 1;
                    }
                }
            }
        } elseif ($user_promo->promo_type == 'subscription') {
            $promo = app($this->subscription_use)->checkSubscription(null, null, 1, 1, null, $user_promo->id_reference, 1, 1);

            if ($promo) {
                if ($promo->subscription_expired_at < $datenow || $promo->subscription_active_at > $datenow) {
                    $remove = 1;
                } elseif ($promo->subscription_user->subscription->daily_usage_limit) {
                    $subs_voucher_today = SubscriptionUserVoucher::where('id_subscription_user', '=', $promo->id_subscription_user)
                                            ->whereDate('used_at', date('Y-m-d'))
                                            ->count();
                    if ($subs_voucher_today >= $promo->subscription_user->subscription->daily_usage_limit) {
                        $remove = 1;
                    }
                }
            }
        } else {
            return response()->json([
                'status' => 'fail',
                'result' => [
                    'total_promo'   => $available_promo
                ]
            ]);
        }

        if (!$promo) {
            return response()->json([
                'status' => 'fail',
                'result' => [
                    'total_promo'   => $available_promo
                ]
            ]);
        }

        $promo = $promo->toArray();

        $getProduct = app($this->promo_campaign)->getProduct($user_promo->promo_type, $promo['deal_voucher']['deals'] ?? $promo['promo_campaign'] ?? $promo['subscription_user']['subscription']);
        $desc = app($this->promo_campaign)->getPromoDescription($user_promo->promo_type, $promo['deal_voucher']['deals'] ?? $promo['promo_campaign'] ?? $promo['subscription_user']['subscription'], $getProduct['product'] ?? '');

        $result = [
            'title'             => $promo['deal_voucher']['deals']['deals_title'] ?? $promo['promo_campaign']['promo_title'] ?? $promo['subscription_user']['subscription']['subscription_title'],
            'description'       => $desc,
            'id_deals_user'     => $promo['id_deals_user'] ?? '',
            'promo_code'        => $promo['promo_code'] ?? '',
            'id_subscription_user'      => $promo['id_subscription_user'] ?? '',
            'remove'            => $remove,
            'total_promo'       => $available_promo
        ];

        return response()->json(MyHelper::checkGet($result));
    }

    public function usePromo($source, $id_promo, $status = 'use', $query = null)
    {
        $user = auth()->user();
        DB::beginTransaction();
        // change is used flag to 0
        $update = DealsUser::where('id_user', '=', $user->id)->where('is_used', '=', 1)->update(['is_used' => 0]);
        $update = SubscriptionUser::where('id_user', '=', $user->id)->where('is_used', '=', 1)->update(['is_used' => 0]);

        if ($status == 'use') {
            if ($source == 'deals') {
                // change specific deals user is used to 1
                $update = DealsUser::where('id_deals_user', '=', $id_promo)->update(['is_used' => 1]);
            } elseif ($source == 'subscription') {
                $update = SubscriptionUser::where('id_subscription_user', '=', $query['id_subscription_user'])->update(['is_used' => 1]);
            }

            if ($source == 'promo_campaign') {
                $promoUseIn = PromoCampaignPromoCode::join('promo_campaigns', 'promo_campaigns.id_promo_campaign', 'promo_campaign_promo_codes.id_promo_campaign')
                    ->where('id_promo_campaign_promo_code', $id_promo)->first()['promo_use_in'] ?? null;
            }
            $update = UserPromo::updateOrCreate(['id_user' => $user->id, 'promo_use_in' => $promoUseIn], ['promo_type' => $source, 'promo_use_in' => $promoUseIn, 'id_reference' => $id_promo]);
        } else {
            $update = UserPromo::where('id_user', '=', $user->id)->delete();
        }

        if ($update) {
            DB::commit();
        } else {
            DB::rollback();
        }

        if (is_numeric($update)) {
            $update = 1;
        }
        $update = MyHelper::checkUpdate($update);

        $update['webview_url'] = "";
        $update['webview_url_v2'] = "";
        if ($source == 'deals') {
            $update['webview_url'] = config('url.api_url') . "api/webview/voucher/" . $id_promo;
            $update['webview_url_v2'] = config('url.api_url') . "api/webview/voucher/v2/" . $id_promo;
        } elseif ($source == 'subscription') {
            if ($id_promo) {
                $update['webview_url'] = config('url.api_url') . "api/webview/mysubscription/" . $id_promo;
            }
        }

        return $update;
    }

    public function cancelPromo(Request $request)
    {
        $post = $request->json()->all();

        if (!empty($post['id_deals_user'])) {
            $source = 'deals';
        } elseif (!empty($post['id_subscription_user'])) {
            $source = 'subscription';
        } else {
            $source = 'promo_campaign';
        }
        $cancel = $this->usePromo($source, $post['id_deals_user'] ?? $post['id_subscription_user'] ?? '', 'cancel');

        if ($cancel) {
            return response()->json($cancel);
        } else {
            return response()->json([
                'status' => 'fail',
                'messages' => 'Failed to update promo'
            ]);
        }
    }

    public function promoGetCashbackRule()
    {
        $getDataConfig = Configs::whereIn('config_name', ['promo code get point','voucher offline get point','voucher online get point','subscription get point'])->get()->toArray();
        $getDataSetting = Setting::whereIn('key', ['cashback_include_bundling'])->get()->toArray();

        $getData = array_merge($getDataConfig, $getDataSetting);
        foreach ($getData as $key => $value) {
            $config[$value['config_name'] ?? $value['key']] = $value['is_active'] ?? $value['value'];
        }

        return $config;
    }

    public function getDataCashback(Request $request)
    {
        $data = $this->promoGetCashbackRule();

        return response()->json(myHelper::checkGet($data));
    }

    public function updateDataCashback(UpdateCashBackRule $request)
    {
        $post = $request->json()->all();
        Configs::updateOrCreate(['config_name' => 'promo code get point'], ['is_active' => ($post['promo_code_cashback'] ?? 0)]);
        Configs::updateOrCreate(['config_name' => 'voucher online get point'], ['is_active' => ($post['voucher_online_cashback'] ?? 0)]);
        Configs::updateOrCreate(['config_name' => 'voucher offline get point'], ['is_active' => ($post['voucher_offline_cashback'] ?? 0)]);
        Configs::updateOrCreate(['config_name' => 'subscription get point'], ['is_active' => ($post['subscription_cashback'] ?? 0)]);
        Setting::updateOrCreate(['key' => 'cashback_include_bundling'], ['value' => ($post['bundling_cashback'] ?? 0)]);

        return response()->json(['status' => 'success']);
    }

    public function availablePromo()
    {
        $available_deals = DealsUser::where('id_user', auth()->user()->id)
                        ->whereIn('paid_status', ['Free', 'Completed'])
                        ->whereNull('used_at')
                        ->where('voucher_expired_at', '>', date('Y-m-d H:i:s'))
                        ->count();

        $available_subs = SubscriptionUser::where('id_user', auth()->user()->id)
                        ->where('subscription_expired_at', '>=', date('Y-m-d H:i:s'))
                        ->whereIn('paid_status', ['Completed','Free'])
                        ->whereHas('subscription_user_vouchers', function ($q) {
                            $q->whereNull('used_at');
                        })
                        ->count();

        return ($available_deals + $available_subs);
    }

    public function checkMinBasketSize($promo_source, $query, $subtotal_per_brand)
    {
        $check = false;
        $min_basket_size = 0;
        switch ($promo_source) {
            case 'promo_code':
                $min_basket_size = $query->min_basket_size;
                $promo_brand = $query->promo_campaign->promo_campaign_brands->pluck('id_brand')->toArray();
                break;

            case 'voucher_online':
                $min_basket_size = $query->dealVoucher->deals->min_basket_size;
                $promo_brand = $query->dealVoucher->deals->deals_brands->pluck('id_brand')->toArray();
                break;

            default:
                # code...
                break;
        }

        if (empty($min_basket_size)) {
            $check = true;
        } else {
            $promo_brand_flipped = array_flip($promo_brand);
            $subtotal = 0;
            foreach ($subtotal_per_brand as $key => $value) {
                if (!isset($promo_brand_flipped[$key])) {
                    continue;
                }
                $subtotal += $value;
                if ($subtotal >= $min_basket_size) {
                    $check = true;
                    break;
                }
            }
        }

        return $check;
    }

    public function checkPromo($request, $user, $promo_source, $data_promo, $id_outlet, $item, $delivery_fee, $subtotal_per_brand, &$error_product)
    {
        $pct = new PromoCampaignTools();
        if ($promo_source == 'promo_code') {
            $validate_user = $pct->validateUser(
                $data_promo->id_promo_campaign,
                $user->id,
                $user->phone,
                $request->device_type,
                $request->device_id,
                $errore,
                $data_promo->id_promo_campaign_promo_code
            );

            $source = 'promo_campaign';
            $id_promo = $data_promo->id_promo_campaign;

            if (!empty($errore)) {
                return [
                    'status'    => 'fail',
                    'messages'  => ['Promo code not valid']
                ];
            }
        } elseif ($promo_source == "voucher_online") {
            $source = 'deals';
            $id_promo = $data_promo->dealVoucher->id_deals;
        }

        $discount_promo = $pct->validatePromo(
            $request,
            $id_promo,
            $id_outlet,
            $item,
            $errors,
            $source,
            $error_product,
            $delivery_fee,
            $subtotal_per_brand
        );

        if (!empty($errors)) {
            return [
                'status'    => 'fail',
                'messages'  => $errors ?? ['Promo is not valid']
            ];
        }

        return [
            'status' => 'success',
            'data'   => $discount_promo
        ];
    }

    public function getTransactionCheckPromoRule($result, $promo_source, $query, $trx_type)
    {
        $check = false;
        $disable_pickup = false;
        $available_payment  = $this->getAvailablePayment()['result'];
        $result['available_payment'] = [];
        $available_delivery = $result['available_delivery'];

        if ($trx_type == 'GO-SEND') {
            $available_delivery = [
                [
                    'code' => 'gosend',
                    'disable' => 0
                ]
            ];
        }

        switch ($promo_source) {
            case 'promo_code':
                $promo = $query;
                if ($promo->promo_type == 'Discount delivery') {
                    $disable_pickup = 1;
                }
                $promo_shipment = $query->promo_campaign->promo_campaign_shipment_method->pluck('shipment_method');
                $promo_payment  = $query->promo_campaign->promo_campaign_payment_method->pluck('payment_method');
                break;

            case 'voucher_online':
                $promo = $query->dealVoucher->deals;
                if ($promo->promo_type == 'Discount delivery') {
                    $disable_pickup = 1;
                }
                $promo_shipment = $query->dealVoucher->deals->deals_shipment_method->pluck('shipment_method');
                $promo_payment  = $query->dealVoucher->deals->deals_payment_method->pluck('payment_method');
                break;

            case 'subscription':
                $promo = Subscription::join('subscription_users', 'subscriptions.id_subscription', '=', 'subscription_users.id_subscription')->where('id_subscription_user', $query->id_subscription_user)->first();
                if ($promo->subscription_discount_type == 'discount_delivery') {
                    $disable_pickup = 1;
                }
                $promo_shipment = $promo->subscription_shipment_method->pluck('shipment_method');
                $promo_payment  = $promo->subscription_payment_method->pluck('payment_method');
                break;

            default:
                return $result;
                break;
        }

        $pct = new PromoCampaignTools();
        if ($promo_shipment) {
            if (!$pct->checkShipmentRule($promo->is_all_shipment, 'Pickup Order', $promo_shipment) || $disable_pickup) {
                $result['pickup_type'] = 0;
            }

            if ($trx_type == 'Pickup Order') {
                $listDelivery = app($this->online_transaction)->listAvailableDelivery(WeHelpYou::listDeliveryRequest())['result']['delivery'] ?? [];
                $delivery_outlet = DeliveryOutlet::where('id_outlet', $result['outlet']['id_outlet'])->get();
                $outletSetting = [];
                foreach ($delivery_outlet as $val) {
                    $outletSetting[$val['code']] = $val;
                }

                $result['delivery_type'] = 0;
                foreach ($listDelivery as $val) {
                    if (
                        $val['show_status'] != 1
                        || $val['available_status'] != 1
                        || (isset($outletSetting[$val['code']]) && ($outletSetting[$val['code']]['available_status'] != 1 || $outletSetting[$val['code']]['show_status'] != 1))
                    ) {
                        continue;
                    }
                    if ($pct->checkShipmentRule($promo->is_all_shipment, $val['code'], $promo_shipment)) {
                        $result['delivery_type'] = 1;
                        break;
                    }
                }
            } else {
                $result['delivery_type'] = 0;
                foreach ($available_delivery as $key => $val) {
                    if ($val['disable']) {
                        continue;
                    }
                    if ($pct->checkShipmentRule($promo->is_all_shipment, $val['code'], $promo_shipment)) {
                        $result['delivery_type'] = 1;
                    } else {
                        if ($trx_type != 'GO-SEND') {
                            $result['available_delivery'][$key]['disable'] = 1;
                        }
                    }
                }
            }
        }

        if ($promo_payment) {
            foreach ($available_payment as $key => $value) {
                if ($pct->checkPaymentRule($promo->is_all_payment, $value['payment_method'], $promo_payment)) {
                    $result['available_payment'][] = $value['code'];
                }
            }
        }

        return $result;
    }

    public function getAvailablePayment()
    {
        $custom_data    = [];
        $custom_request = new \Illuminate\Http\Request();
        $custom_request = $custom_request
                        ->setJson(new \Symfony\Component\HttpFoundation\ParameterBag($custom_data))
                        ->merge($custom_data);

        $payment_list   = app($this->online_transaction)->availablePayment($custom_request);

        return $payment_list;
    }

    public function checkBrandProduct($outlets = [], $products = [])
    {
        $result = [
            'status' => true,
            'messages' => []
        ];

        if (isset($outlets[0]['id_outlet'])) {
            if (!is_array($outlets)) {
                $outlets = $outlets->toArray();
            }
            $outlets = array_column($outlets, 'id_outlet');
        }

        $outlet = BrandOutlet::select('id_brand', 'id_outlet')->whereIn('id_outlet', $outlets)->get()->toArray();
        $brand_outlet = array_column($outlet, 'id_brand');

        if (isset($products[0]['id_brand'])) {
        }
        $brand_product  = [];
        foreach ($products as $value) {
            if (isset($value['id_brand'])) {
                $get_product_brand = $value['id_brand'];
            } else {
                $get_product_brand = app($this->promo_campaign)->splitBrandProduct($value, 'brand');
            }

            if (empty($get_product_brand)) {
                continue;
            }

            $brand_product[] = $get_product_brand;
        }

        // if product doesn't have brand then return true
        if (empty($brand_product)) {
            return $result;
        }

        $brand_product  = array_flip($brand_product);

        $outlet_invalid = [];
        $outlet_valid   = [];
        foreach ($brand_outlet as $key => $value) {
            if (!isset($brand_product[$value])) {
                $outlet_invalid[] = $outlet[$key]['id_outlet'];
            } else {
                $outlet_valid[] = $outlet[$key]['id_outlet'];
            }
        }

        $invalid    = array_flip(array_flip(array_diff($outlet_invalid, $outlet_valid)));

        $messages = [];
        if (!empty($invalid)) {
            $outlet_name = Outlet::whereIn('id_outlet', $invalid)->pluck('outlet_name')->toArray();
            $result['status']   = false;
            $result['messages'] = array_merge(["Outlet tidak mempunyai produk dengan brand yang sesuai."], $outlet_name);
        }

        return $result;
    }

    public function extendPeriod(Request $request)
    {
        $post = $request->json()->all();
        $error_msg = [];
        $end_period = null;
        $start_period = null;
        $publish_end_period = null;
        $publish_start_period = null;
        $expiry_date = null;

        if (!empty($post['expiry_duration']) && !empty($post['expiry_date'])) {
            if ($post['expiry'] == 'dates') {
                $post['expiry_duration'] = null;
            } else {
                $post['expiry_date'] = null;
            }
        }

        if (isset($post['start_period']) && !empty($post['start_period'])) {
            $start_period   = date('Y-m-d H:i:s', strtotime($post['start_period']));
        }

        if (isset($post['end_period']) && !empty($post['end_period'])) {
            $end_period = date('Y-m-d H:i:s', strtotime($post['end_period']));
            if ($end_period < ($start_period ?? date('Y-m-d H:i:s'))) {
                $error_msg[] = 'End period must be a date after ' . ($start_period ?? date('Y-m-d H:i:s')) . '.';
            }
        }

        if (isset($post['publish_start_period']) && !empty($post['publish_start_period'])) {
            $publish_start_period = date('Y-m-d H:i:s', strtotime($post['publish_start_period']));
        }

        if (isset($post['publish_end_period']) && !empty($post['publish_end_period'])) {
            $publish_end_period = date('Y-m-d H:i:s', strtotime($post['publish_end_period']));
            if (isset($post['publish_end_period']) && $publish_end_period < ($publish_start_period ?? date('Y-m-d H:i:s'))) {
                $error_msg[] = 'Publish end period must be a date after ' . ($publish_start_period ?? date('Y-m-d H:i:s')) . '.';
            }
        }

        if (isset($post['expiry_date']) && !empty($post['expiry_date'])) {
            $expiry_date = date('Y-m-d H:i:s', strtotime($post['expiry_date']));
            if (isset($post['expiry_date']) && $expiry_date < date('Y-m-d H:i:s')) {
                $error_msg[] = 'Expiry date must be a date after ' . date('Y-m-d H:i:s') . '.';
            }
        }

        if (!empty($error_msg)) {
            return [
                'status' => 'fail',
                'messages' => $error_msg
            ];
        }

        if (isset($post['id_deals'])) {
            $table      = new Deal();
            $id_table   = 'id_deals';
            $id_post    = $post['id_deals'];

            $data['deals_start'] = $start_period;
            $data['deals_end'] = $end_period;
            $data['deals_publish_start'] = $publish_start_period;
            $data['deals_publish_end'] = $publish_end_period;
            if (isset($post['expiry_date'])) {
                $data['deals_voucher_expired'] = $expiry_date;
            }
            if (isset($post['expiry_duration'])) {
                $data['deals_voucher_duration'] = $post['expiry_duration'];
            }
        }
        if (isset($post['id_promo_campaign'])) {
            $table      = new PromoCampaign();
            $id_table   = 'id_promo_campaign';
            $id_post    = $post['id_promo_campaign'];

            $data['date_start'] = $start_period;
            $data['date_end'] = $end_period;
        }
        if (isset($post['id_subscription'])) {
            $table      = new Subscription();
            $id_table   = 'id_subscription';
            $id_post    = $post['id_subscription'];

            $data['subscription_start'] = $start_period;
            $data['subscription_end'] = $end_period;
            $data['subscription_publish_start'] = $publish_start_period;
            $data['subscription_publish_end'] = $publish_end_period;
            $data['subscription_voucher_expired'] = $expiry_date;
            $data['subscription_voucher_duration'] = $post['expiry_duration'];
        }

        $extend = $table::where($id_table, $id_post)->update($data);

        $extend = MyHelper::checkUpdate($extend);

        return $extend;
    }

    public function updatePromoDescription(Request $request)
    {
        $post = $request->json()->all();
        $error_msg = [];

        if (!empty($error_msg)) {
            return [
                'status' => 'fail',
                'messages' => $error_msg
            ];
        }

        if (isset($post['id_deals'])) {
            $table      = new Deal();
            $id_table   = 'id_deals';
            $id_post    = $post['id_deals'];
        }
        if (isset($post['id_promo_campaign'])) {
            $table      = new PromoCampaign();
            $id_table   = 'id_promo_campaign';
            $id_post    = $post['id_promo_campaign'];
        }
        if (isset($post['id_subscription'])) {
            $table      = new Subscription();
            $id_table   = 'id_subscription';
            $id_post    = $post['id_subscription'];
        }
        if (isset($post['id_deals_promotion_template'])) {
            $table      = new DealsPromotionTemplate();
            $id_table   = 'id_deals_promotion_template';
            $id_post    = $post['id_deals_promotion_template'];
        }

        $data['promo_description'] = $post['promo_description'];
        $update = $table::where($id_table, $id_post)->update($data);

        $update = MyHelper::checkUpdate($update);

        return $update;
    }
}
