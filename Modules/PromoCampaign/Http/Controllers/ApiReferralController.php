<?php

namespace Modules\PromoCampaign\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use \App\Lib\MyHelper;

use \Modules\PromoCampaign\Entities\PromoCampaignReferral;
use \Modules\PromoCampaign\Entities\PromoCampaign;

class ApiReferralController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('promocampaign::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('promocampaign::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('promocampaign::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('promocampaign::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
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
