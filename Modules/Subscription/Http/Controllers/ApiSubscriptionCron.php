<?php

namespace Modules\Subscription\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use App\Lib\Midtrans;

use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\FeaturedSubscription;
use Modules\Subscription\Entities\SubscriptionOutlet;
use Modules\Subscription\Entities\SubscriptionUser;
use Modules\Subscription\Entities\SubscriptionUserVoucher;
use App\Http\Models\Setting;
use App\Http\Models\LogBalance;

class ApiSubscriptionCron extends Controller
{

    function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        ini_set('max_execution_time', 0);
        $this->user     = "Modules\Users\Http\Controllers\ApiUser";
        $this->balance  = "Modules\Balance\Http\Controllers\BalanceController";
    }

    
    function cron(Request $request)
    {
        $now       = date('Y-m-d H:i:s');

        $getSubs = SubscriptionUser::where('paid_status', 'Pending')->where('bought_at', '<=', $now)->get();

        if (empty($getSubs)) {
            return response()->json(['empty']);
        }

        foreach ($getSubs as $key => $value) {
            $singleSubs = SubscriptionUser::where('id_subscription_user', '=', $value->id_subscription_user)->with('subscription')->first();
            
            if (empty($singleSubs)) {
                continue;
            }

            $expired_at = date('Y-m-d H:i:s', strtotime('+30 minutes', strtotime($singleSubs->bought_at)));

            if ($expired_at >= $now) {
                continue;
            }

            $connectMidtrans = Midtrans::expire($singleSubs->subscription_user_receipt_number);

            $singleSubs->paid_status = 'Cancelled';
            $singleSubs->void_date = $now;
            $singleSubs->save();

            $subscription = Subscription::where('id_subscription','=', $singleSubs->id_subscription)->first();
            $subscription->subscription_bought = $subscription->subscription_bought-1;
            $subscription->save();

            if (!$singleSubs) {
                continue;
            }

            $logBalance = LogBalance::where('id_reference', $singleSubs->id_subscription_user)->where('source', 'Subscription Balance')->where('balance', '<', 0)->get();

            foreach ($logBalance as $key => $value) {
                $reversal = app($this->balance)->addLogBalance($singleSubs->id_user, abs($value['balance']), $singleSubs->subscription_user_receipt_number, 'Reversal', $singleSubs->subscription_price_cash);
                $user = User::where('id', $singleSubs->id_user)->first();

                $send = app($this->autocrm)->SendAutoCRM('Buy Subscription Failed Point Refund', $user->phone,
                    [
                        "subscription_title"        => $singleSubs->subscription['subscription_title'],
                        "subscription_price_cash"   => $singleSubs->subscription_price_cash,
                        "bought_at"                 => $singleSubs->bought_at,
                        "id_subscription"           => $singleSubs->id_subscription,
                        "used_point"                => (string) abs($value['balance'])

                    ]
                );
                if($send != true){
                    DB::rollback();
                    return response()->json([
                            'status' => 'fail',
                            'messages' => ['Failed Send notification to customer']
                        ]);
                }
            }
        }

        return response()->json(['success']);
    }

}