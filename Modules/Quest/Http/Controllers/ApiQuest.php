<?php

namespace Modules\Quest\Http\Controllers;

use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Quest\Entities\Quest;
use Modules\Quest\Entities\QuestDetail;

class ApiQuest extends Controller
{
    public $saveImage = "img/quest/";

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('quest::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create(Request $request)
    {
        $post = $request->json()->all();

        if (!file_exists($this->saveImage)) {
            mkdir($this->saveImage, 0777, true);
        }

        DB::beginTransaction();

        if (isset($request['id_quest'])) {
            $request->validate([
                'detail.*.name'                 => 'required',
                'detail.*.short_description'    => 'required'
            ]);
        } else {
            $request->validate([
                'quest.name'                    => 'required',
                'quest.publish_start'           => 'required',
                'quest.date_start'              => 'required',
                'quest.description'             => 'required',
                'quest.image'                   => 'required',
                'detail.*.name'                 => 'required',
                'detail.*.short_description'    => 'required'
            ]);

            $upload = MyHelper::uploadPhotoStrict($post['quest']['image'], $this->saveImage, 500, 500);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $post['quest']['image'] = $upload['path'];
            } else {
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Failed to upload image']
                ]);
            }

            $post['quest']['publish_start']     = date('Y-m-d H:i', strtotime($post['quest']['publish_start']));
            $post['quest']['date_start']        = date('Y-m-d H:i', strtotime($post['quest']['date_start']));
            if (!is_null($post['quest']['publish_end'])) {
                $post['quest']['publish_end']   = date('Y-m-d H:i', strtotime($post['quest']['publish_end']));
            }
            if (!is_null($post['quest']['date_end'])) {
                $post['quest']['date_end']      = date('Y-m-d H:i', strtotime($post['quest']['date_end']));
            }

            try {
                $quest = Quest::create($post['quest']);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status'    => 'fail',
                    'message'   => 'Add Quest Group Failed',
                    'error'     => $e->getMessage()
                ]);
            }
        }

        if (isset($post['detail'])) {
            foreach ($post['detail'] as $key => $value) {
                if (isset($request['id_quest'])) {
                    $post['detail'][$key]['id_quest']   = $request['id_quest'];
                } else {
                    $post['detail'][$key]['id_quest']   = $quest->id_quest;
                }
                $post['detail'][$key]['created_at']             = date('Y-m-d H:i:s');
                $post['detail'][$key]['updated_at']             = date('Y-m-d H:i:s');
            }

            try {
                QuestDetail::insert($post['detail']);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status'    => 'fail',
                    'message'   => 'Add Quest Detail Failed',
                    'error'     => $e->getMessage()
                ]);
            }
        }

        DB::commit();

        if (isset($request['id_quest'])) {
            return response()->json([
                'status'    => 'success',
                'message'   => 'Add Quest Success',
                'data'      => MyHelper::encSlug($request['id_quest'])
            ]);
        } else {
            return response()->json([
                'status'    => 'success',
                'message'   => 'Add Quest Success',
                'data'      => MyHelper::encSlug($quest->id_quest)
            ]);
        }
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
    public function show(Request $request)
    {
        try {
            $data['quest']  = Quest::where('id_quest', MyHelper::decSlug($request['id_quest']))->first();
            $data['detail'] = QuestDetail::with('product_category', 'product', 'outlet', 'province')->where('id_quest', MyHelper::decSlug($request['id_quest']))->get()->toArray();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'    => 'fail',
                'message'   => 'Get Quest Detail Failed',
                'error'     => $e->getMessage()
            ]);
        }

        $data['quest']['image']    = config('url.storage_url_api') . $data['quest']['image'];

        return response()->json([
            'status'    => 'success',
            'data'      => $data
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('quest::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request)
    {
        $post = $request->json()->all();

        DB::beginTransaction();
        try {
            QuestDetail::where('id_quest_detail', $post['id_quest_detail'])->update($post);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'    => 'fail',
                'message'   => 'Update Quest Detail Failed',
                'error'     => $e->getMessage()
            ]);
        }
        DB::commit();

        return response()->json([
            'status'    => 'success'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
