<?php

namespace Modules\IPay88\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\IPay88\Entities\TransactionPaymentIpay88;
use Modules\IPay88\Entities\DealsPaymentIpay88;
use App\Http\Models\Transaction;

use Modules\IPay88\Lib\IPay88;
use App\Lib\MyHelper;

use Modules\IPay88\Http\Requests\Ipay88Response;

class IPay88Controller extends Controller
{
    public function __construct(){
        $this->lib = IPay88::create();
    }

    public function requestView(Request $request) {
        $id_reference = $request->id_reference;
        $type = $request->type;
        $payment_id = $request->payment_id;
        $data =  $this->lib->generateData($id_reference,$type,$payment_id);
        if(!$data){
            return 'Something went wrong';
        }
        return view('ipay88::payment',$data);
    }

    public function notifUser(Ipay88Response $request,$type) {
        $post = $request->post();
        $post['type'] = $type;
        $post['triggers'] = 'user';
        switch ($type) {
            case 'trx':
                $trx_ipay88 = TransactionPaymentIpay88::join('transactions','transactions.id_transaction','=','transaction_payment_ipay88s.id_transaction')
                    ->where('transaction_receipt_number',$post['RefNo'])
                    ->where('transaction_payment_status','<>','Completed')
                    ->first();
                break;

            case 'deals':
                $trx_ipay88 = DealsPaymentIpay88::where('order_id',$post['RefNo'])->first();
                break;
            
            default:
                # code...
                break;
        }
        if(!$trx_ipay88 || $post['Amount'] != $trx_ipay88->amount){
            return 'Transaction Not Found';
        }
        // validating with should paid amount
        $post['Amount'] = $trx_ipay88->amount;
        $requery = $this->lib->reQuery($post, $post['Status']);

        if($requery['valid']){
            $post['from_user'] = 1;
            $post['requery_response'] = $requery['response'];
            $this->lib->update($trx_ipay88,$post);
        }
        $data = [
            'type' => $type,
            'id_reference' => $trx_ipay88->id_transaction?:$trx_ipay88->id_deals_user
        ];
        return view('ipay88::redirect',$data);
    }

    public function notifIpay(Ipay88Response $request,$type) {
        $post = $request->post();
        $post['type'] = $type;
        $post['triggers'] = 'backend';
        switch ($type) {
            case 'trx':
                $trx_ipay88 = TransactionPaymentIpay88::join('transactions','transactions.id_transaction','=','transaction_payment_ipay88s.id_transaction')
                    ->where('transaction_receipt_number',$post['RefNo'])
                    ->first();
                break;

            case 'deals':
                $trx_ipay88 = DealsPaymentIpay88::where('order_id',$post['RefNo'])->first();
                break;
            
            default:
                # code...
                break;
        }
        if(!$trx_ipay88 || $post['Amount'] != $trx_ipay88->amount){
            return MyHelper::checkGet($trx_ipay88,'Transaction Not Found');
        }

        $post['Amount'] = $trx_ipay88->amount;
        $requery = $this->lib->reQuery($post, $post['Status']);

        if($requery['valid']){
            $post['from_backend'] = 1;
            $post['requery_response'] = $requery['response'];
            $this->lib->update($trx_ipay88,$post);
            return [
                'status' => 'success'
            ];
        }else{
            return MyHelper::checkGet([],$requery['response']);
        }
    }
}
