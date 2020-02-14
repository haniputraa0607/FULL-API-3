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
        if(is_array($request->json('rule'))){
            $this->filterList($list,$request->json('rule'),$request->json('operator'));
        }
        if($request->page){
            $list = $list->paginate(10);
        }else{
            $list = $list->get();
        }
        return MyHelper::checkGet($list);
    }

    public function filterList($model,$rule,$operator='and') {
        $where = $operator=='and'?'where':'orWhere';        $where=$operator=='and'?'where':'orWhere';
        foreach ($rule as $var) {
            $var1=['operator'=>$var['operator']??'=','parameter'=>$var['parameter']??null];
            if($var1['operator']=='like'){
                $var1['parameter']='%'.$var1['parameter'].'%';
            }
            $newRule[$var['subject']][]=$var1;
        }
        if($rules=$newRule['review_date']??false){
            foreach ($rules as $rul) {
                $model->{$where.'Date'}('user_feedbacks.created_at',$rul['operator'],$rul['parameter']);
            }
        }
        if($rules=$newRule['rating_value']??false){
            foreach ($rules as $rul) {
                $model->$where('rating_value',$rul['operator'],$rul['parameter']);
            }
        }
        if($rules=$newRule['transaction_date']??false){
            foreach ($rules as $rul) {
                $model->{$where.'Has'}('transaction',function($query) use ($rul){
                    $query->whereDate('transaction_date',$rul['operator'],$rul['parameter']);
                });
            }
        }
        if($rules=$newRule['transaction_type']??false){
            foreach ($rules as $rul) {
                $model->{$where.'Has'}('transaction',function($query) use ($rul){
                    $query->where('transaction_type',$rul['operator'],$rul['parameter']);
                });
            }
        }
        if($rules=$newRule['transaction_receipt_number']??false){
            foreach ($rules as $rul) {
                $model->{$where.'Has'}('transaction',function($query) use ($rul){
                    $query->where('transaction_receipt_number',$rul['operator'],$rul['parameter']);
                });
            }
        }
        if($rules=$newRule['user_name']??false){
            foreach ($rules as $rul) {
                $model->{$where.'Has'}('user',function($query) use ($rul){
                    $query->where('name',$rul['operator'],$rul['parameter']);
                });
            }
        }
        if($rules=$newRule['user_phone']??false){
            foreach ($rules as $rul) {
                $model->{$where.'Has'}('user',function($query) use ($rul){
                    $query->where('phone',$rul['operator'],$rul['parameter']);
                });
            }
        }
        if($rules=$newRule['user_email']??false){
            foreach ($rules as $rul) {
                $model->{$where.'Has'}('user',function($query) use ($rul){
                    $query->where('email',$rul['operator'],$rul['parameter']);
                });
            }
        }
        if($rules=$newRule['outlet']??false){
            foreach ($rules as $rul) {
                $model->{$where.'Has'}('transaction.outlet',function($query) use ($rul){
                    $query->where('id_outlet',$rul['operator'],$rul['parameter']);
                });
            }
        }
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
        $id_transaction = $post['id'];
        $transaction = Transaction::select('id_transaction','id_outlet')->where('id_user',$user->id)
        ->find($id_transaction);
        if(!$transaction){
            return [
                'status' => 'fail',
                'messages' => ['Transaction not found']
            ];
        }
        $rating = RatingItem::select('rating_value','text')->find($post['id_rating_item']);
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
            'rating_value'=> $rating->rating_value,
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
            $id_transaction = $post['id'];
            $transaction = Transaction::select('id_transaction','transaction_receipt_number','id_outlet')->with(['outlet'=>function($query){
                $query->select('outlet_name','id_outlet');
            }])
            ->where('id_user',$user->id)
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
                $interval =(Setting::where('key','popup_min_interval')->pluck('value')->first()?:900);
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
        $result['id'] = $transaction->id_transaction;
        $result['outlet'] = [
            'outlet_name'=>$transaction->outlet->outlet_name
        ];
        $result['ratings'] = RatingItem::select('id_rating_item','image','image_selected','text')->orderBy('order')->get();
        return MyHelper::checkGet($result);
    }
    public function report(Request $request) {
        $post = $request->json()->all();
        $showOutlet = 10;
        $counter = RatingItem::with(['feedbacks'=>function($query) use ($post){
            $this->applyFilter($query,$post);
        },'feedbacks.transaction'=>function($query){
            $query->select('id_transaction','transaction_receipt_number','trasaction_type','transaction_grandtotal');
        },'feedbacks.user'=>function($query){
            $query->select('id','name','phone');
        }])->withCount(['feedbacks'=>function($query) use ($post){
            $this->applyFilter($query,$post);
        }])->get();
        $outletp = UserFeedback::select(\DB::raw('outlets.id_outlet,outlet_name,outlet_code,user_feedbacks.rating_value,count(*) as total'))->join('transactions','transactions.id_transaction','=','user_feedbacks.id_transaction')
        ->join('outlets','transactions.id_outlet','=','outlets.id_outlet')
        ->where('rating_value','1')
        ->groupBy('outlets.id_outlet')
        ->orderBy('total','desc')
        ->take($showOutlet);
        $this->applyFilter($outletp,$post);
        $outletn = UserFeedback::select(\DB::raw('outlets.id_outlet,outlet_name,outlet_code,user_feedbacks.rating_value,count(*) as total'))->join('transactions','transactions.id_transaction','=','user_feedbacks.id_transaction')
        ->join('outlets','transactions.id_outlet','=','outlets.id_outlet')
        ->where('rating_value','-1')
        ->groupBy('outlets.id_outlet')
        ->orderBy('total','desc')
        ->take($showOutlet);
        $this->applyFilter($outletn,$post);
        $outletp->union($outletn);
        if(count($counter)==3){
            $outleto = UserFeedback::select(\DB::raw('outlets.id_outlet,outlet_name,outlet_code,user_feedbacks.rating_value,count(*) as total'))->join('transactions','transactions.id_transaction','=','user_feedbacks.id_transaction')
            ->join('outlets','transactions.id_outlet','=','outlets.id_outlet')
            ->where('rating_value','0')
            ->groupBy('outlets.id_outlet')
            ->orderBy('total','desc')
            ->take($showOutlet);
            $this->applyFilter($outleto,$post);
            $outletp->union($outleto);
        }
        $data['rating_item'] = $counter;
        $data['rating_item_count'] = count($counter);
        $data['outlet_data'] = $outletp->get();
        return MyHelper::checkGet($data);
    }
    // apply filter photos only/notes_only
    public function applyFilter($model,$rule,$col='user_feedbacks'){
        if($rule['notes_only']??false){
            $model->whereNotNull($col.'.notes');
        }
        if($rule['photos_only']??false){
            $model->whereNotNull($col.'.image');
        }
        $model->where($col.'.created_at','>=',$rule['date_start'])->where($col.'.created_at','<=',$rule['date_end']);
    }
    public function reportOutlet(Request $request) {
        $post = $request->json()->all();
        if($post['outlet_code']??false){
            $outlet = Outlet::select(\DB::raw('outlets.id_outlet,outlets.outlet_code,outlets.outlet_name,count(f1.id_user_feedback) as positive_feedback,count(f2.id_user_feedback) as neutral_feedback,count(f3.id_user_feedback) as negative_feedback'))
            ->where('outlet_code',$post['outlet_code'])->join('transactions','outlets.id_outlet','=','transactions.id_outlet')
            ->leftJoin('user_feedbacks as f1',function($join) use ($post){
                $join->on('f1.id_transaction','=','transactions.id_transaction')
                ->where('f1.rating_value','=','1');
                $this->applyFilter($join,$post,'f1');
            })
            ->leftJoin('user_feedbacks as f2',function($join) use ($post){
                $join->on('f2.id_transaction','=','transactions.id_transaction')
                ->where('f2.rating_value','=','0');
                $this->applyFilter($join,$post,'f2');
            })
            ->leftJoin('user_feedbacks as f3',function($join) use ($post){
                $join->on('f3.id_transaction','=','transactions.id_transaction')
                ->where('f3.rating_value','=','-1');
                $this->applyFilter($join,$post,'f3');
            })->first();
            if(!$outlet){
                return MyHelper::checkGet($outlet);
            }
            $data['outlet_data'] = $outlet;
            $post['id_outlet'] = $outlet->id_outlet;
            $data['rating_item'] = RatingItem::with(['feedbacks'=>function($query) use ($post){
                $query->whereHas('transaction',function($query) use ($post){
                    $query->where('id_outlet',$post['id_outlet']);
                });
                $this->applyFilter($query,$post);
            },'feedbacks.transaction'=>function($query){
                $query->select('id_transaction','transaction_receipt_number','trasaction_type','transaction_grandtotal');
            },'feedbacks.user'=>function($query){
                $query->select('id','name','phone');
            }])->withCount(['feedbacks'=>function($query) use ($post){
                $query->whereHas('transaction',function($query) use ($post){
                    $query->where('id_outlet',$post['id_outlet']);
                });
                $this->applyFilter($query,$post);
            }])->get();
            $data['rating_item_count'] = count($data['rating_item']);
            return MyHelper::checkGet($data);
        }else{
            $outlet = Outlet::select(\DB::raw('outlets.id_outlet,outlets.outlet_code,outlets.outlet_name,count(f1.id_user_feedback) as positive_feedback,count(f2.id_user_feedback) as neutral_feedback,count(f3.id_user_feedback) as negative_feedback'))
            ->join('transactions','outlets.id_outlet','=','transactions.id_outlet')
            ->leftJoin('user_feedbacks as f1',function($join) use ($post){
                $join->on('f1.id_transaction','=','transactions.id_transaction')
                ->where('f1.rating_value','=','1');
                $this->applyFilter($join,$post,'f1');
            })
            ->leftJoin('user_feedbacks as f2',function($join) use ($post){
                $join->on('f2.id_transaction','=','transactions.id_transaction')
                ->where('f2.rating_value','=','0');
                $this->applyFilter($join,$post,'f2');
            })
            ->leftJoin('user_feedbacks as f3',function($join) use ($post){
                $join->on('f3.id_transaction','=','transactions.id_transaction')
                ->where('f3.rating_value','=','-1');
                $this->applyFilter($join,$post,'f3');
            })
            ->groupBy('outlets.id_outlet');
            return MyHelper::checkGet($outlet->get()->toArray());
        }
    }
}
