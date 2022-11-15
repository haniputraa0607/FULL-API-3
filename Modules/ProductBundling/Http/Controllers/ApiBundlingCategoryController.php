<?php

namespace Modules\ProductBundling\Http\Controllers;

use App\Http\Models\ProductCategory;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Brand\Entities\BrandProduct;
use Modules\ProductBundling\Entities\BundlingCategory;
use Modules\ProductBundling\Entities\Bundling;
use DB;

class ApiBundlingCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */

    public function listCategory(Request $request)
    {
        $post = $request->json()->all();

        if (!empty($post)) {
            $list = $this->getData($post);
        } else {
            $list = BundlingCategory::where('id_parent_category', 0)->orderBy('bundling_category_order')->get();

            foreach ($list as $key => $value) {
                $child = BundlingCategory::where('id_parent_category', $value['id_bundling_category'])->orderBy('bundling_category_order')->get();
                $list[$key]['child'] = $child;
            }
        }

        return response()->json(MyHelper::checkGet($list));
    }

    public function getData($post = [])
    {
        $category = BundlingCategory::with(['parentCategory'])->select('*');

        if (isset($post['id_parent_category'])) {
            if (is_null($post['id_parent_category']) || $post['id_parent_category'] == 0) {
                $category->master();
            } else {
                $category->parents($post['id_parent_category']);
            }
        } else {
            $category->master();
        }

        if (isset($post['id_bundling_category'])) {
            $category->id($post['id_bundling_category']);
        }

        $category = $category->orderBy('bundling_category_order')->get()->toArray();

        return $category;
    }
    public function index(Request $request)
    {
        $post = $request->json()->all();
        $bundlingCategory = BundlingCategory::orderBy('bundling_category_order', 'asc');

        if (isset($post['id_parent_category'])) {
            $bundlingCategory = $bundlingCategory->where('id_parent_category', $post['id_parent_category']);
        }

        $bundlingCategory = $bundlingCategory->paginate(20)->toArray();
        return MyHelper::checkGet($bundlingCategory);
    }

    public function checkInputCategory($post = [], $type = "update")
    {
        $data = [];

        if (isset($post['bundling_category_name'])) {
            $data['bundling_category_name'] = $post['bundling_category_name'];
        }

        if (isset($post['bundling_category_description'])) {
            $data['bundling_category_description'] = $post['bundling_category_description'];
        } else {
            $data['bundling_category_description'] = "";
        }

        if (isset($post['id_parent_category']) && $post['id_parent_category'] != null) {
            $data['id_parent_category'] = $post['id_parent_category'];
        } else {
            $data['id_parent_category'] = 0;
        }

        return $data;
    }

    public function create(Request $request)
    {

        $post = $request->json()->all();
        $data = $this->checkInputCategory($post, "create");

        if (isset($data['error'])) {
            unset($data['error']);

            return response()->json($data);
        }

        // create
        $create = BundlingCategory::create($data);

        return response()->json(MyHelper::checkCreate($create));
    }

    public function update(Request $request)
    {
        // info
        $dataCategory = BundlingCategory::where('id_bundling_category', $request->json('id_bundling_category'))->get()->toArray();

        if (empty($dataCategory)) {
            return response()->json(MyHelper::checkGet($dataCategory));
        }

        $post = $request->json()->all();

        $data = $this->checkInputCategory($post);

        if (isset($data['error'])) {
            unset($data['error']);

            return response()->json($data);
        }

        // update
        $update = BundlingCategory::where('id_bundling_category', $post['id_bundling_category'])->update($data);

        return response()->json(MyHelper::checkUpdate($update));
    }

    public function delete(Request $request)
    {

        $id = $request->json('id_bundling_category');

        if ($this->checkDeleteParent($id) && $this->checkDeleteBundling($id)) {
            $delete = BundlingCategory::where('id_bundling_category', $id)->delete();
            return response()->json(MyHelper::checkDelete($delete));
        } else {
            $result = [
                'status' => 'fail',
                'messages' => ['category has been used.']
            ];

            return response()->json($result);
        }
    }

    public function checkDeleteParent($id)
    {
        $check = BundlingCategory::where('id_parent_category', $id)->count();

        if ($check == 0) {
            return true;
        } else {
            return false;
        }
    }

    public function checkDeleteBundling($id)
    {
        $check = Bundling::where('id_bundling_category', $id)->count();

        if ($check == 0) {
            return true;
        } else {
            return false;
        }
    }

    public function positionCategory(Request $request)
    {
        $post = $request->json()->all();

        if (!isset($post['category_ids'])) {
            return [
                'status' => 'fail',
                'messages' => ['Category id is required']
            ];
        }
        // update position
        foreach ($post['category_ids'] as $key => $category_id) {
            $update = BundlingCategory::find($category_id)->update(['bundling_category_order' => $key + 1]);
        }

        return ['status' => 'success'];
    }
}
