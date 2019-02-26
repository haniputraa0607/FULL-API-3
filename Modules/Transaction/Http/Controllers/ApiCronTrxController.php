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

class ApiCronTrxController extends Controller
{
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
        }

        return response()->json(['success']);
    }
}
