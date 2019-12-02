<?php

namespace Modules\Subscription\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;

use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\FeaturedSubscription;
use Modules\Subscription\Entities\SubscriptionOutlet;
use Modules\Subscription\Entities\SubscriptionUser;
use Modules\Subscription\Entities\SubscriptionUserVoucher;
use App\Http\Models\Setting;

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

        return $getSubs;
        if (empty($getSubs)) {
            return response()->json(['empty']);
        }

        foreach ($getSubs as $key => $value) {
            $singleSubs = Subscription::where('id_subscription_user', '=', $value->id_subscription_user)->first();
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

            if (!$singleSubs) {
                continue;
            }

            $logBalance = LogBalance::where('id_reference', $singleSubs->id_subscription_user)->where('source', 'Subscription Balance')->where('balance', '<', 0)->get();
        }
        return ['ok'];
    }

}