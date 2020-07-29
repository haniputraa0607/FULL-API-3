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
use Modules\Subscription\Entities\TransactionPaymentSubscription;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;

use Modules\Subscription\Http\Requests\CreateSubscriptionVoucher;
use DB;

class ApiSubscriptionVoucher extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->subscription = "Modules\subscription\Http\Controllers\ApiSubscription";
        $this->claim   		= "Modules\Subscription\Http\Controllers\ApiSubscriptionClaim";
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

    /* AUTO CLAIMED & ASSIGN */
    function autoClaimedAssign($subs, $to) {

        $ret=false;
        foreach ($to as $key => $user) {
            $generate_user = app($this->claim)->createSubscriptionUser($user, $subs);

            if ($generate_user) 
            {
            	$update_receipt = $this->updateSubscriptionReceipt($generate_user->id_subscription_user);
            	
				if ($update_receipt) 
				{
            		$generate_voucher = $this->generateSubsVoucher($subs, $generate_user->id_subscription_user);
	                // return first voucher
	                if(!$ret){
	                    $ret=$generate_voucher;
	                }

	                if ($generate_user) {
	                    continue;
	                }
	                else {
	                    return false;
	                }
				}
				else{
					return false;
				}
            }
            else {
                return false;
            }
        }
        return $ret;
    }

    /* GENERATE VOUCHER */
    function generateSubsVoucher($dataSubs, $id_subscription_user) {

    	$id_subscription = $dataSubs->id_subscription;
    	$subs_voucher = [];
    	for ($i=1; $i <= $dataSubs->subscription_voucher_total; $i++) {

            // generate voucher code
            do {
                $code = $this->generateCode($id_subscription);
                $voucherCode = SubscriptionUserVoucher::where('id_subscription_user', '=', $id_subscription_user)
                             ->where('voucher_code', $code)
                             ->first();
            } while (!empty($voucherCode));

            // create user voucher
            $subs_voucher[] = ([
                'id_subscription_user' => $id_subscription_user,
                'voucher_code'         => strtoupper($code),
                'created_at'           => date('Y-m-d H:i:s'),
                'updated_at'           => date('Y-m-d H:i:s')
            ]);
        }   // end of for

        $save = SubscriptionUserVoucher::insert($subs_voucher);

        return $save;
    }

    function updateSubscriptionReceipt($id_subscription_user)
    {
    	$subs_receipt = 'SUBS-'.time().sprintf("%05d", $id_subscription_user);
        $updateSubs = SubscriptionUser::where('id_subscription_user', '=', $id_subscription_user)->update(['subscription_user_receipt_number' => $subs_receipt]);

        return $updateSubs;
    }

    /**
     * Return voucher for failed or rejected order
     * @param  integer $id_transaction Transaction id from id_transaction column
     * @return boolean        true/false
     */
    public function returnSubscription($id_transaction)
    {
        /**
         * TransactionPaymentSubscription -> ini konsepnya sama kayak promo campaign report jadi dihapus
         * SubscriptionUserVoucher -> ini kolom used_at sama id_trx jadiin kosong
         */
        TransactionPaymentSubscription::where('id_transaction',$id_transaction)->delete();
        SubscriptionUserVoucher::where('id_transaction',$id_transaction)->update([
            'used_at' => null,
            'id_transaction' => null
        ]);
    }
}
