<?php

namespace Modules\PromoCampaign\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use \App\Lib\MyHelper;

use \App\Http\Models\User;
use \App\Http\Models\Setting;
use \Modules\PromoCampaign\Entities\PromoCampaignReferral;
use \Modules\PromoCampaign\Entities\PromoCampaign;
use \Modules\PromoCampaign\Entities\UserReferralCode;
use Modules\PromoCampaign\Entities\PromoCampaignReferralTransaction;

class ApiReferralController extends Controller
{
    public function applyFilter($model,$rule,$table='promo_campaign_referral_transactions') {
        if($rule['date_start']??false){
            $model->whereDate($table.'.created_at','>=',date('Y-m-d',strtotime($rule['date_start'])));
        }
        if($rule['date_end']??false){
            $model->whereDate($table.'.created_at','<=',date('Y-m-d',strtotime($rule['date_end'])));
        }
    }
    /**
     * Provide report data
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function report(Request $request)
    {
        $perpage = 20;
        $order = ['promo_campaign_referral_transactions.created_at','desc'];
        $data['user'] = UserReferralCode::select('users.name','users.phone','user_referral_codes.*','promo_campaign_promo_codes.promo_code as referral_code')
            ->join('promo_campaign_promo_codes','promo_campaign_promo_codes.id_promo_campaign_promo_code','=','user_referral_codes.id_promo_campaign_promo_code')
            ->join('users','user_referral_codes.id_user','=','users.id')
            ->paginate($perpage);
        $data['transaction'] = PromoCampaignReferralTransaction::join('transactions','promo_campaign_referral_transactions.id_transaction','=','transactions.id_transaction')
            ->join('users','users.id','=','transactions.id_user')
            ->orderBy('promo_campaign_referral_transactions.created_at','desc')
            ->orderBy(...$order)
            ->paginate($perpage);
        return MyHelper::checkGet($data);
    }
    public function reportAjax(Request $request, $key)
    {
        $post = $request->json()->all();
        $perpage = 20;
        $order = [$post['order_by']??'promo_campaign_referral_transactions.created_at',$post['order_sorting']??'desc'];
        switch ($key) {
            case 'code':
                $order = [$post['order_by']??'number_transaction',$post['order_sorting']??'desc'];
                $data = UserReferralCode::select('users.name','users.phone','user_referral_codes.*','promo_campaign_promo_codes.promo_code as referral_code')
                ->join('promo_campaign_promo_codes','promo_campaign_promo_codes.id_promo_campaign_promo_code','=','user_referral_codes.id_promo_campaign_promo_code')
                ->join('users','user_referral_codes.id_user','=','users.id')
                ->orderBy(...$order)
                ->paginate($perpage);
                break;
            case 'code-summary':
                $data['data'] = UserReferralCode::select('users.name','users.phone','user_referral_codes.number_transaction')
                ->join('users','user_referral_codes.id_user','=','users.id')
                ->orderBy('number_transaction','desc')
                ->take(30)
                ->get();
                break;
            case 'trx':
                $data = PromoCampaignReferralTransaction::select('promo_campaign_referral_transactions.*','transactions.id_transaction','transactions.transaction_receipt_number','transactions.trasaction_type','transactions.transaction_grandtotal','users.name','users.phone','referrer.name as referrer_name','referrer.phone as referrer_phone')
                ->join('transactions','promo_campaign_referral_transactions.id_transaction','=','transactions.id_transaction')
                ->join('users','users.id','=','transactions.id_user')
                ->join('users as referrer','referrer.id','=','promo_campaign_referral_transactions.id_referrer')
                ->orderBy(...$order);
                $this->applyFilter($data,$post);
                $data = $data->paginate($perpage);
                break;
            case 'trx-summary':
                $data['data'] = PromoCampaignReferralTransaction::select(\DB::raw('count(*) as total,Date(created_at) as trx_date'))
                ->groupBy(\DB::raw('trx_date'))
                ->orderBy('trx_date');
                $this->applyFilter($data['data'],$post);
                $data['data']=$data['data']->get();
                break;
            case 'user':
                $id_user = User::select('id')->where('phone',$post['phone'])->pluck('id')->first();
                $data = PromoCampaignReferralTransaction::with(['user'=>function($query){
                        $query->select(['id','name','phone']);
                    },'transaction'=>function($query){
                        $query->select(['id_transaction','transaction_receipt_number','trasaction_type','transaction_grandtotal']);
                    }])->orderBy(...$order)->where('id_referrer',$id_user);
                $this->applyFilter($data,$post);
                $data = $data->paginate($perpage);
                break;
            case 'user-summary':
                $id_user = User::select('id')->where('phone',$post['phone'])->pluck('id')->first();
                $data['data'] = PromoCampaignReferralTransaction::select(\DB::raw('count(*) as total,Date(promo_campaign_referral_transactions.created_at) as trx_date'))
                    ->join('transactions','promo_campaign_referral_transactions.id_transaction','=','transactions.id_transaction')
                    ->join('users','users.id','=','transactions.id_user')
                    ->where('id_referrer',$id_user)
                    ->groupBy('trx_date');
                $this->applyFilter($data['data'],$post);
                $data['data'] = $data['data']->get();
                break;
            case 'user-total':
                $data = User::select('id','name','phone','promo_campaign_promo_codes.promo_code as referral_code','number_transaction','cashback_earned')
                    ->join('user_referral_codes','users.id','=','user_referral_codes.id_user')
                    ->join('promo_campaign_promo_codes','promo_campaign_promo_codes.id_promo_campaign_promo_code','=','user_referral_codes.id_promo_campaign_promo_code')
                    ->where('phone',$post['phone'])->first();
                break;
            default:
                $data = [];
                break;
        }
        return MyHelper::checkGet($data);
    }
    public function reportUser(Request $request)
    {
        $perpage = 20;
        $post = $request->json()->all();
        $order = ['promo_campaign_referral_transactions.created_at','desc'];
        if($post['ajax']??false){
            $id_user = User::select('id')->where('phone',$post['phone'])->pluck('id')->first();
            $data = PromoCampaignReferralTransaction::with(['user'=>function($query) use ($select_user){
                    $query->select($select_user);
                },'transaction'=>function($query) use ($select_trx){
                    $query->select($select_trx);
                }])->orderBy(...$order)->where('id_referrer',$id_user);
            $this->applyFilter($data,$post);
            $data = $data->paginate($perpage);
        }else{
            $data = User::select('id','name','phone','promo_campaign_promo_codes.promo_code as referral_code','number_transaction','cashback_earned')
                ->join('user_referral_codes','users.id','=','user_referral_codes.id_user')
                ->join('promo_campaign_promo_codes','promo_campaign_promo_codes.id_promo_campaign_promo_code','=','user_referral_codes.id_promo_campaign_promo_code')
                ->where('phone',$post['phone'])->first();
        }
        return MyHelper::checkGet($data);
    }


    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function setting(Request $request) {
        $referral = PromoCampaignReferral::with('promo_campaign')->first()->toArray();
        if($referral){
            $referral['referral_messages'] = Setting::select('value_text')->where('key','referral_messages')->pluck('value_text')->first()?:'Get %value% discount on your first purchase. By using the %code% promo code';
        }
        return MyHelper::checkGet($referral);
    }

    public function settingUpdate(Request $request) {
        $post = $request->json()->all();
        $referral = PromoCampaignReferral::first();
        if(
            ($post['referred_promo_unit'] == 'Percent' && $post['referred_promo_value']>100) || 
            ($post['referrer_promo_unit'] == 'Percent' && $post['referrer_promo_value']>100)
        ){
            return MyHelper::checkGet([],'Percent value should lower or equal than 100');
        }
        $dataPromoCampaign = [
            'promo_title'=>$post['promo_title']??null,
            'date_end'=>$post['date_end']??null
        ];
        $dataPromoCampaignReferral = [
            'referred_promo_type'=>$post['referred_promo_type']??null,
            'referred_promo_unit'=>$post['referred_promo_unit']??null,
            'referred_promo_value'=>$post['referred_promo_value']??null,
            'referred_min_value'=>$post['referred_min_value']??null,
            'referred_promo_value_max'=>$post['referred_promo_value_max']??null,
            'referrer_promo_unit'=>$post['referrer_promo_unit']??null,
            'referrer_promo_value'=>$post['referrer_promo_value']??null,
            'referrer_promo_value_max'=>$post['referrer_promo_value_max']??null
        ];
        \DB::beginTransaction();
        $update = $referral->update($dataPromoCampaignReferral);
        $update2 = PromoCampaign::where('id_promo_campaign',$referral->id_promo_campaign)->update($dataPromoCampaign);
        $update3 = Setting::updateOrCreate(['key'=>'referral_messages'],['value_text'=>$post['referral_messages']]);
        if(!$update || !$update2 || !$update3){
            \DB::rollback();
            return MyHelper::checkUpdate([]);
        }
        \DB::commit();
        return MyHelper::checkUpdate($update);
    }
}
