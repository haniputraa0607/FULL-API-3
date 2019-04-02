<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use DB;
use App\Lib\MyHelper;
use App\Lib\Midtrans;

use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\User;
use App\Http\Models\LogBalance;
use App\Http\Models\LogPoint;

class ApiCronTrxController extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->balance  = "Modules\Balance\Http\Controllers\BalanceController";
    }

    public function cron(Request $request)
    {
        $crossLine = date('Y-m-d H:i:s', strtotime('- 3days'));
        $dateLine  = date('Y-m-d H:i:s', strtotime('- 1days'));
        $now       = date('Y-m-d H:i:s');

        $getTrx = Transaction::where('transaction_payment_status', 'Pending')->where('created_at', '>=', $crossLine)->where('created_at', '<=', $now)->get();

        if (empty($getTrx)) {
            return response()->json(['empty']);
        }

        foreach ($getTrx as $key => $value) {
            $singleTrx = Transaction::where('id_transaction', $value->id_transaction)->first();
            if (empty($singleTrx)) {
                continue;
            }

            $expired_at = date('Y-m-d H:i:s', strtotime('+ 1days', strtotime($singleTrx->transaction_date)));

            if ($expired_at >= $now) {
                continue;
            }

            $productTrx = TransactionProduct::where('id_transaction', $singleTrx->id_transaction)->get();
            if (empty($productTrx)) {
                continue;
            }

            $user = User::where('id', $singleTrx->id_user)->first();
            if (empty($user)) {
                continue;
            }

            $connectMidtrans = Midtrans::expire($singleTrx->transaction_receipt_number);
            // $detail = $this->getHtml($singleTrx, $productTrx, $user->name, $user->phone, $singleTrx->created_at, $singleTrx->transaction_receipt_number);

            // $autoCrm = app($this->autocrm)->SendAutoCRM('Transaction Online Cancel', $user->phone, ['date' => $singleTrx->created_at, 'status' => $singleTrx->transaction_payment_status, 'name'  => $user->name, 'id' => $singleTrx->transaction_receipt_number, 'receipt' => $detail, 'id_reference' => $singleTrx->transaction_receipt_number]);
            // if (!$autoCrm) {
            //     continue;
            // }

            $singleTrx->transaction_payment_status = 'Cancelled';
            $singleTrx->save();
            if (!$singleTrx) {
                continue;
            }

            //reversal balance
            $logBalance = LogBalance::where('id_reference', $singleTrx->id_transaction)->where('source', 'Transaction')->where('balance', '<', 0)->get();
            foreach($logBalance as $logB){
                $reversal = app($this->balance)->addLogBalance( $singleTrx->id_user, abs($logB['balance']), $singleTrx->id_transaction, 'Reversal', $singleTrx->transaction_grandtotal);
            }
        }

        return response()->json(['success']);
    }

    public function completeTransactionPickup(){
        $idTrx = Transaction::whereDate('transaction_date', '<', date('Y-m-d'))->pluck('id_transaction')->toArray();
        //update ready_at
        $dataTrx = TransactionPickup::whereIn('id_transaction', $idTrx)
                                    ->whereNull('ready_at')
                                    ->whereNull('reject_at')
                                    ->update(['ready_at' => date('Y-m-d 00:00:00')]);
        //update receive_at
        $dataTrx = TransactionPickup::whereIn('id_transaction', $idTrx)
                                    ->whereNull('receive_at')
                                    ->whereNull('reject_at')
                                    ->update(['receive_at' => date('Y-m-d 00:00:00')]);
        //update taken_at                           
        $dataTrx = TransactionPickup::whereIn('id_transaction', $idTrx)
                                    ->whereNull('taken_at')
                                    ->whereNull('reject_at')
                                    ->update(['taken_at' => date('Y-m-d 00:00:00')]);

        return response()->json(['status' => 'success']);
    
    }
}
