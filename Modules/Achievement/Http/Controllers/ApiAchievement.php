<?php

namespace Modules\Achievement\Http\Controllers;

use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Achievement\Entities\AchievementCategory;
use Modules\Achievement\Entities\AchievementDetail;
use Modules\Achievement\Entities\AchievementGroup;

class ApiAchievement extends Controller
{
    public $saveImage = "img/achievement/";
    public $saveImageDetail = "img/achievement/detail/";

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request) {
        $data = AchievementGroup::select('achievement_groups.id_achievement_group','achievement_categories.name as category_name','achievement_groups.name','date_start','date_end','publish_start','publish_end')->leftJoin('achievement_categories','achievement_groups.id_achievement_category','=','achievement_categories.id_achievement_category');
        if($request->post('keyword')){
            $data->where('achievement_groups.name','like',"%{$request->post('keyword')}%");
        }
        return MyHelper::checkGet($data->paginate());
    }
    public function category(Request $request)
    {
        return [
            'status'    => 'success',
            'data'      => AchievementCategory::get()->toArray()
        ];
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
        if (!file_exists($this->saveImageDetail)) {
            mkdir($this->saveImageDetail, 0777, true);
        }

        DB::beginTransaction();

        if (isset($request['id_achievement_group'])) {
            $request->validate([
                'detail.*.name'             => 'required',
                'detail.*.logo_badge'       => 'required'
            ]);
        } else {
            $request->validate([
                'category.name'             => 'required',
                'group.name'                => 'required',
                'group.publish_start'       => 'required',
                'group.date_start'          => 'required',
                'group.description'         => 'required',
                'group.order_by'            => 'required',
                'group.logo_badge_default'  => 'required',
                'detail.*.name'             => 'required',
                'detail.*.logo_badge'       => 'required'
            ]);

            $upload = MyHelper::uploadPhotoStrict($post['group']['logo_badge_default'], $this->saveImage, 500, 500);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $post['group']['logo_badge_default'] = $upload['path'];
            } else {
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Failed to upload image']
                ]);
            }

            try {
                $category = AchievementCategory::where('name', $post['category']['name']);
                if ($category->exists()) {
                    $post['group']['id_achievement_category'] = $category->first()->id_achievement_category;
                } else {
                    $post['group']['id_achievement_category'] = AchievementCategory::create($post['category'])->id_achievement_category;
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status'    => 'fail',
                    'message'   => 'Get or Add Category Achievement Failed',
                    'error'     => $e->getMessage()
                ]);
            }

            $post['group']['publish_start']     = date('Y-m-d H:i', strtotime($post['group']['publish_start']));
            $post['group']['date_start']        = date('Y-m-d H:i', strtotime($post['group']['date_start']));
            if (!is_null($post['group']['publish_end'])) {
                $post['group']['publish_end']   = date('Y-m-d H:i', strtotime($post['group']['publish_end']));
            }
            if (!is_null($post['group']['date_end'])) {
                $post['group']['date_end']      = date('Y-m-d H:i', strtotime($post['group']['date_end']));
            }

            try {
                $group = AchievementGroup::create($post['group']);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status'    => 'fail',
                    'message'   => 'Add Achievement Group Failed',
                    'error'     => $e->getMessage()
                ]);
            }
        }

        if (isset($post['detail'])) {
            foreach ($post['detail'] as $key => $value) {
                $uploadDetail = MyHelper::uploadPhotoStrict($post['detail'][$key]['logo_badge'], $this->saveImageDetail, 500, 500);

                if (isset($uploadDetail['status']) && $uploadDetail['status'] == "success") {
                    $post['detail'][$key]['logo_badge'] = $uploadDetail['path'];
                } else {
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => ['Failed to upload image']
                    ]);
                }
                if (isset($request['id_achievement_group'])) {
                    $post['detail'][$key]['id_achievement_group']   = $request['id_achievement_group'];
                } else {
                    $post['detail'][$key]['id_achievement_group']   = $group->id_achievement_group;
                }
                $post['detail'][$key]['created_at']             = date('Y-m-d H:i:s');
                $post['detail'][$key]['updated_at']             = date('Y-m-d H:i:s');
            }

            try {
                AchievementDetail::insert($post['detail']);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status'    => 'fail',
                    'message'   => 'Add Achievement Detail Failed',
                    'error'     => $e->getMessage()
                ]);
            }
        }

        DB::commit();

        if (isset($request['id_achievement_group'])) {
            return response()->json([
                'status'    => 'success',
                'message'   => 'Add Achievement Success',
                'data'      => MyHelper::encSlug($request['id_achievement_group'])
            ]);
        } else {
            return response()->json([
                'status'    => 'success',
                'message'   => 'Add Achievement Success',
                'data'      => MyHelper::encSlug($group->id_achievement_group)
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
            $data['group']      = AchievementGroup::where('id_achievement_group', MyHelper::decSlug($request['id_achievement_group']))->first();
            $data['category']   = AchievementCategory::select('name')->where('id_achievement_category', $data['group']->id_achievement_category)->first();
            $data['detail']     = AchievementDetail::with('product', 'outlet', 'province')->where('id_achievement_group', MyHelper::decSlug($request['id_achievement_group']))->get()->toArray();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'    => 'fail',
                'message'   => 'Get Achievement Detail Failed',
                'error'     => $e->getMessage()
            ]);
        }

        $data['group']['logo_badge_default']    = env('S3_URL_API') . $data['group']['logo_badge_default'];
        foreach ($data['detail'] as $key => $value) {
            $data['detail'][$key]['logo_badge'] = env('S3_URL_API') . $value['logo_badge'];
        }

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
        return view('achievement::edit');
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

        if (isset($post['logo_badge'])) {
            $uploadDetail = MyHelper::uploadPhotoStrict($post['logo_badge'], $this->saveImageDetail, 500, 500);

            if (isset($uploadDetail['status']) && $uploadDetail['status'] == "success") {
                $post['logo_badge'] = $uploadDetail['path'];
            } else {
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Failed to upload image']
                ]);
            }
        }

        DB::beginTransaction();
        try {
            AchievementDetail::where('id_achievement_detail', $post['id_achievement_detail'])->update($post);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'    => 'fail',
                'message'   => 'Update Achievement Detail Failed',
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
    public function destroy(Request $request)
    {
        DB::beginTransaction();

        try {
            AchievementDetail::where('id_achievement_detail', $request['id_achievement_detail'])->delete();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'    => 'fail',
                'message'   => 'Get Achievement Detail Failed',
                'error'     => $e->getMessage()
            ]);
        }

        DB::commit();

        return response()->json([
            'status'    => 'success'
        ]);
    }
}
