<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Transaction;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\TransactionPaymentOvo;
use App\Http\Models\OvoReversal;
use App\Http\Models\OvoReference;

use DB;
use App\Lib\MyHelper;
use App\Lib\Midtrans;
use App\Lib\Ovo;

use Modules\Transaction\Http\Requests\Transaction\ConfirmPayment;

class ApiOvoReversal extends Controller
{
    public $saveImage = "img/payment/manual/";

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->notif = "Modules\Transaction\Http\Controllers\ApiNotification";
        $this->trx = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
    }

    //create transaction payment ovo
    public function insertReversal(Request $request){
        //cari transaction yg harus di reversal
        $TrxReversal = TransactionPaymentOvo::join('transactions', 'transactions.id_transaction', 'transaction_payment_ovos.id_transaction')
                        ->where('push_to_pay_at', '<', date('Y-m-d H:i:s', strtotime('- 70second')))->where('reversal', 'not yet')->get();
        foreach($TrxReversal as $trx){
            DB::beginTransaction();
            //insert to ovo_reversal
            $req['amount'] = (int)$trx['amount'];
            $req['reference_number'] = $trx['reference_number'];
            $req['batch_no'] = $trx['batch_no'];
            $req['transaction_receipt_number'] = $trx['transaction_receipt_number'];

            $ovoReversal = OvoReversal::updateOrCreate(['id_transaction' => $trx['id_transaction'], 'id_transaction_payment_ovo' => $trx['id_transaction_payment_ovo'],
                'date_push_to_pay' => $trx['push_to_pay_at'],
                'request' => json_encode($req)
            ]);

            if($ovoReversal){
                //update status reversal
                $updateStatus = TransactionPaymentOvo::where('id_transaction_payment_ovo', $trx['id_transaction_payment_ovo'])->update(['reversal' => 'yes']);
                if(!$updateStatus){
                    DB::rollback();
                }else{
                    DB::commit();
                }
            }
        }
        return 'success';

    }

    //process reversal
    public function processReversal(Request $request){

        $list = OvoReversal::join('transaction_payment_ovos', 'ovo_reversals.id_transaction_payment_ovo', 'transaction_payment_ovos.id_transaction_payment_ovo')->orderBy('date_push_to_pay')->limit(5)->get();

        foreach($list as $data){

            if($data['is_production'] == '1'){
                $type = 'production';
            }else{
                $type = 'staging';
            }

            $dataReq = json_decode($data['request'], true);
            $dataReq['id_transaction_payment_ovo'] = $data['id_transaction_payment_ovo'];

            $reversal = Ovo::Reversal($dataReq, $dataReq, $dataReq['amount'], $type);

            if(isset($reversal['response'])){
                $response = $reversal['response'];
                $dataUpdate = [];

                $dataUpdate['reversal'] = 'yes';

                if(isset($response['traceNumber'])){
                    $dataUpdate['trace_number'] = $response['traceNumber'];
                }
                if(isset($response['type']) && $response['type'] == '0410'){
                    $dataUpdate['payment_type'] = 'REVERSAL';
                }
                if(isset($response['responseCode'])){
                    $dataUpdate['response_code'] = $response['responseCode'];
                    $dataUpdate = Ovo::detailResponse($dataUpdate);
                }

                $update = TransactionPaymentOvo::where('id_transaction', $data['id_transaction'])->update($dataUpdate);
                if($update){
                    //delete from ovo_reversal
                    $delete = OvoReversal::where('id_ovo_reversal', $data['id_ovo_reversal'])->delete();
                }
            }
        }

        return 'success';

    }

    //process reversal
    public function void(Request $request){
        $post = $request->json()->all();
        $transaction = TransactionPaymentOvo::where('transaction_payment_ovos.id_transaction', $post['id_transaction'])
            ->join('transactions','transactions.id_transaction','=','transaction_payment_ovos.id_transaction')
            ->first();
        if(!$transaction){
            return [
                'status' => 'fail',
                'messages' => [
                    'Transaction not found'
                ]
            ];
        }

        $void = Ovo::Void($transaction);

        return $void;

    }
}
