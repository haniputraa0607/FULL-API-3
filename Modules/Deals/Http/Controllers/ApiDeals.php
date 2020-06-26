<?php

namespace Modules\Deals\Http\Controllers;

use App\Http\Models\Configs;
use App\Http\Models\DealTotal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;

use App\Http\Models\Outlet;
use App\Http\Models\Deal;
use App\Http\Models\DealsOutlet;
use App\Http\Models\DealsPaymentManual;
use App\Http\Models\DealsPaymentMidtran;
use App\Http\Models\DealsUser;
use App\Http\Models\DealsVoucher;
use App\Http\Models\SpinTheWheel;
use App\Http\Models\Setting;
use App\Http\Models\DealsPromotionTemplate;

use Modules\Deals\Entities\DealsProductDiscount;
use Modules\Deals\Entities\DealsProductDiscountRule;
use Modules\Deals\Entities\DealsTierDiscountProduct;
use Modules\Deals\Entities\DealsTierDiscountRule;
use Modules\Deals\Entities\DealsBuyxgetyProductRequirement;
use Modules\Deals\Entities\DealsBuyxgetyRule;

use DB;

use Modules\Deals\Http\Requests\Deals\Create;
use Modules\Deals\Http\Requests\Deals\Update;
use Modules\Deals\Http\Requests\Deals\Delete;
use Modules\Deals\Http\Requests\Deals\ListDeal;
use Modules\Deals\Http\Requests\Deals\DetailDealsRequest;
use Modules\Deals\Http\Requests\Deals\UpdateContentRequest;
use Modules\Deals\Http\Requests\Deals\UpdateComplete;

use Illuminate\Support\Facades\Schema;

class ApiDeals extends Controller
{

