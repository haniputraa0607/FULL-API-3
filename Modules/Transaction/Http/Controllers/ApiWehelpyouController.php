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

    public function updateFakeStatus(Request $request)
    {
    	$case = ['completed', 'driver not found', 'cancelled', 'rejected'];
    	if (!in_array($request->case, $case)) {
    		$case = implode(', ', $case);
    		return [
                'status' => 'fail',
                'messages' => [
                    'case not found, available case : '.$case
                ]
            ];
    	}

		$trx = Transaction::where('transactions.id_transaction', $request->id_transaction)
        		->join('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
        		->with(['outlet' => function($q) {
		            $q->select('id_outlet', 'outlet_name');
		        }])
		        ->where('pickup_by', '!=', 'Customer')
		        ->first();

        if (!$trx) {
            return [
                'status' => 'fail',
                'messages' => [
                    'Transaction not found'
                ]
            ];
        }

        $outlet = $trx->outlet;
        $request->type = $trx->shipment_method ?? $request->type;
        if (!$trx) {
            return MyHelper::checkGet($trx, 'Transaction Not Found');
        }

        $trx->load('transaction_pickup.transaction_pickup_wehelpyou');
        switch (strtolower($request->case)) {
        	case 'rejected':
        		$fakeLog = [1, 11, 8, 9, 96];
        		break;

        	case 'cancelled':
        		$fakeLog = [1, 11, 8, 91];
        		break;

        	case 'driver not found':
        		$fakeLog = [1, 11, 95];
        		break;

        	case 'completed':
        	default:
        		$fakeLog = [1, 11, 8, 9, 2];
        		break;
        }

        $latestStatusId = $trx['transaction_pickup']['transaction_pickup_wehelpyou']['latest_status_id'];
        $flippedFakeLog = array_flip($fakeLog);

        $indexLatestStatusId = $flippedFakeLog[$latestStatusId] ?? false;
        if ($indexLatestStatusId === false) {
        	return [
                'status' => 'fail',
                'messages' => [
                    'Latest status not found'
                ]
            ];
        }

        $nextStatus = $fakeLog[$indexLatestStatusId + 1] ?? false;
        if ($nextStatus === false) {
        	return [
                'status' => 'fail',
                'messages' => [
                    'Next status Log not found'
                ]
            ];
        }

        $trx->fakeStatusId = $nextStatus;
        $trx->fakeStatusCase = strtolower($request->case);
        return WeHelpYou::updateStatus($trx, $trx['transaction_pickup']['transaction_pickup_wehelpyou']['poNo']);
    }
}
