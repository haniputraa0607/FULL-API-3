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

use Modules\Deals\Entities\DealsProductDiscount;
use Modules\Deals\Entities\DealsProductDiscountRule;
use Modules\Deals\Entities\DealsTierDiscountProduct;
use Modules\Deals\Entities\DealsTierDiscountRule;
use Modules\Deals\Entities\DealsBuyxgetyProductRequirement;
use Modules\Deals\Entities\DealsBuyxgetyRule;

use App\Http\Models\User;
use App\Http\Models\Campaign;
use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use App\Http\Models\Setting;
use App\Http\Models\Voucher;
use App\Http\Models\Treatment;
use App\Http\Models\Deal;

use Modules\PromoCampaign\Http\Requests\Step1PromoCampaignRequest;
use Modules\PromoCampaign\Http\Requests\Step2PromoCampaignRequest;
use Modules\PromoCampaign\Http\Requests\DeletePromoCampaignRequest;
use Modules\PromoCampaign\Http\Requests\ValidateCode;

use Modules\PromoCampaign\Lib\PromoCampaignTools;
use App\Lib\MyHelper;
use App\Jobs\GeneratePromoCode;
use DB;
use Hash;
use Modules\SettingFraud\Entities\DailyCheckPromoCode;
use Modules\SettingFraud\Entities\LogCheckPromoCode;

class ApiPromoCampaign extends Controller
{

	function __construct() {
        date_default_timezone_set('Asia/Jakarta');

        $this->online_transaction   = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->fraud   = "Modules\SettingFraud\Http\Controllers\ApiFraud";
    }

    public function index(Request $request)
    {
        $post = $request->json()->all();
        $promo_type = $request->get('promo_type');

        try {

            $query = PromoCampaign::with([
                        'user'
                    ])
                    ->OrderBy('promo_campaigns.id_promo_campaign', 'DESC');
            $count = (new PromoCampaign)->newQuery();

            if (isset($promo_type)) {

                $query = $query->where('promo_type', '=' ,$promo_type);

            }

            if ($request->json('rule')) {
                $filter = $this->filterList($query, $request);
                $this->filterList($count, $request);
            }

            if(!empty($query)){
                $query = $query->paginate(10)->toArray();
                $result = [
                    'status'     => 'success',
                    'result'     => $query,
                    'count'      => count($query)
                ];
            }else{

                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Promo Campaign is empty']
                ]);
            }

