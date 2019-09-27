<?php

namespace Modules\Brand\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\Brand\Entities\Brand;
use App\Lib\MyHelper;
use DB;

class ApiBrandController extends Controller
{
    function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('brand::index');
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['logo_brand'])) {
            $upload = MyHelper::uploadPhoto($post['logo_brand'], $path = 'img/brand/logo/');
            if ($upload['status'] == "success") {
                $post['logo_brand'] = $upload['path'];
            } else {
                $result = [
                    'status'    => 'fail',
                    'messages'    => ['fail upload image']
                ];
                return response()->json($result);
            }
        }

        if (isset($post['image_brand'])) {
            $upload = MyHelper::uploadPhoto($post['image_brand'], $path = 'img/brand/image/');
            if ($upload['status'] == "success") {
                $post['image_brand'] = $upload['path'];
            } else {
                $result = [
                    'status'    => 'fail',
                    'messages'    => ['fail upload image']
                ];
                return response()->json($result);
            }
        }

        DB::beginTransaction();
        if (isset($post['id_brand'])) {
            $request->validate([
                'name_brand'    => 'required'
            ]);

            if (isset($post['code_brand'])) {
                unset($post['code_brand']);
            }

            try {
                Brand::where('id_brand', $post['id_brand'])->update($post);
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Update Brand Failed'
                ];
                DB::rollBack();
                return response()->json($result);
            }
            DB::commit();
            return response()->json(['status'  => 'success', 'result' => ['id_brand' => $post['id_brand']]]);
        } else {
            $request->validate([
                'name_brand'    => 'required',
                'code_brand'    => 'required'
            ]);
            try {
                $save = Brand::create($post);
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Create Brand Failed'
                ];
                DB::rollBack();
                return response()->json($result);
            }
            DB::commit();
            return response()->json(['status'  => 'success', 'result' => ['id_brand' => $save->id_brand]]);
        }
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show(Request $request)
    {
        $post = $request->json()->all();

        $getBrand = Brand::where('id_brand', $post['id_brand'])->get()->first();

        return response()->json(['status'  => 'success', 'result' => $getBrand]);
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy()
    { }

    public function listBrand()
    {
        $brand = Brand::select('id_brand', 'name_brand', 'logo_brand', 'image_brand')->orderByRaw('CASE WHEN order_brand = 0 THEN 1 ELSE 0 END')->orderBy('order_brand');
        if (isset($_GET['page'])) {
            $brand = $brand->paginate(10)->toArray();
            if (!$brand) {
                return response()->json(['status'  => 'fail', 'messages' => ['empty!']]);
            }
            $data['data']           = $brand['data'];
            $data['next_page_url']  = $brand['next_page_url'];
        } else {
            $brand = $brand->get()->toArray();
            if (!$brand) {
                return response()->json(['status'  => 'fail', 'messages' => ['empty!']]);
            }
            $data = $brand;
        }

        return response()->json(['status'  => 'success', 'result' => $data]);
    }
}
