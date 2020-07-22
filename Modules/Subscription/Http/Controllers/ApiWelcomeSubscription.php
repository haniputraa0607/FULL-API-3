<?php

namespace Modules\Subscription\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Configs;
use App\Http\Models\Setting;
use Modules\Subscription\Entities\SubscriptionWelcome;
use Modules\Subscription\Entities\Subscription;

use App\Lib\MyHelper;
use DB;

class ApiWelcomeSubscription extends Controller
{
	function __construct() {
        date_default_timezone_set('Asia/Jakarta');

        $this->subscription   		= "Modules\Subscription\Http\Controllers\ApiSubscription";
        $this->subscription_voucher = "Modules\Subscription\Http\Controllers\ApiSubscriptionVoucher";
        $this->subscription_claim   = "Modules\Subscription\Http\Controllers\ApiSubscriptionClaim";
        $this->setting 				= "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        if(\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
    }

    function setting(Request $request){
        $setting = Setting::where('key', 'welcome_subscription_setting')->first();
        $configUseBrand = Configs::where('config_name', 'use brand')->first();

        if($configUseBrand['is_active']){
            $getSubs = SubscriptionWelcome::join('subscriptions', 'subscriptions.id_subscription', 'subscription_welcomes.id_subscription')
		                ->leftjoin('brands', 'brands.id_brand', 'subscriptions.id_brand')
		                ->select('subscriptions.*','brands.name_brand')
		                ->get()->toArray();
        }else{
        	$getSubs = SubscriptionWelcome::join('subscriptions', 'subscriptions.id_subscription', 'subscription_welcomes.id_subscription')
		                ->select('subscriptions.*','brands.name_brand')
                		->get()->toArray();
        }


        $result = [
            'status' => 'success',
            'data' => [
                'setting' => $setting,
                'subscription' => $getSubs
            ]
        ];
        return response()->json($result);
    }

    function list(Request $request){
        $configUseBrand = Configs::where('config_name', 'use brand')->first();

        if($configUseBrand['is_active']){
            $getSubs = Subscription::leftjoin('brands', 'brands.id_brand', 'subscriptions.id_brand')
		                ->where('subscription_type','welcome')
		                ->where('subscription_step_complete',1)
		                ->select('subscriptions.*','brands.name_brand')
		                ->get()->toArray();
        }else{
            $getSubs = Subscription::where('subscription_type','welcome')
		                ->select('subscriptions.*')
		                ->get()->toArray();
        }

        $result = [
            'status' => 'success',
            'result' => $getSubs
        ];
        return response()->json($result);
    }

    function settingUpdate(Request $request){
        $post = $request->json()->all();
        $deleteSubsTotal = DB::table('subscription_welcomes')->delete(); //Delete all data from tabel subscription total

        //insert data
        $arrInsert = [];
        $list_id = $post['list_subs_id'];
        $count = count($list_id);
	
		foreach ($post['list_subs_id'] as $value) {
			$arrInsert[] = [
				'id_subscription' => $value,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
			];
		}

        $insert = SubscriptionWelcome::insert($arrInsert);
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

    function settingUpdateStatus(Request $request){
        $post 	= $request->json()->all();
        $status = $post['status'];
        $updateStatus = Setting::where('key', 'welcome_subscription_setting')->update(['value' => $status]);

        return response()->json(MyHelper::checkUpdate($updateStatus));
    }

    function injectWelcomeSubscription($user, $phone){
        $getSubs = SubscriptionWelcome::join('subscriptions', 'subscriptions.id_subscription', '=', 'subscription_welcomes.id_subscription')
            ->select('subscriptions.*')->get();
        $count = 0;
        foreach ($getSubs as $val){
            $generateUser = app($this->subscription_voucher)->autoClaimedAssign($val, $user);
            $count++;
            $dataSubs = Subscription::where('id_subscription', $val['id_subscription'])->first(); // get newest update of total claimed subscription
            app($this->subscription_claim)->updateSubs($dataSubs);
        }

        $autocrm = app($this->autocrm)->SendAutoCRM('Receive Welcome Subscription', $phone,
            [
                'count_subscription'      => (string)$count
            ]
        );
        return true;
    }
}