            if ($filter??false) {
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
                    $wherein=$where.'In';
                    $query->$wherein('id_promo_campaign',function($query) use ($value,$where){
                        $query->select('id_promo_campaign')->from(with(new Reports)->getTable())->where('user_phone',$value['operator'],$value['operator'] == 'like'?'%'.$value['parameter'].'%':$value['parameter'])->groupBy('id_promo_campaign');
                    });
                    break;

                    case 'used_at_outlet':
                    $wherein=$where.'In';
                    $query->$wherein('id_promo_campaign',function($query) use ($value,$where){
                        $query->select('id_promo_campaign')->from(with(new Reports)->getTable())->where('id_outlet',$value['parameter'])->groupBy('id_promo_campaign');
                    });
                    break;

                    case 'promo_code':
                    $wherein=$where.'In';
                    $query->$wherein('id_promo_campaign',function($query) use ($value,$where){
                        $query->select('id_promo_campaign')->from(with(new PromoCode)->getTable())->where('promo_code',$value['operator'],$value['operator'] == 'like'?'%'.$value['parameter'].'%':$value['parameter'])->groupBy('id_promo_campaign');
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
            'user',
            'promo_campaign_promo_codes',
            'promo_campaign_have_tags.promo_campaign_tag',
            'outlets',
            'promo_campaign_product_discount_rules',
            'promo_campaign_product_discount.product.category',
            'promo_campaign_tier_discount_rules',
            'promo_campaign_tier_discount_product.product',
            'promo_campaign_buyxgety_rules.product',
            'promo_campaign_buyxgety_product_requirement.product'
        ];
        $promoCampaign = PromoCampaign::with($data)->where('id_promo_campaign', '=', $post['id_promo_campaign'])->get()->toArray();
        if ($promoCampaign) {
            $promoCampaign[0]['used_code'] = PromoCampaignReport::where('promo_campaign_reports.id_promo_campaign', $post['id_promo_campaign'])->get()->count();
            $total = PromoCampaignReport::where('promo_campaign_reports.id_promo_campaign', $post['id_promo_campaign']);
            $this->filterReport($total,$request,$foreign);
            foreach ($foreign as $value) {
                $total->leftJoin(...$value);
            }
            $promoCampaign[0]['total'] = $total->get()->count();

            $total2 = PromoCampaignPromoCode::join('promo_campaigns', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_promo_codes.id_promo_campaign')->where('promo_campaign_promo_codes.id_promo_campaign', $post['id_promo_campaign']);
            $this->filterCoupon($total2,$request,$foreign);
            foreach ($foreign as $value) {
                $total->leftJoin(...$value);
            }
            $promoCampaign[0]['total2'] = $total2->get()->count();
            $result = [
                'status'  => 'success',
                'result'  => $promoCampaign[0]
            ];
        } else {
            $result = [
                'status'  => 'fail',
                'message'  => ['Promo Campaign Not Found']
            ];
        }
        return response()->json($result);
    }
    public function detail2(Request $request) {
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

        if ( ($promoCampaign['code_type']??'')=='Single' ) {
            $promoCampaignPromoCode = PromoCampaignPromoCode::where('id_promo_campaign', '=', $post['id_promo_campaign'])->first();
        }

        if ($promoCampaign) {
            $promoCampaign = $promoCampaign->toArray();
            $promoCampaign['count'] = count($promoCampaign['promo_campaign_reports']);
            if ($promoCampaignPromoCode??false) {
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
        $count = (new PromoCampaignReport)->newQuery()->where('promo_campaign_reports.id_promo_campaign', $post['id_promo_campaign']);
        $total = (new PromoCampaignReport)->newQuery()->where('promo_campaign_reports.id_promo_campaign', $post['id_promo_campaign']);
        $foreign=[];
        $foreign2=[];
        if($post['rule']??false){
            $this->filterReport($query,$request,$foreign);
            $this->filterReport($count,$request,$foreign2);
        }
        $column = ['promo_code','user_name','created_at','receipt_number','outlet','device_type'];
        if($post['start']){
            $query->skip($post['start']);
        }
        if($post['length']>0){
            $query->take($post['length']);
        }
        foreach ($post['order'] as $value) {
            switch ($column[$value['column']]) {
                case 'promo_code':
                $foreign['promo_campaign_promo_codes']=array('promo_campaign_promo_codes','promo_campaign_promo_codes.id_promo_campaign_promo_code','=','promo_campaign_reports.id_promo_campaign_promo_code');
                $query->orderBy('promo_code',$value['dir']);
                break;
                
                case 'receipt_number':
                $foreign['transactions']=array('transactions','transactions.id_transaction','=','promo_campaign_reports.id_transaction');
                $query->orderBy('transaction_receipt_number',$value['dir']);
                break;
                
                case 'outlet':
                $foreign['outlets']=array('outlets','outlets.id_outlet','=','promo_campaign_reports.id_outlet');
                $query->orderBy('outlet_name',$value['dir']);
                break;
                
                default:
                $query->orderBy('promo_campaign_reports.'.$column[$value['column']],$value['dir']);
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
        $count = $count->get()->count();
        $total = $total->get()->count();

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

    protected function filterReport($query, $request,&$foreign='')
    {
        $query->groupBy('promo_campaign_reports.id_promo_campaign_report');
        $allowed = array(
            'operator' => ['=', 'like', '<', '>', '<=', '>='],
            'subject' => ['promo_code','user_phone','created_at','receipt_number','id_outlet','device_type','outlet_count','user_count'],
            'mainSubject' => ['user_phone','created_at','id_outlet','device_type']
        );
        $return = [];
        $where = $request->json('operator') == 'or' ? 'orWhere' : 'where';
        $rule = $request->json('rule');
        $query->where(function($queryx) use ($rule,$allowed,$where,$query,&$foreign,$request){
            $foreign=array();
            $outletCount=0;
            $userCount=0;
            foreach ($rule??[] as $value) {
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
                    if($value['subject']=='created_at'){
                        $queryx->$where(\DB::raw('UNIX_TIMESTAMP(promo_campaign_reports.'.$value['subject'].')'), $value['operator'], strtotime($value['parameter']));
                    }else{
                        $queryx->$where('promo_campaign_reports.'.$value['subject'], $value['operator'], $value['parameter']);
                    }
                } else {
                    switch ($value['subject']) {
                        case 'promo_code':
                        $foreign['promo_campaign_promo_codes']=['promo_campaign_promo_codes','promo_campaign_promo_codes.id_promo_campaign_promo_code','=','promo_campaign_reports.id_promo_campaign_promo_code'];
                        $queryx->$where('promo_code', $value['operator'], $value['parameter']);
                        break;
                        
                        case 'receipt_number':
                        $foreign['transactions']=['transactions','transactions.id_transaction','=','promo_campaign_reports.id_transaction'];
                        $queryx->$where('transaction_receipt_number', $value['operator'], $value['parameter']);
                        break;

                        case 'outlet_count':
                        if(!$outletCount){
                            $query->addSelect('outlet_total');
                            $outletCount=1;
                        }
                        $foreign['t2']=[\DB::raw('(SELECT COUNT(*) AS outlet_total, id_outlet FROM `promo_campaign_reports` WHERE id_promo_campaign = '.$request->json('id_promo_campaign').' GROUP BY id_outlet) AS `t2`'),'promo_campaign_reports.id_outlet','=','t2.id_outlet'];
                        $queryx->$where('outlet_total', $value['operator'], $value['parameter']);
                        break;


                        case 'user_count':
                        if(!$userCount){
                            $query->addSelect('user_total');
                            $userCount=1;
                        }
                        $foreign['t3']=[\DB::raw('(SELECT COUNT(*) AS user_total, id_user FROM `promo_campaign_reports` WHERE id_promo_campaign = '.$request->json('id_promo_campaign').' GROUP BY id_user) AS `t3`'),'promo_campaign_reports.id_user','=','t3.id_user'];
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

    public function Coupon(Request $request)
    {
        $post = $request->json()->all();

        $query = PromoCampaignPromoCode::select('promo_campaign_promo_codes.*', 'promo_campaigns.limitation_usage')
                ->join('promo_campaigns', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_promo_codes.id_promo_campaign')
                ->where('promo_campaign_promo_codes.id_promo_campaign', $post['id_promo_campaign']);

        $filter = null;
        $count = (new PromoCampaignPromoCode)->newQuery()
                    ->join('promo_campaigns', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_promo_codes.id_promo_campaign')
                    ->where('promo_campaign_promo_codes.id_promo_campaign', $post['id_promo_campaign']);
        $total = (new PromoCampaignPromoCode)->newQuery()
                    ->join('promo_campaigns', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_promo_codes.id_promo_campaign')
                    ->where('promo_campaign_promo_codes.id_promo_campaign', $post['id_promo_campaign']);
        $foreign=[];
        $foreign2=[];
        if($post['rule2']??false){
            $this->filterCoupon($query,$request,$foreign);
            $this->filterCoupon($count,$request,$foreign2);
        }
        $column = ['promo_code','status','usage','available','limitation_usage'];
        if($post['start']){
            $query->skip($post['start']);
        }
        if($post['length']>0){
            $query->take($post['length']);
        }
        foreach ($post['order'] as $value) {
            switch ($column[$value['column']]) {
                case 'status':
                case 'available':
                $query->orderBy('usage',$value['dir']);
                break;
                
                case 'limitation_usage':
                $query->orderBy('limitation_usage',$value['dir']);
                break;
                
                default:
                $query->orderBy('promo_campaign_promo_codes.'.$column[$value['column']],$value['dir']);
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
        $count = $count->get()->count();
        $total = $total->get()->count();

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

    protected function filterCoupon($query, $request,&$foreign='')
    {
        $query->groupBy('promo_campaign_promo_codes.id_promo_campaign_promo_code');
        $allowed = array(
            'operator' => ['=', 'like', '<', '>', '<=', '>='],
            'subject' => ['coupon_code','status','used','available','max_used'],
        );
        $return = [];
        $where = $request->json('operator2') == 'or' ? 'orWhere' : 'where';
        $whereRaw = $request->json('operator2') == 'or' ? 'orWhereRaw' : 'whereRaw';
        $rule = $request->json('rule2');
        $query->where(function($queryx) use ($rule,$allowed,$where,$query,&$foreign,$request,$whereRaw){
            $foreign=array();
            $outletCount=0;
            $userCount=0;
            foreach ($rule??[] as $value) {
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
                    if ($value['parameter'] == 'Not used') 
                    {
                        $queryx->$where('usage', '=', 0);
                    }
                    elseif( $value['parameter'] == 'Used' )
                    {
                        $queryx->$where('usage', '=', 'limitation_usage');
                    }
                    else
                    {
                        $queryx->$where('usage', '!=', 0)->$where('usage', '!=', 'limitation_usage');
                    }

                    break;

                    case 'used':
                    $queryx->$where('usage', $value['operator'], $value['parameter']);
                    break;

                    case 'available':
                    $queryx->$whereRaw('limitation_usage - promo_campaign_promo_codes.usage '.$value['operator'].' '.$value['parameter']);
                    break;

                    case 'max_used':
                    $queryx->$where('limitation_usage', $value['operator'], $value['parameter']);
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
            $checkCode = PromoCampaignPromoCode::where('promo_code', '=', $post['search_code'])->get()->toArray();
        } else {
            $checkCode = PromoCampaign::where('prefix_code', '=', $post['search_code'])->get()->toArray();
        }

        if (isset($checkCode) && !empty($checkCode)) {
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
        $user = $request->user();

        $post['prefix_code'] = strtoupper($post['prefix_code']);

        $post['date_start'] = $this->generateDate($post['date_start']);
        $post['date_end']   = $this->generateDate($post['date_end']);

        if ($post['code_type'] == 'Multiple') {
        	
        	$max_char_digit = 28;
        	$max_coupon_posibility = pow($max_char_digit, $post['number_last_code']);
            if ( $max_coupon_posibility < $post['total_coupon'] ) {
            	$result = [
                    'status'  => 'fail',
                    'messages'  => ['Total Coupon must be equal or less than total Generate random code']
                ];
                return $result;
            }
        }

        DB::beginTransaction();

        if (isset($post['id_promo_campaign'])) {
            $post['last_updated_by'] = $user['id'];
            $datenow = date("Y-m-d H:i:s");
            $checkData = PromoCampaign::with(['haveTags.tag', 'promoCode'])->where('id_promo_campaign', '=', $post['id_promo_campaign'])->get()->toArray();

            if ($checkData[0]['date_start'] <= $datenow) {
                $post['date_start']     = $checkData[0]['date_start'];
                $post['date_end']       = $checkData[0]['date_end'];

                if ($checkData[0]['code_type'] == 'Single') {
                    $checkData[0]['promo_code'] = $checkData[0]['promo_code'][0];
                }

                if ($checkData[0]['code_type'] != $post['code_type'] || $checkData[0]['prefix_code'] != $post['prefix_code'] || $checkData[0]['number_last_code'] != $post['number_last_code'] || $checkData[0]['promo_code']['promo_code'] != $post['promo_code'] || $checkData[0]['total_coupon'] != $post['total_coupon']) {
                    $promo_code = $post['promo_code'];

                    if ($post['code_type'] == 'Single') {
                        unset($post['promo_code']);
                    }

                    if (isset($post['promo_tag'])) {
                        $insertTag = $this->insertTag('update', $post['id_promo_campaign'], $post['promo_tag']);
                        unset($post['promo_tag']);
                    }

                    $promoCampaign = PromoCampaign::where('id_promo_campaign', '=', $post['id_promo_campaign'])->update($post);
                    $generateCode = $this->generateCode('update', $post['id_promo_campaign'], $post['code_type'], $promo_code, $post['prefix_code'], $post['number_last_code'], $post['total_coupon']);

                    if ($generateCode['status'] == 'success') {
                        $result = [
                            'status'  => 'success',
                            'result'  => 'Update Promo Campaign & Promo Code Success',
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
                    if (isset($post['promo_code']) || $post['promo_code'] == null) {
                        unset($post['promo_code']);
                    }

                    if (isset($post['promo_tag'])) {
                        $insertTag = $this->insertTag('update', $post['id_promo_campaign'], $post['promo_tag']);
                        unset($post['promo_tag']);
                    }

                    $promoCampaign = PromoCampaign::where('id_promo_campaign', '=', $post['id_promo_campaign'])->update($post);
                    $generateCode = $this->generateCode('update', $post['id_promo_campaign'], $post['code_type'], $promo_code, $post['prefix_code'], $post['number_last_code'], $post['total_coupon']);

                    if ($promoCampaign == 1) {
                        $result = [
                            'status'  => 'success',
                            'result'  => 'Update Promo Campaign',
                            'promo-campaign'  => $post
                        ];
                    } else {
                        DB::rollBack();
                        $result = ['status'  => 'fail'];
                    }
                }
            } else {
                $promo_code = $post['promo_code'];

                if (isset($post['promo_code']) || $post['promo_code'] == null) {
                    unset($post['promo_code']);
                }

                if (isset($post['promo_tag'])) {
                    $insertTag = $this->insertTag('update', $post['id_promo_campaign'], $post['promo_tag']);
                    unset($post['promo_tag']);
                }

                $generateCode = $this->generateCode('update', $post['id_promo_campaign'], $post['code_type'], $promo_code, $post['prefix_code'], $post['number_last_code'], $post['total_coupon']);

                try {
                    PromoCampaign::where('id_promo_campaign', '=', $post['id_promo_campaign'])->update($post);
                    $result = [
                        'status'  => 'success',
                        'result'  => 'Update Promo Campaign Before Start',
                        'promo-campaign'  => $post
                    ];
                } catch (\Exception $e) {
                    DB::rollBack();
                    $result = ['status' => 'fail'];
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

            $promoCampaign = PromoCampaign::create($post);
            $generateCode = $this->generateCode('insert', $promoCampaign['id_promo_campaign'], $post['code_type'], $post['promo_code'], $post['prefix_code'], $post['number_last_code'], $post['total_coupon']);
            if (isset($post['promo_tag'])) {
                $insertTag = $this->insertTag(null, $promoCampaign['id_promo_campaign'], $post['promo_tag']);
            }

            $post['id_promo_campaign'] = $promoCampaign['id_promo_campaign'];

            if ($generateCode['status'] == 'success') {
                $result = [
                    'status'  => 'success',
                    'result'  => 'Creates Promo Campaign & Promo Code Success',
                    'promo-campaign'  => $post
                ];
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
        $user = $request->user();
        if (!empty($post['id_deals'])) {
        	$source = 'deals';
        	$table = new Deal;
        	$id_table = 'id_deals';
        	$id_post = $post['id_deals'];
        	$error_message = 'Deals';
        }else{
        	$source = 'promo_campaign';
        	$table = new PromoCampaign;
        	$id_table = 'id_promo_campaign';
        	$id_post = $post['id_promo_campaign'];
        	$error_message = 'Deals';
        }

        DB::beginTransaction();

        $dataPromoCampaign['promo_type'] = $post['promo_type'];
        if ($source == 'promo_campaign') {
        	$dataPromoCampaign['step_complete'] = 1;
	        $dataPromoCampaign['last_updated_by'] = $user['id'];
	        $dataPromoCampaign['user_type'] = $post['filter_user'];
	        $dataPromoCampaign['specific_user'] = $post['specific_user']??null;

	        if ($post['filter_outlet'] == 'All Outlet') 
	        {
	            $createFilterOutlet = $this->createOutletFilter('all_outlet', 1, $post['id_promo_campaign'], null);
	        } 
	        elseif ($post['filter_outlet'] == 'Selected') 
	        {
	            $createFilterOutlet = $this->createOutletFilter('selected', 0, $post['id_promo_campaign'], $post['multiple_outlet']);
	        } 
	        else 
	        {
	            $createFilterOutlet = [
	                'status'  => 'fail',
	                'message' => 'Create Filter Outlet Failed'
	            ];
	            DB::rollBack();
	            return response()->json($createFilterOutlet);
	        }
        }

        $update = $table::where($id_table, $id_post)->update($dataPromoCampaign);

        if ($post['promo_type'] == 'Product Discount') {

            if ($post['filter_product'] == 'All Product') {
                $createFilterProduct = $this->createProductFilter('all_product', 1, $id_post, null, $post['discount_type'], $post['discount_value'], $post['max_product'], $post['max_percent_discount'], $source, $table, $id_table);
            } elseif ($post['filter_product'] == 'Selected') {
                $createFilterProduct = $this->createProductFilter('selected', 0, $id_post, $post['multiple_product'], $post['discount_type'], $post['discount_value'], $post['max_product'], $post['max_percent_discount'], $source, $table, $id_table);
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
                $createFilterProduct = $this->createPromoTierDiscount($id_post, array($post['product']), $post['discount_type'], $post['promo_rule'], $source, $table, $id_table);
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
                $createFilterProduct = $this->createBuyXGetYDiscount($id_post, $post['product'], $post['promo_rule'], $source, $table, $id_table);

            } catch (Exception $e) {
                $createFilterProduct = [
                    'status'  => 'fail',
                    'messages' => 'Create Promo Type Failed'
                ];
                DB::rollBack();
                return response()->json($createFilterProduct);
            }
        }

        DB::commit();
        return response()->json($createFilterProduct);
    }

    function createOutletFilter($parameter, $operator, $id_promo_campaign, $outlet)
    {
        if (PromoCampaignOutlet::where('id_promo_campaign', '=', $id_promo_campaign)->exists()) {
            PromoCampaignOutlet::where('id_promo_campaign', '=', $id_promo_campaign)->delete();
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
                return response()->json($result);
            }
        } else {
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
                return response()->json($result);
            }
        }
        return $result;
    }

    public function deleteAllProductRule($source, $id_post)
    {
    	try {
    		
	    	if ($source == 'promo_campaign') 
	    	{
		        PromoCampaignProductDiscountRule::where('id_promo_campaign', '=', $id_post)->delete();
		        PromoCampaignTierDiscountRule::where('id_promo_campaign', '=', $id_post)->delete();
		        PromoCampaignBuyxgetyRule::where('id_promo_campaign', '=', $id_post)->delete();

		        PromoCampaignTierDiscountProduct::where('id_promo_campaign', '=', $id_post)->delete();
		        PromoCampaignProductDiscount::where('id_promo_campaign', '=', $id_post)->delete();
		        PromoCampaignBuyxgetyProductRequirement::where('id_promo_campaign', '=', $id_post)->delete();

	    	}
	    	elseif ($source == 'deals') 
	    	{
	    		DealsProductDiscountRule::where('id_deals', '=', $id_post)->delete();
		        DealsTierDiscountRule::where('id_deals', '=', $id_post)->delete();
		        DealsBuyxgetyRule::where('id_deals', '=', $id_post)->delete();

		        DealsTierDiscountProduct::where('id_deals', '=', $id_post)->delete();
		        DealsProductDiscount::where('id_deals', '=', $id_post)->delete();
		        DealsBuyxgetyProductRequirement::where('id_deals', '=', $id_post)->delete();

	    	}

	    	return true;
    	} catch (Exception $e) {
    		return false;
    	}
    }

    public function createProductFilter($parameter, $operator, $id_post, $product, $discount_type, $discount_value, $max_product, $max_percent_discount, $source, $table, $id_table)
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

    	if ($source == 'promo_campaign') 
    	{
	        $table_product_discount_rule = new PromoCampaignProductDiscountRule;
	        $table_product_discount = new PromoCampaignProductDiscount;
    	}
    	elseif ($source == 'deals') 
    	{
	        $table_product_discount_rule = new DealsProductDiscountRule;
	        $table_product_discount = new DealsProductDiscount;
    	}

    	if ($discount_type == 'Nominal') {
        	$max_percent_discount = NULL;
        }

        $data = [
            $id_table => $id_post,
            'is_all_product'    		=> $operator,
            'discount_type'     		=> $discount_type,
            'discount_value'    		=> $discount_value,
            'max_product'       		=> $max_product,
            'max_percent_discount'      => $max_percent_discount,
            'created_at'        		=> date('Y-m-d H:i:s'),
            'updated_at'        		=> date('Y-m-d H:i:s')
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
            $dataProduct = [];
            for ($i = 0; $i < count($product); $i++) {
                $dataProduct[$i]['id_product']           = array_values($product)[$i];
                $dataProduct[$i][$id_table]    			 = $id_post;
                $dataProduct[$i]['created_at']           = date('Y-m-d H:i:s');
                $dataProduct[$i]['updated_at']           = date('Y-m-d H:i:s');
            }
            try {
                $table_product_discount_rule::insert($data);
                $table_product_discount::insert($dataProduct);
                $result = ['status'  => 'success'];
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

    public function createPromoTierDiscount($id_post, $product, $discount_type, $rules, $source, $table, $id_table)
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
    	
    	if ($source == 'promo_campaign') 
    	{
	        $table_tier_discount_rule = new PromoCampaignTierDiscountRule;
	        $table_tier_discount_product = new PromoCampaignTierDiscountProduct;
    	}
    	elseif ($source == 'deals') 
    	{
	        $table_tier_discount_rule = new DealsTierDiscountRule;
	        $table_tier_discount_product = new DealsTierDiscountProduct;
    	}

    	if ($discount_type == 'Nominal') {
        	$is_nominal = 1;
        }else{
        	$is_nominal = 0;
        }

        $data = [];
        foreach ($rules as $key => $rule) {
            $data[] = [
                $id_table => $id_post,
                'discount_type'     => $discount_type,
                'max_qty'           => $rule['max_qty'],
                'min_qty'           => $rule['min_qty'],
                'discount_value'    => $rule['discount_value'],
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s')
            ];
	        if ($is_nominal) {
	        	$data[$key]['max_percent_discount'] = null;
	        }else{
	        	$data[$key]['max_percent_discount'] = $rule['max_percent_discount'];
	        }
        }

        $dataProduct = [];
        for ($i = 0; $i < count($product); $i++) {
            $dataProduct[$i]['id_product']           = array_values($product)[$i];
            $dataProduct[$i][$id_table]    = $id_post;
            $dataProduct[$i]['created_at']           = date('Y-m-d H:i:s');
            $dataProduct[$i]['updated_at']           = date('Y-m-d H:i:s');
        }
        
        try {
            $table_tier_discount_rule::insert($data);
            $table_tier_discount_product::insert($dataProduct);
            $result = ['status'  => 'success'];
        } catch (\Exception $e) {
            $result = [
                'status'  => 'fail',
                'message' => 'Create Filter Product Failed'
            ];
            DB::rollBack();
            return response()->json($result);
        }
        return $result;
    }

    public function createBuyXGetYDiscount($id_post, $product, $rules, $source, $table, $id_table)
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

    	if ($source == 'promo_campaign') 
    	{
	        $table_buyxgety_discount_rule = new PromoCampaignBuyxgetyRule;
	        $table_buyxgety_discount_product = new PromoCampaignBuyxgetyProductRequirement;
    	}
    	elseif ($source == 'deals') 
    	{
	        $table_buyxgety_discount_rule = new DealsBuyxgetyRule;
	        $table_buyxgety_discount_product = new DealsBuyxgetyProductRequirement;
    	}

        $data = [];
        foreach ($rules as $key => $rule) {

            $data[$key] = [
                $id_table   	=> $id_post,
                'benefit_id_product'  	=> $rule['benefit_id_product'] == 0 ? $product : $rule['benefit_id_product'],
                'max_qty_requirement' 	=> $rule['max_qty_requirement'],
                'min_qty_requirement' 	=> $rule['min_qty_requirement'],
                'benefit_qty'         	=> $rule['benefit_qty'],
                'max_percent_discount'  => $rule['max_percent_discount']
            ];

            if ($rule['benefit_type'] == "percent") 
            {
                $data[$key]['discount_type'] = 'percent';
                $data[$key]['discount_value'] = $rule['discount_percent'];
                $data[$key]['benefit_qty'] = 1;
            }
            elseif($rule['benefit_type'] == "nominal")
            {
            	$data[$key]['discount_type'] = 'nominal';
                $data[$key]['discount_value'] = $rule['discount_nominal'];
                $data[$key]['benefit_qty'] = 1;
                $data[$key]['max_percent_discount'] = null;
            }
            elseif($rule['benefit_type'] == "free")
            {
                $data[$key]['discount_type'] = 'percent';
                $data[$key]['discount_value'] = 100;
                $data[$key]['max_percent_discount'] = null;
            }
            else
            {
                $data[$key]['discount_type'] = 'nominal';
                $data[$key]['discount_value'] = 0;
                $data[$key]['benefit_qty'] = 1;
            }

        }

        $dataProduct['id_product']           = $product;
        $dataProduct[$id_table]    			 = $id_post;
        $dataProduct['created_at']           = date('Y-m-d H:i:s');
        $dataProduct['updated_at']           = date('Y-m-d H:i:s');

        try {
            $table_buyxgety_discount_rule::insert($data);
            $table_buyxgety_discount_product::insert($dataProduct);
            $result = ['status'  => 'success'];
        } catch (\Illuminate\Database\QueryException $e) {
            $result = [
                'status'  => 'fail',
                'message' => $e->getMessage()
            ];
            DB::rollBack();
            return response()->json($result);
        }
        return $result;
    }

    function generateDate($date)
    {
        $datetimearr    = explode(' - ', $date);

        $datearr        = explode(' ', $datetimearr[0]);

        $date = $datearr[0].'-'.$datearr[1].'-'.$datearr[2];

        $date = date('Y-m-d', strtotime($date)).' '.$datetimearr[1] . ":00";
        return $date;

    }

    function removeDuplicateCode($code, $total_coupon)
    {
    	$unique_code = array_column($code, 'promo_code');
        // $unique_code = array_intersect_key($unique_code, array_unique( array_map("strtolower", $unique_code)));
        $unique_code = array_unique($unique_code);
        $code = array_filter($code, function ($key, $value) use ($unique_code) {
        	return in_array($value, array_keys($unique_code));
        }, ARRAY_FILTER_USE_BOTH);
        $duplicate = $total_coupon-count($code);

        return [
        	'code' => $code,
        	'duplicate' => $duplicate
        ]; 
    }

    function generateMultipleCode($old_code=null, $id, $prefix_code, $number_last_code, $total_coupon)
    {
    	if (empty($old_code)) {
    		$i = 0;
    	}else{
    		$i = count($old_code)-1;
    		$total_coupon = $i+$total_coupon;
    	}
    	for (; $i < $total_coupon; $i++) 
        {
            $generateCode[$i]['id_promo_campaign']  = $id;
            $generateCode[$i]['promo_code']         = implode('-', [$prefix_code, MyHelper::createrandom($number_last_code, 'PromoCode')]);
            $generateCode[$i]['created_at']         = date('Y-m-d H:i:s');
            $generateCode[$i]['updated_at']         = date('Y-m-d H:i:s');
        	array_push($old_code, $generateCode[$i]);
        }
        return $old_code;
    }

    function generateCode($status, $id, $type_code, $promo_code = null, $prefix_code = null, $number_last_code = null, $total_coupon = null)
    {
        $generateCode = [];
        if ($type_code == 'Multiple') 
        {
            if ($total_coupon <= 1000) 
            {
                for ($i = 0; $i < $total_coupon; $i++) 
                {
                    $generateCode[$i]['id_promo_campaign']  = $id;
                    $generateCode[$i]['promo_code']         = implode('-', [$prefix_code, MyHelper::createrandom($number_last_code, 'PromoCode')]);
                    $generateCode[$i]['created_at']         = date('Y-m-d H:i:s');
                    $generateCode[$i]['updated_at']         = date('Y-m-d H:i:s');
                }

                // $unique_code = $this->removeDuplicateCode($generateCode, $total_coupon);
                // $duplicate = $unique_code['duplicate'];
                // $generateCode2 = $unique_code['code'];
                // $i = 0;
                // while ($duplicate != 0 ) 
                // {
                // 	$generateCode2 = $this->generateMultipleCode($generateCode2, $id, $prefix_code, $number_last_code, $total_coupon);
                // 	$unique_code = $this->removeDuplicateCode($generateCode2, $total_coupon);
                // 	$duplicate = $unique_code['duplicate'];
                // 	$generateCode2 = $unique_code['code'];

                // }
                // $generateCode = $generateCode2;
            }
            else
            {
                GeneratePromoCode::dispatch($status, $id, $prefix_code, $number_last_code, $total_coupon)->allOnConnection('database');
                $result = ['status'  => 'success'];
                return $result;
            }
        } 
        else 
        {
            $generateCode['id_promo_campaign']  = $id;
            $generateCode['promo_code']         = $promo_code;
            $generateCode['created_at']         = date('Y-m-d H:i:s');
            $generateCode['updated_at']         = date('Y-m-d H:i:s');
        }

        if ($status == 'insert') 
        {
            try 
            {
              	PromoCampaignPromoCode::insert($generateCode);
                $result = ['status'  => 'success'];
            } 
            catch (\Exception $e) 
            {
                $result = ['status' => 'fail'];
            }
        } 
        else 
        {
            try 
            {
                PromoCampaignPromoCode::where('id_promo_campaign', $id)->delete();
                PromoCampaignPromoCode::insert($generateCode);
                $result = ['status'  => 'success'];
            } 
            catch (\Exception $e) 
            {
                $result = ['status' => 'fail'];
            }
        }

        return $result;
    }

    public function insertTag($status = null, $id_promo_campaign, $promo_tag)
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
                            'promo_campaign_have_tags.promo_campaign_tag'
                        ])
                        ->where('id_promo_campaign', '=', $post['id_promo_campaign'])->first();

        if (!empty($promoCampaign) && $promoCampaign['code_type'] == 'Single') {
            $promoCampaign = $promoCampaign->with('promo_campaign_promo_codes', 'promo_campaign_have_tags.promo_campaign_tag')->where('id_promo_campaign', '=', $post['id_promo_campaign'])->first();
        }
        
        if (isset($promoCampaign)) {
            $promoCampaign = $promoCampaign->toArray();
        }else{
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
                            'promo_campaign_buyxgety_rules',
                            'outlets'
                        ])
                        ->where('id_promo_campaign', '=', $post['id_promo_campaign'])
                        ->first();

        if (isset($promoCampaign)) {
            $promoCampaign = $promoCampaign->toArray();
        }else{
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

        if ($post['get'] == 'Outlet') 
        {
            $data = Outlet::select('id_outlet', DB::raw('CONCAT(outlet_code, " - ", outlet_name) AS outlet'))->get()->toArray();
        } 
        elseif ($post['get'] == 'Product') 
        {
            $data = Product::select('id_product', DB::raw('CONCAT(product_code, " - ", product_name) AS product'))->get()->toArray();
        } 
        else 
        {
            $data = 'null';
        }

        return response()->json($data);
    }

    public function delete(DeletePromoCampaignRequest $request){
        $post = $request->json()->all();
        $user = auth()->user();
        $password = $request['password'];
        DB::beginTransaction();
        if (Hash::check($password, $user->password)){

            $checkData = PromoCampaign::where('id_promo_campaign','=',$post['id_promo_campaign'])->first();
            if ($checkData) {
                $delete = PromoCampaign::where('id_promo_campaign','=',$post['id_promo_campaign'])->delete();
                DB::commit();
                return MyHelper::checkDelete($delete);
            }else{
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

    public function validateCode(ValidateCode $request){
        $id_user 		= $request->user()->id;
        $phone 	 		= $request->user()->phone;
        $device_id		= $request->device_id;
        $device_type	= $request->device_type;
        $id_outlet		= $request->id_outlet;

        $code=PromoCampaignPromoCode::where('promo_code',$request->promo_code)
                ->join('promo_campaigns', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_promo_codes.id_promo_campaign')
                ->where('step_complete', '=', 1)
                ->where( function($q){
                	$q->whereColumn('usage','<','limitation_usage')
                		->orWhere('code_type','Single');
                } )
                ->first();

        if(!$code){
            return [
                'status'=>'fail',
                'messages'=>['Promo code not valid']
            ];
        }
        $pct=new PromoCampaignTools();
        if(!$pct->validateUser($code->id_promo_campaign, $id_user, $phone, $device_type, $device_id, $errore)){
            return [
                'status'=>'fail',
                'messages'=>$errore??['Promo code not valid']
            ];
        }
        $errors=[];
        $trx=$request->item;

        // return $pct->getRequiredProduct($code->id_promo_campaign);
        return [$pct->validatePromo($code->id_promo_campaign, $id_outlet, $trx, $errors), $errors];
        if($result=$pct->validatePromo($code->id_promo_campaign, $id_outlet, $trx, $errors)){
            $code->load('promo_campaign');
            $result['promo_title']=$code->promo_campaign->campaign_name;
            $result['promo_code']=$request->promo_code;
        }else{
            $result=[
                'status'=>'fail',
                'messages'=>$errors
            ];
            return $result;
        }
        return MyHelper::checkGet($result);
    }

    public function checkValid(ValidateCode $request)
    {
    	$id_user 		= $request->user()->id;
        $phone 	 		= $request->user()->phone;
        $device_id		= $request->device_id;
        $device_type	= $request->device_type;
        $id_outlet		= $request->id_outlet;
        $ip = $request->ip();

        /* Check promo code*/
        $dataCheckPromoCode = [
            'id_user'    => $id_user,
            'device_id'  => $device_id,
            'promo_code' => $request->promo_code,
            'ip'         => $ip
        ];
        $checkFraud = app($this->fraud)->fraudCheckPromoCode($dataCheckPromoCode);
        if($checkFraud['status'] == 'fail'){
            return $checkFraud;
        }
        /* End check promo code */

        // get data promo code, promo campaign, outlet, rule, and product
        $code=PromoCampaignPromoCode::where('promo_code',$request->promo_code)
                ->join('promo_campaigns', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_promo_codes.id_promo_campaign')
                ->where('step_complete', '=', 1)
                ->where( function($q){
                	$q->whereColumn('usage','<','limitation_usage')
                		->orWhere('code_type','Single');
                } )
                ->with([
					'promo_campaign.promo_campaign_outlets',
					'promo_campaign.promo_campaign_product_discount.product' => function($q) {
						$q->select('id_product', 'id_product_category', 'product_code', 'product_name');
					},
					'promo_campaign.promo_campaign_buyxgety_product_requirement.product' => function($q) {
						$q->select('id_product', 'id_product_category', 'product_code', 'product_name');
					},
					'promo_campaign.promo_campaign_tier_discount_product.product' => function($q) {
						$q->select('id_product', 'id_product_category', 'product_code', 'product_name');
					},
					'promo_campaign.promo_campaign_product_discount_rules',
					'promo_campaign.promo_campaign_tier_discount_rules',
					'promo_campaign.promo_campaign_buyxgety_rules'
				])
                ->first();

        if(!$code){
            return [
                'status'=>'fail',
                'messages'=>['Promo code not valid']
            ];
        }else{
        	$code = $code->toArray();
        }
        
        // add available product
        if ( ($code['promo_campaign']['promo_campaign_product_discount_rules']['is_all_product']??false) == 1) 
        {
        	$applied_product = '*';
        	$product = 'semua product';
        }
        elseif ( !empty($code['promo_campaign']['promo_campaign_product_discount']) )
        {
        	$applied_product = $code['promo_campaign']['promo_campaign_product_discount'];
        	$product = $applied_product[0]['product']['product_name']??'product tertentu';
        }
        elseif ( !empty($code['promo_campaign']['promo_campaign_tier_discount_product']) )
        {
        	$applied_product = $code['promo_campaign']['promo_campaign_tier_discount_product'];
        	$product = $applied_product['product']['product_name']??'product tertentu';
        }
        elseif ( !empty($code['promo_campaign']['promo_campaign_buyxgety_product_requirement']) )
        {
        	$applied_product = $code['promo_campaign']['promo_campaign_buyxgety_product_requirement'];
        	$product = $applied_product['product']['product_name']??'product tertentu';
        }
        else
        {
        	$applied_product = [];
        	$product = [];
        }

        // add description
        if ($code['promo_type'] == 'Product discount') 
        {
        	$discount = $code['promo_campaign']['promo_campaign_product_discount_rules']['discount_type']??'Nominal';
        	$qty = $code['promo_campaign']['promo_campaign_product_discount_rules']['max_product']??0;

        	if ($discount == 'Percent') {
        		$discount = ($code['promo_campaign']['promo_campaign_product_discount_rules']['discount_value']??0).' %';
        	}else{
        		$discount = 'Rp '.number_format($code['promo_campaign']['promo_campaign_product_discount_rules']['discount_value']??0);
        	}

    		$key_null = 'Anda berhak mendapatkan potongan %discount% untuk pembelian %product%. Maksimal %qty% buah untuk setiap produk.';
    		$desc = Setting::where('key', '=', 'description_product_discount')->first()['value']??$key_null;

    		$desc = MyHelper::simpleReplace($desc,['discount'=>$discount, 'product'=>$product, 'qty'=>$qty,]);
    	}
    	elseif ($code['promo_type'] == 'Tier discount') 
    	{
    		$min_qty = 1;
    		$max_qty = 1;

    		foreach ($code['promo_campaign']['promo_campaign_tier_discount_rules'] as $key => $rule) {
				$min_req=$rule['min_qty'];
				$max_req=$rule['max_qty'];

				if($min_qty===null||$rule['min_qty']<$min_qty){
					$min_qty=$min_req;
				}
				if($max_qty===null||$rule['max_qty']>$max_qty){
					$max_qty=$max_req;
				}
    		}
    		$key = 'description_tier_discount';
    		$key_null = 'Anda berhak mendapatkan potongan setelah melakukan pembelian %product% sebanyak %minmax%';
    		$minmax=$min_qty!=$max_qty?"$min_qty - $max_qty":$min_qty;
    		$desc = Setting::where('key', '=', 'description_tier_discount')->first()['value']??$key_null;

    		$desc = MyHelper::simpleReplace($desc,['product'=>$product, 'minmax'=>$minmax]);
    	}
    	elseif ($code['promo_type'] == 'Buy X Get Y') 
    	{
    		$min_qty = 1;
    		$max_qty = 1;
    		foreach ($code['promo_campaign']['promo_campaign_buyxgety_rules'] as $key => $rule) {
				$min_req=$rule['min_qty_requirement'];
				$max_req=$rule['max_qty_requirement'];

				if($min_qty===null||$rule['min_qty_requirement']<$min_qty){
					$min_qty=$min_req;
				}
				if($max_qty===null||$rule['max_qty_requirement']>$max_qty){
					$max_qty=$max_req;
				}
    		}
    		$key = 'description_buyxgety_discount';
    		$key_null = 'Anda berhak mendapatkan potongan setelah melakukan pembelian %product% sebanyak %min% - %max%';
    		$minmax=$min_qty!=$max_qty?"$min_qty - $max_qty":$min_qty;
    		$desc = Setting::where('key', '=', 'description_buyxgety_discount')->first()['value']??$key_null;

    		$desc = MyHelper::simpleReplace($desc,['product'=>$product, 'minmax'=>$minmax]);
    	}
    	else
    	{
    		$key = null;
    		$desc = 'no description';
    	}

        $errors=[];
        // check user
        $pct=new PromoCampaignTools();
        if(!$pct->validateUser($code['id_promo_campaign'], $id_user, $phone, $device_type, $device_id, $errors)){
            return [
                'status'=>'fail',
                'messages'=>$errore??['Promo code not valid']
            ];
        }

        // check outlet
		if (isset($id_outlet)) {
			if (!$pct->checkOutletRule($id_outlet, $code['promo_campaign']['is_all_outlet'],$code['promo_campaign']['promo_campaign_outlets'])) {
					return [
	                'status'=>'fail',
	                'messages'=>['Promo tidak berlaku di outlet ini']
	            ];
			}
		}

		$result['title'] 		= $code['promo_title'];
        $result['description']	= $desc;
		$result['errors'] 		= $errors;
		$result['promo_code'] 	= $request->promo_code;

		$result = MyHelper::checkGet($result);
		// return $result;
		// check item
		if (!empty($request->item)) {
        	$bearer = $request->header('Authorization');
        
	        if ($bearer == "") {
	            return [
	                'status'=>'fail',
	                'messages'=>['Promo code not valid']
	            ];
	        }
	        $post = $request->json()->all();
	        $post['log_save'] = 1;
	        $trx = MyHelper::postCURLWithBearer('api/transaction/check', $post, $bearer);
	        
	        foreach ($trx['result'] as $key => $value) {
	        	$result['result'][$key] = $value;
	        }
	        $result['messages'] = $trx['messages'];
	        $result['promo_error'] = $trx['promo_error'];
        }


		return $result;
    }

    public function getProduct($source, $query)
    {
    	// return $source;
    	if ( ($query[$source.'_product_discount_rules']['is_all_product']??false) == 1) 
        {
        	$applied_product = '*';
        	$product = 'semua product';
        }
        elseif ( !empty($query[$source.'_product_discount']) )
        {
        	$applied_product = $query[$source.'_product_discount'];
        	$product = $applied_product[0]['product']['product_name']??'product tertentu';
        }
        elseif ( !empty($query[$source.'_tier_discount_product']) )
        {
        	$applied_product = $query[$source.'_tier_discount_product'];
        	$product = $applied_product['product']['product_name']??'product tertentu';
        }
        elseif ( !empty($query[$source.'_buyxgety_product_requirement']) )
        {
        	$applied_product = $query[$source.'_buyxgety_product_requirement'];
        	$product = $applied_product['product']['product_name']??'product tertentu';
        }
        else
        {
        	$applied_product = [];
        	$product = [];
        }

        $result = [
        	'applied_product' => $applied_product,
        	'product' => $product
        ];
        return $result;
    }

    public function getPromoDescription($source, $query, $product)
    {
    	// add description
        if ($query['promo_type'] == 'Product discount') 
        {
        	$discount = $query[$source.'_product_discount_rules']['discount_type']??'Nominal';
        	$qty = $query[$source.'_product_discount_rules']['max_product']??0;

        	if ($discount == 'Percent') {
        		$discount = ($query[$source.'_product_discount_rules']['discount_value']??0).' %';
        	}else{
        		$discount = 'Rp '.number_format($query[$source.'_product_discount_rules']['discount_value']??0);
        	}

    		$key_null = 'Anda berhak mendapatkan potongan %discount% untuk pembelian %product%. Maksimal %qty% buah untuk setiap produk.';
    		$desc = Setting::where('key', '=', 'description_product_discount')->first()['value']??$key_null;

    		$desc = MyHelper::simpleReplace($desc,['discount'=>$discount, 'product'=>$product, 'qty'=>$qty,]);
    	}
    	elseif ($query['promo_type'] == 'Tier discount') 
    	{
    		$min_qty = 1;
    		$max_qty = 1;

    		foreach ($query[$source.'_tier_discount_rules'] as $key => $rule) {
				$min_req=$rule['min_qty'];
				$max_req=$rule['max_qty'];

				if($min_qty===null||$rule['min_qty']<$min_qty){
					$min_qty=$min_req;
				}
				if($max_qty===null||$rule['max_qty']>$max_qty){
					$max_qty=$max_req;
				}
    		}
    		$key = 'description_tier_discount';
    		$key_null = 'Anda berhak mendapatkan potongan setelah melakukan pembelian %product% sebanyak %minmax%';
    		$minmax=$min_qty!=$max_qty?"$min_qty - $max_qty":$min_qty;
    		$desc = Setting::where('key', '=', 'description_tier_discount')->first()['value']??$key_null;

    		$desc = MyHelper::simpleReplace($desc,['product'=>$product, 'minmax'=>$minmax]);
    	}
    	elseif ($query['promo_type'] == 'Buy X Get Y') 
    	{
    		$min_qty = 1;
    		$max_qty = 1;
    		foreach ($query[$source.'_buyxgety_rules'] as $key => $rule) {
				$min_req=$rule['min_qty_requirement'];
				$max_req=$rule['max_qty_requirement'];

				if($min_qty===null||$rule['min_qty_requirement']<$min_qty){
					$min_qty=$min_req;
				}
				if($max_qty===null||$rule['max_qty_requirement']>$max_qty){
					$max_qty=$max_req;
				}
    		}
    		$key = 'description_buyxgety_discount';
    		$key_null = 'Anda berhak mendapatkan potongan setelah melakukan pembelian %product% sebanyak %min% - %max%';
    		$minmax=$min_qty!=$max_qty?"$min_qty - $max_qty":$min_qty;
    		$desc = Setting::where('key', '=', 'description_buyxgety_discount')->first()['value']??$key_null;

    		$desc = MyHelper::simpleReplace($desc,['product'=>$product, 'minmax'=>$minmax]);
    	}
    	else
    	{
    		$key = null;
    		$desc = 'no description';
    	}

    	return $desc;
    }
}
