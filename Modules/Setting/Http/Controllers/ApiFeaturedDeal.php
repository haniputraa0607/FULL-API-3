<?php

namespace Modules\Setting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\FeaturedDeal;
use App\Lib\MyHelper;

use Modules\Setting\Http\Requests\FeaturedDeal\CreateRequest;
use Modules\Setting\Http\Requests\FeaturedDeal\UpdateRequest;

use DB;

class ApiFeaturedDeal extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        // get featured_deal with news title
        $featured_deals = FeaturedDeal::with('deals')->orderBy('order','asc')->get();

        return response()->json(MyHelper::checkGet($featured_deals));
    }


    public function create(CreateRequest $request)
    {
        $post=$request->except('_token');
        $create = FeaturedDeal::create($post);

        return response()->json(MyHelper::checkCreate($create));
    }

    // reorder position
    public function reorder(Request $request)
    {
        $post = $request->json()->all();

        DB::beginTransaction();
        foreach ($post['id_featured_deals'] as $key => $id_featured_deal) {
            // reorder
            $update = FeaturedDeal::find($id_featured_deal)->update(['order' => $key+1]);

            if (!$update) {
                DB:: rollback();
                return [
                    'status' => 'fail',
                    'messages' => ['Sort featured deal failed']
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
    public function update(UpdateRequest $request)
    {
        $post = $request->json()->all();
        $featured_deal = FeaturedDeal::find($post['id_featured_deals']);
        $update = $featured_deal->update($post);

        return response()->json(MyHelper::checkCreate($update));
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy(Request $request)
    {
        $post = $request->json()->all();
        $featured_deal = FeaturedDeal::find($post['id_featured_deals']);
        if (!$featured_deal) {
            return [
                'status' => 'fail',
                'messages' => ['Data not found']
            ];
        }

        $delete = $featured_deal->delete();

        return response()->json(MyHelper::checkDelete($delete));
    }
}
