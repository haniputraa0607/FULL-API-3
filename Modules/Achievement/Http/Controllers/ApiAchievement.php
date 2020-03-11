<?php

namespace Modules\Achievement\Http\Controllers;

use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Achievement\Entities\AchievementCategory;
use Modules\Achievement\Entities\AchievementGroup;

class ApiAchievement extends Controller
{
    public $saveImage = "img/achievement/";

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('achievement::index');
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
        $request->validate([
            'category.name'             => 'required',
            'group.name'                => 'required',
            'group.publish_start'       => 'required',
            'group.date_start'          => 'required',
            'group.description'         => 'required',
            'group.order_by'            => 'required',
            'group.logo_badge_default'  => 'required'
        ]);

        $post = $request->json()->all();

        if (!file_exists($this->saveImage)) {
            mkdir($this->saveImage, 0777, true);
        }

        $upload = MyHelper::uploadPhotoStrict($post['group']['logo_badge_default'], $this->saveImage, 500, 500);

        if (isset($upload['status']) && $upload['status'] == "success") {
            $post['group']['logo_badge_default'] = $upload['path'];
        } else {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Failed to upload image']
            ]);
        }

        DB::beginTransaction();

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
            AchievementGroup::create($post['group']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'    => 'fail',
                'message'   => 'Add Achievement Failed',
                'error'     => $e->getMessage()
            ]);
        }

        DB::commit();

        return response()->json([
            'status'    => 'success',
            'message'   => 'Add Achievement Success'
        ]);
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
        return view('achievement::show');
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
    public function update(Request $request, $id)
    {
        //
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
