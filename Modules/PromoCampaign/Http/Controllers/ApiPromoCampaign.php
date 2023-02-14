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
use Modules\PromoCampaign\Entities\PromoCampaignDiscountBillRule;
use Modules\PromoCampaign\Entities\PromoCampaignDiscountBillProduct;
use Modules\PromoCampaign\Entities\PromoCampaignDiscountDeliveryRule;
use Modules\PromoCampaign\Entities\PromoCampaignShipmentMethod;
use Modules\PromoCampaign\Entities\PromoCampaignPaymentMethod;
use Modules\PromoCampaign\Entities\PromoCampaignBrand;
use Modules\PromoCampaign\Entities\PromoCampaignBuyxgetyProductModifier;
use Modules\PromoCampaign\Entities\PromoCampaignOutletGroup;
use Modules\Deals\Entities\DealsProductDiscount;
use Modules\Deals\Entities\DealsProductDiscountRule;
use Modules\Deals\Entities\DealsTierDiscountProduct;
use Modules\Deals\Entities\DealsTierDiscountRule;
use Modules\Deals\Entities\DealsBuyxgetyProductRequirement;
use Modules\Deals\Entities\DealsBuyxgetyRule;
use Modules\Deals\Entities\DealsBuyxgetyProductModifier;
use Modules\Deals\Entities\DealsDiscountBillRule;
use Modules\Deals\Entities\DealsDiscountBillProduct;
use Modules\Deals\Entities\DealsDiscountDeliveryRule;
use Modules\Deals\Entities\DealsShipmentMethod;
use Modules\Deals\Entities\DealsPaymentMethod;
use Modules\Promotion\Entities\DealsPromotionProductDiscount;
use Modules\Promotion\Entities\DealsPromotionProductDiscountRule;
use Modules\Promotion\Entities\DealsPromotionTierDiscountProduct;
use Modules\Promotion\Entities\DealsPromotionTierDiscountRule;
use Modules\Promotion\Entities\DealsPromotionBuyxgetyProductRequirement;
use Modules\Promotion\Entities\DealsPromotionBuyxgetyRule;
use Modules\Promotion\Entities\DealsPromotionBuyxgetyProductModifier;
use Modules\Promotion\Entities\DealsPromotionDiscountBillRule;
use Modules\Promotion\Entities\DealsPromotionDiscountBillProduct;
use Modules\Promotion\Entities\DealsPromotionDiscountDeliveryRule;
use Modules\Promotion\Entities\DealsPromotionShipmentMethod;
use Modules\Promotion\Entities\DealsPromotionPaymentMethod;
use Modules\Subscription\Entities\SubscriptionUser;
use Modules\Subscription\Entities\SubscriptionUserVoucher;
use Modules\Brand\Entities\BrandProduct;
use Modules\Brand\Entities\BrandOutlet;
use App\Http\Models\User;
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
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantPivot;
use Modules\Product\Entities\ProductModifierGroupPivot;
use Modules\Outlet\Entities\OutletGroup;
use Modules\PromoCampaign\Http\Requests\Step1PromoCampaignRequest;
use Modules\PromoCampaign\Http\Requests\Step2PromoCampaignRequest;
use Modules\PromoCampaign\Http\Requests\DeletePromoCampaignRequest;
use Modules\PromoCampaign\Http\Requests\ValidateCode;
use Modules\PromoCampaign\Lib\PromoCampaignTools;
use App\Lib\MyHelper;
use App\Jobs\GeneratePromoCode;
use App\Jobs\ExportPromoCodeJob;
use DB;
use Hash;
use Modules\SettingFraud\Entities\DailyCheckPromoCode;
use Modules\SettingFraud\Entities\LogCheckPromoCode;
use Illuminate\Support\Facades\Auth;
use File;
use App\Lib\WeHelpYou;

