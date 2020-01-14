<?php

namespace Modules\UserFeedback\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\UserFeedback\Entities\UserFeedback;
use Modules\UserFeedback\Entities\RatingItem;

use Modules\UserFeedback\Http\Requests\CreateRequest;
use Modules\UserFeedback\Http\Requests\DeleteRequest;
use Modules\UserFeedback\Http\Requests\DetailRequest;
use Modules\UserFeedback\Http\Requests\GetFormRequest;

use App\Http\Models\Transaction;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;

use Modules\UserFeedback\Entities\UserFeedbackLog;

use App\Lib\MyHelper;

class ApiUserFeedbackController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $list = UserFeedback::select('user_feedbacks.*')->join('outlets','outlets.id_outlet','=','user_feedbacks.id_outlet')->with(['transaction'=>function($query){
            $query->select('id_transaction','transaction_receipt_number','trasaction_type','transaction_grandtotal');
        },'user'=>function($query){
            $query->select('id','name','phone');
        }]);
        if($outlet_code = $request->json('outlet_code')){
            $list->where('outlet_code',$outlet_code);
        }
        if($request->page){
            $list = $list->paginate(10);
        }else{
            $list = $list->get();
        }
        return MyHelper::checkGet($list);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(CreateRequest $request)
    {
        $post = $request->json()->all();
        $user = $request->user();
        $id_trx = explode(',',$post['id']);
        $id_transaction = $id_trx[1]??'';
        $rn = $id_trx[0]??'';
        $transaction = Transaction::select('id_transaction','id_outlet')
        ->where('transaction_receipt_number',$id_trx[0])
        ->find($id_transaction);
        if(!$transaction){
            return [
                'status' => 'fail',
                'messages' => ['Transaction not found']
            ];
        }
        $rating = RatingItem::select('text')->find($post['id_rating_item']);
        if(!$rating){
            return [
                'status' => 'fail',
                'messages' => ['Rating item not found']
            ];
        }
        if(($post['image']??false)&&($post['ext']??false)){
            $upload = MyHelper::uploadFile($post['image'],'img/user_feedback/',$post['ext']);
            if($upload['status']!='success'){
                return [
                    'status' => 'fail',
                    'messages' => ['Fail upload file']
                ];
            }
        }
        $insert = [
            'id_outlet' => $transaction->id_outlet,
            'id_user' => $user->id,
            'id_rating_item'=> $post['id_rating_item'],
            'rating_item_text'=> $rating->text,
            'id_transaction'=> $id_transaction,
            'notes'=> $post['notes'],
            'image'=> $upload['path']??null
        ];
        $create = UserFeedback::updateOrCreate(['id_transaction'=>$id_transaction],$insert);
        UserFeedbackLog::where('id_user',$request->user()->id)->delete();
        if($create){
            Transaction::where('id_user',$user->id)->update(['show_rate_popup'=>0]);
        }
        return MyHelper::checkCreate($create);
    }

    /**
     * User refuse to rate
     * @param Request $request
     * @return Response
     */
    public function refuse(Request $request)
    {
        $user = $request->user();
        $update = Transaction::where('id_user',$user->id)->update(['show_rate_popup'=>0]);
        return MyHelper::checkUpdate($update);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show(DetailRequest $request)
    {
        $feedback = UserFeedback::where(['id_transaction'=>$request->post('id_transaction')])->find($request->post('id_user_feedback'));
        if(!$feedback){
            return [
                'status'=>'fail',
                'messages'=>['User feedback not found']
            ];
        }
        $feedback->load(['transaction'=>function($query){
            $query->select('id_transaction','transaction_receipt_number','trasaction_type','transaction_grandtotal');
        },'outlet'=>function($query){
            $query->select('id_outlet','outlet_name','outlet_code');
        },'user'=>function($query){
            $query->select('id','name','phone');
        }]);
        return MyHelper::checkGet($feedback);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(DeleteRequest $request)
    {
        $delete = UserFeedback::where(['id_user_feedback'=>$request->json('id_user_feedback')])->delete();
        return MyHelper::checkDelete($delete);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function getDetail(Request $request) {
        $post = $request->json()->all();
        // rating item
        $user = $request->user();
        if($post['id']??false){
            $id_trx = explode(',',$post['id']);
            $id_transaction = $id_trx[1]??'';
            $rn = $id_trx[0]??'';
            $transaction = Transaction::select('id_transaction','transaction_receipt_number','id_outlet')->with(['outlet'=>function($query){
                $query->select('outlet_name','id_outlet');
            }])
            ->where(['transaction_receipt_number'=>$rn,'id_transaction'=>$id_transaction,'id_user'=>$user->id])
            ->find($id_transaction);
            if(!$transaction){
                return [
                    'status' => 'fail',
                    'messages' => ['Transaction not found']
                ];
            }
        }else{
            $user->load('log_popup');
            $log_popup = $user->log_popup;
            if($log_popup){
                $interval =(Setting::where('key','popup_min_interval')->pluck('value')->first()?:15)*60;
                if(
                    $log_popup->refuse_count>=(Setting::where('key','popup_max_refuse')->pluck('value')->first()?:3) ||
                    strtotime($log_popup->last_popup)+$interval>time()
                ){
                    return MyHelper::checkGet([]);
                }
                $log_popup->refuse_count++;
                $log_popup->last_popup = date('Y-m-d H:i:s');
                $log_popup->save();
            }else{
                UserFeedbackLog::create([
                    'id_user' => $user->id,
                    'refuse_count' => 1,
                    'last_popup' => date('Y-m-d H:i:s')
                ]);
            }
            $transaction = Transaction::select('id_transaction','transaction_receipt_number','id_outlet')->with(['outlet'=>function($query){
                $query->select('outlet_name','id_outlet');
            }])
            ->where(['show_rate_popup'=>1,'id_user'=>$user->id])
            ->first();
            if(!$transaction){
                return MyHelper::checkGet([]);
            }
        }
        $result['id_transaction'] = $transaction->id_transaction;
        $result['id'] = $transaction->transaction_receipt_number.','.$transaction->id_transaction;
        $result['outlet'] = $transaction['outlet'];
        $result['ratings'] = RatingItem::select('id_rating_item','image','image_selected','text')->orderBy('order')->get();
        return MyHelper::checkGet($result);
    }
}
