<?php

namespace Modules\PromoCampaign\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use \App\Lib\MyHelper;

use \App\Http\Models\User;
use \Modules\PromoCampaign\Entities\PromoCampaignReferral;
use \Modules\PromoCampaign\Entities\PromoCampaign;
use \Modules\PromoCampaign\Entities\UserReferralCode;
use Modules\PromoCampaign\Entities\PromoCampaignReferralTransaction;

class ApiReferralController extends Controller
{
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
        $perpage = 20;
        $order = ['promo_campaign_referral_transactions.created_at','desc'];
        if($key == 'code'){
            $data = UserReferralCode::select('users.name','users.phone','user_referral_codes.*','promo_campaign_promo_codes.promo_code as referral_code')
            ->join('promo_campaign_promo_codes','promo_campaign_promo_codes.id_promo_campaign_promo_code','=','user_referral_codes.id_promo_campaign_promo_code')
            ->join('users','user_referral_codes.id_user','=','users.id')
            ->paginate($perpage);
        }elseif($key == 'trx'){
            $data = PromoCampaignReferralTransaction::join('transactions','promo_campaign_referral_transactions.id_transaction','=','transactions.id_transaction')
            ->join('users','users.id','=','transactions.id_user')
            ->orderBy(...$order)
            ->paginate($perpage);
        }
        return MyHelper::checkGet($data);
    }
    public function reportUser(Request $request)
    {
        $perpage = 20;
        $post = $request->json()->all();
        $select_user = ['id','name','phone'];
        $select_trx = ['id_transaction','transaction_receipt_number','trasaction_type','transaction_grandtotal'];
        $order = ['promo_campaign_referral_transactions.created_at','desc'];
        if($post['ajax']??false){
            $id_user = User::select('id')->where('phone',$post['phone'])->pluck('id')->first();
            $data = PromoCampaignReferralTransaction::with(['user'=>function($query) use ($select_user){
                    $query->select($select_user);
                },'transaction'=>function($query) use ($select_trx){
                    $query->select($select_trx);
                }])->orderBy(...$order)->where('id_referrer',$id_user)->paginate($perpage);
        }else{
            $data = User::select('id','name','phone','promo_campaign_promo_codes.promo_code as referral_code','number_transaction','cashback_earned')
                ->join('user_referral_codes','users.id','=','user_referral_codes.id_user')
                ->join('promo_campaign_promo_codes','promo_campaign_promo_codes.id_promo_campaign_promo_code','=','user_referral_codes.id_promo_campaign_promo_code')
                ->where('phone',$post['phone'])
                ->with(['referred_transaction'=>function($query) use ($perpage,$order){
                    $query->orderBy(...$order)->paginate($perpage);
                },'referred_transaction.user'=>function($query) use ($select_user){
                    $query->select($select_user);
                },'referred_transaction.transaction'=>function($query) use ($select_trx){
                    $query->select($select_trx);
                }])->first();
        }
        return MyHelper::checkGet($data);
    }


    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function setting(Request $request) {
        $referral = PromoCampaignReferral::with('promo_campaign')->first();
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
        if(!$update || !$update2){
            \DB::rollback();
            return MyHelper::checkUpdate([]);
        }
        \DB::commit();
        return MyHelper::checkUpdate($update);
    }
}
