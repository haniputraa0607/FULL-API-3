<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\LogApiGosend;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionPickupWehelpyou;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\User;
use App\Lib\GoSend;
use App\Lib\WeHelpYou;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;

use DB;

class ApiWehelpyouController extends Controller
{
    public function __construct()
    {
        $this->autocrm    = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->getNotif   = "Modules\Transaction\Http\Controllers\ApiNotification";
        $this->membership = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->trx        = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->outlet_app = "Modules\OutletApp\Http\Controllers\ApiOutletApp";
    }
    
    /**
     * Cron check status wehelpyou
     */
    public function cronCheckStatus()
    {
        $log = MyHelper::logCron('Check Status Wehelpyou');
        try {
            $trxWehelpyous = TransactionPickupWehelpyou::select('id_transaction')->join('transaction_pickups', 'transaction_pickups.id_transaction_pickup', 'transaction_pickup_wehelpyous.id_transaction_pickup')
				->whereNotIn('latest_status', ['cancelled', 'Finished'])
                ->whereDate('transaction_pickup_wehelpyous.created_at', date('Y-m-d'))
                ->where('transaction_pickup_wehelpyous.updated_at', '<', date('Y-m-d H:i:s', time() - (5 * 60)))
                ->get();

            foreach ($trxWehelpyous as $trxWehelpyou) {
                app('Modules\OutletApp\Http\Controllers\ApiOutletApp')->refreshDeliveryStatus(new Request(['id_transaction' => $trxWehelpyou->id_transaction, 'type' => 'wehelpyou']));
            }

            $log->success(['checked' => count($trxWehelpyous)]);
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
            return ['status' => 'fail'];
        }
    }
}
