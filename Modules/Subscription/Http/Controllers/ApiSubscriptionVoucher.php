<?php

namespace Modules\Subscription\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Lib\MyHelper;


use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\SubscriptionPaymentMidtran;
use Modules\Subscription\Entities\FeaturedSubscription;
use Modules\Subscription\Entities\SubscriptionOutlet;
use Modules\Subscription\Entities\SubscriptionUser;
use Modules\Subscription\Entities\SubscriptionUserVoucher;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;

use Modules\Subscription\Http\Requests\CreateSubscriptionVoucher;
use DB;

class ApiSubscriptionVoucher extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->subscription        = "Modules\subscription\Http\Controllers\ApiSubscription";
    }

    /* GENERATE CODE */
    function generateCode($id_deals) {
        $code = 'subs'.sprintf('%03d', $id_deals).MyHelper::createRandomPIN(5);

        return $code;
    }

    /* CREATE VOUCHER USER */
    function createVoucherUser($post) {
        $create = SubscriptionUser::create($post);

        if ($create) {
            $create = SubscriptionUser::with(['user', 'subscription_user_vouchers'])->where('id_subscription_user', $create->id_subscription_user)->first();

            // add notif mobile
            // $addNotif = MyHelper::addUserNotification($create->id_user,'voucher');
        }

        return $create;
    }

}
