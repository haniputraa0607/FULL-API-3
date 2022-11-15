<?php

namespace Modules\Setting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\PromoCampaign\Entities\FeaturedPromoCampaign;
use App\Lib\MyHelper;
use DB;

class ApiFeaturedPromoCampaign extends Controller
{
    public function indexMerchant()
    {
        $featured = FeaturedPromoCampaign::with('promo_campaign')->where('feature_type', 'merchant')->orderBy('order', 'asc')->get();

        return response()->json(MyHelper::checkGet($featured));
    }

    public function index()
    {
        $featured = FeaturedPromoCampaign::with('promo_campaign')->where('feature_type', 'home')->orderBy('order', 'asc')->get();

        return response()->json(MyHelper::checkGet($featured));
    }

    public function create(Request $request)
    {
        $post = $request->except('_token');
        $post['date_start'] = date('Y-m-d H:i:s', strtotime($post['date_start']));
        $post['date_end'] = date('Y-m-d H:i:s', strtotime($post['date_end']));
        $post['feature_type'] = $post['feature_type'] ?? 'home';

        $create = FeaturedPromoCampaign::updateOrCreate(['id_promo_campaign' => $post['id_promo_campaign'], 'feature_type' => $post['feature_type']], $post);

        return response()->json(MyHelper::checkCreate($create));
    }

    // reorder position
    public function reorder(Request $request)
    {
        $post = $request->json()->all();

        DB::beginTransaction();
        foreach ($post['id_featured_promo_campaign'] as $key => $id_featured_promo_campaign) {
            // reorder
            $update = FeaturedPromoCampaign::find($id_featured_promo_campaign)->update(['order' => $key + 1]);

            if (!$update) {
                DB:: rollback();
                return [
                    'status' => 'fail',
                    'messages' => ['Sort featured promo campaign failed']
                ];
            }
        }
        DB::commit();

        return response()->json(MyHelper::checkUpdate($update));
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    {
        $post = $request->json()->all();
        $post['date_start'] = date('Y-m-d H:i:s', strtotime($post['date_start']));
        $post['date_end'] = date('Y-m-d H:i:s', strtotime($post['date_end']));

        $featured = FeaturedPromoCampaign::find($post['id_featured_promo_campaign']);
        $update = $featured->update($post);

        return response()->json(MyHelper::checkCreate($update));
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy(Request $request)
    {
        $post = $request->json()->all();
        $featured = FeaturedPromoCampaign::find($post['id_featured_promo_campaign']);
        if (!$featured) {
            return [
                'status' => 'fail',
                'messages' => ['Data not found']
            ];
        }

        $delete = $featured->delete();

        return response()->json(MyHelper::checkDelete($delete));
    }
}
