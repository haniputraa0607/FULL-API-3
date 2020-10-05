<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\Transaction;
use App\Http\Models\TransactionPayment;
use App\Http\Models\StockLog;

use App\Http\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Transaction\Entities\LogInvalidTransaction;
use Modules\Transaction\Http\Requests\TransactionNew;

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;
use Image;

use Auth;

class ApiInvalidTransactionController extends Controller
{
    public $saveImage = "img/transaction/manual-payment/";

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function filterMarkFlag(Request $request){
        $post = $request->json()->all();

        $data = Transaction::join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
            ->leftJoin('disburse_outlet_transactions as dot', 'dot.id_transaction', 'transactions.id_transaction')
            ->whereNull('dot.id_disburse_outlet')
            ->select('outlets.*', 'transactions.*', 'transaction_pickups.*');

        if(isset($post['invalid']) && $post['invalid'] == 1){
            $data = $data->where('transaction_flag_invalid', 'Invalid');
        }else{
            $data = $data->where(function ($q){
                $q->where('transaction_flag_invalid', 'Valid')
                    ->orWhereNull('transaction_flag_invalid');
            });
        }

        if(isset($post['filter_type']) && !empty($post['filter_type'])){

            if($post['filter_type'] == 'receipt_number'){
                $param = array_column($post['conditions'], 'parameter');
                $data = $data->whereIn('transaction_receipt_number', $param);
            }elseif($post['filter_type'] == 'order_id'){
                $param = array_column($post['conditions'], 'parameter');
                $data = $data->whereIn('order_id', $param);
            }
        }

        $result = $data->paginate(20);
        return response()->json(MyHelper::checkGet($result));
    }

    public function markAsInvalidAdd(Request $request){
        $post = $request->json()->all();
        $get = User::where('id', $request->user()->id)->first();

        if (password_verify($post['pin'], $get['password'])) {
            $dataInsertLog = [
                'id_transaction' => $post['id_transaction'],
                'reason' => $post['reason'],
                'tansaction_flag' => 'Invalid',
                'updated_by' => $request->user()->id,
                'updated_date' => date('Y-m-d H:i:s')
            ];

            $dataUpdateTrx = [
                'transaction_flag_invalid' => 'Invalid'
            ];

            if (isset($post['image'])) {
                $upload = MyHelper::uploadPhotoStrict($post['image'], 'img/invalid-flag/', 800, 800, $post['id_transaction']);
                if (isset($upload['status']) && $upload['status'] == "success") {
                    $dataUpdateTrx['image_invalid_flag'] = $upload['path'];
                }
            }

            $add = LogInvalidTransaction::create($dataInsertLog);
            $update = Transaction::where('id_transaction', $post['id_transaction'])->update($dataUpdateTrx);

            return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Please Input Correct Pin']]);
        }
    }

    public function markAsValidUpdate(Request $request){
        $post = $request->json()->all();

        $get = User::where('id', $request->user()->id)->first();

        if (password_verify($post['pin'], $get['password'])) {
            $dataInsertLog = [
                'id_transaction' => $post['id_transaction'],
                'reason' => $post['reason'],
                'tansaction_flag' => 'Valid',
                'updated_by' => $request->user()->id,
                'updated_date' => date('Y-m-d H:i:s')
            ];

            $dataUpdateTrx = [
                'transaction_flag_invalid' => 'Valid'
            ];

            $add = LogInvalidTransaction::create($dataInsertLog);
            $update = Transaction::where('id_transaction', $post['id_transaction'])->update($dataUpdateTrx);

            return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Please Input Correct Pin']]);
        }
    }

    public function logInvalidFlag(Request $request){
        $post = $request->json()->all();

        $list = LogInvalidTransaction::join('transactions', 'transactions.id_transaction', 'log_invalid_transactions.id_transaction')
                ->join('users', 'users.id', 'log_invalid_transactions.updated_by')
                ->groupBy('log_invalid_transactions.id_transaction');

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'status'){
                            $list->where('transactions.transaction_flag_invalid', $row['operator']);
                        }

                        if($row['subject'] == 'receipt_number'){
                            if($row['operator'] == '='){
                                $list->where('transactions.transaction_receipt_number', $row['parameter']);
                            }else{
                                $list->where('transactions.transaction_receipt_number', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'updated_by'){
                            if($row['operator'] == '='){
                                $list->whereIn('id_log_invalid_transaction', function ($q) use($row){
                                    $q->select('l.id_log_invalid_transaction')
                                        ->from('log_invalid_transactions as l')
                                        ->join('users', 'users.id', 'l.updated_by')
                                        ->where('users.name', $row['parameter']);
                                });
                            }else{
                                $list->whereIn('id_log_invalid_transaction', function ($q) use($row){
                                    $q->select('l.id_log_invalid_transaction')
                                        ->from('log_invalid_transactions as l')
                                        ->join('users', 'users.id', 'l.updated_by')
                                        ->where('users.name', 'like', '%'.$row['parameter'].'%');
                                });
                            }
                        }
                    }
                }
            }else{
                $list->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'status'){
                                $subquery->orWhere('transactions.transaction_flag_invalid', $row['operator']);
                            }

                            if($row['subject'] == 'receipt_number'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('transactions.transaction_receipt_number', $row['parameter']);
                                }else{
                                    $subquery->orWhere('transactions.transaction_receipt_number', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'updated_by'){
                                if($row['operator'] == '='){
                                    $subquery->orWhereIn('id_log_invalid_transaction', function ($q) use($row){
                                        $q->select('l.id_log_invalid_transaction')
                                            ->from('log_invalid_transactions as l')
                                            ->join('users', 'users.id', 'l.updated_by')
                                            ->where('users.name', $row['parameter']);
                                    });
                                }else{
                                    $subquery->orWhereIn('id_log_invalid_transaction', function ($q) use($row){
                                        $q->select('l.id_log_invalid_transaction')
                                            ->from('log_invalid_transactions as l')
                                            ->join('users', 'users.id', 'l.updated_by')
                                            ->where('users.name', 'like', '%'.$row['parameter'].'%');
                                    });
                                }
                            }
                        }
                    }
                });
            }
        }

        $list = $list->paginate(30);

        return MyHelper::checkGet($list);
    }

    public function detailInvalidFlag(Request $request){
        $post = $request->json()->all();
        $list = LogInvalidTransaction::join('transactions', 'transactions.id_transaction', 'log_invalid_transactions.id_transaction')
            ->join('users', 'users.id', 'log_invalid_transactions.updated_by')
            ->where('log_invalid_transactions.id_transaction', $request['id_transaction'])
            ->select(DB::raw('DATE_FORMAT(log_invalid_transactions.updated_date, "%d %M %Y %H:%i") as updated_date'), 'users.name', 'log_invalid_transactions.tansaction_flag', 'transactions.transaction_receipt_number', 'log_invalid_transactions.reason', 'transactions.image_invalid_flag')
            ->get()->toArray();

        if($list){
            $list[0]['url_storage'] =  config('url.storage_url_api');
        }
        return MyHelper::checkGet($list);
    }
}
