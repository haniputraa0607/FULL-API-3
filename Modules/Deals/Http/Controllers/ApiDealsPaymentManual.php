<?php

namespace Modules\Deals\Http\Controllers;

use App\Http\Models\DealsPaymentManual;
use App\Http\Models\Deal;
use App\Http\Models\DealsUser;
use App\Http\Models\ManualPayment;;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\Deals\Http\Requests\ManualPayment\ManualPaymentConfirm;

use App\Lib\MyHelper;
use DB;

class ApiDealsPaymentManual extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function manualPaymentList(Request $request, $type) {
        $post = $request->json()->all();
        $list = DealsPaymentManual::with('deal');

        if($type == "accepted" ){
            $list = $list->whereNotNull('confirmed_at');
        }elseif($type == "declined" ){
            $list = $list->whereNotNull('cancelled_at');
        }else{
            $list = $list->whereNull('cancelled_at')->whereNull('confirmed_at');
        }
        $list = $list->paginate(10)->toArray();
        return response()->json(MyHelper::checkGet($list));
    }

    public function detailManualPaymentUnpay(Request $request) {
        $id = $request->json('id_deals_payment_manual');
        $data = DealsPaymentManual::where('id_deals_payment_manual', $id)->with('deal', 'deals_user', 'deals_user.user', 'user')->first()->toArray();
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
        $updateConfirm = DealsPaymentManual::find($post['id_deals_payment_manual']);
        $update = $updateConfirm->update($confirm);
        if($update){
            $updateTrans = DealsUser::find($updateConfirm->id_deals_user)->update(['paid_status'=>$status]);
            if($update){
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

        $query = DealsPaymentManual::select('deals_payment_manuals.*')->with('deal')
        ->leftJoin('users as admin','deals_payment_manuals.id_user_confirming','=','admin.id')
        ->leftJoin('deals','deals_payment_manuals.id_deals','=','deals.id_deals')
        ->leftJoin('deals_users','deals_payment_manuals.id_deals_user','=','deals_users.id_deals_user')
        ->leftJoin('users','deals_users.id_user','=','users.id')
        ->orderBy('deals_payment_manuals.payment_date', 'DESC');

        if($type == "accepted" ){
            $query = $query->whereNotNull('confirmed_at');
        }elseif($type == "declined" ){
            $query = $query->whereNotNull('cancelled_at');
        }else{
            $query = $query->whereNull('cancelled_at')->whereNull('confirmed_at');
        }

        if(!empty($post['date_start'])){
            $start = date('Y-m-d', strtotime($post['date_start']));
            $query = $query->where('deals_payment_manuals.payment_date', '>=', $start);
        }
        if(!empty($post['date_end'])){
            $end = date('Y-m-d', strtotime($post['date_end']));
            $query = $query->where('deals_payment_manuals.payment_date', '<=', $end);
        }

        if (isset($post['conditions'])) {
            foreach ($post['conditions'] as $key => $con) {
                if ($con['subject'] == 'deals_title') {
                    $var = 'deals.deals_title';
                } elseif ($con['subject'] == 'name' || $con['subject'] == 'phone' || $con['subject'] == 'email') {
                    $var = 'users.'.$con['subject'];
                } elseif ($con['subject'] == 'payment_method' || $con['subject'] == 'payment_nominal' || $con['subject'] == 'payment_bank' || $con['subject'] == 'payment_account_number' || $con['subject'] == 'payment_account_name') {
                    $var = 'deals_payment_manuals.'.$con['subject'];
                } elseif ($con['subject'] == 'confirm_by') {
                    $var = 'admin.name';
                } elseif ($con['subject'] == 'voucher_price_cash') {
                    $var = 'deals_user.voucher_price_cash';
                }

                if ($con['subject'] == 'deals_title' || $con['subject'] == 'name' || $con['subject'] == 'phone' || $con['subject'] == 'email' || $con['subject'] == 'payment_bank' || $con['subject'] == 'payment_account_number' || $con['subject'] == 'payment_account_name' || $con['subject'] == 'confirm_by') {
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

                if ($con['subject'] == 'payment_nominal' || $con['subject'] == 'voucher_price_cash') {
                    if ($post['rule'] == 'and' || $key == 0) {
                        $query = $query->where($var, $con['operator'], $con['parameter']);
                    } else {
                        $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                    }
                }

                if ($con['subject'] == 'confirmed_at' || $con['subject'] == 'cancelled_at') {
                    $var = 'deals_payment_manuals.'.$con['subject'];
                    $op = explode(' - ', $con['parameter']);
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
        // return response()->json($akhir);
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
}