    function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->user     = "Modules\Users\Http\Controllers\ApiUser";
        $this->hidden_deals     = "Modules\Deals\Http\Controllers\ApiHiddenDeals";
        $this->autocrm = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->subscription = "Modules\Subscription\Http\Controllers\ApiSubscription";
        $this->promo_campaign       = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
    }

    public $saveImage = "img/deals/";


    function rangePoint()
    {
        $start = Setting::where('key', 'point_range_start')->get()->first();
        $end = Setting::where('key', 'point_range_end')->get()->first();

        if (!$start) {
            $start['value'] = 0;
        }

        if (!$end) {
            $end['value'] = 1000000;
        }

        return response()->json([
            'status'    => 'success',
            'result'    => [
                'point_range_start' => $start['value'],
                'point_range_end'   => $end['value'],
            ]
        ]);
    }

    /* CHECK INPUTAN */
    function checkInputan($post)
    {

        $data = [];

        if (isset($post['deals_promo_id_type'])) {
            $data['deals_promo_id_type'] = $post['deals_promo_id_type'];
        }
        if (isset($post['deals_type'])) {
            $data['deals_type'] = $post['deals_type'];
        }
        if (isset($post['deals_voucher_type'])) {
            $data['deals_voucher_type'] = $post['deals_voucher_type'];
            if ($data['deals_voucher_type'] == 'Unlimited') {
            	$data['deals_total_voucher'] = 0;
            }

            if ($post['deals_type'] == 'Promotion') 
            {
	            if($post['deals_voucher_type'] == 'List Vouchers'){
					$data['deals_list_voucher'] = str_replace("\r\n", ',', $post['voucher_code']);
				}else{
					$data['deals_list_voucher'] = null;
				}
            }
        }
        if (isset($post['deals_promo_id'])) {
            $data['deals_promo_id'] = $post['deals_promo_id'];
        }
        if (isset($post['deals_title'])) {
            $data['deals_title'] = $post['deals_title'];
        }
        if (isset($post['deals_second_title'])) {
            $data['deals_second_title'] = $post['deals_second_title'];
        }
        if (isset($post['deals_description'])) {
            $data['deals_description'] = $post['deals_description'];
        }
        if (isset($post['product_type'])) {
            $data['product_type'] = $post['product_type'];
        }
        if (isset($post['deals_tos'])) {
            $data['deals_tos'] = $post['deals_tos'];
        }
        if (isset($post['deals_short_description'])) {
            $data['deals_short_description'] = $post['deals_short_description'];
        }
        if (isset($post['deals_image'])) {

            if ($post['deals_type'] == 'Promotion') 
            {
            	$promotionPath = 'img/promotion/deals';
            }
            if (!file_exists($promotionPath??$this->saveImage)) {
                mkdir($promotionPath??$this->saveImage, 0777, true);
            }

            $upload = MyHelper::uploadPhotoStrict($post['deals_image'], ($promotionPath??$this->saveImage), 500, 500);
            
            if (isset($upload['status']) && $upload['status'] == "success") {
                $data['deals_image'] = $upload['path'];
            } else {
                $result = [
                    'error'    => 1,
                    'status'   => 'fail',
                    'messages' => ['fail upload image']
                ];

                return $result;
            }
        }
        // if (isset($post['deals_video'])) {
        //     $data['deals_video'] = $post['deals_video'];
        // }
        if (isset($post['id_product'])) {
            $data['id_product'] = $post['id_product'];
        }
        if (isset($post['id_brand'])) {
            $data['id_brand'] = $post['id_brand'];
        }
        if (isset($post['deals_start'])) {
            $data['deals_start'] = date('Y-m-d H:i:s', strtotime($post['deals_start']));
        }
        if (isset($post['deals_end'])) {
            $data['deals_end'] = date('Y-m-d H:i:s', strtotime($post['deals_end']));
        }
        if (isset($post['deals_publish_start'])) {
            $data['deals_publish_start'] = date('Y-m-d H:i:s', strtotime($post['deals_publish_start']));
        }
        if (isset($post['deals_publish_end'])) {
            $data['deals_publish_end'] = date('Y-m-d H:i:s', strtotime($post['deals_publish_end']));
        }

        // ---------------------------- DURATION
        if (isset($post['deals_voucher_duration'])) {
            $data['deals_voucher_duration'] = $post['deals_voucher_duration'];
        }
        if (empty($post['deals_voucher_duration']) || is_null($post['deals_voucher_duration'])) {
            $data['deals_voucher_duration'] = null;
        }

        // ---------------------------- EXPIRED
        if (isset($post['deals_voucher_expired'])) {
            $data['deals_voucher_expired'] = $post['deals_voucher_expired'];
        }
        if (empty($post['deals_voucher_expired']) || is_null($post['deals_voucher_expired'])) {
            $data['deals_voucher_expired'] = null;
        }
        // ---------------------------- VOUCHER START
        $data['deals_voucher_start']=$post['deals_voucher_start']??null;
        // ---------------------------- POINT
        if (isset($post['deals_voucher_price_point'])) {
            $data['deals_voucher_price_point'] = $post['deals_voucher_price_point'];
        }

        if (empty($post['deals_voucher_price_point']) || is_null($post['deals_voucher_price_point'])) {
            $data['deals_voucher_price_point'] = null;
        }

        // ---------------------------- CASH
        if (isset($post['deals_voucher_price_cash'])) {
            $data['deals_voucher_price_cash'] = $post['deals_voucher_price_cash'];
        }
        if (empty($post['deals_voucher_price_cash']) || is_null($post['deals_voucher_price_cash'])) {
            $data['deals_voucher_price_cash'] = null;
        }

        if (isset($post['deals_total_voucher'])) {
            $data['deals_total_voucher'] = $post['deals_total_voucher'];
        }
        if (isset($post['deals_total_claimed'])) {
            $data['deals_total_claimed'] = $post['deals_total_claimed'];
        }
        if (isset($post['deals_total_redeemed'])) {
            $data['deals_total_redeemed'] = $post['deals_total_redeemed'];
        }
        if (isset($post['deals_total_used'])) {
            $data['deals_total_used'] = $post['deals_total_used'];
        }
        if (isset($post['id_outlet'])) {
        	if ($post['deals_type'] == 'Promotion') {
        		$data['deals_list_outlet'] = implode(',', $post['id_outlet']);
				unset($data['id_outlet']);
        	}else{
        	    $data['id_outlet'] = $post['id_outlet'];
        	}
            if (in_array("all", $data['id_outlet'])){
                $data['is_all_outlet'] = 1;
                $data['id_outlet'] = [];
            }else{
                $data['is_all_outlet'] = 0;
            }
        }
        if (isset($post['user_limit'])) {
            $data['user_limit'] = $post['user_limit'];
        } else {
            $data['user_limit'] = 0;
        }

        if (isset($post['is_online'])) {
            $data['is_online'] = 1;
        } else {
            $data['is_online'] = 0;
        }

        if (isset($post['is_offline'])) {
            $data['is_offline'] = 1;
        } else {
            $data['is_offline'] = 0;
            $data['deals_promo_id_type'] = null;
            $data['deals_promo_id'] = null;
        }

        if (isset($post['charged_central']) || isset($post['charged_outlet'])) {
            $data['charged_central'] = $post['charged_central'];
            $data['charged_outlet'] = $post['charged_outlet'];
        }

        return $data;
    }

    /* CREATE */
    function create($data)
    {
        $data = $this->checkInputan($data);
        $data['created_by'] = auth()->user()->id;
        // error
        if (isset($data['error'])) {
            unset($data['error']);
            return response()->json($data);
        }

        if ($data['deals_type'] == 'Promotion') {
        	$save = DealsPromotionTemplate::create($data);
        }else{
        	$save = Deal::create($data);
        }

        if ($save) {
            if (isset($data['id_outlet']) && $data['is_all_outlet'] == 0) {
                if (isset($data['id_outlet'])) {
                    $saveOutlet = $this->saveOutlet($save, $data['id_outlet']);

                    if (!$saveOutlet) {
                        return false;
                    }
                }
            }
        }
        return $save;
    }

    /* CREATE REQUEST */
    function createReq(Create $request)
    {
        DB::beginTransaction();
        $save = $this->create($request->json()->all());

        if ($save) {
            DB::commit();
            $dt = '';
            switch ($save->deals_type){
                case 'Deals':
                    $dt = 'Deals';
                    break;
                case 'Hidden':
                    $dt = 'Inject Voucher';
                    break;
                case 'WelcomeVoucher':
                    $dt = 'Welcome Voucher';
                    break;
            }
            $deals = $save->toArray();
            $send = app($this->autocrm)->SendAutoCRM('Create '.$dt, $request->user()->phone, [
                'voucher_type' => $deals['deals_voucher_type']?:'',
                'promo_id_type' => $deals['deals_promo_id_type']?:'',
                'promo_id' => $deals['deals_promo_id']?:'',
                'detail' => view('deals::emails.detail',['detail'=>$deals])->render()
            ]+$deals,null,true);
        } else {
            DB::rollback();
        }

        return response()->json(MyHelper::checkCreate($save));
    }

    /* LIST */
    function listDeal(ListDeal $request) {
        
        if($request->json('forSelect2'))
        {
            $deals = Deal::select('id_deals','deals_title')
            		->where('deals_type','Deals')
            		->whereDoesntHave('featured_deals');

            if ($request->json('featured')) {
            	$deals = $deals->where('deals_end', '>', date('Y-m-d H:i:s'))
            			->where('deals_publish_end', '>', date('Y-m-d H:i:s'))
            			->where('step_complete', '=', 1);
            }

            return MyHelper::checkGet($deals->get());
        }

        // return $request->json()->all();
        $deals = (new Deal)->newQuery();
        $user = $request->user();
        $curBalance = (int) $user->balance??0;
        if($request->json('admin')){
            $deals->addSelect('id_brand');
            $deals->with('brand');
        }else{
            if($request->json('deals_type') != 'WelcomeVoucher' && !$request->json('web')){
                $deals->where('deals_end', '>', date('Y-m-d H:i:s'));
            }
        }
        if ($request->json('id_outlet') && is_integer($request->json('id_outlet'))) {
            $deals = $deals->join('deals_outlets', 'deals.id_deals', 'deals_outlets.id_deals')
                ->where('id_outlet', $request->json('id_outlet'))
                ->addSelect('deals.*')->distinct();
        }

        // brand
        if ($request->json('id_brand')) {
            $deals->where('id_brand',$request->json('id_brand'));
        }
        // deals subscription
        if ($request->json('deals_type') == "Subscription") {
            $deals->with('deals_subscriptions');
        }

        if ($request->json('id_deals')) {
            $deals->with([
                'deals_vouchers',
                // 'deals_vouchers.deals_voucher_user',
                // 'deals_vouchers.deals_user.user'
            ])->where('id_deals', $request->json('id_deals'))->with(['outlets', 'outlets.city', 'product','brand']);
        }else{
            $deals->addSelect('id_deals','deals_title','deals_second_title','deals_voucher_price_point','deals_voucher_price_cash','deals_total_voucher','deals_total_claimed','deals_voucher_type','deals_image','deals_start','deals_end','deals_type','is_offline','is_online','step_complete','deals_total_used','promo_type');
            if(strpos($request->user()->level,'Admin')>=0){
                $deals->addSelect('deals_promo_id','deals_publish_start','deals_publish_end','created_at');
            }
            // return($deals->toSql());
        }
        if ($request->json('rule')){
             $this->filterList($deals,$request->json('rule'),$request->json('operator')??'and');
        }
        if ($request->json('publish')) {
            $deals->where('deals_publish_end', '>=', date('Y-m-d H:i:s'));
            $deals->where('step_complete', '=', 1);
        }

        if ($request->json('deals_type')) {
            // get > 1 deals types
            if (is_array($request->json('deals_type'))) {
                $deals->whereIn('deals_type', $request->json('deals_type'));
            } else {
                $deals->where('deals_type', $request->json('deals_type'));
            }
        }

        if ($request->json('deals_promo_id')) {
            $deals->where('deals_promo_id', $request->json('deals_promo_id'));
        }

        if ($request->json('key_free')) {
            $deals->where(function($query) use ($request){
                $query->where('deals_title', 'LIKE', '%' . $request->json('key_free') . '%')
                    ->orWhere('deals_second_title', 'LIKE', '%' . $request->json('key_free') . '%');
            });
        }


        /* ========================= TYPE ========================= */
        $deals->where(function ($query) use ($request) {
            // cash
            if ($request->json('voucher_type_paid')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNotNull('deals_voucher_price_cash');
                    if(is_numeric($val=$request->json('price_range_start'))){
                        $amp->where('deals_voucher_price_cash','>=',$val);
                    }
                    if(is_numeric($val=$request->json('price_range_end'))){
                        $amp->where('deals_voucher_price_cash','<=',$val);
                    }
                });
                // print_r('voucher_type_paid');
                // print_r($query->get()->toArray());die();
            }

            if ($request->json('voucher_type_point')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNotNull('deals_voucher_price_point');
                    if(is_numeric($val=$request->json('point_range_start'))){
                        $amp->where('deals_voucher_price_point','>=',$val);
                    }
                    if(is_numeric($val=$request->json('point_range_end'))){
                        $amp->where('deals_voucher_price_point','<=',$val);
                    }
                });
                // print_r('voucher_type_point');
                // print_r($query->get()->toArray());die();
            }

            if ($request->json('voucher_type_free')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNull('deals_voucher_price_point')->whereNull('deals_voucher_price_cash');
                });
                // print_r('voucher_type_free');
                // print_r($query->get()->toArray());die();
            }
        });

        // print_r($deals->get()->toArray());
        // $deals = $deals->orderBy('deals_start', 'ASC');

        if ($request->json('lowest_point')) {
            $deals->orderBy('deals_voucher_price_point', 'ASC');
        }

        if ($request->json('highest_point')) {
            $deals->orderBy('deals_voucher_price_point', 'DESC');
        }

        if ($request->json('alphabetical')) {
            $deals->orderBy('deals_title', 'ASC');
        } else if ($request->json('newest')) {
            $deals->orderBy('deals_publish_start', 'DESC');
        } else if ($request->json('oldest')) {
            $deals->orderBy('deals_publish_start', 'ASC');
        } else {
            $deals->orderBy('deals_end', 'ASC');
        }
        if ($request->json('id_city')) {
            $deals->with('outlets','outlets.city');
        }

        $deals = $deals->get()->toArray();
        // print_r($deals); exit();

        if (!empty($deals)) {
            $city = "";

            // jika ada id city yg faq
            if ($request->json('id_city')) {
                $city = $request->json('id_city');
            }

            $deals = $this->kotacuks($deals, $city,$request->json('admin'));
        }

        if ($request->json('highest_available_voucher')) {
            $tempDeals = [];
            $dealsUnlimited = $this->unlimited($deals);

            if (!empty($dealsUnlimited)) {
                foreach ($dealsUnlimited as $key => $value) {
                    array_push($tempDeals, $deals[$key]);
                }
            }

            $limited = $this->limited($deals);

            if (!empty($limited)) {
                $tempTempDeals = [];
                foreach ($limited as $key => $value) {
                    array_push($tempTempDeals, $deals[$key]);
                }

                $tempTempDeals = $this->highestAvailableVoucher($tempTempDeals);

                // return $tempTempDeals;
                $tempDeals =  array_merge($tempDeals, $tempTempDeals);
            }

            $deals = $tempDeals;
        }

        if ($request->json('lowest_available_voucher')) {
            $tempDeals = [];

            $limited = $this->limited($deals);

            if (!empty($limited)) {
                foreach ($limited as $key => $value) {
                    array_push($tempDeals, $deals[$key]);
                }

                $tempDeals = $this->lowestAvailableVoucher($tempDeals);
            }

            $dealsUnlimited = $this->unlimited($deals);

            if (!empty($dealsUnlimited)) {
                foreach ($dealsUnlimited as $key => $value) {
                    array_push($tempDeals, $deals[$key]);
                }
            }

            $deals = $tempDeals;
        }



        // if deals detail, add webview url & btn text
        if ($request->json('id_deals') && !empty($deals)) {
            //url webview
            $deals[0]['webview_url'] = env('APP_URL') . "webview/deals/" . $deals[0]['id_deals'] . "/" . $deals[0]['deals_type'];
            // text tombol beli
            $deals[0]['button_status'] = 0;
            //text konfirmasi pembelian
            if($deals[0]['deals_voucher_price_type']=='free'){
                //voucher free
                $deals[0]['button_text'] = 'Ambil';
                $payment_message = Setting::where('key', 'payment_messages')->pluck('value_text')->first()??'Kamu yakin ingin mengambil voucher ini?';
                $payment_message = MyHelper::simpleReplace($payment_message,['deals_title'=>$deals[0]['deals_title']]);
            }
            elseif($deals[0]['deals_voucher_price_type']=='point')
            {
                $deals[0]['button_text'] = 'Tukar';
                $payment_message = Setting::where('key', 'payment_messages_point')->pluck('value_text')->first()??'Anda akan menukarkan %point% points anda dengan Voucher %deals_title%?';
                $payment_message = MyHelper::simpleReplace($payment_message,['point'=>$deals[0]['deals_voucher_price_point'],'deals_title'=>$deals[0]['deals_title']]);
            }
            else
            {
                $deals[0]['button_text'] = 'Beli';
                $payment_message = Setting::where('key', 'payment_messages_cash')->pluck('value_text')->first()??'Anda akan membeli Voucher %deals_title% dengan harga %cash% ?';
                $payment_message = MyHelper::simpleReplace($payment_message,['cash'=>$deals[0]['deals_voucher_price_cash'],'deals_title'=>$deals[0]['deals_title']]);
            }
            $payment_success_message = Setting::where('key', 'payment_success_messages')->pluck('value_text')->first()??'Apakah kamu ingin menggunakan Voucher sekarang?';
            $deals[0]['payment_message'] = $payment_message;
            $deals[0]['payment_success_message'] = $payment_success_message;
            if($deals[0]['deals_voucher_price_type']=='free'&&$deals[0]['deals_status']=='available'){
                $deals[0]['button_status']=1;
            }else {
                if($deals[0]['deals_voucher_price_type']=='point'){
                    $deals[0]['button_status']=$deals[0]['deals_voucher_price_point']<=$curBalance?1:0;
                    if($deals[0]['deals_voucher_price_point']>$curBalance){
                        $deals[0]['payment_fail_message'] = Setting::where('key', 'payment_fail_messages')->pluck('value_text')->first()??'Mohon maaf, point anda tidak cukup';
                    }
                }else{
                    if($deals[0]['deals_status']=='available'){
                        $deals[0]['button_status'] = 1;
                    }
                }
            }
        }

        //jika mobile di pagination
        if (!$request->json('web')) {
            //pagination
            if ($request->get('page')) {
                $page = $request->get('page');
            } else {
                $page = 1;
            }

            $resultData = [];
            $paginate   = 10;
            $start      = $paginate * ($page - 1);
            $all        = $paginate * $page;
            $end        = $all;
            $next       = true;

            if ($all > count($deals)) {
                $end = count($deals);
                $next = false;
            }


            for ($i=$start; $i < $end; $i++) {
                $deals[$i]['time_to_end']=strtotime($deals[$i]['deals_end'])-time();
                array_push($resultData, $deals[$i]);
            }

            $result['current_page']  = $page;
            $result['data']          = $resultData;
            $result['total']         = count($resultData);
            $result['next_page_url'] = null;
            if ($next == true) {
                $next_page = (int) $page + 1;
                $result['next_page_url'] = ENV('APP_API_URL') . 'api/deals/list?page=' . $next_page;
            }


            // print_r($deals); exit();
            if(!$result['total']){
                $result=[];
            }

            if(
                $request->json('voucher_type_point') ||
                $request->json('voucher_type_paid') ||
                $request->json('voucher_type_free') ||
                $request->json('id_city') ||
                $request->json('key_free')
            ){
                $resultMessage = 'Maaf, voucher yang kamu cari belum tersedia';
            }else{
                $resultMessage = 'Nantikan penawaran menarik dari kami';
            }
            return response()->json(MyHelper::checkGet($result, $resultMessage));

        }else{
            return response()->json(MyHelper::checkGet($deals));
        }
    }

    /* LIST */
    function myDeal(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();

        $deals = DealsUser::with(['deals_voucher.deal'])
        ->where('id_user', $user['id'])
        ->where('id_deals_user', $post['id_deals_user'])
        ->whereNull('redeemed_at')
        ->whereIn('paid_status', ['Completed','Free'])
        ->first();

        return response()->json(MyHelper::checkGet($deals));
    }
    public function filterList($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }
        $where=$operator=='and'?'where':'orWhere';
        $subjects=['deals_title','deals_title','deals_second_title','deals_promo_id_type','deals_promo_id','id_brand','deals_total_voucher','deals_start', 'deals_end', 'deals_publish_start', 'deals_publish_end', 'deals_voucher_start', 'deals_voucher_expired', 'deals_voucher_duration', 'user_limit', 'total_voucher_subscription', 'deals_total_claimed', 'deals_total_redeemed', 'deals_total_used', 'created_at', 'updated_at'];
        foreach ($subjects as $subject) {
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    $query->$where($subject,$rule[0],$rule[1]);
                }
            }
        }
        if($rules2=$newRule['voucher_code']??false){
            foreach ($rules2 as $rule) {
                $query->{$where.'Has'}('deals_vouchers',function($query) use ($rule){
                    $query->where('deals_vouchers.voucher_code',$rule[0],$rule[1]);
                });
            }
        }
        if($rules2=$newRule['used_by']??false){
            foreach ($rules2 as $rule) {
                $query->{$where.'Has'}('deals_vouchers.deals_voucher_user',function($query) use ($rule){
                    $query->where('phone',$rule[0],$rule[1]);
                });
            }
        }
        if($rules2=$newRule['deals_total_available']??false){
            foreach ($rules2 as $rule) {
                $query->$where(DB::raw('(deals.deals_total_voucher - deals.deals_total_claimed)'),$rule[0],$rule[1]);
            }
        }
        if($rules2=$newRule['id_outlet']??false){
            foreach ($rules2 as $rule) {
                $query->{$where.'Has'}('outlets',function($query) use ($rule){
                    $query->where('outlets.id_outlet',$rule[0],$rule[1]);
                });
            }
        }
        if($rules2=$newRule['voucher_claim_time']??false){
            foreach ($rules2 as $rule) {
                $rule[1]=strtotime($rule[1]);
                $query->{$where.'Has'}('deals_vouchers',function($query) use ($rule){
                    $query->whereHas('deals_user',function($query) use ($rule){
                        $query->where(DB::raw('UNIX_TIMESTAMP(deals_users.claimed_at)'),$rule[0],$rule[1]);
                    });
                });
            }
        }
        if($rules2=$newRule['voucher_redeem_time']??false){
            foreach ($rules2 as $rule) {
                $rule[1]=strtotime($rule[1]);
                $query->{$where.'Has'}('deals_vouchers',function($query) use ($rule){
                    $query->whereHas('deals_user',function($query) use ($rule){
                        $query->where('deals_users.redeemed_at',$rule[0],$rule[1]);
                    });
                });
            }
        }
        if($rules2=$newRule['voucher_used_time']??false){
            foreach ($rules2 as $rule) {
                $rule[1]=strtotime($rule[1]);
                $query->{$where.'Has'}('deals_vouchers',function($query) use ($rule){
                    $query->whereHas('deals_user',function($query) use ($rule){
                        $query->where('deals_users.used_at',$rule[0],$rule[1]);
                    });
                });
            }
        }
    }
    /* UNLIMITED */
    function unlimited($deals)
    {
        $unlimited = array_filter(array_column($deals, "available_voucher"), function ($deals) {
            if ($deals == "*") {
                return $deals;
            }
        });

        return $unlimited;
    }

    function limited($deals)
    {
        $limited = array_filter(array_column($deals, "available_voucher"), function ($deals) {
            if ($deals != "*") {
                return $deals;
            }
        });

        return $limited;
    }

    /* SORT DEALS */
    function highestAvailableVoucher($deals)
    {
        usort($deals, function ($a, $b) {
            return $a['available_voucher'] < $b['available_voucher'];
        });

        return $deals;
    }

    function lowestAvailableVoucher($deals)
    {
        usort($deals, function ($a, $b) {
            return $a['available_voucher'] > $b['available_voucher'];
        });

        return $deals;
    }

    /* INI LIST KOTA */
    function kotacuks($deals, $city = "",$admin=false)
    {
        $timeNow = date('Y-m-d H:i:s');

        foreach ($deals as $key => $value) {
            $markerCity = 0;

            $deals[$key]['outlet_by_city'] = [];

            // set time
            $deals[$key]['time_server'] = $timeNow;

            if (!empty($value['outlets'])) {
                // ambil kotanya dulu
                $kota = array_column($value['outlets'], 'city');
                $kota = array_values(array_map("unserialize", array_unique(array_map("serialize", $kota))));

                // jika ada pencarian kota
                if (!empty($city)) {
                    $cariKota = array_search($city, array_column($kota, 'id_city'));

                    if (is_integer($cariKota)) {
                        $markerCity = 1;
                    }
                }

                foreach ($kota as $k => $v) {
                    if ($v) {

                        $kota[$k]['outlet'] = [];

                        foreach ($value['outlets'] as $outlet) {
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

                $deals[$key]['outlet_by_city'] = $kota;
            }

            // unset($deals[$key]['outlets']);
            // jika ada pencarian kota
            if (!empty($city)) {
                if ($markerCity == 0) {
                    unset($deals[$key]);
                    continue;
                }
            }

            $calc = $value['deals_total_voucher'] - $value['deals_total_claimed'];

            if ($value['deals_voucher_type'] == "Unlimited") {
                $calc = '*';
            }

            if(is_numeric($calc)){
                if($calc||$admin){
                    $deals[$key]['percent_voucher'] = $calc*100/$value['deals_total_voucher'];
                }else{
                    unset($deals[$key]);
                    continue;
                }
            }else{
                $deals[$key]['percent_voucher'] = 100;
            }

            $deals[$key]['show'] = 1;
            $deals[$key]['available_voucher'] = (string) $calc;
            // deals masih ada?
            // print_r($deals[$key]['available_voucher']);
        }

        // print_r($deals); exit();
        $deals = array_values($deals);

        return $deals;
    }

    /* LIST USER */
    function listUserVoucher(Request $request)
    {
    	$post = $request->json()->all();
        $deals = DealsUser::join('deals_vouchers', 'deals_vouchers.id_deals_voucher', '=', 'deals_users.id_deals_voucher');

        if ($request->json('id_deals')) {
            $deals->where('deals_vouchers.id_deals', $request->json('id_deals'));
        }

        if ($request->json('rule')){
             $this->filterUserVoucher($deals,$request->json('rule'),$request->json('operator')??'and');
        }

        $deals = $deals->with([
        			'user',
        			'outlet',
        			'dealVoucher.transaction_voucher' => function($q) {
        				$q->where('status','=','success');
        			},
        			'dealVoucher.transaction_voucher.transaction' => function($q) {
        				$q->select(
        					'id_transaction',
        					'transaction_receipt_number',
        					'trasaction_type',
        					'transaction_grandtotal'
        				);
        			}
        		]);
        $deals = $deals->orderBy('claimed_at', "ASC")->paginate(10);

        return response()->json(MyHelper::checkGet($deals));
    }

    /* FILTER LIST USER VOUCHER */
    public function filterUserVoucher($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }

        $where=$operator=='and'?'where':'orWhere';

        if($rules2=$newRule['status']??false){
            foreach ($rules2 as $rule) {

            	if ($rule[1] == 'used') 
            	{
	                $query->{$where.'NotNull'}('used_at');
            	}
            	elseif ($rule[1] == 'expired') 
            	{
	                $query->$where(function($q) {
	                	$q->whereNotNull('voucher_expired_at')
	                		->whereDate('voucher_expired_at','<',date("Y-m-d H:i:s"));
	                });
            	}
            	elseif ($rule[1] == 'redeemed') 
            	{
	                $query->{$where.'NotNull'}('redeemed_at');
            	}
            	else
            	{
	                $query->{$where.'NotNull'}('claimed_at');
            	}
            }
        }
        if($rules2=$newRule['used_by']??false){
            foreach ($rules2 as $rule) {
                $query->{$where.'Has'}('user',function($query) use ($rule){
                    $query->where('phone',$rule[0],$rule[1]);
                });
            }
        }
        if($rules2=$newRule['claim_date']??false){
            foreach ($rules2 as $rule) {
                $query->{$where.'Date'}('claimed_at',$rule[0],date("Y-m-d H:i:s", strtotime($rule[1])));
            }
        }
        if($rules2=$newRule['id_outlet']??false){
            foreach ($rules2 as $rule) {
                $query->{$where.'Has'}('outlet',function($query) use ($rule){
                    $query->where('outlets.id_outlet',$rule[0],$rule[1]);
                });
            }
        }
    }

    /* LIST VOUCHER */
    function listVoucher(Request $request)
    {

    	if ($request->select) {
        	$deals = DealsVoucher::select($request->select);
    	}else{
        	$deals = DealsVoucher::select('*');
    	}

        if ($request->json('id_deals')) {
            $deals->where('id_deals', $request->json('id_deals'));
        }

        if ($request->is_all) {
        	$deals = $deals->get();
        }else{
        	$deals = $deals->paginate(10);
        }

        return response()->json(MyHelper::checkGet($deals));
    }

    /* UPDATE */
    function update($id, $data)
    {
        $data = $this->checkInputan($data);
        $deals = Deal::find($id);
        $data['step_complete'] = 0;
        $data['last_updated_by'] = auth()->user()->id;

        if ($deals['id_brand'] != $data['id_brand']) {
        	app($this->promo_campaign)->deleteAllProductRule('deals', $id);
        }
        if (($data['deals_voucher_type']??false) != 'List Vouchers') {
        	DealsVoucher::where('id_deals',$id)->delete();
        }
        if ( !empty($deals['deals_total_claimed']) ) {
        	return false;
        }
        // error
        if (isset($data['error'])) {
            unset($data['error']);
            return response()->json($data);
        }

        // delete old images
        if (isset($data['deals_image'])) {
            $this->deleteImage($id);
        }

        if (isset($data['id_outlet'])) {

            // DELETE
            $this->deleteOutlet($id);

            // SAVE
            if($data['is_all_outlet'] == 0){
                $saveOutlet = $this->saveOutlet($deals, $data['id_outlet']);
            }
            unset($data['id_outlet']);
        }

        $save = Deal::where('id_deals', $id)->update($data);

        return $save;
    }

    /* DELETE IMAGE */
    function deleteImage($id)
    {
        $cekImage = Deal::where('id_deals', $id)->get()->first();

        if (!empty($cekImage)) {
            if (!empty($cekImage->deals_image)) {
                $delete = MyHelper::deletePhoto($cekImage->deals_image);
            }
        }
        return true;
    }

    /* UPDATE REQUEST */
    function updateReq(Update $request)
    {

        DB::beginTransaction();
        $save = $this->update($request->json('id_deals'), $request->json()->all());

        if ($save) {
            DB::commit();
            $dt = '';
            switch (strtolower($request->json('deals_type'))){
                case 'deals':
                    $dt = 'Deals';
                    break;
                case 'hidden':
                    $dt = 'Inject Voucher';
                    break;
                case 'welcomevoucher':
                    $dt = 'Welcome Voucher';
                    break;
            }
            $deals = Deal::where('id_deals',$request->json('id_deals'))->first()->toArray();
            $send = app($this->autocrm)->SendAutoCRM('Update '.$dt, $request->user()->phone, [
                'voucher_type' => $deals['deals_voucher_type']?:'',
                'promo_id_type' => $deals['deals_promo_id_type']?:'',
                'promo_id' => $deals['deals_promo_id']?:'',
                'detail' => view('deals::emails.detail',['detail'=>$deals])->render()
            ]+$deals,null,true);
	        return response()->json(MyHelper::checkUpdate($save));
        } else {
            DB::rollback();
        	return response()->json(['status' => 'fail','messages' => ['Cannot update deals because someone has already claimed a voucher']]);
        }

    }

    /* DELETE */
    function delete($id)
    {
        // delete outlet
        DealsOutlet::where('id_deals', $id)->delete();

        $delete = Deal::where('id_deals', $id)->delete();
        return $delete;
    }

    /* DELETE REQUEST */
    function deleteReq(Delete $request)
    {
        DB::beginTransaction();

        // check spin the wheel
        if ($request->json('deals_type') !== null && $request->json('deals_type') == "Spin") {
            $spin = SpinTheWheel::where('id_deals', $request->json('id_deals'))->first();
            if ($spin != null) {
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Item already used in Spin The Wheel Setting.']
                ]);
            }
        }

        $check = $this->checkDelete($request->json('id_deals'));
        if ($check) {
            // delete image first
            $this->deleteImage($request->json('id_deals'));

            $delete = $this->delete($request->json('id_deals'));

            if ($delete) {
                DB::commit();
            } else {
                DB::rollback();
            }

            return response()->json(MyHelper::checkDelete($delete));
        } else {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Deal already used.']
            ]);
        }
    }

    /* CHECK DELETE */
    function checkDelete($id)
    {
        $database = [
            'deals_vouchers',
            'deals_payment_manuals',
            'deals_payment_midtrans',
        ];

        foreach ($database as $val) {
            // check apakah ada atau nggak tablenya
            if (Schema::hasTable($val)) {
                $cek = DB::table($val);

                if ($val == "deals_vouchers") {
                    $cek->where('deals_voucher_status', '=', 'Sent');
                }

                $cek = $cek->where('id_deals', $id)->first();

                if (!empty($cek)) {
                    return false;
                }
            }
        }

        return true;
    }

    /* OUTLET */
    function saveOutlet($deals, $id_outlet = [])
    {
        $id_deals=$deals->id_deals;
        $id_brand=$deals->id_brand;
        $dataOutlet = [];

        /*If select all outlet, not save to table deals outlet*/
        foreach ($id_outlet as $value) {
            array_push($dataOutlet, [
                'id_outlet' => $value,
                'id_deals'  => $id_deals
            ]);
        }

        if (!empty($dataOutlet)) {
            $save = DealsOutlet::insert($dataOutlet);

            return $save;
        } else {
            return false;
        }

        return true;
    }

    /* DELETE OUTLET */
    function deleteOutlet($id_deals)
    {
        $delete = DealsOutlet::where('id_deals', $id_deals)->delete();

        return $delete;
    }

    /*Welcome Voucher*/
    function listDealsWelcomeVoucher(Request $request){
        $configUseBrand = Configs::where('config_name', 'use brand')->first();

        if($configUseBrand['is_active']){
            $getDeals = Deal::join('brands', 'brands.id_brand', 'deals.id_brand')
                ->where('deals_type','WelcomeVoucher')
                ->select('deals.*','brands.name_brand')
                ->get()->toArray();
        }else{
            $getDeals = Deal::where('deals_type','WelcomeVoucher')
                ->select('deals.*')
                ->get()->toArray();
        }


        $result = [
            'status' => 'success',
            'result' => $getDeals
        ];
        return response()->json($result);
    }

    function welcomeVoucherSetting(Request $request){
        $setting = Setting::where('key', 'welcome_voucher_setting')->first();
        $configUseBrand = Configs::where('config_name', 'use brand')->first();

        if($configUseBrand['is_active']){
            $getDeals = DealTotal::join('deals', 'deals.id_deals', 'deals_total.id_deals')
                ->join('brands', 'brands.id_brand', 'deals.id_brand')
                ->select('deals.*','deals_total.deals_total','brands.name_brand')
                ->get()->toArray();
        }else{
            $getDeals = DealTotal::join('deals', 'deals.id_deals', 'deals_total.id_deals')
                ->select('deals.*','deals_total.deals_total')
                ->get()->toArray();
        }


        $result = [
            'status' => 'success',
            'data' => [
                'setting' => $setting,
                'deals' => $getDeals
            ]
        ];
        return response()->json($result);
    }

    function welcomeVoucherSettingUpdate(Request $request){
        $post = $request->json()->all();

        $deleteDealsTotal = DB::table('deals_total')->delete();//Delete all data from tabel deals total

        //insert data
        $arrInsert = [];
        $list_id = $post['list_deals_id'];
        $list_deals_total = $post['list_deals_total'];
        $count = count($list_id);

        for($i=0;$i<$count;$i++){
            $data = [
                'id_deals' => $list_id[$i],
                'deals_total' => $list_deals_total[$i],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            array_push($arrInsert,$data);
        }

        $insert = DealTotal::insert($arrInsert);
        if($insert){
            $result = [
                'status' => 'success'
            ];
        }else{
            $result = [
                'status' => 'fail'
            ];
        }

        return response()->json($result);
    }

    function welcomeVoucherSettingUpdateStatus(Request $request){
        $post = $request->json()->all();
        $status = $post['status'];
        $updateStatus = Setting::where('key', 'welcome_voucher_setting')->update(['value' => $status]);

        return response()->json(MyHelper::checkUpdate($updateStatus));
    }

    function injectWelcomeVoucher($user, $phone){
        $getDeals = DealTotal::join('deals', 'deals.id_deals', '=', 'deals_total.id_deals')
            ->select('deals.*','deals_total.deals_total')->get();
        $count = 0;
        foreach ($getDeals as $val){
            for($i=0;$i<$val['deals_total'];$i++){
                $generateVoucher = app($this->hidden_deals)->autoClaimedAssign($val, $user, $val['deals_total']);
                $count++;
            }
        }

        $autocrm = app($this->autocrm)->SendAutoCRM('Receive Welcome Voucher', $phone,
            [
                'count_voucher'      => (string)$count
            ]
        );
        return true;
    }

    public function detail(DetailDealsRequest $request)
    {
        $post = $request->json()->all();
        $user = $request->user();

        $deals = $this->getDealsData($post['id_deals'], $post['step'], $post['deals_type']);

        if (isset($deals)) {
            $deals = $deals->toArray();
        }else{
            $deals = false;
        }

        if ($deals) {
            $result = [
                'status'  => 'success',
                'result'  => $deals
            ];
        } else {
            $result = [
                'status'  => 'fail',
                'messages'  => ['Deals Not Found']
            ];
        }

        return response()->json($result);
    }

    function getDealsData($id_deals, $step, $deals_type='Deals')
    {
    	$post['id_deals'] = $id_deals;
    	$post['step'] = $step;
    	$post['deals_type'] = $deals_type;

    	if ($deals_type == 'Promotion') {
    		$deals = DealsPromotionTemplate::where('id_deals_promotion_template', '=', $post['id_deals']);
    		$table = 'deals_promotion';
    	}else{
    		$deals = Deal::where('id_deals', '=', $post['id_deals'])->where('deals_type','=',$post['deals_type']);
    		$table = 'deals';
    	}

        if ($post['step'] == 1 || $post['step'] == 'all') {
			$deals = $deals->with(['outlets']);
        }

        if ($post['step'] == 2 || $post['step'] == 'all') {
			$deals = $deals->with([  
                $table.'_product_discount.product', 
                $table.'_product_discount_rules', 
                $table.'_tier_discount_product.product', 
                $table.'_tier_discount_rules', 
                $table.'_buyxgety_product_requirement.product', 
                $table.'_buyxgety_rules.product',
                'brand'
            ]);
        }

        if ($post['step'] == 3 || $post['step'] == 'all') {
			$deals = $deals->with([$table.'_content.'.$table.'_content_details']);
        }

        if ($post['step'] == 'all') {
			$deals = $deals->with(['created_by_user']);
        }

        $deals = $deals->first();

        return $deals;
    }

    public function updateContent(UpdateContentRequest $request)
    {
    	$post = $request->json()->all();

    	db::beginTransaction();

    	if ($post['deals_type'] != 'Promotion') {
    		$source = 'deals';
	    	$check = Deal::where('id_deals','=',$post['id_deals'])->first();
	    	if (!empty($check['deals_total_claimed']) ) {
				return [
	                'status'  => 'fail',
	                'messages' => 'Cannot update deals because someone has already claimed a voucher'
	            ];
			}
    	}
    	else
    	{
    		$source = 'deals_promotion';
    		$check = DealsPromotionTemplate::where('id_deals_promotion_template','=',$post['id_deals'])->first();
    	}

    	if ( empty($check) ) {
			return [
                'status'  => 'fail',
                'messages' => 'Deals not found'
            ];
		}

    	$update = app($this->subscription)->createOrUpdateContent($post, $source);

    	if ($update)
    	{
    		if ($post['deals_type'] != 'Promotion') {
				$update = Deal::where('id_deals','=',$post['id_deals'])->update(['deals_description' => $post['deals_description'], 'step_complete' => 0, 'last_updated_by' => auth()->user()->id]);
    		}else{
				$update = DealsPromotionTemplate::where('id_deals_promotion_template','=',$post['id_deals'])->update(['deals_description' => $post['deals_description'], 'step_complete' => 0, 'last_updated_by' => auth()->user()->id]);
    		}

            if ($update) 
			{
		        DB::commit();
		    }
		    else
		    {
		        DB::rollback();
		        return  response()->json([
		            'status'   => 'fail',
		            'messages' => 'Update Deals failed'
		        ]);
		    }
        }
        else
        {
            DB::rollback();
            return  response()->json([
                'status'   => 'fail',
                'messages' => 'Update Deals failed'
            ]);
        }

         return response()->json(MyHelper::checkUpdate($update));
    }

    /*============================= Start Filter & Sort V2 ================================*/
    function listDealV2(Request $request) {
        $deals = (new Deal)->newQuery();
        $deals->where('deals_type', '!=','WelcomeVoucher');
        $deals->where('deals_publish_end', '>=', date('Y-m-d H:i:s'));
        $deals->where('step_complete', '=', 1);

        if ($request->json('id_outlet') && is_integer($request->json('id_outlet'))) {
            $deals->leftJoin('deals_outlets', 'deals.id_deals', 'deals_outlets.id_deals')
            	->where(function($query) use ($request){
	                $query->where('id_outlet', $request->json('id_outlet'))
	                		->orWhere('deals.is_all_outlet','=',1);
            	})
	            ->addSelect('deals.*')->distinct();
        }

        // brand
        if ($request->json('id_brand')) {
            $deals->where('id_brand',$request->json('id_brand'));
        }

        $deals->addSelect('id_brand', 'deals.id_deals','deals_title','deals_second_title','deals_voucher_price_point','deals_voucher_price_cash','deals_total_voucher','deals_total_claimed','deals_voucher_type','deals_image','deals_start','deals_end','deals_type','is_offline','is_online');

        if ($request->json('key_free')) {
            $deals->where(function($query) use ($request){
                $query->where('deals_title', 'LIKE', '%' . $request->json('key_free') . '%')
                    ->orWhere('deals_second_title', 'LIKE', '%' . $request->json('key_free') . '%');
            });
        }

        $deals->where(function($query) use ($request){

        	if(!$request->json('voucher_type_cash') &&  !$request->json('voucher_type_point') &&  !$request->json('voucher_type_free'))
        	{
        		if ($request->json('min_price')) {
	                $query->where('deals_voucher_price_cash', '>=', $request->json('min_price'));
	            }

	            if ($request->json('max_price')) {
	                $query->where('deals_voucher_price_cash', '<=', $request->json('max_price'));
	            }
        	}
        	else
        	{
	        	if ($request->json('voucher_type_cash')) {
	                $query->orWhere(function ($amp) use ($request) {
	                    $amp->whereNotNull('deals_voucher_price_cash');
	                    if($val=$request->json('min_price')){
	                        $amp->where('deals_voucher_price_cash','>=',$val);
	                    }
	                    if($val=$request->json('max_price')){
	                        $amp->where('deals_voucher_price_cash','<=',$val);
	                    }
	                });
	            }

	            if ($request->json('voucher_type_point')) {
	                $query->orWhere(function ($amp) use ($request) {
	                    $amp->whereNotNull('deals_voucher_price_point');
	                    if($val=$request->json('min_interval_point')){
	                        $amp->where('deals_voucher_price_point','>=',$val);
	                    }
	                    if($val=$request->json('max_interval_point')){
	                        $amp->where('deals_voucher_price_point','<=',$val);
	                    }
	                });
	            }

	            if ($request->json('voucher_type_free')) {
	                $query->orWhere(function ($amp) use ($request) {
	                    $amp->whereNull('deals_voucher_price_point')->whereNull('deals_voucher_price_cash');
	                });
	            }
        	}
        });

        if($request->json('sort')){
            if($request->json('sort') == 'best'){
                $deals->orderBy('deals_total_claimed', 'desc');
            }elseif($request->json('sort') == 'new'){
                $deals->orderBy('deals_publish_start', 'desc');
            }elseif($request->json('sort') == 'periode'){
                $deals->orderBy('deals_end', 'asc');
            }
        }
        $deals = $deals->with('brand')->get()->toArray();

        if (!empty($deals)) {
            $city = "";

            // jika ada id city yg faq
            if ($request->json('id_city')) {
                $city = $request->json('id_city');
            }

            $deals = $this->kotacuks($deals, $city,$request->json('admin'));
        }

        if ($request->get('page')) {
            $page = $request->get('page');
        } else {
            $page = 1;
        }

        $resultData = [];
        $paginate   = 10;
        $start      = $paginate * ($page - 1);
        $all        = $paginate * $page;
        $end        = $all;
        $next       = true;

        if ($all > count($deals)) {
            $end = count($deals);
            $next = false;
        }


        for ($i=$start; $i < $end; $i++) {
            $deals[$i]['time_to_end']=strtotime($deals[$i]['deals_end'])-time();
            array_push($resultData, $deals[$i]);
        }

        $result['current_page']  = $page;
        $result['data']          = $resultData;
        $result['total']         = count($resultData);
        $result['next_page_url'] = null;
        if ($next == true) {
            $next_page = (int) $page + 1;
            $result['next_page_url'] = ENV('APP_API_URL') . 'api/deals/list?page=' . $next_page;
        }

        if(!$result['total']){
            $result=[];
        }

        if(
            $request->json('voucher_type_point') ||
            $request->json('voucher_type_cash') ||
            $request->json('voucher_type_free') ||
            $request->json('key_free')
        ){
            $resultMessage = 'Maaf, voucher yang kamu cari belum tersedia';
        }else{
            $resultMessage = 'Nantikan penawaran menarik dari kami';
        }
        return response()->json(MyHelper::checkGet($result, $resultMessage));
    }
    /*============================= End Filter & Sort V2 ================================*/
    
    public function updateComplete(UpdateComplete $request)
    {
    	$post = $request->json()->all();

    	$check = $this->checkComplete($post['id_deals'], $step, $errors, $post['deals_type']);

		if ($check)
		{
			if ($post['deals_type'] == 'Promotion') {
				$update = Deal::where('id_deals','=',$post['id_deals'])->update(['step_complete' => 1, 'last_updated_by' => auth()->user()->id]);
			}else{
				$update = Deal::where('id_deals','=',$post['id_deals'])->update(['step_complete' => 1, 'last_updated_by' => auth()->user()->id]);
			}

			if ($update)
			{
				return ['status' => 'success'];
			}else{
				return ['status'=> 'fail', 'messages' => ['Update deals failed']];
			}
		}
		else
		{
			return [
				'status'	=> 'fail',
				'step' 		=> $step,
				'messages' 	=> [$errors]
			];
		}
    }

    public function checkComplete($id, &$step, &$errors, $promo_type)
    {
    	$deals = $this->getDealsData($id, 'all', $promo_type);
    	if (!$deals) {
    		$errors = 'Deals not found';
    		return false;
    	}

    	$deals = $deals->toArray();
    	if ( $deals['is_online'] == 1)
    	{
	    	if ( empty($deals['deals_product_discount_rules']) && empty($deals['deals_tier_discount_rules']) && empty($deals['deals_buyxgety_rules']) )
	    	{
	    		$step = 2;
	    		$errors = 'Deals not complete';
	    		return false;
	    	}
    	}

    	if ( $deals['is_offline'] == 1)
    	{
    		if ( empty($deals['deals_promo_id_type']) && empty($deals['deals_promo_id']) )
	    	{
	    		$step = 2;
	    		$errors = 'Deals not complete';
	    		return false;
	    	}
    	}

    	if ( empty($deals['deals_content']) || empty($deals['deals_description']) ) {
    		$step = 3;
	    	$errors = 'Deals not complete';
    		return false;
    	}

    	return true;
    }
}
