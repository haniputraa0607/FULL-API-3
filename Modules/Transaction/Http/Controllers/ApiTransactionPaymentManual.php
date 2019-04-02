<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\TransactionPaymentManual;
use App\Http\Models\Transaction;
use App\Http\Models\ManualPayment;
use App\Http\Models\ManualPaymentMethod;
use App\Http\Models\Bank;
use App\Http\Models\BankMethod;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\Transaction\Http\Requests\ManualPaymentConfirm;

use App\Lib\MyHelper;
use DB;

class ApiTransactionPaymentManual extends Controller
{
    public $saveImage = "img/transaction/manual-payment/";

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->notif = "Modules\Transaction\Http\Controllers\ApiNotification";
    }

    public function manualPaymentList(Request $request, $type) {
        $post = $request->json()->all();
        $list = TransactionPaymentManual::with('transaction', 'manual_payment_method');

        if($type == "accepted" ){
            $list = $list->whereNotNull('confirmed_at');
        }elseif($type == "declined" ){
            $list = $list->whereNotNull('cancelled_at');
        }else{
            $list = $list->whereNull('cancelled_at')->whereNull('confirmed_at');
        }
        $list = $list->whereDate('payment_date', '>=', date('Y-m-01'))->whereDate('payment_date', '<=', date('Y-m-d'))->paginate(10)->toArray();
        return response()->json(MyHelper::checkGet($list));
    }

    public function detailManualPaymentUnpay(Request $request) {
        $id = $request->json('transaction_receipt_number');
        $data = Transaction::where('transaction_receipt_number', $id)->with('user', 'transaction_payment_manuals', 'transaction_payment_manuals.user')->first()->toArray();
        return response()->json(MyHelper::checkGet($data));
    }

    public function manualPaymentConfirm(ManualPaymentConfirm $request) {
        $post = $request->json()->all(); 
        $user = $request->user();

        $confirm['id_user_confirming'] = $user['id'];
        
        if($post['status'] == 'accept'){
            $confirm['confirmed_at'] = date('Y-m-d h:i:s');
            $confirm['payment_note_confirm'] = $post['payment_note_confirm'];
            $status = 'Completed';
        }else{
            $confirm['cancelled_at'] = date('Y-m-d h:i:s');
            $status = 'Cancelled';
        }
        
        DB::beginTransaction();
        $updateConfirm = TransactionPaymentManual::find($post['id_transaction_payment_manual']);
        $update = $updateConfirm->update($confirm);
        
        if($update){
            $updateTrans = Transaction::find($updateConfirm->id_transaction)->update(['transaction_payment_status'=>$status]);
            if($updateTrans){

                if($status == 'Completed'){
                    $trans = Transaction::with('user.memberships', 'outlet', 'productTransaction')->where('id_transaction', $updateConfirm->id_transaction)->first();
                    
                    //SEND NOTIF FRAUD
                    $fraud = app($this->notif)->checkFraud($trans);
                    if ($fraud == false) {
                        DB::rollback();
                        return response()->json([
                            'status'   => 'fail',
                            'messages' => ['Send Notification Fraud Detection Failed']
                        ]);
                    }

                    //SEND NOTIF TO ADMIN OUTLET
                    $sendNotif = app($this->notif)->sendNotif($trans);
    
    
                    $savePoint = app($this->notif)->savePoint($trans);
    
                    if (!$savePoint) {
                        DB::rollback();
                        return response()->json([
                            'status'   => 'fail',
                            'messages' => ['Transaction failed']
                        ]);
                    }
                    
                    app($this->notif)->notification(['order_id' => $trans['transaction_receipt_number']], $trans);
                }
                
                DB::commit();
                return response()->json(MyHelper::checkUpdate($update));
            }else{
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Manual payment confirmation failed']
                ]);
            }
        }else{
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Manual payment confirmation failed']
            ]);
        }

    }

    public function transactionPaymentManualFilter(Request $request, $type) {
        $post = $request->json()->all();
        $conditions = [];
        $rule = '';
        $search = '';

        $query = TransactionPaymentManual::with('user', 'transaction', 'transaction.user')
        ->leftJoin('users as admin','transaction_payment_manuals.id_user_confirming','=','admin.id')
        ->leftJoin('transactions','transaction_payment_manuals.id_transaction','=','transactions.id_transaction')
        ->leftJoin('users','transactions.id_user','=','users.id')
        ->orderBy('transaction_payment_manuals.payment_date', 'ASC');

        if($type == "accepted" ){
            $query = $query->whereNotNull('confirmed_at');
        }elseif($type == "declined" ){
            $query = $query->whereNotNull('cancelled_at');
        }else{
            $query = $query->whereNull('cancelled_at')->whereNull('confirmed_at');
        }

        if(!empty($post['date_start'])){
            $start = date('Y-m-d', strtotime($post['date_start']));
            $query = $query->where('transaction_payment_manuals.payment_date', '>=', $start);
        }
        if(!empty($post['date_end'])){
            $end = date('Y-m-d', strtotime($post['date_end']));
            $query = $query->where('transaction_payment_manuals.payment_date', '<=', $end);
        }

        if (isset($post['conditions'])) {
            foreach ($post['conditions'] as $key => $con) {
                if ($con['subject'] == 'receipt') {
                    $var = 'transactions.transaction_receipt_number';
                } elseif ($con['subject'] == 'name' || $con['subject'] == 'phone' || $con['subject'] == 'email') {
                    $var = 'users.'.$con['subject'];
                } elseif ($con['subject'] == 'payment_method' || $con['subject'] == 'payment_nominal' || $con['subject'] == 'payment_bank' || $con['subject'] == 'payment_account_number' || $con['subject'] == 'payment_account_name') {
                    $var = 'transaction_payment_manuals.'.$con['subject'];
                } elseif ($con['subject'] == 'confirm_by') {
                    $var = 'admin.name';
                } elseif ($con['subject'] == 'grand_total') {
                    $var = 'transactions.transaction_grandtotal';
                }

                if ($con['subject'] == 'receipt' || $con['subject'] == 'name' || $con['subject'] == 'phone' || $con['subject'] == 'email' || $con['subject'] == 'payment_bank' || $con['subject'] == 'payment_account_number' || $con['subject'] == 'payment_account_name' || $con['subject'] == 'confirm_by') {
                    if ($post['rule'] == 'and' || $key == 0) {
                        if ($con['operator'] == 'like') {
                            $query = $query->where($var, 'like', '%'.$con['parameter'].'%');
                        } else {
                            $query = $query->where($var, '=', $con['parameter']);
                        }
                    } else {
                        if ($con['operator'] == 'like') {
                            $query = $query->orWhere($var, 'like', '%'.$con['parameter'].'%');
                        } else {
                            $query = $query->orWhere($var, '=', $con['parameter']);
                        }
                    }
                }

                if ($con['subject'] == 'payment_nominal' || $con['subject'] == 'grand_total') {
                    if ($post['rule'] == 'and' || $key == 0) {
                        $query = $query->where($var, $con['operator'], $con['parameter']);
                    } else {
                        $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                    }
                }

                if ($con['subject'] == 'confirmed_at' || $con['subject'] == 'cancelled_at') {
                    $var = 'transaction_payment_manuals.'.$con['subject'];
                    if ($post['rule'] == 'and' || $key == 0) {
                        $query = $query->whereDate($var, $con['operator'], date('Y-m-d', strtotime($con['parameter'])));
                    } else {
                        $query = $query->orWhereDate($var, $con['operator'], date('Y-m-d', strtotime($con['parameter'])));
                    }
                }

                if ($con['subject'] == 'gender' ) {
                    if ($post['rule'] == 'and' || $key == 0) {
                        $query = $query->where($var, '=', $con['parameter']);
                    } else {
                        $query = $query->orWhere($var, '=', $con['parameter']);
                    }
                }

            }

            $conditions = $post['conditions'];
            $rule       = $post['rule'];
            $search     = '1';
        }

        $akhir = $query->paginate(10)->toArray();
        // return response()->json($query->toSql());
        if ($akhir) {
            $result = [
                'status'     => 'success',
                'result'       => $akhir,
                'count'      => count($akhir['data']),
                'conditions' => $conditions,
                'rule'       => $rule,
                'search'     => $search
            ];
        } else {
            $result = [
                'status'     => 'fail',
                'result'       => $akhir,
                'count'      => count($akhir['data']),
                'conditions' => $conditions,
                'rule'       => $rule,
                'search'     => $search
            ];
        }

        return response()->json($result);
    }

    public function listPaymentMethod(Request $request){
        $method = ManualPayment::with('manual_payment_methods', 'manual_payment_methods.manual_payment_tutorials')->get()->toArray();
        return response()->json(MyHelper::checkGet($method));
    }

    public function bankList() {
		$query = Bank::get()->toArray();
        return response()->json(MyHelper::checkGet($query));
	}
	
	public function bankDelete(Request $request) {
        $id = $request->json('id');
        $check =  Bank::where('id_bank', $id)->first();
        if (empty($check)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Delete bank failed']
            ]);
        } else {
			$check->delete();
		}

        return response()->json(MyHelper::checkDelete($check));
    }
	
	public function bankCreate(Request $request) {
        $create = Bank::create(['nama_bank' => $request->json('nama_bank')]);
        return response()->json(MyHelper::checkCreate($create));
    }
	
	public function bankMethodList() {
		$query = BankMethod::get()->toArray();
        return response()->json(MyHelper::checkGet($query));
	}
	
	public function bankMethodDelete(Request $request) {
        $id = $request->json('id');
        $check =  BankMethod::where('id_bank_method', $id)->first();
        if (empty($check)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Delete payment method failed']
            ]);
        } else {
			$check->delete();
		}

        return response()->json(MyHelper::checkDelete($check));
    }
	
	public function bankmethodCreate(Request $request) {
        $create =  BankMethod::create(['method' => $request->json('method')]);
        return response()->json(MyHelper::checkCreate($create));
    }
	
    public function list() {
        $method = ManualPayment::select('id_manual_payment', 'manual_payment_name')->get()->toArray();
        return response()->json(MyHelper::checkGet($method));
    }

    public function paymentMethod(Request $request) {
        $post = $request->json()->all();
        $list = ManualPaymentMethod::select('id_manual_payment_method', 'payment_method_name');

        if (isset($post['id_manual_payment'])) {
            $list = $list->where('id_manual_payment', $post['id_manual_payment']);
        }

        $list = $list->get()->toArray();
        return response()->json(MyHelper::checkGet($list));
    }
}
