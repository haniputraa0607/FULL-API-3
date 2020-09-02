<?php

namespace Modules\Subscription\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;

use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\FeaturedSubscription;
use Modules\Subscription\Entities\SubscriptionOutlet;
use Modules\Subscription\Entities\SubscriptionProduct;
use Modules\Subscription\Entities\SubscriptionContent;
use Modules\Subscription\Entities\SubscriptionContentDetail;
use Modules\Subscription\Entities\SubscriptionUser;
use Modules\Subscription\Entities\SubscriptionUserVoucher;
use Modules\Deals\Entities\DealsContent;
use Modules\Deals\Entities\DealsContentDetail;
use Modules\Promotion\Entities\DealsPromotionContent;
use Modules\Promotion\Entities\DealsPromotionContentDetail;
use App\Http\Models\Setting;

use Modules\Subscription\Http\Requests\ListSubscription;
use Modules\Subscription\Http\Requests\Step1Subscription;
use Modules\Subscription\Http\Requests\Step2Subscription;
use Modules\Subscription\Http\Requests\Step3Subscription;
use Modules\Subscription\Http\Requests\DetailSubscription;
use Modules\Subscription\Http\Requests\DeleteSubscription;
use Modules\Subscription\Http\Requests\UpdateCompleteSubscription;
use DB;

class ApiSubscriptionReport extends Controller
{

    function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->user     = "Modules\Users\Http\Controllers\ApiUser";
    }

    function transactionReport(Request $request)
    {
    	$list 	= SubscriptionUserVoucher::orderBy('updated_at', 'Desc')
    			->with([
    				'transaction' => function($q) {
    					$q->select('id_transaction', 'id_outlet', 'transaction_receipt_number', 'transaction_grandtotal');
    				},
    				'transaction.disburse_outlet_transaction' => function($q) {
    					$q->select('id_disburse_transaction','id_transaction', 'subscription');
    				},
    				'transaction.transaction_payment_subscription' => function($q) {
    					$q->select('id_transaction','subscription_nominal');
    				},
    				'transaction.outlet' => function($q) {
    					$q->select('id_outlet', 'outlet_code', 'outlet_name');
    				},
    				'subscription_user' => function($q) {
    					$q->select('id_subscription_user', 'id_subscription', 'id_user', 'bought_at', 'subscription_expired_at', 'subscription_price_point', 'subscription_price_cash');
    				},
    				'subscription_user.subscription' => function($q) {
    					$q->select('id_subscription', 'subscription_title', 'subscription_type');
    				},
    				'subscription_user.user' => function($q) {
    					$q->select('id', 'name', 'phone');
    				},
    			]);
    			// ->where('id_transaction','184');

// return [$request['rule'][0]['parameter']];
    	if ($request->json('rule')){
             $a = $this->filterReport($list,$request);
             // return $a;
        }
// return $request;
    	$list 	= $list->paginate(10);

    	return MyHelper::checkGet($list);
    }

    function listStartedSubscription(Request $request){
        $data = Subscription::where('subscription_start', '<', date('Y-m-d H:i:s'))
        		->where('subscription_step_complete','1')
                ->select('id_subscription', 'subscription_title')->get()->toArray();

        return response()->json(MyHelper::checkGet($data));
    }

    protected function filterReport($query, $request)
    {
        $allowed = [
            'operator' 	=> ['=', 'like', '<', '>', '<=', '>='],
            'subject' 	=> ['name', 'phone', 'subscription', 'bought_at', 'expired_at', 'used_at', 'outlet', 'transaction_receipt_number', 'subscription_price', 'voucher_code', 'subscription_nominal', 'transaction_grandtotal', 'charged_outlet', 'charged_central'],
            'mainSubject' => ['subscription_user_receipt_number','paid_status','bought_at']
        ];
        $return 	= [];
        $where 		= $request->json('operator') == 'or' ? 'orWhere' : 'where';
        $whereDate 	= $request->json('operator') == 'or' ? 'orWhereDate' : 'whereDate';
        $whereHas 	= $request->json('operator') == 'or' ? 'orWhereHas' : 'whereHas';
        $whereIn 	= $request->json('operator') == 'or' ? 'orWhereIn' : 'whereIn';
        $rule 		= $request->json('rule');
        $query->where(function($queryx) use ($rule,$allowed,$where,$query,$request, $whereDate, $whereHas, $whereIn){
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

                if ($value['subject'] == 'name' || $value['subject'] == 'phone') {
                	$queryx->$whereHas('subscription_user.user', function($q) use ( $where, $value){
                		$q->where($value['subject'], $value['operator'], $value['parameter']);
                	});
                }
                elseif ($value['subject'] == 'subscription') {
                	$queryx->$whereHas('subscription_user.subscription', function($q) use ( $whereIn, $value){
                		$q->whereIn('id_subscription', $value['parameter']);
                	});
                }
                /*
                elseif ($value['subject'] == 'bought_at' || $value['subject'] == 'expired_at') {
                	$queryx->$whereHas('subscription_user', function($q) use ( $where, $value){
                		$q->where('id_subscription', $value['parameter']);
                	});
                }*/


                $return[] = $value;
            }
        });
        return ['filter' => $return, 'filter_operator' => $request->json('operator')];
    }
}