<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\LogApiGosend;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionPickupWehelpyou;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\User;
use Modules\OutletApp\Http\Requests\DetailOrder;
use App\Lib\GoSend;
use App\Lib\WeHelpYou;
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
                ->whereNotIn('transaction_pickup_wehelpyous.latest_status_id', WeHelpYou::orderEndStatusId())
                ->whereDate('transaction_pickup_wehelpyous.created_at', date('Y-m-d'))
                ->where('transaction_pickup_wehelpyous.updated_at', '<', date('Y-m-d H:i:s', time() - (1 * 60)))
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
                    'case not found, available case : ' . $case
                ]
            ];
        }

        $trx = Transaction::where('transactions.id_transaction', $request->id_transaction)
                ->join('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                ->with(['outlet' => function ($q) {
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

    public function cronCancelDelivery()
    {
        $log = MyHelper::logCron('Cancel Delivery Reject Order Wehelpyou');
        try {
            $endStatusWehelpyou = Wehelpyou::orderEndFailStatusId();
            $limitTime = date('Y-m-d H:i:s', strtotime('-10minutes'));

            $transactions = Transaction::select([
                    'transaction_pickup_wehelpyous.updated_at',
                    'transaction_pickup_wehelpyous.latest_status_id',
                    'transaction_pickup_wehelpyous.poNo',
                    'order_id',
                    'transaction_receipt_number',
                    'transactions.id_transaction',
                    'id_outlet',
                    'transaction_date',
                    'transaction_pickup_wehelpyou_updates.date',
                    'transaction_pickups.pickup_by'
                ])
                ->join('transaction_pickups', 'transaction_pickups.id_transaction', '=', 'transactions.id_transaction')
                ->join('transaction_pickup_wehelpyous', 'transaction_pickup_wehelpyous.id_transaction_pickup', '=', 'transaction_pickups.id_transaction_pickup')
                ->leftJoin('transaction_pickup_wehelpyou_updates', 'transaction_pickup_wehelpyou_updates.id_transaction_pickup_wehelpyou', '=', 'transaction_pickup_wehelpyous.id_transaction_pickup_wehelpyou')
                ->whereNull('transaction_pickups.reject_at')
                ->whereDate('transaction_date', date('Y-m-d'))
                ->where('transaction_pickup_wehelpyou_updates.date', '<', $limitTime)
                ->where('transaction_payment_status', 'Completed')
                ->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where('transaction_pickup_wehelpyous.latest_status_id', '11') // finding driver
                        ->where('transaction_pickup_wehelpyou_updates.status_id', '11'); // finding driver
                    })->orWhere(function ($q2) {
                        $q2->where('transaction_pickup_wehelpyous.latest_status_id', '1') // on progress
                        ->where('transaction_pickup_wehelpyou_updates.status_id', '1'); // on progress
                    });
                })
                ->with('outlet')
                ->get();

            $processed = [
                'found' => $transactions->count(),
                'cancelled' => 0,
                'failed_cancel' => 0,
                'errors' => [],
            ];
            foreach ($transactions as $transaction) {
                // cancel booking delivery
                $poNo = $transaction['poNo'];
                if (!$poNo) {
                    $processed['failed_cancel']++;
                    $processed['errors'][] = $transaction['order_id'] . ' PO number not found';
                    continue;
                }

                $cancel = WeHelpYou::cancelOrder($poNo);
                if (($cancel['status_code'] ?? false) == '200') {
                    app($this->outlet_app)->refreshDeliveryStatus(new Request([
                        'id_transaction' => $transaction['id_transaction'],
                        'type' => 'wehelpyou'
                    ]));
                } else {
                    $processed['failed_cancel']++;
                    $processed['errors'][] = $transaction['order_id'] . ' Cancel order failed';
                    continue;
                }

                // reject order
                $params = [
                    'order_id' => $transaction['order_id'],
                    'reason'   => 'auto reject order by system [no driver]'
                ];

                // mocking request object and create fake request
                $fake_request = new DetailOrder();
                $fake_request->setJson(new \Symfony\Component\HttpFoundation\ParameterBag($params));
                $fake_request->merge(['user' => $transaction->outlet]);
                $fake_request->setUserResolver(function () use ($transaction) {
                    return $transaction->outlet;
                });

                $reject = app($this->outlet_app)->rejectOrder($fake_request);

                if ($reject instanceof \Illuminate\Http\JsonResponse || $reject instanceof \Illuminate\Http\Response) {
                    $reject = $reject->original;
                }

                if (is_array($reject)) {
                    if (($reject['status'] ?? false) == 'success') {
                        $dataNotif = [
                            'subject' => 'Order Dibatalkan',
                            'string_body' => $transaction['order_id'] . ' - ' . $transaction['transaction_receipt_number'],
                            'type' => 'trx',
                            'id_reference' => $transaction['id_transaction'],
                            'id_transaction' => $transaction['id_transaction']
                        ];
                        app($this->outlet_app)->outletNotif($dataNotif, $transaction->id_outlet);
                        $processed['cancelled']++;
                    } else {
                        $processed['failed_cancel']++;
                        $processed['errors'][] = $reject['messages'] ?? $transaction['order_id'] . 'Something went wrong';
                    }
                } else {
                    $processed['failed_cancel']++;
                    $processed['errors'][] = $transaction['order_id'] . ' Something went wrong';
                }
            }

            $log->success($processed);
            return $processed;
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
            return ['status' => 'fail', 'messages' => [$e->getMessage()]];
        }
    }
}
