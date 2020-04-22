<?php

namespace Modules\Setting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\Subscription\Entities\FeaturedSubscription;
use App\Lib\MyHelper;

use Modules\Setting\Http\Requests\FeaturedSubscription\CreateRequest;
use Modules\Setting\Http\Requests\FeaturedSubscription\UpdateRequest;

use DB;

class ApiFeaturedSubscription extends Controller
{
    public function index()
    {
        // get featured_subscription with news title
        $featured_subscription = FeaturedSubscription::with('subscription')->orderBy('order','asc')->get();

        return response()->json(MyHelper::checkGet($featured_subscription));
    }


    public function create(CreateRequest $request)
    {
        $post=$request->except('_token');
		$post['date_start'] = date('Y-m-d H:i:s', strtotime($post['date_start']));
		$post['date_end'] = date('Y-m-d H:i:s', strtotime($post['date_end']));

        $create = FeaturedSubscription::create($post);

        return response()->json(MyHelper::checkCreate($create));
    }

    // reorder position
    public function reorder(Request $request)
    {
        $post = $request->json()->all();

        DB::beginTransaction();
        foreach ($post['id_featured_subscription'] as $key => $id_featured_subscription) {
            // reorder
            $update = FeaturedSubscription::find($id_featured_subscription)->update(['order' => $key+1]);

            if (!$update) {
                DB:: rollback();
                return [
                    'status' => 'fail',
                    'messages' => ['Sort featured subscription failed']
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
        $post['date_start'] = date('Y-m-d H:i:s', strtotime($post['date_start']));
		$post['date_end'] = date('Y-m-d H:i:s', strtotime($post['date_end']));

        $featured_subscription = FeaturedSubscription::find($post['id_featured_subscription']);
        $update = $featured_subscription->update($post);

        return response()->json(MyHelper::checkCreate($update));
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy(Request $request)
    {
        $post = $request->json()->all();
        $featured_subscription = FeaturedSubscription::find($post['id_featured_subscription']);
        if (!$featured_subscription) {
            return [
                'status' => 'fail',
                'messages' => ['Data not found']
            ];
        }

        $delete = $featured_subscription->delete();

        return response()->json(MyHelper::checkDelete($delete));
    }
}