class ApiPromoCampaign extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->online_transaction   = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->fraud   = "Modules\SettingFraud\Http\Controllers\ApiFraud";
        $this->deals   = "Modules\Deals\Http\Controllers\ApiDeals";
        $this->voucher   = "Modules\Deals\Http\Controllers\ApiDealsVoucher";
        $this->subscription   = "Modules\Subscription\Http\Controllers\ApiSubscriptionUse";
        $this->promo        = "Modules\PromoCampaign\Http\Controllers\ApiPromo";
        $this->autocrm      = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }

    public function index(Request $request)
    {
        $post = $request->json()->all();
        $promo_type = $request->get('promo_type');

        try {
            $query = PromoCampaign::with([
                        'user'
                    ])
                    ->where(function ($query) {
                        $query
                              ->where('promo_type', '!=', 'Referral')
                              ->orWhereNull('promo_type');
                    })
                    ->OrderBy('promo_campaigns.id_promo_campaign', 'DESC');
            $count = (new PromoCampaign())->newQuery();

            if (isset($promo_type)) {
                $query = $query->where('promo_type', '=', $promo_type);
            }

            if ($request->json('rule')) {
                $filter = $this->filterList($query, $request);
                $this->filterList($count, $request);
            }

            if (!empty($query)) {
                $query = $query->paginate(10)->toArray();
                $result = [
                    'status'     => 'success',
                    'result'     => $query,
                    'count'      => count($query)
                ];
            } else {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Promo Campaign is empty']
                ]);
            }

            if ($filter ?? false) {
                $result = array_merge($result, $filter);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'messages' => [$e->getMessage()]]);
        }
    }

    protected function filterList($query, $request)
    {
        $allowed = array(
            'operator' => ['=', 'like', '<', '>', '<=', '>='],
            'subject' => [
                'campaign_name',
                'promo_title',
                'code_type',
                'prefix_code',
                'number_last_code',
                'total_code',
                'date_start',
                'date_end',
                'is_all_outlet',
                'promo_type',
                'used_code',
                'id_outlet',
                'id_product',
                'id_user',
                'used_by_user',
                'used_at_outlet',
                'promo_code'
            ],
            'mainSubject' => [
                'campaign_name',
                'promo_title',
                'code_type',
                'prefix_code',
                'number_last_code',
                'total_code',
                'date_start',
                'date_end',
                'is_all_outlet',
                'promo_type',
                'used_code'
            ]
        );
        $request->validate([
            'operator' => 'required|in:or,and',
            'rule.*.subject' => 'required|in:' . implode(',', $allowed['subject']),
            'rule.*.operator' => 'in:' . implode(',', $allowed['operator']),
            'rule.*.parameter' => 'required'
        ]);
        $return = [];
        $where = $request->json('operator') == 'or' ? 'orWhere' : 'where';
        if ($request->json('date_start')) {
            $query->where('date_start', '>=', $request->json('date_start'));
        }
        if ($request->json('date_end')) {
            $query->where('date_end', '<=', $request->json('date_end'));
        }
        $rule = $request->json('rule');
        foreach ($rule as $value) {
            if (in_array($value['subject'], $allowed['mainSubject'])) {
                if (!in_array($value['subject'], $allowed['subject'])) {
                    continue;
                }
                if (!(isset($value['operator']) && $value['operator'] && in_array($value['operator'], $allowed['operator']))) {
                    $value['operator'] = '=';
                }
                if ($value['operator'] == 'like') {
                    $query->$where($value['subject'], $value['operator'], '%' . $value['parameter'] . '%');
                } else {
                    $query->$where($value['subject'], $value['operator'], $value['parameter']);
                }
            } else {
                switch ($value['subject']) {
                    case 'id_outlet':
                        if ($value['parameter'] == '0') {
                            $query->$where('is_all_outlet', '1');
                        } else {
                            $query->leftJoin('promo_campaign_outlets', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_outlets.id_promo_campaign');
                            $query->$where(function ($query) use ($value) {
                                $query->where('promo_campaign_outlets.id_outlet', $value['parameter']);
                                $query->orWhere('is_all_outlet', '1');
                            });
                        }
                        break;

                    case 'id_user':
                        $query->leftJoin('promo_campaign_user_filters', 'promo_campaign_user_filters.id_promo_campaign', '=', 'promo_campaigns.id_promo_campaign');
                        switch ($value['parameter']) {
                            case 'all user':
                                $query->$where('promo_campaign_user_filters.subject', 'all_user');
                                break;

                            case 'new user':
                                $query->$where(function ($query) {
                                    $query->where('promo_campaign_user_filters.subject', 'count_transaction');
                                    $query->where('promo_campaign_user_filters.parameter', '0');
                                });
                                break;

                            case 'existing user':
                                $query->$where(function ($query) {
                                    $query->where('promo_campaign_user_filters.subject', 'count_transaction');
                                    $query->where('promo_campaign_user_filters.parameter', '1');
                                });
                                break;

                            default:
                                # code...
                                break;
                        }
                        break;

                    case 'id_product':
                        $query->leftJoin('promo_campaign_buyxgety_product_requirements', 'promo_campaign_buyxgety_product_requirements.id_promo_campaign', '=', 'promo_campaigns.id_promo_campaign');
                        $query->leftJoin('promo_campaign_product_discounts', 'promo_campaign_product_discounts.id_promo_campaign', '=', 'promo_campaigns.id_promo_campaign');
                        $query->leftJoin('promo_campaign_tier_discount_products', 'promo_campaign_tier_discount_products.id_promo_campaign', '=', 'promo_campaigns.id_promo_campaign');
                        if ($value['parameter'] == '0') {
                            $query->$where(function ($query) {
                                $query->where('promo_type', 'Product discount');
                                $query->where('promo_campaign_product_discounts.id_product', null);
                            });
                        } else {
                            $query->$where(DB::raw('IF(promo_type=\'Product discount\',promo_campaign_product_discounts.id_product,IF(promo_type=\'Tier discount\',promo_campaign_tier_discount_products.id_product,promo_campaign_buyxgety_product_requirements.id_product))'), $value['parameter']);
                        }
                        break;

                    case 'used_by_user':
                        $wherein = $where . 'In';
                        $query->$wherein('id_promo_campaign', function ($query) use ($value, $where) {
                            $query->select('id_promo_campaign')->from(with(new Reports())->getTable())->where('user_phone', $value['operator'], $value['operator'] == 'like' ? '%' . $value['parameter'] . '%' : $value['parameter'])->groupBy('id_promo_campaign');
                        });
                        break;

                    case 'used_at_outlet':
                        $wherein = $where . 'In';
                        $query->$wherein('id_promo_campaign', function ($query) use ($value, $where) {
                            $query->select('id_promo_campaign')->from(with(new Reports())->getTable())->where('id_outlet', $value['parameter'])->groupBy('id_promo_campaign');
                        });
                        break;

                    case 'promo_code':
                        $wherein = $where . 'In';
                        $query->$wherein('id_promo_campaign', function ($query) use ($value, $where) {
                            $query->select('id_promo_campaign')->from(with(new PromoCampaignPromoCode())->getTable())->where('promo_code', $value['operator'], $value['operator'] == 'like' ? '%' . $value['parameter'] . '%' : $value['parameter'])->groupBy('id_promo_campaign');
                        });
                        break;

                    default:
                        # code...
                        break;
                }
            }
            $return[] = $value;
        }
        return [
            'rule' => $return,
            'operator' => $request->json('operator')
        ];
    }

    public function detail(Request $request)
    {
        $post = $request->json()->all();
        $data = [
            'user' => function ($q) {
                $q->select('id', 'name', 'level');
            },
            'promo_campaign_have_tags.promo_campaign_tag',
            'outlets',
            'outlet_groups',
            'promo_campaign_product_discount_rules',
            'promo_campaign_discount_bill_rules',
            'promo_campaign_discount_bill_products.product',
            'promo_campaign_discount_bill_products.brand',
            'promo_campaign_discount_bill_products.product_variant_pivot.product_variant',
            'promo_campaign_discount_delivery_rules',
            'promo_campaign_product_discount.product.category',
            'promo_campaign_product_discount.brand',
            'promo_campaign_product_discount.product_variant_pivot.product_variant',
            'promo_campaign_tier_discount_rules',
            'promo_campaign_tier_discount_product.product',
            'promo_campaign_tier_discount_product.brand',
            'promo_campaign_tier_discount_product.product_variant_pivot.product_variant',
            'promo_campaign_buyxgety_rules.product',
            'promo_campaign_buyxgety_rules.brand',
            'promo_campaign_buyxgety_rules.product_variant_pivot.product_variant',
            'promo_campaign_buyxgety_rules.promo_campaign_buyxgety_product_modifiers.modifier',
            'promo_campaign_buyxgety_product_requirement.product',
            'promo_campaign_buyxgety_product_requirement.brand',
            'promo_campaign_buyxgety_product_requirement.product_variant_pivot.product_variant',
            'promo_campaign_shipment_method',
            'promo_campaign_payment_method',
            'brands',
            'brand',
            'promo_campaign_reports'
        ];
        $promoCampaign = PromoCampaign::with($data)->where('id_promo_campaign', '=', $post['id_promo_campaign'])->first();
        if ($promoCampaign['code_type'] == 'Single') {
            $promoCampaign->load('promo_campaign_promo_codes');
        }
        $promoCampaign = $promoCampaign->toArray();
        if ($promoCampaign) {
            if ($promoCampaign['code_type'] != 'Single') {
                $promoCampaign['used_code'] = PromoCampaignReport::where('promo_campaign_reports.id_promo_campaign', $post['id_promo_campaign'])
                    ->distinct()->count('id_promo_campaign_promo_code', 'id_transaction_group');
            } else {
                $promoCampaign['used_code'] = PromoCampaignReport::where('promo_campaign_reports.id_promo_campaign', $post['id_promo_campaign'])
                    ->distinct()->count('id_transaction_group');
            }

            $total = PromoCampaignReport::where('promo_campaign_reports.id_promo_campaign', $post['id_promo_campaign']);
            $this->filterReport($total, $request, $foreign);
            foreach ($foreign as $value) {
                $total->leftJoin(...$value);
            }
            $promoCampaign['total'] = $total->count();

            $total2 = PromoCampaignPromoCode::join('promo_campaigns', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_promo_codes.id_promo_campaign')->where('promo_campaign_promo_codes.id_promo_campaign', $post['id_promo_campaign']);
            $this->filterCoupon($total2, $request, $foreign);
            foreach ($foreign as $value) {
                $total->leftJoin(...$value);
            }
            $promoCampaign['total2'] = $total2->count();
            $getProduct = $this->getProduct('promo_campaign', $promoCampaign);
            $desc = $this->getPromoDescription('promo_campaign', $promoCampaign, $getProduct['product'] ?? '', true);

            $promoCampaign['description'] = $desc;

            $result = [
                'status'  => 'success',
                'result'  => $promoCampaign
            ];
        } else {
            $result = [
                'status'  => 'fail',
                'message'  => ['Promo Campaign Not Found']
            ];
        }
        return response()->json($result);
    }
    public function detail2(Request $request)
    {
        $post = $request->json()->all();

        $promoCampaign = PromoCampaign::with(
            'user',
            'promo_campaign_have_tags.promo_campaign_tag',
            'promo_campaign_product_discount_rules',
            'promo_campaign_product_discount.product.category',
            'promo_campaign_tier_discount_rules',
            'promo_campaign_tier_discount_product.product',
            'promo_campaign_buyxgety_rules.product',
            'promo_campaign_buyxgety_product_requirement.product',
            'promo_campaign_reports',
            'outlets'
        )
                        ->where('id_promo_campaign', '=', $post['id_promo_campaign'])
                        ->first();

        if (($promoCampaign['code_type'] ?? '') == 'Single') {
            $promoCampaignPromoCode = PromoCampaignPromoCode::where('id_promo_campaign', '=', $post['id_promo_campaign'])->first();
        }

        if ($promoCampaign) {
            $promoCampaign = $promoCampaign->toArray();
            $promoCampaign['count'] = count($promoCampaign['promo_campaign_reports']);
            if ($promoCampaignPromoCode ?? false) {
                $promoCampaign['promo_campaign_promo_codes'] = $promoCampaignPromoCode;
            }
            $result = [
                'status'  => 'success',
                'result'  => $promoCampaign
            ];
        } else {
            $result = [
                'status'  => 'fail',
                'message'  => ['Promo Campaign Not Found']
            ];
        }
        return response()->json($result);
    }

    public function report(Request $request)
    {
        $post = $request->json()->all();
        $query = PromoCampaignReport::select('promo_campaign_reports.*')->with(['promo_campaign_promo_code','transaction','outlet'])->where('promo_campaign_reports.id_promo_campaign', $post['id_promo_campaign']);
        $filter = null;
        $count = (new PromoCampaignReport())->newQuery()->where('promo_campaign_reports.id_promo_campaign', $post['id_promo_campaign']);
        $total = (new PromoCampaignReport())->newQuery()->where('promo_campaign_reports.id_promo_campaign', $post['id_promo_campaign']);
        $foreign = [];
        $foreign2 = [];
        if ($post['rule'] ?? false) {
            $this->filterReport($query, $request, $foreign);
            $this->filterReport($count, $request, $foreign2);
        }
        $column = ['promo_code','user_name','created_at','receipt_number','outlet','device_type'];
        if ($post['start']) {
            $query->skip($post['start']);
        }
        if ($post['length'] > 0) {
            $query->take($post['length']);
        }
        foreach ($post['order'] as $value) {
            switch ($column[$value['column']]) {
                case 'promo_code':
                    $foreign['promo_campaign_promo_codes'] = array('promo_campaign_promo_codes','promo_campaign_promo_codes.id_promo_campaign_promo_code','=','promo_campaign_reports.id_promo_campaign_promo_code');
                    $query->orderBy('promo_code', $value['dir']);
                    break;

                case 'receipt_number':
                    $foreign['transactions'] = array('transactions','transactions.id_transaction','=','promo_campaign_reports.id_transaction');
                    $query->orderBy('transaction_receipt_number', $value['dir']);
                    break;

                case 'outlet':
                    $foreign['outlets'] = array('outlets','outlets.id_outlet','=','promo_campaign_reports.id_outlet');
                    $query->orderBy('outlet_name', $value['dir']);
                    break;

                default:
                    $query->orderBy('promo_campaign_reports.' . $column[$value['column']], $value['dir']);
                    break;
            }
        }
        foreach ($foreign as $value) {
            $query->leftJoin(...$value);
        }
        foreach ($foreign2 as $value) {
            $count->leftJoin(...$value);
        }

        $query = $query->get()->toArray();
        $count = $count->count();
        $total = $total->count();

        if (isset($query) && !empty($query)) {
            $result = [
                'status'  => 'success',
                'result'  => $query,
                'total'  => $total,
                'count'  => $count
            ];
        } else {
            $result = [
                'status'  => 'fail',
                'message'  => ['No Report']
            ];
        }
        return response()->json($result);
    }

    protected function filterReport($query, $request, &$foreign = '')
    {
        // $query->groupBy('promo_campaign_reports.id_promo_campaign_report');
        $allowed = array(
            'operator' => ['=', 'like', '<', '>', '<=', '>='],
            'subject' => ['promo_code','user_phone','created_at','receipt_number','id_outlet','device_type','outlet_count','user_count'],
            'mainSubject' => ['user_phone','created_at','id_outlet','device_type']
        );
        $return = [];
        $where = $request->json('operator') == 'or' ? 'orWhere' : 'where';
        $rule = $request->json('rule');
        $query->where(function ($queryx) use ($rule, $allowed, $where, $query, &$foreign, $request) {
            $foreign = array();
            $outletCount = 0;
            $userCount = 0;
            foreach ($rule ?? [] as $value) {
                if (!in_array($value['subject'], $allowed['subject'])) {
                    continue;
                }
                if (!(isset($value['operator']) && $value['operator'] && in_array($value['operator'], $allowed['operator']))) {
                    $value['operator'] = '=';
                }
                if ($value['operator'] == 'like') {
                    $value['parameter'] = '%' . $value['parameter'] . '%';
                }
                if (in_array($value['subject'], $allowed['mainSubject'])) {
                    if ($value['subject'] == 'created_at') {
                        $queryx->$where(\DB::raw('UNIX_TIMESTAMP(promo_campaign_reports.' . $value['subject'] . ')'), $value['operator'], strtotime($value['parameter']));
                    } else {
                        $queryx->$where('promo_campaign_reports.' . $value['subject'], $value['operator'], $value['parameter']);
                    }
                } else {
                    switch ($value['subject']) {
                        case 'promo_code':
                            $foreign['promo_campaign_promo_codes'] = ['promo_campaign_promo_codes','promo_campaign_promo_codes.id_promo_campaign_promo_code','=','promo_campaign_reports.id_promo_campaign_promo_code'];
                            $queryx->$where('promo_code', $value['operator'], $value['parameter']);
                            break;

                        case 'receipt_number':
                            $foreign['transactions'] = ['transactions','transactions.id_transaction','=','promo_campaign_reports.id_transaction'];
                            $queryx->$where('transaction_receipt_number', $value['operator'], $value['parameter']);
                            break;

                        case 'outlet_count':
                            if (!$outletCount) {
                                $query->addSelect('outlet_total');
                                $outletCount = 1;
                            }
                            $foreign['t2'] = [\DB::raw('(SELECT COUNT(*) AS outlet_total, id_outlet FROM `promo_campaign_reports` WHERE id_promo_campaign = ' . $request->json('id_promo_campaign') . ' GROUP BY id_outlet) AS `t2`'),'promo_campaign_reports.id_outlet','=','t2.id_outlet'];
                            $queryx->$where('outlet_total', $value['operator'], $value['parameter']);
                            break;


                        case 'user_count':
                            if (!$userCount) {
                                $query->addSelect('user_total');
                                $userCount = 1;
                            }
                            $foreign['t3'] = [\DB::raw('(SELECT COUNT(*) AS user_total, id_user FROM `promo_campaign_reports` WHERE id_promo_campaign = ' . $request->json('id_promo_campaign') . ' GROUP BY id_user) AS `t3`'),'promo_campaign_reports.id_user','=','t3.id_user'];
                            $queryx->$where('user_total', $value['operator'], $value['parameter']);
                            break;

                        default:
                            # code...
                            break;
                    }
                }
                $return[] = $value;
            }
        });
        return ['filter' => $return, 'filter_operator' => $request->json('operator')];
    }

    public function coupon(Request $request)
    {
        $post = $request->json()->all();

        $query = PromoCampaignPromoCode::select('promo_campaign_promo_codes.*', 'promo_campaigns.code_limit')
                ->join('promo_campaigns', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_promo_codes.id_promo_campaign')
                ->where('promo_campaign_promo_codes.id_promo_campaign', $post['id_promo_campaign']);

        $filter = null;
        $count = (new PromoCampaignPromoCode())->newQuery()
                    ->join('promo_campaigns', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_promo_codes.id_promo_campaign')
                    ->where('promo_campaign_promo_codes.id_promo_campaign', $post['id_promo_campaign']);
        $total = (new PromoCampaignPromoCode())->newQuery()
                    ->join('promo_campaigns', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_promo_codes.id_promo_campaign')
                    ->where('promo_campaign_promo_codes.id_promo_campaign', $post['id_promo_campaign']);
        $foreign = [];
        $foreign2 = [];
        if ($post['rule2'] ?? false) {
            $this->filterCoupon($query, $request, $foreign);
            $this->filterCoupon($count, $request, $foreign2);
        }
        $column = ['promo_code','status','usage','available','code_limit'];
        if ($post['start']) {
            $query->skip($post['start']);
        }
        if ($post['length'] > 0) {
            $query->take($post['length']);
        }
        foreach ($post['order'] as $value) {
            switch ($column[$value['column']]) {
                case 'status':
                case 'available':
                    $query->orderBy('usage', $value['dir']);
                    break;

                case 'code_limit':
                    $query->orderBy('code_limit', $value['dir']);
                    break;

                default:
                    $query->orderBy('promo_campaign_promo_codes.' . $column[$value['column']], $value['dir']);
                    break;
            }
        }
        foreach ($foreign as $value) {
            $query->leftJoin(...$value);
        }
        foreach ($foreign2 as $value) {
            $count->leftJoin(...$value);
        }

        $query = $query->get()->toArray();
        $count = $count->count();
        $total = $total->count();

        if (isset($query) && !empty($query)) {
            $result = [
                'status'  => 'success',
                'result'  => $query,
                'total'  => $total,
                'count'  => $count
            ];
        } else {
            $result = [
                'status'  => 'fail',
                'message'  => ['No Report']
            ];
        }
        return response()->json($result);
    }

    protected function filterCoupon($query, $request, &$foreign = '')
    {
        // $query->groupBy('promo_campaign_promo_codes.id_promo_campaign_promo_code');
        $allowed = array(
            'operator' => ['=', 'like', '<', '>', '<=', '>='],
            'subject' => ['coupon_code','status','used','available','max_used'],
        );
        $return = [];
        $where = $request->json('operator2') == 'or' ? 'orWhere' : 'where';
        $whereRaw = $request->json('operator2') == 'or' ? 'orWhereRaw' : 'whereRaw';
        $rule = $request->json('rule2');
        $query->where(function ($queryx) use ($rule, $allowed, $where, $query, &$foreign, $request, $whereRaw) {
            $foreign = array();
            $outletCount = 0;
            $userCount = 0;
            foreach ($rule ?? [] as $value) {
                if (!in_array($value['subject'], $allowed['subject'])) {
                    continue;
                }
                if (!(isset($value['operator']) && $value['operator'] && in_array($value['operator'], $allowed['operator']))) {
                    $value['operator'] = '=';
                }
                if ($value['operator'] == 'like') {
                    $value['parameter'] = '%' . $value['parameter'] . '%';
                }
                switch ($value['subject']) {
                    case 'coupon_code':
                        $queryx->$where('promo_code', $value['operator'], $value['parameter']);
                        break;

                    case 'status':
                        if ($value['parameter'] == 'Not used') {
                            $queryx->$where('usage', '=', 0);
                        } elseif ($value['parameter'] == 'Used') {
                            $queryx->$where('usage', '=', 'code_limit');
                        } else {
                            $queryx->$where('usage', '!=', 0)->$where('usage', '!=', 'code_limit');
                        }

                        break;

                    case 'used':
                        $queryx->$where('usage', $value['operator'], $value['parameter']);
                        break;

                    case 'available':
                        $queryx->$whereRaw('code_limit - promo_campaign_promo_codes.usage ' . $value['operator'] . ' ' . $value['parameter']);
                        break;

                    case 'max_used':
                        $queryx->$where('code_limit', $value['operator'], $value['parameter']);
                        break;

                    default:
                        # code...
                        break;
                }

                $return[] = $value;
            }
        });
        return ['filter' => $return, 'filter_operator' => $request->json('operator')];
    }

    public function getTag(Request $request)
    {
        $post = $request->json()->all();

        $data = PromoCampaignTag::get()->toArray();

        return response()->json($data);
    }

    public function check(Request $request)
    {
        $post = $request->json()->all();

        if ($post['type_code'] == 'single') {
            $query = PromoCampaignPromoCode::where('promo_code', '=', $post['search_code']);
        } else {
            $query = PromoCampaign::where('prefix_code', '=', $post['search_code']);
        }

        if (is_numeric($request->promo_id)) {
            $query = $query->where('id_promo_campaign', '!=', $request->promo_id);
        }
        $checkCode = $query->first();

        if ($checkCode) {
            $result = [
                'status'  => 'not available'
            ];
        } else {
            $result = [
                'status'  => 'available'
            ];
        }
        return response()->json($result);
    }

    public function step1(Step1PromoCampaignRequest $request)
    {
        $post = $request->json()->all();

        if (isset($post['used_code_update'])) {
            return $this->usedCodeUpdate($request, 'step1');
        }

        $user = $request->user();
        $post['prefix_code'] = strtoupper($post['prefix_code']);
        $post['date_start'] = $this->generateDate($post['date_start']);
        $post['date_end']   = $this->generateDate($post['date_end']);

        if ($post['code_type'] == 'Multiple') {
            $max_char_digit = 28;
            $max_coupon_posibility = pow($max_char_digit, $post['number_last_code']);
            if ($max_coupon_posibility < $post['total_coupon']) {
                $result = [
                    'status'  => 'fail',
                    'messages'  => ['Total Coupon must be equal or less than total Generate random code']
                ];
                return $result;
            }
            $allow_char = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            for ($i = 0; isset($post['prefix_code'][$i]); $i++) {
                $strpos = strpos($allow_char, $post['prefix_code'][$i]);
                if ($strpos === false) {
                    $result =  [
                        'status'  => 'fail',
                        'messages'  => ['Prefix code must be alphanumeric']
                    ];
                    return response()->json($result);
                }
            }

            $post['limitation_usage'] = 0;
        } else {
            $post['user_limit'] = 0;
            $post['code_limit'] = 0;
        }

        DB::beginTransaction();

        if (isset($post['id_promo_campaign'])) {
            $post['last_updated_by'] = $user['id'];
            $datenow = date("Y-m-d H:i:s");
            $checkData = PromoCampaign::with([
                            'promo_campaign_have_tags.promo_campaign_tag',
                            'promo_campaign_promo_codes' => function ($q) {
                                $q->limit(1);
                            },
                            'promo_campaign_reports' => function ($q) {
                                $q->limit(1);
                            },
                            'brand',
                            'promo_campaign_brands'
                        ])
                        ->where('id_promo_campaign', '=', $post['id_promo_campaign'])
                        ->get()
                        ->toArray();

            if (empty($checkData)) {
                return response()->json([
                    'status'  => 'fail',
                    'messages'  => ['Promo not found']
                ]);
            }

            /* prevent update promo when promo is used
            if (!empty($checkData[0]['promo_campaign_reports'])) {
                return response()->json([
                    'status'  => 'fail',
                    'messages'  => ['Cannot update promo, promo already used']
                ]);
            }*/

            $del_rule   = false;
            $brand_now  = array_column($checkData[0]['promo_campaign_brands'], 'id_brand');
            if (!empty($post['id_brand'])) {
                $brand_new  = $post['id_brand'];
                unset($post['id_brand']);

                $check_brand = array_merge(array_diff($brand_now, $brand_new), array_diff($brand_new, $brand_now));

                if (!empty($check_brand)) {
                    $del_rule = true;
                }
            } else {
                PromoCampaignBrand::where('id_promo_campaign', $post['id_promo_campaign'])->delete();
            }

            if ($del_rule) {
                $delete_rule = $this->deleteAllProductRule('promo_campaign', $post['id_promo_campaign']);
                $delete_outlet_rule = $this->deleteOutletRule('promo_campaign', $post['id_promo_campaign']);
                $post['step_complete'] = 0;
                if (!$delete_rule || !$delete_outlet_rule) {
                    return response()->json([
                        'status'  => 'fail',
                        'messages'  => ['Update Failed']
                    ]);
                }

                if (!empty($brand_new)) {
                    $insert_brand = $this->insertPromoCampaignBrand($post['id_promo_campaign'], $brand_new);
                    if (!$insert_brand) {
                        return response()->json([
                            'status'  => 'fail',
                            'messages'  => ['Insert Promo Campaign Brand Failed']
                        ]);
                    }
                }
            }

            if ($checkData[0]['code_type'] == 'Single') {
                $checkData[0]['promo_code'] = $checkData[0]['promo_campaign_promo_codes'][0]['promo_code'];
            }
            if (
                    $checkData[0]['code_type'] != $post['code_type']
                    || $checkData[0]['prefix_code'] != $post['prefix_code']
                    || $checkData[0]['number_last_code'] != $post['number_last_code']
                    || ($checkData[0]['promo_code'] ?? null) != $post['promo_code']
                    || $checkData[0]['total_coupon'] != $post['total_coupon']
            ) {
                $promo_code = $post['promo_code'];

                unset($post['promo_code']);
                // if ($post['code_type'] == 'Single') {
                //     unset($post['promo_code']);
                // }

                if (isset($post['promo_tag'])) {
                    $insertTag = $this->insertTag('update', $post['id_promo_campaign'], $post['promo_tag']);
                    unset($post['promo_tag']);
                }

                if (!empty($post['promo_image'])) {
                    $upload = $this->insertPromoImage($post['promo_image'], 300, 300);
                    if (empty($upload['promo_image'])) {
                        return [
                            'status'  => 'fail',
                            'messages'  => ['Failed to upload image']
                        ];
                    }
                    $post['promo_image'] = $upload['promo_image'];
                }

                if (!empty($post['promo_image_detail'])) {
                    $uploadDetail = $this->insertPromoImage($post['promo_image_detail'], 750, 375);
                    if (empty($uploadDetail['promo_image'])) {
                        return [
                            'status'  => 'fail',
                            'messages'  => ['Failed to upload image']
                        ];
                    }
                    $post['promo_image_detail'] = $uploadDetail['promo_image'];
                }

                $promoCampaign = PromoCampaign::where('id_promo_campaign', '=', $post['id_promo_campaign'])->update($post);

                if (!$promoCampaign) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => 'fail',
                        'messages'  => ['Update Failed']
                    ]);
                }
                $generateCode = $this->generateCode('update', $post['id_promo_campaign'], $post['code_type'], $promo_code, $post['prefix_code'], $post['number_last_code'], $post['total_coupon']);


                if ($generateCode['status'] == 'success') {
                    $result = [
                        'status'  => 'success',
                        'result'  => 'Update Promo Campaign Success',
                        'promo-campaign'  => $post
                    ];
                } else {
                    DB::rollBack();
                    $result = [
                        'status'  => 'fail',
                        'message'  => ['Create Another Unique Promo Code']
                    ];
                }
            } else {
                $promo_code = $post['promo_code'] ?? null;
                if (isset($post['promo_code']) || $post['promo_code'] == null) {
                    unset($post['promo_code']);
                }

                if (isset($post['promo_tag'])) {
                    $insertTag = $this->insertTag('update', $post['id_promo_campaign'], $post['promo_tag']);
                    unset($post['promo_tag']);
                }

                $promoCampaign = PromoCampaign::where('id_promo_campaign', '=', $post['id_promo_campaign'])->first();

                if (!empty($post['promo_image'])) {
                    $upload = $this->insertPromoImage($post['promo_image'], 300, 300);
                    if (empty($upload['promo_image'])) {
                        return [
                            'status'  => 'fail',
                            'messages'  => ['Failed to upload image']
                        ];
                    }
                    $post['promo_image'] = $upload['promo_image'];
                }

                if (!empty($post['promo_image_detail'])) {
                    $uploadDetail = $this->insertPromoImage($post['promo_image_detail'], 750, 375);
                    if (empty($uploadDetail['promo_image'])) {
                        return [
                            'status'  => 'fail',
                            'messages'  => ['Failed to upload image']
                        ];
                    }
                    $post['promo_image_detail'] = $uploadDetail['promo_image'];
                }

                $promoCampaignUpdate = $promoCampaign->update($post);
                $generateCode = $this->generateCode('update', $post['id_promo_campaign'], $post['code_type'], $promo_code, $post['prefix_code'], $post['number_last_code'], $post['total_coupon']);


                if ($promoCampaignUpdate == 1) {
                    $promoCampaign = $promoCampaign->toArray();

                    $result = [
                        'status'  => 'success',
                        'result'  => 'Promo Campaign has been updated',
                        'promo-campaign'  => $post
                    ];
                    $send = app($this->autocrm)->SendAutoCRM('Update Promo Campaign', $user['phone'], [
                        'campaign_name' => $promoCampaign['campaign_name'] ?: '',
                        'promo_title' => $promoCampaign['promo_title'] ?: '',
                        'code_type' => $promoCampaign['code_type'] ?: '',
                        'prefix_code' => $promoCampaign['prefix_code'] ?: '',
                        'number_last_code' => $promoCampaign['number_last_code'] ?: '',
                        'total_coupon' => number_format($promoCampaign['total_coupon'], 0, ',', '.') ?: '',
                        'created_at' => date('d F Y H:i', strtotime($promoCampaign['created_at'])) ?: '',
                        'updated_at' => date('d F Y H:i', strtotime($promoCampaign['updated_at'])) ?: '',
                        'detail' => view('promocampaign::emails.detail', ['detail' => $promoCampaign])->render()
                    ] + $promoCampaign, null, true);
                } else {
                    DB::rollBack();
                    $result = ['status'  => 'fail'];
                }
            }
        } else {
            $post['created_by'] = $user['id'];
            if ($post['code_type'] == 'Single') {
                $post['prefix_code'] = null;
                $post['number_last_code'] = null;
            } else {
                $post['promo_code'] = null;
            }

            if ($post['date_start'] <= date("Y-m-d H:i:s")) {
                $start_date = new \DateTime($post['date_start']);
                $diff_date = $start_date->diff(new \DateTime($post['date_end']));

                $date_end = new \DateTime(date("Y-m-d H:i:s"));
                $date_end->add(new \DateInterval($diff_date->format('P%yY%mM%dDT%hH%iM%sS')));

                $post['date_start'] = date("Y-m-d H:i:s");
                $post['date_end']   = $date_end->format('Y-m-d H:i:s');
            }

            if (!empty($post['id_brand'])) {
                $brands = $post['id_brand'];
                unset($post['id_brand']);
            }

            if (!empty($post['promo_image'])) {
                $upload = $this->insertPromoImage($post['promo_image'], 300, 300);
                if (empty($upload['promo_image'])) {
                    return [
                        'status'  => 'fail',
                        'messages'  => ['Failed to upload image']
                    ];
                }
                $post['promo_image'] = $upload['promo_image'];
            }

            if (!empty($post['promo_image_detail'])) {
                $uploadDetail = $this->insertPromoImage($post['promo_image_detail'], 750, 375);
                if (empty($uploadDetail['promo_image'])) {
                    return [
                        'status'  => 'fail',
                        'messages'  => ['Failed to upload image']
                    ];
                }
                $post['promo_image_detail'] = $uploadDetail['promo_image'];
            }

            $promoCampaign = PromoCampaign::create($post);
            $generateCode = $this->generateCode('insert', $promoCampaign['id_promo_campaign'], $post['code_type'], $post['promo_code'], $post['prefix_code'], $post['number_last_code'], $post['total_coupon']);
            if (isset($post['promo_tag'])) {
                $insertTag = $this->insertTag(null, $promoCampaign['id_promo_campaign'], $post['promo_tag']);
            }

            if (!empty($brands)) {
                $insert_brand = $this->insertPromoCampaignBrand($promoCampaign['id_promo_campaign'], $brands);
                if (!$insert_brand) {
                    return response()->json([
                        'status'  => 'fail',
                        'messages'  => ['Insert Promo Campaign Brand Failed']
                    ]);
                }
            }

            $post['id_promo_campaign'] = $promoCampaign['id_promo_campaign'];

            if ($generateCode['status'] == 'success') {
                $result = [
                    'status'  => 'success',
                    'result'  => 'Creates Promo Campaign Success',
                    'promo-campaign'  => $post
                ];
                $promoCampaign = $promoCampaign->toArray();
                $send = app($this->autocrm)->SendAutoCRM('Create Promo Campaign', $user['phone'], [
                    'campaign_name' => $promoCampaign['campaign_name'] ?: '',
                    'promo_title' => $promoCampaign['promo_title'] ?: '',
                    'code_type' => $promoCampaign['code_type'] ?: '',
                    'prefix_code' => $promoCampaign['prefix_code'] ?: '',
                    'number_last_code' => $promoCampaign['number_last_code'] ?: '',
                    'total_coupon' => number_format($promoCampaign['total_coupon'], 0, ',', '.') ?: '',
                    'created_at' => date('d F Y H:i', strtotime($promoCampaign['created_at'])) ?: '',
                    'updated_at' => date('d F Y H:i', strtotime($promoCampaign['updated_at'])) ?: '',
                    'detail' => view('promocampaign::emails.detail', ['detail' => $promoCampaign])->render()
                ] + $promoCampaign, null, true);
            } else {
                DB::rollBack();
                $result = [
                    'status'  => 'fail',
                    'message'  => ['Create Another Unique Promo Code']
                ];
            }
        }

        DB::commit();
        return response()->json($result);
    }

    public function step2(Step2PromoCampaignRequest $request)
    {
        $post = $request->json()->all();
        $post['promo_type'] = $post['promo_type'] ?? null;
        $user = $request->user();
        if (isset($post['used_code_update'])) {
            return $this->usedCodeUpdate($request, 'step2');
        }

        if (!empty($post['id_deals'])) {
            if ($post['deals_type'] != 'Promotion') {
                $source = 'deals';
                $table = new Deal();
                $id_table = 'id_deals';
                $id_post = $post['id_deals'];
                $error_message = 'Deals';
                $warning_image = 'deals';
            } else {
                $source = 'deals_promotion';
                $table = new DealsPromotionTemplate();
                $id_table = 'id_deals_promotion_template';
                $id_post = $post['id_deals'];
                $error_message = 'Deals';
                $warning_image = 'deals';
            }
        } else {
            $source = 'promo_campaign';
            $table = new PromoCampaign();
            $id_table = 'id_promo_campaign';
            $id_post = $post['id_promo_campaign'];
            $warning_image = 'promo_campaign';
            $error_message = 'Deals';
        }

        DB::beginTransaction();
        $dataPromoCampaign['promo_type'] = $post['promo_type'];
        if ($source == 'promo_campaign') {
            $saveImagePath = 'img/promo-campaign/warning-image/';
            $dataPromoCampaign['step_complete'] = 1;
            $dataPromoCampaign['last_updated_by'] = $user['id'];
            $dataPromoCampaign['user_type'] = $post['filter_user'];
            $dataPromoCampaign['specific_user'] = $post['specific_user'] ?? null;
            $dataPromoCampaign['min_basket_size'] = $post['min_basket_size'] ?? null;

            if ($post['filter_outlet'] == 'All Outlet') {
                $createFilterOutlet = $this->createOutletFilter('all_outlet', 1, $post['id_promo_campaign'], null);
            } elseif ($post['filter_outlet'] == 'Selected') {
                $createFilterOutlet = $this->createOutletFilter('selected', 0, $post['id_promo_campaign'], $post['multiple_outlet']);
            } elseif ($post['filter_outlet'] == 'Outlet Group') {
                $createFilterOutlet = $this->createOutletFilter('outlet_group', 0, $post['id_promo_campaign'], null, $post['multiple_outlet_group']);
            } else {
                $createFilterOutlet = [
                    'status'  => 'fail',
                    'message' => 'Create Filter Outlet Failed'
                ];
                DB::rollBack();
                return response()->json($createFilterOutlet);
            }

            if ($createFilterOutlet['status'] != 'success') {
                return $createFilterOutlet;
            }
        } else {
            $saveImagePath = 'img/deals/warning-image/';
            $dataPromoCampaign['deals_promo_id_type']   = $post['deals_promo_id_type'] ?? null;
            $dataPromoCampaign['deals_promo_id']        = $dataPromoCampaign['deals_promo_id_type'] == 'nominal' ? $post['deals_promo_id_nominal'] : ($post['deals_promo_id_promoid'] ?? null);
            $dataPromoCampaign['last_updated_by']       = auth()->user()->id;
            $dataPromoCampaign['step_complete']         = 0;
            $dataPromoCampaign['min_basket_size']       = $post['min_basket_size'] ?? null;
        }

        $data_promo = $table::where($id_table, $id_post)->first();
        $product_type = $data_promo['product_type'] ?? 'single';
        if (!empty($post['id_deals'])) {
            if (!empty($data_promo['deals_total_claimed'])) {
                return [
                    'status'  => 'fail',
                    'message' => 'Cannot update deals because someone has already claimed a voucher'
                ];
            }
        }

        $dataPromoCampaign['product_rule'] = $post['product_rule'] ?? null;
        if (
            !isset($post['product_rule'])
            && (($post['promo_type'] == 'Product discount' && $post['filter_product'] == 'Selected')
                || $post['promo_type'] == 'Tier discount'
                || $post['promo_type'] == 'Buy X Get Y'
            )
        ) {
            $dataPromoCampaign['product_rule'] = 'or';
        }

        $update = $table::where($id_table, $id_post)->update($dataPromoCampaign);

        if (isset($post['filter_shipment'])) {
            $update_shipment_rule = $this->createShipmentRule($source, $id_table, $id_post, $post);

            if ($update_shipment_rule['status'] != 'success') {
                return $update_shipment_rule;
            }
        }

        $update_payment_method_rule = $this->createPaymentRule($source, $id_table, $id_post, $post);

        if ($update_payment_method_rule['status'] != 'success') {
            return $update_payment_method_rule;
        }

        if ($post['promo_type'] == 'Product discount') {
            if ($post['filter_product'] == 'All Product') {
                $createFilterProduct = $this->createProductFilter('all_product', 1, $id_post, null, $post['discount_type'], $post['discount_value'], $post['max_product'], $post['max_percent_discount'], $source, $table, $id_table, $product_type);
            } elseif ($post['filter_product'] == 'Selected') {
                $createFilterProduct = $this->createProductFilter('selected', 0, $id_post, $post['multiple_product'], $post['discount_type'], $post['discount_value'], $post['max_product'], $post['max_percent_discount'], $source, $table, $id_table, $product_type);
            } else {
                $createFilterProduct = [
                    'status'  => 'fail',
                    'messages' => 'Create Promo Type Failed'
                ];
                DB::rollBack();
                return response()->json($createFilterProduct);
            }
        } elseif ($post['promo_type'] == 'Tier discount') {
            try {
                $createFilterProduct = $this->createPromoTierDiscount($id_post, $post['multiple_product'] ?? [], $post['discount_type'], $post['promo_rule'], $source, $table, $id_table, $product_type, $post['filter_product']);
            } catch (Exception $e) {
                $createFilterProduct = [
                    'status'  => 'fail',
                    'messages' => 'Create Promo Type Failed'
                ];
                DB::rollBack();
                return response()->json($createFilterProduct);
            }
        } elseif ($post['promo_type'] == 'Buy X Get Y') {
            try {
                $createFilterProduct = $this->createBuyXGetYDiscount($id_post, $post['multiple_product'] ?? [], $post['promo_rule'], $source, $table, $id_table, $product_type, $post['filter_product']);
            } catch (Exception $e) {
                $createFilterProduct = [
                    'status'  => 'fail',
                    'messages' => 'Create Promo Type Failed'
                ];
                DB::rollBack();
                return response()->json($createFilterProduct);
            }
        } elseif ($post['promo_type'] == 'Discount bill') {
            try {
                $post['filter_product_bill'] = $post['filter_product_bill'] ?? 'All Product';
                $createFilterProduct = $this->createDiscountBill($id_post, $post['discount_type'], $post['discount_value'], $post['max_percent_discount'], $source, $table, $id_table, $post['filter_product_bill'], $post['multiple_product'] ?? [], $product_type);
            } catch (Exception $e) {
                $createFilterProduct = [
                    'status'  => 'fail',
                    'messages' => 'Create Promo Type Failed'
                ];
                DB::rollBack();
                return response()->json($createFilterProduct);
            }
        } elseif ($post['promo_type'] == 'Discount delivery') {
            try {
                $createFilterProduct = $this->createDiscountDelivery($id_post, $post['discount_type'], $post['discount_value'], $post['max_percent_discount'], $source, $table, $id_table);
            } catch (Exception $e) {
                $createFilterProduct = [
                    'status'  => 'fail',
                    'messages' => 'Create Promo Type Failed'
                ];
                DB::rollBack();
                return response()->json($createFilterProduct);
            }
        } else {
            $createFilterProduct = $this->deleteAllProductRule($source, $id_post);
            if ($createFilterProduct) {
                $createFilterProduct = ['status' => 'success'];
            } else {
                $createFilterProduct = [
                    'status'  => 'fail',
                    'messages' => 'Create Promo Type Failed'
                ];
                DB::rollBack();
                return response()->json($createFilterProduct);
            }
        }

        DB::commit();

        $outlets = $data_promo->{$source . '_outlets'};
        $products = $createFilterProduct['products'] ?? false;
        if (isset($data_promo->deals_list_outlet)) {
            $outlets = explode(',', $data_promo->deals_list_outlet);
            if (in_array('all', $outlets)) {
                $outlets = [];
            }
        }

        if (
            !empty($outlets)
            && !empty($products)
            && $data_promo['is_all_outlet'] != 1
            && ($data_promo[$source . '_product_discount']['is_all_product'] ?? 0) != 1
        ) {
            unset($createFilterProduct['products']);

            $check_brand_product = app($this->promo)->checkBrandProduct($outlets, $products);
            if ($check_brand_product['status'] == false) {
                $createFilterProduct['brand_product_error'] = array_merge($createFilterProduct['messages'] ?? [], $check_brand_product['messages'] ?? ['Outlet tidak mempunyai produk dengan brand yang sesuai.']);

                if ($source == 'promo_campaign') {
                    PromoCampaign::where('id_promo_campaign', $id_post)->update(['step_complete' => 0]);
                }
            }
        }

        return response()->json($createFilterProduct);
    }

    public function usedCodeUpdate($request, $step)
    {
        DB::beginTransaction();
        switch ($step) {
            case 'step1':
                $data = [
                    'last_updated_by'   => $request->user()->id,
                    'campaign_name'     => $request->campaign_name,
                    'promo_title'       => $request->promo_title,
                    'date_end'          => $this->generateDate($request->date_end),
                ];

                if ($request->user()->level == 'Super Admin') {
                    $data['date_start']         = $this->generateDate($request->date_start);
                    $data['charged_central']    = $request->charged_central;
                    $data['charged_outlet']     = $request->charged_outlet;
                    $data['brand_rule']         = $request->brand_rule;
                    $data['product_type']       = $request->product_type;
                }

                $update = PromoCampaign::where('id_promo_campaign', $request->id_promo_campaign)->update($data);

                if ($update) {
                    if (isset($request->promo_tag)) {
                        $update = $this->insertTag('update', $request->id_promo_campaign, $request->promo_tag);
                    } else {
                        $update = PromoCampaignHaveTag::where('id_promo_campaign', '=', $request->id_promo_campaign)->delete();
                        $update = true;
                    }

                    if ($request->user()->level == 'Super Admin') {
                        if ($request->id_brand) {
                            $update = $this->insertPromoCampaignBrand($request->id_promo_campaign, $request->id_brand);
                        }
                    }
                }

                break;

            case 'step2':
                $data = [
                    'last_updated_by'   => $request->user()->id,
                    'user_type'         => $request->filter_user,
                    'specific_user'     => $request->specific_user
                ];

                $update = PromoCampaign::where('id_promo_campaign', $request->id_promo_campaign)->update($data);

                if ($update) {
                    if ($request->filter_outlet == 'All Outlet') {
                        $update = $this->createOutletFilter('all_outlet', 1, $request->id_promo_campaign, null);
                    } elseif ($request->filter_outlet == 'Selected') {
                        $update = $this->createOutletFilter('selected', 0, $request->id_promo_campaign, $request->multiple_outlet);
                    } elseif ($request->filter_outlet == 'Outlet Group') {
                        $update = $this->createOutletFilter('outlet_group', 0, $request->id_promo_campaign, null, $request->multiple_outlet_group);
                    } else {
                        $update = false;
                    }

                    if ($update && $update['status'] != 'success') {
                        $update = false;
                    }
                }

                break;

            default:
                $update = false;
                break;
        }

        if ($update) {
            DB::commit();
        } else {
            DB::rollBack();
        }

        return MyHelper::checkUpdate($update);
    }

    public function createOutletFilter($parameter, $operator, $id_promo_campaign, $outlet, $outlet_groups = [])
    {
        if (PromoCampaignOutlet::where('id_promo_campaign', '=', $id_promo_campaign)->exists()) {
            PromoCampaignOutlet::where('id_promo_campaign', '=', $id_promo_campaign)->delete();
        }

        if (PromoCampaignOutletGroup::where('id_promo_campaign', '=', $id_promo_campaign)->exists()) {
            PromoCampaignOutletGroup::where('id_promo_campaign', '=', $id_promo_campaign)->delete();
        }

        if ($parameter == 'all_outlet') {
            try {
                PromoCampaign::where('id_promo_campaign', '=', $id_promo_campaign)->update(['is_all_outlet' => $operator]);
                $result = ['status'  => 'success'];
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Create Filter Outlet Failed'
                ];
                DB::rollBack();
                return $result;
            }
        } elseif ($parameter == 'selected') {
            $dataOutlet = [];
            for ($i = 0; $i < count($outlet); $i++) {
                $dataOutlet[$i]['id_outlet']            = array_values($outlet)[$i];
                $dataOutlet[$i]['id_promo_campaign']    = $id_promo_campaign;
                $dataOutlet[$i]['created_at']           = date('Y-m-d H:i:s');
                $dataOutlet[$i]['updated_at']           = date('Y-m-d H:i:s');
            }
            try {
                PromoCampaignOutlet::insert($dataOutlet);
                PromoCampaign::where('id_promo_campaign', '=', $id_promo_campaign)->update(['is_all_outlet' => $operator]);
                $result = ['status'  => 'success'];
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Create Filter Outlet Failed'
                ];
                DB::rollBack();
                return $result;
            }
        } elseif ($parameter == 'outlet_group') {
            $data_outlet_group = [];
            foreach ($outlet_groups as $value) {
                $data_outlet_group[] = [
                    'id_outlet_group'      => $value,
                    'id_promo_campaign'    => $id_promo_campaign,
                    'created_at'           => date('Y-m-d H:i:s'),
                    'updated_at'           => date('Y-m-d H:i:s')
                ];
            }

            try {
                PromoCampaignOutletGroup::insert($data_outlet_group);
                PromoCampaign::where('id_promo_campaign', '=', $id_promo_campaign)->update(['is_all_outlet' => $operator]);
                $result = ['status'  => 'success'];
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Create Filter Outlet Failed'
                ];
                DB::rollBack();
                return $result;
            }
        }

        return $result;
    }

    public function insertPromoCampaignBrand($id_promo_campaign, $brand_list = [])
    {
        $data = [];
        foreach ($brand_list as $id_brand) {
            $temp = [
                'id_promo_campaign' => $id_promo_campaign,
                'id_brand' => $id_brand
            ];
            $data[] = $temp;
        }

        try {
            $save = PromoCampaignBrand::where('id_promo_campaign', $id_promo_campaign)->delete();
            $save = PromoCampaignBrand::insert($data);
            $status = true;
        } catch (\Exception $e) {
            $status = false;
        }

        return $status;
    }

    public function deleteAllProductRule($source, $id_post)
    {
        try {
            if ($source == 'promo_campaign') {
                PromoCampaignProductDiscountRule::where('id_promo_campaign', '=', $id_post)->delete();
                PromoCampaignTierDiscountRule::where('id_promo_campaign', '=', $id_post)->delete();
                PromoCampaignBuyxgetyRule::where('id_promo_campaign', '=', $id_post)->delete();
                PromoCampaignDiscountBillRule::where('id_promo_campaign', '=', $id_post)->delete();
                PromoCampaignDiscountDeliveryRule::where('id_promo_campaign', '=', $id_post)->delete();

                PromoCampaignTierDiscountProduct::where('id_promo_campaign', '=', $id_post)->delete();
                PromoCampaignProductDiscount::where('id_promo_campaign', '=', $id_post)->delete();
                PromoCampaignBuyxgetyProductRequirement::where('id_promo_campaign', '=', $id_post)->delete();
                PromoCampaignDiscountBillProduct::where('id_promo_campaign', '=', $id_post)->delete();
            } elseif ($source == 'deals') {
                DealsProductDiscountRule::where('id_deals', '=', $id_post)->delete();
                DealsTierDiscountRule::where('id_deals', '=', $id_post)->delete();
                DealsBuyxgetyRule::where('id_deals', '=', $id_post)->delete();
                DealsDiscountBillRule::where('id_deals', '=', $id_post)->delete();
                DealsDiscountDeliveryRule::where('id_deals', '=', $id_post)->delete();

                DealsTierDiscountProduct::where('id_deals', '=', $id_post)->delete();
                DealsProductDiscount::where('id_deals', '=', $id_post)->delete();
                DealsBuyxgetyProductRequirement::where('id_deals', '=', $id_post)->delete();
                DealsDiscountBillProduct::where('id_deals', '=', $id_post)->delete();
            } elseif ($source == 'deals_promotion') {
                DealsPromotionProductDiscountRule::where('id_deals', '=', $id_post)->delete();
                DealsPromotionTierDiscountRule::where('id_deals', '=', $id_post)->delete();
                DealsPromotionBuyxgetyRule::where('id_deals', '=', $id_post)->delete();
                DealsPromotionDiscountBillRule::where('id_deals', '=', $id_post)->delete();
                DealsPromotionDiscountDeliveryRule::where('id_deals', '=', $id_post)->delete();

                DealsPromotionTierDiscountProduct::where('id_deals', '=', $id_post)->delete();
                DealsPromotionProductDiscount::where('id_deals', '=', $id_post)->delete();
                DealsPromotionBuyxgetyProductRequirement::where('id_deals', '=', $id_post)->delete();
                DealsPromotionDiscountBillProduct::where('id_deals', '=', $id_post)->delete();
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function deleteOutletRule($source, $id_post)
    {
        try {
            if ($source == 'promo_campaign') {
                PromoCampaignOutlet::where('id_promo_campaign', '=', $id_post)->delete();
            } elseif ($source == 'deals') {
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function createProductFilter($parameter, $operator, $id_post, $product, $discount_type, $discount_value, $max_product, $max_percent_discount, $source, $table, $id_table, $product_type)
    {
        $delete_rule = $this->deleteAllProductRule($source, $id_post);

        if (!$delete_rule) {
            $result = [
                'status'  => 'fail',
                'message' => 'Create Filter Product Failed'
            ];
            DB::rollBack();
            return response()->json($result);
        }

        if ($source == 'promo_campaign') {
            $table_product_discount_rule = new PromoCampaignProductDiscountRule();
            $table_product_discount = new PromoCampaignProductDiscount();
        } elseif ($source == 'deals') {
            $table_product_discount_rule = new DealsProductDiscountRule();
            $table_product_discount = new DealsProductDiscount();
        } elseif ($source == 'deals_promotion') {
            $table_product_discount_rule = new DealsPromotionProductDiscountRule();
            $table_product_discount = new DealsPromotionProductDiscount();
            $id_table = 'id_deals';
        }

        if ($discount_type == 'Nominal') {
            $max_percent_discount = null;
        }

        if ($discount_type == 'Nominal') {
            $max_percent_discount = null;
        }

        $data = [

            $id_table => $id_post,
            'is_all_product'            => $operator,
            'discount_type'             => $discount_type,
            'discount_value'            => $discount_value,
            'max_product'               => $max_product,
            'max_percent_discount'      => $max_percent_discount,
            'created_at'                => date('Y-m-d H:i:s'),
            'updated_at'                => date('Y-m-d H:i:s')
        ];
        if ($parameter == 'all_product') {
            try {
                $table_product_discount_rule::insert($data);
                $result = ['status'  => 'success'];
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Create Filter Product Failed'
                ];
                DB::rollBack();
                return response()->json($result);
            }
        } else {
            $data_product = $this->getProductInsertFormat($product, $id_table, $id_post);

            try {
                $table_product_discount_rule::insert($data);
                $table_product_discount::insert($data_product);
                $result = ['status'  => 'success', 'products' => $data_product];
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Create Filter Product Failed'
                ];
                DB::rollBack();
                return response()->json($result);
            }
        }
        return $result;
    }

    public function createPromoTierDiscount($id_post, $product, $discount_type, $rules, $source, $table, $id_table, $product_type, $filter_product)
    {
        if (!$rules) {
            return [
                'status'  => 'fail',
                'message' => 'Rule empty'
            ];
        }

        $delete_rule = $this->deleteAllProductRule($source, $id_post);

        if (!$delete_rule) {
            $result = [
                'status'  => 'fail',
                'message' => 'Create Filter Product Failed'
            ];
            DB::rollBack();
            return response()->json($result);
        }

        if ($source == 'promo_campaign') {
            $table_tier_discount_rule = new PromoCampaignTierDiscountRule();
            $table_tier_discount_product = new PromoCampaignTierDiscountProduct();
        } elseif ($source == 'deals') {
            $table_tier_discount_rule = new DealsTierDiscountRule();
            $table_tier_discount_product = new DealsTierDiscountProduct();
        } elseif ($source == 'deals_promotion') {
            $table_tier_discount_rule = new DealsPromotionTierDiscountRule();
            $table_tier_discount_product = new DealsPromotionTierDiscountProduct();
            $id_table = 'id_deals';
        }

        if ($discount_type == 'Nominal') {
            $is_nominal = 1;
        } else {
            $is_nominal = 0;
        }

        if ($discount_type == 'Nominal') {
            $is_nominal = 1;
        } else {
            $is_nominal = 0;
        }

        $data = [];
        foreach ($rules as $key => $rule) {
            $data[$key] = [
                $id_table => $id_post,
                'discount_type'     => $discount_type,
                'max_qty'           => $rule['max_qty'],
                'min_qty'           => $rule['min_qty'],
                'discount_value'    => $rule['discount_value'],
                'is_all_product'    => ($filter_product == 'selected') ? 0 : 1,
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ];
            if ($is_nominal) {
                $data[$key]['max_percent_discount'] = null;
            } else {
                $data[$key]['max_percent_discount'] = $rule['max_percent_discount'];
            }
        }

        try {
            $table_tier_discount_rule::insert($data);
            $result = ['status'  => 'success'];

            if ($filter_product == 'selected') {
                $data_product = $this->getProductInsertFormat($product, $id_table, $id_post);
                $table_tier_discount_product::insert($data_product);
                $result = $result + ['products' => $data_product];
            }
        } catch (\Exception $e) {
            $result = [
                'status'  => 'fail',
                'message' => 'Create Filter Product Failed'
            ];
        }
        return $result;
    }

    public function createBuyXGetYDiscount($id_post, $product, $rules, $source, $table, $id_table, $product_type, $filter_product)
    {
        if (!$rules) {
            return [
                'status'  => 'fail',
                'message' => 'Rule empty'
            ];
        }
        $delete_rule = $this->deleteAllProductRule($source, $id_post);

        if (!$delete_rule) {
            $result = [
                'status'  => 'fail',
                'message' => 'Create Filter Product Failed'
            ];
            DB::rollBack();
            return response()->json($result);
        }

        if ($source == 'promo_campaign') {
            $table_buyxgety_discount_rule = new PromoCampaignBuyxgetyRule();
            $table_buyxgety_discount_product = new PromoCampaignBuyxgetyProductRequirement();
            $table_buyxgety_modifier = new PromoCampaignBuyxgetyProductModifier();
            $id_rule_table = 'id_promo_campaign_buyxgety_rule';
        } elseif ($source == 'deals') {
            $table_buyxgety_discount_rule = new DealsBuyxgetyRule();
            $table_buyxgety_discount_product = new DealsBuyxgetyProductRequirement();
            $table_buyxgety_modifier = new DealsBuyxgetyProductModifier();
            $id_rule_table = 'id_deals_buyxgety_rule';
        } elseif ($source == 'deals_promotion') {
            $table_buyxgety_discount_rule = new DealsPromotionBuyxgetyRule();
            $table_buyxgety_discount_product = new DealsPromotionBuyxgetyProductRequirement();
            $table_buyxgety_modifier = new DealsPromotionBuyxgetyProductModifier();
            $id_rule_table = 'id_deals_buyxgety_rule';
            $id_table = 'id_deals';
        }

        try {
            $data = [];
            $extra_modifier = [];
            foreach ($rules as $key => $rule) {
                $data[$key] = [
                    $id_table       => $id_post,
                    // 'benefit_id_product'     => $rule['benefit_id_product'] == 0 ? $product : $rule['benefit_id_product'],
                    'benefit_id_product'        => $this->splitBrandProduct($rule['benefit_id_product'], 'product'),
                    'id_brand'                  => $this->splitBrandProduct($rule['benefit_id_product'], 'brand'),
                    'id_product_variant_group'  => null,
                    'max_qty_requirement'       => $rule['max_qty_requirement'],
                    'min_qty_requirement'       => $rule['min_qty_requirement'],
                    'benefit_qty'               => $rule['benefit_qty'],
                    'max_percent_discount'      => $rule['max_percent_discount'],
                    'is_all_product'            => ($filter_product == 'selected') ? 0 : 1
                ];


                if ($rule['benefit_type'] == "percent") {
                    $data[$key]['discount_type'] = 'percent';
                    $data[$key]['discount_value'] = $rule['discount_percent'];
                    $data[$key]['benefit_qty'] = 1;
                } elseif ($rule['benefit_type'] == "nominal") {
                    $data[$key]['discount_type'] = 'nominal';
                    $data[$key]['discount_value'] = $rule['discount_nominal'];
                    $data[$key]['benefit_qty'] = 1;
                    $data[$key]['max_percent_discount'] = null;
                } elseif ($rule['benefit_type'] == "free") {
                    $data[$key]['discount_type'] = 'percent';
                    $data[$key]['discount_value'] = 100;
                    $data[$key]['max_percent_discount'] = null;
                } else {
                    $data[$key]['discount_type'] = 'nominal';
                    $data[$key]['discount_value'] = 0;
                    $data[$key]['benefit_qty'] = 1;
                }

                if (isset($rule['id_product_variant_group'])) {
                    $extra_modifier[$key] = explode('-', $rule['id_product_variant_group']);
                    $data[$key]['id_product_variant_group'] = $extra_modifier[$key][0] ?? null;
                }

                $save_rule = $table_buyxgety_discount_rule::create($data[$key]);
                if ($data[$key]['id_product_variant_group']) {
                    $extra_mod_data = [];
                    unset($extra_modifier[$key][0]);
                    foreach ($extra_modifier[$key] as $modifier) {
                        $extra_mod_data[] = [
                            'id_product_modifier' => $modifier,
                            $id_rule_table  => $save_rule[$id_rule_table],
                            'created_at'    => date('Y-m-d H:i:s'),
                            'updated_at'    => date('Y-m-d H:i:s')
                        ];
                    }
                    $table_buyxgety_modifier::insert($extra_mod_data);
                }
            }

            $result = ['status'  => 'success'];

            if ($filter_product == 'selected') {
                $data_product = $this->getProductInsertFormat($product, $id_table, $id_post);

                $table_buyxgety_discount_product::insert($data_product);

                $result = $result + ['products' => $data_product];
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $result = [
                'status'  => 'fail',
                'message' => 'Create Buy X get Y rules failed'
            ];
            \Log::error($e);
            DB::rollBack();
        }
        return $result;
    }

    public function createDiscountBill($id_post, $discount_type, $discount_value, $max_percent_discount, $source, $table, $id_table, $filter_product, $products, $product_type)
    {
        $delete_rule = $this->deleteAllProductRule($source, $id_post);

        if (!$delete_rule) {
            $result = [
                'status'  => 'fail',
                'message' => 'Create Filter Product Failed'
            ];
            DB::rollBack();
            return response()->json($result);
        }

        if ($source == 'promo_campaign') {
            $table_discount_bill_rule = new PromoCampaignDiscountBillRule();
            $table_discount_bill_product = new PromoCampaignDiscountBillProduct();
        } elseif ($source == 'deals') {
            $table_discount_bill_rule = new DealsDiscountBillRule();
            $table_discount_bill_product = new DealsDiscountBillProduct();
        } elseif ($source == 'deals_promotion') {
            $table_discount_bill_rule = new DealsPromotionDiscountBillRule();
            $table_discount_bill_product = new DealsPromotionDiscountBillProduct();
            $id_table = 'id_deals';
        }

        if ($discount_type == 'Nominal') {
            $max_percent_discount = null;
        }

        $data = [

            $id_table               => $id_post,
            'discount_type'         => $discount_type,
            'discount_value'        => $discount_value,
            'max_percent_discount'  => $max_percent_discount,
            'is_all_product'        => ($filter_product == 'All Product') ? 1 : 0,
            'created_at'            => date('Y-m-d H:i:s'),
            'updated_at'            => date('Y-m-d H:i:s')
        ];

        if ($filter_product != 'All Product') {
            $data_product = $this->getProductInsertFormat($products, $id_table, $id_post);
        }

        try {
            $table_discount_bill_rule::insert($data);
            if ($filter_product != 'All Product') {
                $table_discount_bill_product::insert($data_product);
            }
            $result = ['status'  => 'success'];
        } catch (\Exception $e) {
            $result = [
                'status'  => 'fail',
                'message' => 'Create Discount Failed'
            ];
            DB::rollBack();
        }

        return $result;
    }

    public function createDiscountDelivery($id_post, $discount_type, $discount_value, $max_percent_discount, $source, $table, $id_table)
    {

        $delete_rule = $this->deleteAllProductRule($source, $id_post);

        if (!$delete_rule) {
            $result = [
                'status'  => 'fail',
                'message' => 'Create Filter Product Failed'
            ];
            DB::rollBack();
            return response()->json($result);
        }

        if ($source == 'promo_campaign') {
            $table_discount_bill_rule = new PromoCampaignDiscountDeliveryRule();
        } elseif ($source == 'deals') {
            $table_discount_bill_rule = new DealsDiscountDeliveryRule();
        } elseif ($source == 'deals_promotion') {
            $table_discount_bill_rule = new DealsPromotionDiscountDeliveryRule();
            $id_table = 'id_deals';
        }

        if ($discount_type == 'Nominal') {
            $max_percent_discount = null;
        }

        $data = [

            $id_table               => $id_post,
            'discount_type'         => $discount_type,
            'discount_value'        => $discount_value,
            'max_percent_discount'  => $max_percent_discount,
            'created_at'            => date('Y-m-d H:i:s'),
            'updated_at'            => date('Y-m-d H:i:s')
        ];

        try {
            $table_discount_bill_rule::insert($data);
            $result = ['status'  => 'success'];
        } catch (\Exception $e) {
            $result = [
                'status'  => 'fail',
                'message' => 'Create Discount Failed'
            ];
            DB::rollBack();
            return response()->json($result);
        }

        return $result;
    }

    public function generateDate($date)
    {
        if (!isset($date)) {
            return null;
        }
        $datetimearr    = explode(' - ', $date);

        $datearr        = explode(' ', $datetimearr[0]);

        $date = $datearr[0] . '-' . $datearr[1] . '-' . $datearr[2];

        $date = date('Y-m-d', strtotime($date)) . ' ' . $datetimearr[1] . ":00";
        return $date;
    }

    public function removeDuplicateCode($code, $total_coupon)
    {
        $unique_code = array_column($code, 'promo_code');
        // $unique_code = array_intersect_key($unique_code, array_unique( array_map("strtolower", $unique_code)));
        $unique_code = array_unique($unique_code);
        $code = array_filter($code, function ($key, $value) use ($unique_code) {
            return in_array($value, array_keys($unique_code));
        }, ARRAY_FILTER_USE_BOTH);
        $duplicate = $total_coupon - count($code);

        return [
            'code' => $code,
            'duplicate' => $duplicate
        ];
    }

    public function generateMultipleCode($old_code, $id, $prefix_code, $number_last_code, $total_coupon)
    {
        if (empty($old_code)) {
            $i = 0;
        } else {
            $i = count($old_code) - 1;
            $total_coupon = $i + $total_coupon;
        }
        for (; $i < $total_coupon; $i++) {
            $generateCode[$i]['id_promo_campaign']  = $id;
            $generateCode[$i]['promo_code']         = implode('', [$prefix_code, MyHelper::createrandom($number_last_code, 'PromoCode')]);
            $generateCode[$i]['created_at']         = date('Y-m-d H:i:s');
            $generateCode[$i]['updated_at']         = date('Y-m-d H:i:s');
            array_push($old_code, $generateCode[$i]);
        }
        return $old_code;
    }

    public function generateCode($status, $id, $type_code, $promo_code = null, $prefix_code = null, $number_last_code = null, $total_coupon = null)
    {
        $generateCode = [];
        if ($type_code == 'Multiple') {
            if ($total_coupon <= 1000) {
                for ($i = 0; $i < $total_coupon; $i++) {
                    $generateCode[$i]['id_promo_campaign']  = $id;
                    $generateCode[$i]['promo_code']         = implode('', [$prefix_code, MyHelper::createrandom($number_last_code, 'PromoCode')]);
                    $generateCode[$i]['created_at']         = date('Y-m-d H:i:s');
                    $generateCode[$i]['updated_at']         = date('Y-m-d H:i:s');
                }

                // $unique_code = $this->removeDuplicateCode($generateCode, $total_coupon);
                // $duplicate = $unique_code['duplicate'];
                // $generateCode2 = $unique_code['code'];
                // $i = 0;
                // while ($duplicate != 0 )
                // {
                //  $generateCode2 = $this->generateMultipleCode($generateCode2, $id, $prefix_code, $number_last_code, $total_coupon);
                //  $unique_code = $this->removeDuplicateCode($generateCode2, $total_coupon);
                //  $duplicate = $unique_code['duplicate'];
                //  $generateCode2 = $unique_code['code'];

                // }
                // $generateCode = $generateCode2;
            } else {
                GeneratePromoCode::dispatch($status, $id, $prefix_code, $number_last_code, $total_coupon)
                ->onQueue('high')
                ->allOnConnection('database');
                $result = ['status'  => 'success'];
                return $result;
            }
        } else {
            $generateCode['id_promo_campaign']  = $id;
            $generateCode['promo_code']         = $promo_code;
            $generateCode['created_at']         = date('Y-m-d H:i:s');
            $generateCode['updated_at']         = date('Y-m-d H:i:s');
        }

        if ($status == 'insert') {
            try {
                PromoCampaignPromoCode::insert($generateCode);
                $result = ['status'  => 'success'];
            } catch (\Exception $e) {
                $result = ['status' => 'fail'];
            }
        } else {
            try {
                PromoCampaignPromoCode::where('id_promo_campaign', $id)->delete();
                PromoCampaignPromoCode::insert($generateCode);
                $result = ['status'  => 'success'];
            } catch (\Exception $e) {
                $result = ['status' => 'fail'];
            }
        }

        return $result;
    }

    public function insertTag($status, $id_promo_campaign, $promo_tag)
    {
        foreach ($promo_tag as $key => $value) {
            $data = ['tag_name' => $value];
            $tag[] = PromoCampaignTag::updateOrCreate(['tag_name' => $value])->id_promo_campaign_tag;
        }
        $tagID = [];
        for ($i = 0; $i < count($tag); $i++) {
            if (is_numeric(array_values($tag)[$i])) {
                $tagID[$i]['id_promo_campaign_tag']     = array_values($tag)[$i];
                $tagID[$i]['id_promo_campaign']         = $id_promo_campaign;
            }
        }

        if ($status == 'update') {
            PromoCampaignHaveTag::where('id_promo_campaign', '=', $id_promo_campaign)->delete();
        }

        try {
            PromoCampaignHaveTag::insert($tagID);
            $result = ['status'  => 'success'];
        } catch (\Exception $e) {
            $result = ['status' => 'fail'];
        }

        return $result;
    }

    public function showStep1(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();

        $promoCampaign = PromoCampaign::with([
                            'promo_campaign_have_tags.promo_campaign_tag',
                            'promo_campaign_reports' => function ($q) {
                                $q->first();
                            },
                            'promo_campaign_brands'
                        ])
                        ->where('id_promo_campaign', '=', $post['id_promo_campaign'])->first();

        if (!empty($promoCampaign) && $promoCampaign['code_type'] == 'Single') {
            $promoCampaign = $promoCampaign->load(['promo_campaign_promo_codes' => function ($q) {
                $q->first();
            }]);
        }

        if (isset($promoCampaign)) {
            $promoCampaign = $promoCampaign->toArray();
        } else {
            $promoCampaign = false;
        }

        if ($promoCampaign) {
            $result = [
                'status'  => 'success',
                'result'  => $promoCampaign
            ];
        } else {
            $result = [
                'status'  => 'fail',
                'messages'  => ['Promo Campaign Not Found']
            ];
        }
        return response()->json($result);
    }

    public function showStep2(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();

        $promoCampaign = PromoCampaign::with([
                            'user',
                            'products',
                            'promo_campaign_have_tags.promo_campaign_tag',
                            'promo_campaign_product_discount',
                            'promo_campaign_product_discount_rules',
                            'promo_campaign_tier_discount_product',
                            'promo_campaign_tier_discount_rules',
                            'promo_campaign_buyxgety_product_requirement',
                            'promo_campaign_buyxgety_rules.promo_campaign_buyxgety_product_modifiers',
                            'outlets',
                            'outlet_groups',
                            'brands',
                            'promo_campaign_discount_bill_rules',
                            'promo_campaign_discount_bill_products',
                            'promo_campaign_discount_delivery_rules',
                            'promo_campaign_shipment_method',
                            'promo_campaign_payment_method',
                            'promo_campaign_reports' => function ($q) {
                                $q->first();
                            }
                        ])
                        ->where('id_promo_campaign', '=', $post['id_promo_campaign'])
                        ->first();

        if (isset($promoCampaign)) {
            $promoCampaign = $promoCampaign->toArray();
        } else {
            $promoCampaign = false;
        }

        if ($promoCampaign) {
            $result = [
                'status'  => 'success',
                'result'  => $promoCampaign
            ];
        } else {
            $result = [
                'status'  => 'fail',
                'messages'  => ['Promo Campaign Not Found']
            ];
        }

        return response()->json($result);
    }

    public function getData(Request $request)
    {
        $post = $request->json()->all();

        if ($post['get'] == 'Outlet') {
            $data = Outlet::select('id_outlet', DB::raw('CONCAT(outlet_code, " - ", outlet_name) AS outlet'));

            if (!empty($post['brand'])) {
                $data = $data->whereHas('brands', function ($query) use ($post) {
                    $query->whereIn('brands.id_brand', $post['brand']);
                });
            }

            $data = $data->get()->toArray();
        } elseif ($post['get'] == 'Product') {
            $data = [];
            $load_data = [];
            if (isset($post['product_type'])) {
                switch ($post['product_type']) {
                    case 'single':
                        $load_data[] = 'single';
                        break;

                    case 'variant':
                        $load_data[] = 'variant';
                        break;

                    case 'single + variant':
                        $load_data[] = 'single';
                        $load_data[] = 'variant';
                        break;

                    default:
                        $load_data[] = 'single';
                        break;
                }
            } else {
                $load_data[] = 'single';
            }

            if (in_array('single', $load_data)) {
                $product = Product::select([
                            'products.id_product',
                            'brands.id_brand',
                            DB::raw('CONCAT(name_brand, " - ", product_code, " - ", product_name) AS product, "" as id_product_variant_group')
                        ])
                        ->leftJoin('brand_product', 'products.id_product', '=', 'brand_product.id_product')
                        ->join('brands', 'brands.id_brand', '=', 'brand_product.id_brand')
                        ->groupBy('brand_product.id_brand_product')
                        ->orderBy('brands.id_brand');

                if (!empty($post['brand'])) {
                    $product = $product->whereIn('brands.id_brand', $post['brand']);
                }

                $product = $product->get()->toArray();

                $data = array_merge($data, $product);
            }

            if (in_array('variant', $load_data)) {
                $product_variant = ProductVariantGroup::leftJoin('brand_product', 'product_variant_groups.id_product', '=', 'brand_product.id_product')
                        ->join('brands', 'brands.id_brand', '=', 'brand_product.id_brand')
                        ->join('products', 'products.id_product', '=', 'product_variant_groups.id_product')
                        ->where('products.product_variant_status', 1)
                        ->with('product_variant_pivot_simple')
                        ->orderBy('brands.id_brand');

                if (!empty($post['brand'])) {
                    $product_variant = $product_variant->whereIn('brands.id_brand', $post['brand']);
                }

                $product_variant = $product_variant->get()->toArray();

                $formatted_product_variant = [];
                if ($product_variant) {
                    foreach ($product_variant as $value) {
                        $variant = '';
                        if (!empty($value)) {
                            $variant = array_column($value['product_variant_pivot_simple'], 'product_variant_name');
                            $variant = ' ' . implode(',', $variant);
                        }

                        $formatted_product_variant[] = [
                            'id_product' => $value['id_product'],
                            'id_brand'  => $value['id_brand'],
                            'product'   => $value['name_brand'] . ' - ' . $value['product_code'] . ' - ' . $value['product_name'] . $variant,
                            'id_product_variant_group' => $value['id_product_variant_group']
                        ];
                    }
                }

                $data = array_merge($data, $formatted_product_variant);
            }
        } elseif ($post['get'] == 'Product Variant') {
            $data = ProductVariantGroup::where('product_variant_groups.id_product', $post['id_product'])
                    ->where('product_variant_groups.product_variant_group_visibility', 'Visible')
                    ->whereDoesntHave('product_variant_pivot.product_variant', function ($q) {
                        $q->where('product_variant_visibility', 'Hidden');
                    })
                    ->with(['product_variant_pivot'])
                    ->leftJoin('products', 'products.id_product', '=', 'product_variant_groups.id_product')
                    ->where('products.product_variant_status', '1')
                    ->get();

            $extra_modifier_product = ProductModifierGroupPivot::where('id_product', $post['id_product'])
                                        ->leftJoin('product_modifiers', 'product_modifiers.id_product_modifier_group', '=', 'product_modifier_group_pivots.id_product_modifier_group')
                                        ->where('product_modifiers.product_modifier_visibility', 'Visible')
                                        ->get();

            $extra_modifier_product_group = [];
            foreach ($extra_modifier_product as $val) {
                $extra_modifier_product_group[$val['id_product_modifier_group']][$val['id_product_modifier']] = $val['text_detail_trx'] ?? $val['text'];
            }

            if ($data) {
                $variant_data = [];
                foreach ($data as $key => $value) {
                    $variant_name   = $value->product_variant_pivot->pluck('product_variant_name')->toArray();
                    $variant_id     = $value->product_variant_pivot->pluck('id_product_variant')->toArray();
                    $extra_modifier_variant_group = [];
                    $name = implode(', ', $variant_name);

                    $extra_modifier_variant = ProductModifierGroupPivot::whereIn('id_product_variant', $variant_id)
                                        ->leftJoin('product_modifiers', 'product_modifiers.id_product_modifier_group', '=', 'product_modifier_group_pivots.id_product_modifier_group')
                                        ->where('product_modifiers.product_modifier_visibility', 'Visible')
                                        ->get();

                    foreach ($extra_modifier_variant as $val) {
                        $extra_modifier_variant_group[$val['id_product_modifier_group']][$val['id_product_modifier']] = $val['text_detail_trx'] ?? $val['text'];
                    }

                    $temp_data = [
                        'id_product_variant_group' => $value['id_product_variant_group'],
                        'product_variant_group_code' => $value['product_variant_group_code'],
                        'product_variant_group_name' => $name,
                        'extra_modifier_variant' => $extra_modifier_variant_group
                    ];
                    $variant_data[] = $temp_data;
                }

                $result = [];
                foreach ($variant_data as $variant) {
                    // apply extra modifier product
                    $new_data = [];
                    foreach ($extra_modifier_product_group as $id_extra_mod_product_group => $modifier_product) {
                        if (!empty($new_data)) {
                            $data = $new_data;
                            $new_data = [];
                            foreach ($modifier_product as $id_extra_mod_product => $mod_name) {
                                foreach ($data as $value) {
                                    $temp_data = [
                                        'id_product_variant_group' => $value['id_product_variant_group'] . '-' . $id_extra_mod_product,
                                        'product_variant_group_name' => $value['product_variant_group_name'] . ', ' . $mod_name
                                    ];
                                    $new_data[] = $temp_data;
                                }
                            }
                        } else {
                            foreach ($modifier_product as $id_extra_mod_product => $mod_name) {
                                $temp_data = [
                                    'id_product_variant_group' => $variant['id_product_variant_group'] . '-' . $id_extra_mod_product,
                                    'product_variant_group_name' => $variant['product_variant_group_name'] . ', ' . $mod_name
                                ];
                                $new_data[] = $temp_data;
                            }
                        }
                    }

                    // apply extra modifier variant
                    foreach ($variant['extra_modifier_variant'] as $id_extra_mod_variant_group => $modifier_variant) {
                        if (!empty($new_data)) {
                            $data = $new_data;
                            $new_data = [];
                            foreach ($modifier_variant as $id_extra_mod_variant => $mod_name) {
                                foreach ($data as $value) {
                                    $temp_data = [
                                        'id_product_variant_group' => $value['id_product_variant_group'] . '-' . $id_extra_mod_variant,
                                        'product_variant_group_name' => $value['product_variant_group_name'] . ', ' . $mod_name
                                    ];
                                    $new_data[] = $temp_data;
                                }
                            }
                        } else {
                            foreach ($modifier_variant as $id_extra_mod_variant => $mod_name) {
                                $temp_data = [
                                    'id_product_variant_group' => $variant['id_product_variant_group'] . '-' . $id_extra_mod_variant,
                                    'product_variant_group_name' => $variant['product_variant_group_name'] . ', ' . $mod_name
                                ];
                                $new_data[] = $temp_data;
                            }
                        }
                    }

                    $result = array_merge($result, $new_data);
                }

                $data = $result ?: $variant_data;
            }
        } elseif ($post['get'] == 'Outlet Group') {
            $data = OutletGroup::select('id_outlet_group', 'outlet_group_name', 'outlet_group_type')->orderBy('updated_at', 'desc');

            $data = $data->get()->toArray();
        } else {
            $data = 'null';
        }

        return response()->json($data);
    }

    public function delete(DeletePromoCampaignRequest $request)
    {
        $post = $request->json()->all();
        $user = auth()->user();
        $password = $request['password'];
        DB::beginTransaction();
        if (Hash::check($password, $user->password)) {
            $checkData = PromoCampaign::where('id_promo_campaign', '=', $post['id_promo_campaign'])->first();
            if ($checkData) {
                $delete = PromoCampaign::where('id_promo_campaign', '=', $post['id_promo_campaign'])->delete();
                DB::commit();
                return MyHelper::checkDelete($delete);
            } else {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['promo campaign not found']
                ]);
            }
        } else {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['unauthenticated']
            ]);
        }
    }

    public function validateCode(ValidateCode $request)
    {
        $id_user        = $request->user()->id;
        $phone          = $request->user()->phone;
        $device_id      = $request->device_id;
        $device_type    = $request->device_type;
        $id_outlet      = $request->id_outlet;

        $code = PromoCampaignPromoCode::where('promo_code', $request->promo_code)
                ->join('promo_campaigns', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_promo_codes.id_promo_campaign')
                ->where('step_complete', '=', 1)
                ->where(function ($q) {
                    $q->whereColumn('usage', '<', 'limitation_usage')
                        ->orWhere('code_type', 'Single');
                })
                ->first();

        if (!$code) {
            return [
                'status' => 'fail',
                'messages' => ['Promo code not valid']
            ];
        }
        $pct = new PromoCampaignTools();
        if (!$pct->validateUser($code->id_promo_campaign, $id_user, $phone, $device_type, $device_id, $errore, $code->id_promo_campaign_promo_code)) {
            return [
                'status' => 'fail',
                'messages' => $errore ?? ['Promo code not valid']
            ];
        }
        $errors = [];
        $trx = $request->item;

        // return $pct->getRequiredProduct($code->id_promo_campaign);
        return [$pct->validatePromo($code->id_promo_campaign, $id_outlet, $trx, $errors), $errors];
        if ($result = $pct->validatePromo($code->id_promo_campaign, $id_outlet, $trx, $errors)) {
            $code->load('promo_campaign');
            $result['promo_title'] = $code->promo_campaign->campaign_name;
            $result['promo_code'] = $request->promo_code;
        } else {
            $result = [
                'status' => 'fail',
                'messages' => $errors
            ];
            return $result;
        }
        return MyHelper::checkGet($result);
    }

    public function checkValid(ValidateCode $request)
    {
        $id_user        = $request->user()->id;
        $phone          = $request->user()->phone;
        $device_id      = $request->device_id;
        $device_type    = $request->device_type;
        $id_outlet      = $request->id_outlet;
        $ip             = $request->ip();
        $pct            = new PromoCampaignTools();

        // get data
        if ($request->promo_code && !$request->id_deals_user && !$request->id_subscription_user) {
            /* Check promo code*/
            $dataCheckPromoCode = [
                'id_user'    => $id_user,
                'device_id'  => $device_id,
                'promo_code' => $request->promo_code,
                'ip'         => $ip
            ];
            $checkFraud = app($this->fraud)->fraudCheckPromoCode($dataCheckPromoCode);
            if ($checkFraud['status'] == 'fail') {
                return $checkFraud;
            }
            /* End check promo code */

            // get data promo code, promo campaign, outlet, rule, and product
            $code = PromoCampaignPromoCode::where('promo_code', $request->promo_code)
                    ->join('promo_campaigns', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_promo_codes.id_promo_campaign')
                    ->where('step_complete', '=', 1)
                    ->where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->where('code_type', 'Multiple')
                                ->where(function ($q3) {
                                    $q3->whereColumn('usage', '<', 'code_limit')
                                        ->orWhere('code_limit', 0);
                                });
                        })
                        ->orWhere(function ($q2) {
                            $q2->where('code_type', 'Single')
                                ->where(function ($q3) {
                                    $q3->whereColumn('total_coupon', '>', 'used_code')
                                        ->orWhere('total_coupon', 0);
                                });
                        });
                    })
                    ->with([
                        'promo_campaign.promo_campaign_outlets',
                        'promo_campaign.brand',
                        'promo_campaign.promo_campaign_product_discount.product' => function ($q) {
                            $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                        },
                        'promo_campaign.promo_campaign_buyxgety_product_requirement.product' => function ($q) {
                            $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                        },
                        'promo_campaign.promo_campaign_tier_discount_product.product' => function ($q) {
                            $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                        },
                        'promo_campaign.promo_campaign_discount_bill_products.product' => function ($q) {
                            $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                        },
                        'promo_campaign.promo_campaign_product_discount_rules',
                        'promo_campaign.promo_campaign_tier_discount_rules',
                        'promo_campaign.promo_campaign_buyxgety_rules',
                        'promo_campaign.promo_campaign_discount_bill_rules',
                        'promo_campaign.promo_campaign_discount_delivery_rules',
                        'promo_campaign.promo_campaign_payment_method',
                        'promo_campaign.promo_campaign_shipment_method'
                    ])
                    ->first();
            if (!$code) {
                return [
                    'status' => 'fail',
                    'messages' => ['Promo tidak tersedia']
                ];
            }

            $msgUseIn = [
                'Consultation' => 'konsultasi',
                'Product' => 'produk'
            ];
            if ((empty($request->promo_use_in) && $code['promo_campaign']['promo_use_in'] == 'Consultation') || (!empty($request->promo_use_in) && $request->promo_use_in != $code['promo_campaign']['promo_use_in'])) {
                return [
                    'status' => 'fail',
                    'messages' => ['Promo hanya berlaku untuk transaksi ' . $msgUseIn[$code['promo_campaign']['promo_use_in']] ?? 'Produk']
                ];
            }

            if ($code['promo_campaign']['date_start'] > date('Y-m-d H:i:s')) {
                $date_start = MyHelper::dateFormatInd($code['promo_campaign']['date_start'], true, false) . ' pukul ' . date('H:i', strtotime($code['promo_campaign']['date_start']));
                return [
                    'status' => 'fail',
                    'messages' => ['Promo berlaku pada ' . $date_start]
                ];
            }

            if ($code['promo_campaign']['date_end'] < date('Y-m-d H:i:s')) {
                return [
                    'status' => 'fail',
                    'messages' => ['Promo telah berakhir']
                ];
            }

            if ($code->promo_campaign->promo_type == 'Referral') {
                $referer = UserReferralCode::where('id_promo_campaign_promo_code', $code->id_promo_campaign_promo_code)
                    ->join('users', 'users.id', '=', 'user_referral_codes.id_user')
                    ->where('users.is_suspended', '=', 0)
                    ->first();
                if (!$referer) {
                    return [
                        'status' => 'fail',
                        'messages' => ['Kode promo tidak ditemukan']
                    ];
                }
            }

            $code = $code->toArray();

            // check user
            if (!$pct->validateUser($code['id_promo_campaign'], $id_user, $phone, $device_type, $device_id, $errors, $code['id_promo_campaign_promo_code'])) {
                return [
                    'status' => 'fail',
                    'messages' => $errors ?? ['Promo tidak tersedia']
                ];
            }

            $query = $code;
            $id_brand = $query['id_brand'];
            $source = 'promo_campaign';
        } elseif (!$request->promo_code && $request->id_deals_user && !$request->id_subscription_user) {
            $deals = DealsUser::where('id_deals_user', '=', $request->id_deals_user)
                    ->whereIn('paid_status', ['Free', 'Completed'])
                    ->with([
                        'dealVoucher.deals.outlets_active',
                        'dealVoucher.deals.brand',
                        'dealVoucher.deals.deals_product_discount.product' => function ($q) {
                            $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                        },
                        'dealVoucher.deals.deals_tier_discount_product.product' => function ($q) {
                            $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                        },
                        'dealVoucher.deals.deals_buyxgety_product_requirement.product' => function ($q) {
                            $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                        },
                        'dealVoucher.deals.deals_discount_bill_products.product' => function ($q) {
                            $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                        },
                        'dealVoucher.deals.deals_product_discount_rules',
                        'dealVoucher.deals.deals_tier_discount_rules',
                        'dealVoucher.deals.deals_buyxgety_rules',
                        'dealVoucher.deals.deals_discount_bill_rules',
                        'dealVoucher.deals.deals_discount_delivery_rules',
                        'dealVoucher.deals.deals_payment_method',
                        'dealVoucher.deals.deals_shipment_method'
                    ])
                    ->first();

            if (!empty($deals['used_at'])) {
                return [
                    'status' => 'fail',
                    'messages' => ['Voucher sudah pernah digunakan']
                ];
            }

            if (!$deals) {
                return [
                    'status' => 'fail',
                    'messages' => ['Voucher tidak tersedia']
                ];
            }

            if ($deals['voucher_expired_at'] < date('Y-m-d H:i:s')) {
                return [
                    'status' => 'fail',
                    'messages' => ['Batas waktu penggunaan voucer sudah berakhir']
                ];
            }

            if ($deals['voucher_active_at'] > date('Y-m-d H:i:s') && !empty($deals['voucher_active_at'])) {
                $date_start = MyHelper::dateFormatInd($deals['voucher_active_at'], true, false) . ' pukul ' . date('H:i', strtotime($deals['voucher_active_at']));
                return [
                    'status' => 'fail',
                    'messages' => ['Voucer mulai berlaku pada ' . $date_start]
                ];
            }

            $deals = $deals->toArray();
            $query = $deals['deal_voucher'];
            $id_brand = $query['deals']['id_brand'];
            $source = 'deals';
        } elseif (!$request->promo_code && !$request->id_deals_user && $request->id_subscription_user) {
            $subs = app($this->subscription)->checkSubscription($request->id_subscription_user, 'outlet', 'product', 'product_detail', null, null, 'brand', 'promo_rule');

            if (!$subs) {
                return [
                    'status' => 'fail',
                    'messages' => ['Subscription tidak tersedia']
                ];
            }

            if ($subs['subscription_expired_at'] < date('Y-m-d H:i:s')) {
                return [
                    'status' => 'fail',
                    'messages' => ['Batas waktu penggunaan Subscription telah berakhir']
                ];
            }

            if ($subs['subscription_active_at'] > date('Y-m-d H:i:s') && !empty($subs['subscription_active_at'])) {
                $date_start = MyHelper::dateFormatInd($subs['subscription_active_at'], true, false) . ' pukul ' . date('H:i', strtotime($subs['subscription_active_at']));
                return [
                    'status' => 'fail',
                    'messages' => ['Subscription berlaku pada ' . $date_start]
                ];
            }

            if ($subs->subscription_user->subscription->daily_usage_limit) {
                $subs_voucher_today = SubscriptionUserVoucher::where('id_subscription_user', '=', $subs->id_subscription_user)
                                        ->whereDate('used_at', date('Y-m-d'))
                                        ->count();
                if ($subs_voucher_today >= $subs->subscription_user->subscription->daily_usage_limit) {
                    return [
                        'status' => 'fail',
                        'messages' => ['Subscription telah mencapai limit penggunaan harian']
                    ];
                }
            }
            $subs = $subs->toArray();
            $query = $subs['subscription_user'];
            $id_brand = $subs['subscription_user']['subscription']['id_brand'];
            $source = 'subscription';
        } else {
            return [
                'status' => 'fail',
                'messages' => ['Can only use Subscription, Promo Code, or Voucher']
            ];
        }

        $getProduct = $this->getProduct($source, $query[$source]);

        $desc = $this->getPromoDescription($source, $query[$source], $getProduct['product'] ?? '');

        $errors = [];

        // check outlet
        if (isset($id_outlet)) {
            if (!$pct->checkOutletRule($id_outlet, $query[$source]['is_all_outlet'] ?? 0, $query[$source][$source . '_outlets'] ?? $query[$source]['outlets_active'], $id_brand ?? null)) {
                    return [
                    'status' => 'fail',
                    'messages' => ['Promo tidak berlaku di outlet ini']
                    ];
            }
        }

        $result['title']                = $query[$source]['promo_title'] ?? $query[$source]['deals_title'] ?? $query[$source]['subscription_title'] ?? '';
        $result['description']          = $desc;
        $result['promo_code']           = $request->promo_code;
        $result['id_deals_user']        = $request->id_deals_user;
        $result['id_subscription_user'] = $request->id_subscription_user;

        $result = MyHelper::checkGet($result);

        // check item
        if (!empty($request->item)) {
            $bearer = $request->header('Authorization');

            if ($bearer == "") {
                return [
                    'status' => 'fail',
                    'messages' => ['Promo code not valid']
                ];
            }
            $post = $request->json()->all();
            $post['log_save'] = 1;
            $custom_request = new \Modules\Transaction\Http\Requests\CheckTransaction();
            $custom_request = $custom_request
                            ->setJson(new \Symfony\Component\HttpFoundation\ParameterBag($post))
                            ->merge($post)
                            ->setUserResolver(function () use ($request) {
                                return $request->user();
                            });
            $trx =  app($this->online_transaction)->checkTransaction($custom_request);
            // $trx = MyHelper::postCURLWithBearer('api/transaction/check', $post, $bearer);

            foreach ($trx['result'] as $key => $value) {
                $result['result'][$key] = $value;
            }
            $result['messages'] = $trx['messages'];
            $result['promo_error'] = $trx['promo_error'];
        }

        // save used promo
        if ($source == 'deals') {
            $change_used_voucher = app($this->promo)->usePromo($source, $request->id_deals_user);
            if (($change_used_voucher['status'] ?? false) == 'success') {
                $result['result']['webview_url'] = $change_used_voucher['webview_url'];
                $result['result']['webview_url_v2'] = $change_used_voucher['webview_url_v2'];
            } else {
                return [
                    'status' => 'fail',
                    'messages' => ['Something went wrong']
                ];
            }
        } elseif ($source == 'subscription') {
            $change_used_voucher = app($this->promo)->usePromo($source, $subs['id_subscription_user_voucher'], 'use', $subs);

            if (($change_used_voucher['status'] ?? false) == 'success') {
                $result['result']['webview_url'] = $change_used_voucher['webview_url'];
            } else {
                return [
                    'status' => 'fail',
                    'messages' => ['Something went wrong']
                ];
            }
        } else {
            $change_used_voucher = app($this->promo)->usePromo($source, $query['id_promo_campaign_promo_code']);
            if (!$change_used_voucher) {
                return [
                    'status' => 'fail',
                    'messages' => ['Something went wrong']
                ];
            }
        }

        return $result;
    }

    public function getProduct($source, $query, $id_outlet = null)
    {
        $default_product = $query['product_rule'] === 'and' ? 'semua produk tertentu' : 'produk tertentu';

        if ($source == 'subscription') {
            if (!empty($query['is_all_product']) || empty($query['subscription_products'])) {
                $applied_product = '*';
                $product = $default_product;
            } elseif (!empty($query['subscription_products'])) {
                if (!$query['id_brand']) {
                    $brand = BrandProduct::join('brand_outlet', 'brand_product.id_brand', '=', 'brand_outlet.id_brand')
                            ->where('brand_outlet.id_outlet', $id_outlet)
                            ->where('brand_product.id_product', $query['subscription_products'][0]['id_product'])
                            ->whereNotNull('brand_product.id_product_category')
                            ->first();
                }

                $applied_product = $query['subscription_products'];
                // $applied_product[0]['id_brand'] = $query['id_brand'] ?? $brand['id_brand'];
                $applied_product[0]['product_code'] = $applied_product[0]['product']['product_code'];

                $product_total = count($query['subscription_products']);
                if ($product_total == 1) {
                    $product = $query['subscription_products'][0]['product']['product_name'] ?? $default_product;
                    if (isset($query['subscription_products'][0]['id_product_variant_group'])) {
                        $variant = ProductVariantPivot::join('product_variants as pv', 'pv.id_product_variant', 'product_variant_pivot.id_product_variant')
                                    ->where('product_variant_pivot.id_product_variant_group', $query['subscription_products'][0]['id_product_variant_group'])
                                    ->pluck('product_variant_name')->toArray();
                        $variant_text = implode(' ', $variant);
                        $product .= ' ' . $variant_text;
                    }
                } else {
                    $product = $default_product;
                }
            } else {
                $applied_product = [];
                $product = [];
            }
        } else {
            if (!is_array($query)) {
                $query = $query->load(
                    $source . '_product_discount.product',
                    $source . '_tier_discount_product.product',
                    $source . '_buyxgety_product_requirement.product',
                    $source . '_discount_bill_products.product'
                )->toArray();
            }

            if (
                ($query[$source . '_product_discount_rules']['is_all_product'] ?? false) == 1
                || ($query['promo_type'] ?? false) == 'Referral'
                || ($query[$source . '_discount_bill_rules']['is_all_product'] ?? false) == 1
                || ($query[$source . '_tier_discount_rules'][0]['is_all_product'] ?? false) == 1
                || ($query[$source . '_buyxgety_rules'][0]['is_all_product'] ?? false) == 1
            ) {
                $applied_product = '*';
                $product = $default_product;
            } else {
                $applied_product = $query[$source . '_product_discount'] ?: $query[$source . '_tier_discount_product'] ?: $query[$source . '_buyxgety_product_requirement'] ?: $query[$source . '_discount_bill_products'] ?: [];

                if (empty($applied_product)) {
                    $product = null;
                } elseif (count($applied_product) == 1) {
                    $product = $applied_product[0]['product']['product_name'] ?? $default_product;
                    if (isset($applied_product[0]['id_product_variant_group'])) {
                        $variant = ProductVariantPivot::join('product_variants as pv', 'pv.id_product_variant', 'product_variant_pivot.id_product_variant')
                                    ->where('product_variant_pivot.id_product_variant_group', $applied_product[0]['id_product_variant_group'])
                                    ->pluck('product_variant_name')->toArray();
                        $variant_text = implode(' ', $variant);
                        $product .= ' ' . $variant_text;
                    }
                } else {
                    $product = $default_product;
                }
            }

            /*
            if ( ($query[$source.'_product_discount_rules']['is_all_product']??false) == 1 || ($query['promo_type']??false) == 'Referral')
            {
                $applied_product = '*';
                $product = $default_product;
            }
            elseif ( !empty($query[$source.'_product_discount']) )
            {
                $applied_product = $query[$source.'_product_discount'];
                if (count($applied_product) == 1) {
                    $product = $applied_product[0]['product']['product_name'] ?? $default_product;
                }else{
                    $product = $default_product;
                }
            }
            elseif ( !empty($query[$source.'_tier_discount_product']) )
            {
                $applied_product = $query[$source.'_tier_discount_product'];
                if (count($applied_product) == 1) {
                    $product = $applied_product[0]['product']['product_name'] ?? $default_product;
                }else{
                    $product = $default_product;
                }
            }
            elseif ( !empty($query[$source.'_buyxgety_product_requirement']) )
            {
                $applied_product = $query[$source.'_buyxgety_product_requirement'];
                if (count($applied_product) == 1) {
                    $product = $applied_product[0]['product']['product_name'] ?? $default_product;
                }else{
                    $product = $default_product;
                }
            }
            elseif ( !empty($query[$source.'_discount_bill_rules']) )
            {
                if ($query[$source.'_discount_bill_rules']['is_all_product'] === 0) {
                    $applied_product = $query[$source.'_discount_bill_products'];
                    if (count($applied_product) == 1) {
                        $product = $applied_product[0]['product']['product_name'] ?? $default_product;
                    }else{
                        $product = $default_product;
                    }
                }
                else{
                    $applied_product = '*';
                    $product = $default_product;
                }
            }
            else
            {
                $applied_product = [];
                $product = [];
            }*/
        }

        $result = [
            'applied_product' => $applied_product,
            'product' => $product
        ];
        return $result;
    }

    public function getPromoDescription($source, $query, $product, $use_global = false)
    {
        if (!empty($query['promo_description']) && !$use_global) {
            return $query['promo_description'];
        }

        $brand = $query['brand']['name_brand'] ?? null;

        $payment_text = null;
        if (!empty($query[$source . '_payment_method']) && $query['is_all_payment'] != 1) {
            $available_payment = config('payment_method');
            $payment_list = [];
            foreach ($available_payment as $key => $value) {
                $payment_list[$value['payment_method']] = $value['text'];
            }
            $payment_text = '';
            $payment_count  = count($query[$source . '_payment_method']);
            $i = 1;
            foreach ($query[$source . '_payment_method'] as $key => $value) {
                if ($i == 1) {
                    $payment_text .= $payment_list[$value['payment_method']];
                } elseif ($i == $payment_count) {
                    $payment_text .= ' maupun ' . $payment_list[$value['payment_method']];
                } else {
                    $payment_text .= ', ' . $payment_list[$value['payment_method']];
                }

                $i++;
            }
            if ($payment_text) {
                $payment_text = 'berlaku untuk pembayaran menggunakan ' . $payment_text;
            }
        } else {
            // $payment_text = 'semua metode pembayaran';
            $payment_text = null;
        }

        $shipment_text = null;
        if (!empty($query[$source . '_shipment_method']) && $query['is_all_shipment'] != 1) {
            $online_trx = app($this->online_transaction);
            $shipment_list = array_column($query[$source . '_shipment_method'], 'shipment_method');

            $shipment_list = array_flip($shipment_list);
            if (isset($shipment_list['GO-SEND'])) {
                $shipment_list['gosend'] = $shipment_list['GO-SEND'];
                unset($shipment_list['GO-SEND']);
            }
            if (isset($shipment_list['Pickup Order'])) {
                $temp['Pick Up'] = $shipment_list['Pickup Order'];
                unset($shipment_list['Pickup Order']);
                $shipment_list = $temp  + $shipment_list;
            }

            $shipment_list = array_flip($shipment_list);

            $setting_shipment_list =  $online_trx->listAvailableDelivery(WeHelpYou::listDeliveryRequest())['result']['delivery'] ?? [];
            $setting_shipment_list =  array_column($setting_shipment_list, 'code');

            $selected_shipment = [];
            if (array_diff($setting_shipment_list, $shipment_list)) {
                $shipment_list = array_map(function ($shipment) use ($online_trx) {
                    return $online_trx->getCourierName($shipment);
                }, $shipment_list);
            } else {
                $shipment_list[] = 'Delivery';
                $shipment_list = array_diff($shipment_list, $setting_shipment_list);
            }

            $shipment_text = '';
            $shipment_count = count($shipment_list);
            $i = 1;
            foreach ($shipment_list as $key => $value) {
                if ($i == 1) {
                    $shipment_text .= $value;
                } elseif ($i == $shipment_count) {
                    $shipment_text .= ' maupun ' . $value;
                } else {
                    $shipment_text .= ', ' . $value;
                }
                $i++;
            }

            if ($shipment_text) {
                $shipment_text = 'berlaku untuk ' . $shipment_text;
            }
        } else {
            // $shipment_text = 'semua tipe order';
            $shipment_text = null;
        }

        $global_text = '';
        if ($source == 'promo_campaign') {
            $promo = 'Kode Promo';
        } elseif ($source == 'subscription') {
            $promo = 'Subscription';
        } elseif ($source == 'deals' || $source == 'deals_promotion') {
            $promo = 'Voucher';
        }
        if (isset($payment_text) && isset($shipment_text)) {
            $global_text .= $promo . ' ' . '%shipment_text% dan %payment_text%';
        } elseif (isset($payment_text)) {
            $global_text .= $promo . ' ' . '%payment_text%';
        } elseif (isset($shipment_text)) {
            $global_text .= $promo . ' ' . '%shipment_text%';
        }

        if ($source == 'subscription') {
            if (!empty($query['subscription_voucher_percent'])) {
                $discount = 'Percent';
            } else {
                $discount = 'Nominal';
            }

            if (!empty($query['subscription_voucher_percent'])) {
                $discount = ($query['subscription_voucher_percent'] ?? 0) . '%';
            } else {
                $discount = 'Rp ' . number_format(($query['subscription_voucher_nominal'] ?? 0), 0, ',', '.');
            }

            if ($query['subscription_discount_type'] == 'discount_delivery') {
                $desc = 'Diskon ongkos kirim %discount%';
            } else {
                // $desc = Setting::where('key', '=', $key)->first()['value']??$key_null;
                $desc = 'Diskon %discount% untuk pembelian %product%';
            }

            $desc = MyHelper::simpleReplace($desc, ['discount' => $discount, 'product' => $product, 'brand' => $brand]);
        } else {
            if ($query['promo_type'] == 'Product discount') {
                $discount = $query[$source . '_product_discount_rules']['discount_type'] ?? 'Nominal';
                $qty = $query[$source . '_product_discount_rules']['max_product'] ?? 0;

                if ($discount == 'Percent') {
                    $discount = ($query[$source . '_product_discount_rules']['discount_value'] ?? 0) . '%';
                } else {
                    $discount = 'Rp ' . number_format(($query[$source . '_product_discount_rules']['discount_value'] ?? 0), 0, ',', '.');
                }

                if (empty($qty)) {
                    $key = 'description_product_discount_brand_no_qty';
                    // $key_null = 'Anda berhak mendapatkan potongan %discount% untuk pembelian %product%';
                    $key_null = 'Diskon %discount% untuk pembelian %product%';
                } else {
                    $key = 'description_product_discount_brand';
                    // $key_null = 'Anda berhak mendapatkan potongan %discount% untuk pembelian %product%. Maksimal %qty% buah untuk setiap produk';
                    $key_null = 'Diskon %discount% untuk pembelian %product%. Maksimal %qty% item untuk setiap produk';
                }

                // $desc = Setting::where('key', '=', $key)->first()['value']??$key_null;
                $desc = $key_null;

                $desc = MyHelper::simpleReplace($desc, ['discount' => $discount, 'product' => $product, 'qty' => $qty, 'brand' => $brand]);
            } elseif ($query['promo_type'] == 'Tier discount') {
                $min_qty = null;
                $max_qty = null;
                $discount_rule = [];

                foreach ($query[$source . '_tier_discount_rules'] as $key => $rule) {
                    $min_req = $rule['min_qty'];
                    $max_req = $rule['max_qty'];

                    if ($min_qty === null || $rule['min_qty'] < $min_qty) {
                        $min_qty = $min_req;
                        $min_rule = $rule;
                    }
                    if ($max_qty === null || $rule['max_qty'] > $max_qty) {
                        $max_qty = $max_req;
                        $max_rule = $rule;
                    }

                    if ($rule['discount_type'] == 'Percent') {
                        $discount_rule[] = ($rule['discount_value'] ?? 0) . '%';
                    } else {
                        $discount_rule[] = 'Rp ' . number_format(($rule['discount_value'] ?? 0), 0, ',', '.');
                    }
                }

                // if single rule
                if (count($discount_rule) == 1) {
                    $discount = $discount_rule[0];
                } else {
                    $discount_first = reset($discount_rule);
                    $discount_last  = end($discount_rule);
                    $discount = $discount_first . ' sampai ' . $discount_last;
                    if ($discount_first == $discount_last) {
                        $discount = $discount_first;
                    }
                }

                $key = 'description_tier_discount_brand';
                // $key_null = 'Anda berhak mendapatkan potongan setelah melakukan pembelian %product% sebanyak %minmax%';
                $key_null = 'Diskon %discount% setelah pembelian minimal %min_qty% %product%';

                $minmax = $min_qty != $max_qty ? "$min_qty - $max_qty" : $min_qty;
                // $desc = Setting::where('key', '=', $key)->first()['value']??$key_null;
                $desc = $key_null;

                $desc = MyHelper::simpleReplace($desc, ['product' => $product, 'minmax' => $minmax, 'brand' => $brand, 'min_qty' => $min_qty, 'discount' => $discount]);
            } elseif ($query['promo_type'] == 'Buy X Get Y') {
                $min_qty = null;
                $max_qty = null;
                $discount_rule = [];
                $product_benefit = null;
                $promo_rules = $query[$source . '_buyxgety_rules'];
                foreach ($promo_rules as $key => $rule) {
                    $min_req = $rule['min_qty_requirement'];
                    $max_req = $rule['max_qty_requirement'];

                    if ($min_qty === null || $rule['min_qty_requirement'] < $min_qty) {
                        $min_qty = $min_req;
                    }
                    if ($max_qty === null || $rule['max_qty_requirement'] > $max_qty) {
                        $max_qty = $max_req;
                    }

                    if ($rule['discount_type'] == 'percent') {
                        if ($rule['discount_value'] == 100) {
                            $discount_rule[] = 'gratis ' . $rule['benefit_qty'] . ' item';
                        } else {
                            $discount_rule[] = ($rule['discount_value'] ?? 0) . '%';
                        }
                    } else {
                        $discount_rule[] = 'Rp ' . number_format(($rule['discount_value'] ?? 0), 0, ',', '.');
                    }

                    $product_benefit[$rule['id_brand']][$rule['benefit_id_product']][$rule['id_product_variant_group']] = [
                        'id_brand' => $rule['id_brand'],
                        'id_product' => $rule['benefit_id_product'],
                        'id_product_variant_group' => $rule['id_product_variant_group']
                    ];
                }

                $discount = '';
                if (count($promo_rules) == 1) { // only 1 rule available
                    $product_benefit = Product::where('id_product', $promo_rules[0]['benefit_id_product'])->select('product_name')->first();
                    $variant = ProductVariantPivot::join('product_variants', 'product_variants.id_product_variant', 'product_variant_pivot.id_product_variant')
                                ->where('id_product_variant_group', $promo_rules[0]['id_product_variant_group'])->pluck('product_variant_name');

                    $product_benefit = $product_benefit['product_name'] ?? '';
                    if ($variant->isNotEmpty()) {
                        $variant = implode(' ', $variant->toArray());
                        $product_benefit = $product_benefit . ' ' . $variant;
                    }

                    if ($promo_rules[0]['discount_type'] == 'percent') {
                        if ($promo_rules[0]['discount_value'] == 100) {
                            $discount = 'Gratis ' . $promo_rules[0]['benefit_qty'];
                        } else {
                            $discount = 'Diskon ' . $promo_rules[0]['discount_value'] . '%';
                        }
                    } else {
                        $discount = 'Diskon ' . MyHelper::requestNumber($promo_rules[0]['discount_value'], '_CURRENCY');
                    }

                    $req_product = $query[$source . '_buyxgety_product_requirement'];
                    if (
                        count($req_product) == 1
                        && count($promo_rules) == 1
                        && $req_product[0]['id_product'] == $promo_rules[0]['benefit_id_product']
                        && (($promo_rules[0]['discount_value'] != 100 && $promo_rules[0]['discount_type'] == 'percent') || $promo_rules[0]['discount_type'] != 'percent')
                    ) {
                        $product_benefit = $product_benefit . ' selanjutnya';
                    }

                    $desc = '%discount% %product_benefit% setelah pembelian minimal %min_qty% %product%';
                } else {
                    // multi rule
                    $product_benefit = 'product tertentu';
                    $desc = 'Diskon untuk %product_benefit% setelah pembelian minimal %min_qty% %product%';
                }

                /*$discount = '';
                $discount_count = count($discount_rule);
                $i = 1;
                foreach ($discount_rule as $key => $value) {
                    if ($i == 1) {
                        $discount .= $value;
                    }
                    elseif ($i == $discount_count) {
                        $discount .= ' atau '.$value;
                    }
                    else {
                        $discount .= ', '.$value;
                    }

                    $i++;
                }*/

                $key = 'description_buyxgety_discount_brand';
                // $key_null = 'Anda berhak mendapatkan potongan setelah melakukan pembelian %product% sebanyak %minmax%';
                // $key_null = 'Diskon %discount% untuk %product_benefit% setelah pembelian minimal %min_qty% %product%';
                $minmax = $min_qty != $max_qty ? "$min_qty - $max_qty" : $min_qty;
                // $desc = Setting::where('key', '=', $key)->first()['value']??$key_null;
                // $desc = $key_null;

                $desc = MyHelper::simpleReplace($desc, ['product' => $product, 'minmax' => $minmax, 'brand' => $brand, 'min_qty' => $min_qty, 'discount' => $discount, 'product_benefit' => $product_benefit]);
            } elseif ($query['promo_type'] == 'Discount bill') {
                $discount = $query[$source . '_discount_bill_rules']['discount_type'] ?? 'Nominal';
                $max_percent_discount = $query[$source . '_discount_bill_rules']['max_percent_discount'] ?? 0;

                if ($discount == 'Percent') {
                    $discount = ($query[$source . '_discount_bill_rules']['discount_value'] ?? 0) . '%';
                } else {
                    $discount = 'Rp ' . number_format(($query[$source . '_discount_bill_rules']['discount_value'] ?? 0), 0, ',', '.');
                }

                $text = 'Diskon %discount% untuk pembelian %product%';

                $desc = MyHelper::simpleReplace($text, ['discount' => $discount, 'product' => $product]);
            } elseif ($query['promo_type'] == 'Discount delivery') {
                $discount = $query[$source . '_discount_delivery_rules']['discount_type'] ?? 'Nominal';
                $max_percent_discount = $query[$source . '_discount_delivery_rules']['max_percent_discount'] ?? 0;

                if ($discount == 'Percent') {
                    $discount = ($query[$source . '_discount_delivery_rules']['discount_value'] ?? 0) . '%';
                } else {
                    $discount = 'Rp ' . number_format(($query[$source . '_discount_delivery_rules']['discount_value'] ?? 0), 0, ',', '.');
                }

                $text = 'Diskon ongkos kirim %discount%';

                $desc = MyHelper::simpleReplace($text, ['discount' => $discount]);
            } else {
                $key = null;
                $desc = null;
            }
        }

        if ($desc) {
            $text = $desc;
            if (substr($text, -1) != '.') {
                $separator = '. ';
                $global_text = ucfirst($global_text);
            } else {
                $separator = ' ' ;
            }

            if ($global_text) {
                $text = $text . $separator . $global_text;
            }

            $desc = MyHelper::simpleReplace($text, ['payment_text' => $payment_text, 'shipment_text' => $shipment_text]);
        } else {
            $desc = 'no description';
        }

        return $desc;
    }

    public function checkPromoCode($promo_code, $outlet = null, $promo_rule = null, $id_promo_campaign_promo_code = null, $brand = null)
    {
        if (!empty($id_promo_campaign_promo_code)) {
            $code = PromoCampaignPromoCode::where('id_promo_campaign_promo_code', $id_promo_campaign_promo_code);
        } else {
            $code = PromoCampaignPromoCode::where('promo_code', $promo_code);
        }

        $code = $code->join('promo_campaigns', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_promo_codes.id_promo_campaign')
                    ->where('step_complete', '=', 1)
                    ->where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->where('code_type', 'Multiple')
                                ->where(function ($q3) {
                                    $q3->whereColumn('usage', '<', 'code_limit')
                                        ->orWhere('code_limit', 0);
                                });
                        })
                        ->orWhere(function ($q2) {
                            $q2->where('code_type', 'Single')
                                ->where(function ($q3) {
                                    $q3->whereColumn('total_coupon', '>', 'used_code')
                                        ->orWhere('total_coupon', 0);
                                });
                        });
                    });

        if (!empty($outlet)) {
            $code = $code->with(['promo_campaign.promo_campaign_outlets', 'promo_campaign.outlet_groups']);
        }

        if (!empty($promo_rule)) {
            $code = $code->with([
                    'promo_campaign.promo_campaign_product_discount',
                    'promo_campaign.promo_campaign_buyxgety_product_requirement',
                    'promo_campaign.promo_campaign_tier_discount_product',
                    'promo_campaign.promo_campaign_product_discount_rules',
                    'promo_campaign.promo_campaign_tier_discount_rules',
                    'promo_campaign.promo_campaign_buyxgety_rules',
                    'promo_campaign.promo_campaign_product_discount.product' => function ($q) {
                        $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                    },
                    'promo_campaign.promo_campaign_buyxgety_product_requirement.product' => function ($q) {
                        $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                    },
                    'promo_campaign.promo_campaign_tier_discount_product.product' => function ($q) {
                        $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                    },
                    'promo_campaign.promo_campaign_discount_bill_rules',
                    'promo_campaign.promo_campaign_discount_bill_products.product' => function ($q) {
                        $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                    },
                    'promo_campaign.promo_campaign_discount_delivery_rules',
                    'promo_campaign.promo_campaign_payment_method',
                    'promo_campaign.promo_campaign_shipment_method'
                ]);
        }

        if (!empty($brand)) {
            $code = $code->with(['promo_campaign.brand']);
        }

        $code = $code->first();

        return $code;
    }

    public function checkVoucher($id_deals_user = null, $outlet = null, $promo_rule = null, $brand = null)
    {
        $deals = new DealsUser();

        if (!empty($id_deals_user)) {
            $deals = $deals->where('id_deals_user', '=', $id_deals_user)->where('id_user', '=', auth()->user()->id);
            $deals = $deals->whereIn('paid_status', ['Free', 'Completed'])
                        ->whereNull('used_at')
                        ->where('voucher_expired_at', '>=', date('Y-m-d H:i:s'))
                        ->where(function ($q) {
                            $q->where('voucher_active_at', '<=', date('Y-m-d H:i:s'))
                                ->orWhereNull('voucher_active_at');
                        });
        } else {
            $deals = $deals->where('id_user', '=', auth()->user()->id)->where('is_used', '=', 1);
            $deals = $deals->with(['dealVoucher.deal']);
        }



        if (!empty($outlet)) {
            $deals = $deals->with(['dealVoucher.deals.outlets_active', 'dealVoucher.deals.outlet_groups']);
        }

        if (!empty($promo_rule)) {
            $deals = $deals->with([
                    'dealVoucher.deals.outlets_active',
                    'dealVoucher.deals.deals_product_discount',
                    'dealVoucher.deals.deals_tier_discount_product',
                    'dealVoucher.deals.deals_buyxgety_product_requirement',
                    'dealVoucher.deals.deals_product_discount_rules',
                    'dealVoucher.deals.deals_tier_discount_rules',
                    'dealVoucher.deals.deals_buyxgety_rules',
                    'dealVoucher.deals.deals_product_discount.product' => function ($q) {
                        $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                    },
                    'dealVoucher.deals.deals_tier_discount_product.product' => function ($q) {
                        $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                    },
                    'dealVoucher.deals.deals_buyxgety_product_requirement.product' => function ($q) {
                        $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                    },
                    'dealVoucher.deals.deals_discount_bill_rules',
                    'dealVoucher.deals.deals_discount_bill_products.product' => function ($q) {
                        $q->select('id_product', 'id_product_category', 'product_code', 'product_name');
                    },
                    'dealVoucher.deals.deals_discount_delivery_rules',
                    'dealVoucher.deals.deals_payment_method',
                    'dealVoucher.deals.deals_shipment_method'
                ]);
        }

        if (!empty($brand)) {
            $deals = $deals->with(['dealVoucher.deals.brand']);
        }

        $deals = $deals->first();

        return $deals;
    }

    public function promoError($source, $errore = null, $errors = null, $errorProduct = 0)
    {
        if ($source == 'transaction') {
            $setting = ['promo_error_title', 'promo_error_ok_button', 'promo_error_ok_button_v2', 'promo_error_cancel_button'];
            $getData = Setting::whereIn('key', $setting)->get()->toArray();
            $data = [];
            $result['all_item'] = false;
            foreach ($getData as $key => $value) {
                $data[$key] = $value;
            }

            if ($errorProduct == 1 || $errorProduct === 'all') {
                $result['button_ok'] = $data['promo_error_ok_button'] ?? 'Tambah item';
            } else {
                $result['button_ok'] = $data['promo_error_ok_button_v2'] ?? 'Ok';
            }
            $result['title'] = $data['promo_error_title'] ?? 'Promo tidak berlaku';
            $result['button_cancel'] = $data['promo_error_cancel_button'] ?? 'Hapus promo';
            $result['product_label'] = "";
            $result['product'] = null;

            if ($errorProduct === 'all') {
                $result['all_item'] = true;
            }
        } else {
            return null;
        }

        $result['message'] = [];
        if (isset($errore)) {
            foreach ($errore as $key => $value) {
                array_push($result['message'], $value);
            }
        }
        if (isset($errors)) {
            foreach ($errors as $key => $value) {
                array_push($result['message'], $value);
            }
        }

        return $result;
    }


    public function addReport($id_promo_campaign, $id_promo_campaign_promo_code, $id_transaction, $id_outlet, $device_id, $device_type, $id_transaction_group)
    {
        $data = [
            'id_promo_campaign_promo_code'  => $id_promo_campaign_promo_code,
            'id_promo_campaign' => $id_promo_campaign,
            'id_transaction'    => $id_transaction,
            'id_transaction_group'  => $id_transaction_group,
            'id_outlet'         => $id_outlet,
            'device_id'         => $device_id,
            'device_type'       => $device_type,
            'user_name'         => Auth()->user()->name,
            'user_phone'        => Auth()->user()->phone,
            'id_user'           => Auth()->user()->id
        ];

        $insert = PromoCampaignReport::create($data);

        if (!$insert) {
            return false;
        }

        $used_code = PromoCampaignReport::where('id_promo_campaign', $id_promo_campaign)->distinct()->count('id_transaction_group');
        $update = PromoCampaign::where('id_promo_campaign', $id_promo_campaign)->update(['used_code' => $used_code]);

        if (!$update) {
            return false;
        }

        $usage_code = PromoCampaignReport::where('id_promo_campaign_promo_code', $id_promo_campaign_promo_code)->distinct()->count('id_transaction_group');
        $update = PromoCampaignPromoCode::where('id_promo_campaign_promo_code', $id_promo_campaign_promo_code)->update(['usage' => $usage_code]);

        if (!$update) {
            return false;
        }

        return true;
    }

    public function deleteReport($id_transaction, $id_promo_campaign_promo_code)
    {
        $getReport = PromoCampaignReport::with('promo_campaign')
                        ->where('id_promo_campaign_promo_code', $id_promo_campaign_promo_code)
                        ->where('id_transaction', '=', $id_transaction)
                        ->first();

        if ($getReport) {
            $delete = PromoCampaignReport::where('id_transaction', '=', $id_transaction)
                        ->where('id_promo_campaign_promo_code', $id_promo_campaign_promo_code)
                        ->delete();

            if ($delete) {
                $get_code = PromoCampaignPromoCode::where('id_promo_campaign_promo_code', $id_promo_campaign_promo_code)->first();
                $update = PromoCampaignPromoCode::where('id_promo_campaign_promo_code', $id_promo_campaign_promo_code)->update(['usage' => $get_code->usage - 1]);

                if ($update) {
                    $update = PromoCampaign::where('id_promo_campaign', '=', $getReport['id_promo_campaign'])->update(['used_code' => $getReport->promo_campaign->used_code - 1]);

                    if ($update) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

        return true;
    }

    public function exportCreate(Request $request)
    {
        $post   = $request->json()->all();
        $now    = date("Y-m-d H:i:s");
        $post['export_date'] = $now;
        $data   = [
            'id_promo_campaign' => $post['id_promo_campaign']
        ];

        $update = PromoCampaign::where('id_promo_campaign', $post['id_promo_campaign'])
                ->update([
                    'export_status' => 'Running',
                    'export_date'   => $now
                ]);

        if ($update) {
            ExportPromoCodeJob::dispatch($data)->allOnConnection('database');
        }

        return response()->json(MyHelper::checkUpdate($update));
    }

    public function exportPromoCode($filter)
    {
        $code = $this->getPromoCode($filter['id_promo_campaign'], $filter['status']);

        foreach ($code->cursor() as $value) {
            $data['promo_code'] = $value['promo_code'];

            if ($filter['status'] == 'used') {
                $data['usage'] = $value['usage'];
            }

            yield $data;
        }
    }

    public function getPromoCode($id_promo_campaign, $status)
    {
        $code = PromoCampaignPromoCode::where('id_promo_campaign', $id_promo_campaign);

        if ($status == 'used') {
            $code->where('usage', '!=', '0');
        } elseif ($status == 'unused') {
            $code->where('usage', '=', '0');
        }

        return $code;
    }

    public function actionExport(Request $request)
    {
        $post = $request->json()->all();
        $action = $post['action'];
        $id_promo_campaign = $post['id_promo_campaign'];

        if ($action == 'download') {
            $data = PromoCampaign::where('id_promo_campaign', $id_promo_campaign)->first();
            if (!empty($data)) {
                $data['export_url'] = config('url.storage_url_api') . $data['export_url'];
            }
            return response()->json(MyHelper::checkGet($data));
        } elseif ($action == 'deleted') {
            $data = PromoCampaign::where('id_promo_campaign', $id_promo_campaign)->first();
            $file = public_path() . '/' . $data['export_url'];
            if (config('configs.STORAGE') == 'local') {
                $delete = File::delete($file);
            } else {
                $delete = MyHelper::deleteFile($file);
            }

            if ($delete) {
                $update = PromoCampaign::where('id_promo_campaign', $id_promo_campaign)->update(['export_status' => 'Deleted']);
                return response()->json(MyHelper::checkUpdate($update));
            } else {
                return response()->json(['status' => 'fail', 'messages' => ['failed to delete file']]);
            }
        }
    }

    public function createShipmentRule($source, $id_table, $id_post, $post)
    {
        if ($source == 'promo_campaign') {
            $table_shipment = new PromoCampaignShipmentMethod();
            $table_promo = new PromoCampaign();
        } elseif ($source == 'deals') {
            $table_shipment = new DealsShipmentMethod();
            $table_promo = new Deal();
        } elseif ($source == 'deals_promotion') {
            $table_shipment = new DealsPromotionShipmentMethod();
            $table_promo = new DealsPromotionTemplate();
            $id_table = 'id_deals';
        }

        $table_shipment::where($id_table, '=', $id_post)->delete();

        $post['shipment_method'] = $post['shipment_method'] ?? [];

        if ($post['filter_shipment'] == 'all_shipment') {
            try {
                if ($source == 'deals_promotion') {
                    $id_table = 'id_deals_promotion_template';
                }
                $table_promo::where($id_table, '=', $id_post)->update(['is_all_shipment' => 1]);
                $result = ['status'  => 'success'];
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Create Shipment Rule Failed'
                ];
                return $result;
            }
        } else {
            $data_shipment = [];
            foreach ($post['shipment_method'] as $key => $value) {
                if ($value == 'Pickup Order' && $post['promo_type'] == 'Discount delivery') {
                    continue;
                }
                $temp_data = [
                    $id_table => $id_post,
                    'shipment_method' => $value,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                $data_shipment[] = $temp_data;
            }

            if (empty($data_shipment)) {
                if ($post['promo_type'] != 'Discount delivery') {
                    $delivery_pickup = [
                        $id_table => $id_post,
                        'shipment_method' => 'Pickup Order',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    $data_shipment[] = $delivery_pickup;
                }
                $delivery_gosend = [
                    $id_table => $id_post,
                    'shipment_method' => 'GO-SEND',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $data_shipment[] = $delivery_gosend;
            }

            try {
                if ($source == 'deals_promotion') {
                    $id_table = 'id_deals_promotion_template';
                }
                $table_promo::where($id_table, '=', $id_post)->update(['is_all_shipment' => 0]);
                $table_shipment::insert($data_shipment);
                $result = ['status'  => 'success'];
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Create Shipment Rule Failed'
                ];
                return $result;
            }
        }
        return $result;
    }

    public function createPaymentRule($source, $id_table, $id_post, $post)
    {
        if ($source == 'promo_campaign') {
            $table_payment = new PromoCampaignPaymentMethod();
            $table_promo = new PromoCampaign();
        } elseif ($source == 'deals') {
            $table_payment = new DealsPaymentMethod();
            $table_promo = new Deal();
        } elseif ($source == 'deals_promotion') {
            $table_payment = new DealsPromotionPaymentMethod();
            $table_promo = new DealsPromotionTemplate();
            $id_table = 'id_deals';
        }

        $table_payment::where($id_table, '=', $id_post)->delete();

        if ($post['filter_payment'] == 'all_payment') {
            try {
                if ($source == 'deals_promotion') {
                    $id_table = 'id_deals_promotion_template';
                }
                $table_promo::where($id_table, '=', $id_post)->update(['is_all_payment' => 1]);
                $result = ['status'  => 'success'];
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Create Payment Method Rule Failed'
                ];
                return $result;
            }
        } else {
            $data_payment = [];
            foreach ($post['payment_method'] as $key => $value) {
                $temp_data = [
                    $id_table => $id_post,
                    'payment_method' => $value,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                $data_payment[] = $temp_data;
            }
            try {
                if ($source == 'deals_promotion') {
                    $id_table = 'id_deals_promotion_template';
                }
                $table_promo::where($id_table, '=', $id_post)->update(['is_all_payment' => 0]);
                $table_payment::insert($data_payment);
                $result = ['status'  => 'success'];
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Create Payment Method Rule Failed'
                ];
                return $result;
            }
        }
        return $result;
    }

    public function splitBrandProduct($id_brand_product, $type)
    {
        $result = null;
        if (strpos($id_brand_product, '-') !== false) {
            $split = explode('-', $id_brand_product);
            if ($type == 'product') {
                $result = $split[1];
            } else {
                $result = $split[0];
            }
        } else {
            if ($type == 'product') {
                $result = $id_brand_product;
            }
        }

        return $result;
    }

    public function splitProductVariant($id_product_variant_group)
    {
        $result = null;
        if (strpos($id_product_variant_group, '-') !== false) {
            $split = explode('-', $id_product_variant_group);
            if ($type == 'product') {
                $result = $split[1];
            } else {
                $result = $split[0];
            }
        } else {
            if ($type == 'product') {
                $result = $id_product_variant_group;
            }
        }

        return $result;
    }

    public function splitProductFormat($id_product)
    {
        $split = explode('-', $id_product);

        $result['id_brand'] = $split[0] ?? null;
        $result['id_product'] = $split[1] ?? null;
        $result['id_product_variant_group'] = is_numeric($split[2] ?? null) ? $split[2] : null;

        return $result;
    }

    public function getProductInsertFormat($products, $id_table, $id_post)
    {
        $product_only = [];
        $product_variant = [];
        $data_product = [];

        // get product only
        foreach ($products as $value) {
            $split_product = $this->splitProductFormat($value);

            if (!empty($split_product['id_product_variant_group'])) {
                $product_variant[] = $value;
            } else {
                $product_only[] = $split_product['id_brand'] . '-' . $split_product['id_product'];
                $data_product[] = [
                    'id_brand'      => $split_product['id_brand'],
                    'id_product'    => $split_product['id_product'],
                    'id_product_variant_group' => null,
                    $id_table       => $id_post,
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s')
                ];
            }
        }

        $product_only_flipped = array_flip($product_only);

        // get product with variant, if exist in product only, remove product variant
        foreach ($product_variant as $value) {
            $split_product = $this->splitProductFormat($value);

            if (isset($product_only_flipped[$split_product['id_brand'] . '-' . $split_product['id_product']])) {
                continue;
            }

            $data_product[] = [
                'id_brand'      => $split_product['id_brand'],
                'id_product'    => $split_product['id_product'],
                'id_product_variant_group' => $split_product['id_product_variant_group'],
                $id_table       => $id_post,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s')
            ];
        }

        return $data_product;
    }

    public function insertPromoImage($promoImage, $width = null, $height = null)
    {
        if (empty($promoImage)) {
            return [
                'status' => 'fail',
                'messages' => ['Failed to upload image']
            ];
        }

        if (!file_exists("img/promo-campaign/")) {
            mkdir("img/promo-campaign/", 0755, true);
        }

        $upload = MyHelper::uploadPhotoStrict($promoImage, "img/promo-campaign/", $width, $height);

        if (($upload['status'] ?? false) == "success") {
            return [
                'status'  => 'success',
                'promo_image'  => $upload['path']
            ];
        } else {
            return [
                'status'  => 'fail',
                'messages'  => ['Failed to upload image']
            ];
        }
    }

    public function activeCampaign(Request $request)
    {
        $campaign = PromoCampaign::select('id_promo_campaign', 'promo_title')
            ->where('date_end', '>=', date('Y-m-d'))
            ->where(function ($q) {
                $q->where('promo_type', '!=', 'Referral')
                    ->orWhereNull('promo_type');
            })
            ->OrderBy('id_promo_campaign', 'DESC')
            ->where('promo_campaign_visibility', '=', 'Visible')
            ->where('step_complete', '=', 1);

        if ($request->promo_use) {
            $campaign->where('promo_use_in', $request->promo_use);
        }

        if ($request->featured) {
            if ($request->featured_type) {
                $campaign->whereDoesntHave('featured_promo_campaign', function ($q) use ($request) {
                    $q->where('feature_type', $request->featured_type);
                });
            } else {
                $campaign->whereDoesntHave('featured_promo_campaign');
            }
        }

        $res = $campaign->get();

        return MyHelper::checkGet($res);
    }

    public function updateVisibility(Request $request)
    {
        $post = $request->all();

        if (!empty($post['id_promo_campaign'])) {
            $update = PromoCampaign::where('id_promo_campaign', $post['id_promo_campaign'])->update(['promo_campaign_visibility' => $post['promo_campaign_visibility']]);

            return response()->json(MyHelper::checkUpdate($update));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function onGoingPromoCampaign(Request $request)
    {
        $post = $request->all();

        $now = date('Y-m-d H-i-s');
        $home_text = Setting::whereIn('key', ['share_promo_code'])->get()->keyBy('key');
        $text['share'] = $home_text['share_promo_code']['value_text'] ?? 'Bagikan %promo_code% ke teman-teman'; //dummy

        $promo_campaign = PromoCampaign::select('id_promo_campaign', 'promo_title', 'promo_image', 'promo_image_detail', 'date_start', 'date_end', 'code_type', 'promo_description')
            ->where('date_end', '>=', $now)
            ->where('date_start', '<=', $now)
            ->where(function ($q) {
                $q->whereHas('brands', function ($query) {
                    $query->where('brand_active', 1);
                })->orWhereDoesntHave('brands');
            })
            ->where('promo_campaign_visibility', 'Visible')
            ->where('step_complete', 1)
            ->OrderBy('id_promo_campaign', 'DESC');

        if (!empty($post['promo_type']) && $post['promo_type'] == 'merchant') {
            $promo_campaign->where('promo_use_in', 'Product');
        }

        if (isset($post['page'])) {
            $promo_campaign = $promo_campaign->paginate($request->length ?: 10)->toArray();
            $promo_map = $promo_campaign['data'];
        } else {
            $promo_campaign = $promo_campaign->get()->toArray();
            $promo_map = $promo_campaign;
        }

        $promo_new = array_map(function ($value) use ($text) {
            $value['code'] = null;
            $value['share_promo'] = null;
            if ($value['code_type'] == 'Single') {
                $promo_code = PromoCampaignPromoCode::where('id_promo_campaign', $value['id_promo_campaign'])->select('promo_code')->first();
                $value['code'] = $promo_code['promo_code'];
                $value['share_promo'] = str_replace('%promo_code%', $value['code'], $text['share']);
            }

            $monthBahasa = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $dayStart = date('j', strtotime($value['date_start']));
            $dayEnd = date('j', strtotime($value['date_end']));
            $monthStart = date('n', strtotime($value['date_start']));
            $monthEnd = date('n', strtotime($value['date_end']));

            if ($monthStart == $monthEnd) {
                $value['date_text'] = $dayStart . '-' . $dayEnd . ' ' . $monthBahasa[$monthStart];
            } else {
                $value['date_text'] = $dayStart . ' ' . $monthBahasa[$monthStart] . ' - ' . $dayEnd . ' ' . $monthBahasa[$monthEnd];
            }

            return $value;
        }, $promo_map);

        if (isset($post['page'])) {
            $promo_campaign['data'] = $promo_new;
        } else {
            $promo_campaign = $promo_new;
        }

        return [
            'status' => 'success',
            'result' => $promo_campaign
        ];
    }

    public function detailOnGoingPromoCampaign(Request $request)
    {
        $post = $request->all();
        $now = date('Y-m-d H-i-s');
        if (isset($post['id_promo_campaign']) && !empty($post['id_promo_campaign'])) {
            $id_promo = $post['id_promo_campaign'];
            $home_text = Setting::whereIn('key', ['share_promo_code'])->get()->keyBy('key');
            $text['share'] = $home_text['share_promo_code']['value_text'] ?? 'Bagikan %promo_code% ke teman-teman'; //dummy

            $promo_campaign = PromoCampaign::select('id_promo_campaign', 'promo_title', 'promo_image', 'promo_image_detail', 'date_start', 'date_end', 'code_type', 'promo_description')
                ->where('id_promo_campaign', $id_promo)
                ->where('date_end', '>=', $now)
                ->where('date_start', '<=', $now)
                ->where(function ($q) {
                    $q->whereHas('brands', function ($query) {
                        $query->where('brand_active', 1);
                    })->orWhereDoesntHave('brands');
                })
                ->orderBy('id_promo_campaign', 'DESC')
                ->first();

            if (!$promo_campaign) {
                return [
                    'status' => 'fail',
                    'messages' => ['Promo tidak ditemukan']
                ];
            }

            $promo_campaign = $promo_campaign->toArray();
            if ($promo_campaign['code_type'] == 'Single') {
                $promo_code = PromoCampaignPromoCode::where('id_promo_campaign', $promo_campaign['id_promo_campaign'])->select('promo_code')->first();
                $promo_campaign['code'] = $promo_code['promo_code'];
                $promo_campaign['share_promo'] = str_replace('%promo_code%', $promo_campaign['code'], $text['share']);
            }

            $monthBahasa = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $dayStart = date('j', strtotime($promo_campaign['date_start']));
            $dayEnd = date('j', strtotime($promo_campaign['date_end']));
            $monthStart = date('n', strtotime($promo_campaign['date_start']));
            $monthEnd = date('n', strtotime($promo_campaign['date_end']));

            if ($monthStart == $monthEnd) {
                $promo_campaign['date_text'] = $dayStart . '-' . $dayEnd . ' ' . $monthBahasa[$monthStart];
            } else {
                $promo_campaign['date_text'] = $dayStart . ' ' . $monthBahasa[$monthStart] . ' - ' . $dayEnd . ' ' . $monthBahasa[$monthEnd];
            }

            return [
                'status' => 'success',
                'result' => $promo_campaign,
            ];
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }
}
