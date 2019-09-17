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
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('brand::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    { }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show()
    {
        return view('brand::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit()
    {
        return view('brand::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    { }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy()
    { }

    public function listBrand()
    {
        $brand = Brand::orderBy('order_brand', 'id_brand')->get()->toArray();

        if (!$brand) {
            return response()->json(['status'  => 'fail', 'messages' => ['empty!']]);
        }

        $nullOrZero = [];
        foreach ($brand as $key => $value) {
            if ($value['order_brand'] == null || $value['order_brand'] == 0) {
                $nullOrZero[] = $brand[$key];
                unset($brand[$key]);
            }
        }

        $dataMerge = array_merge($brand, $nullOrZero);

        $result = [];
        if ($dataMerge) {
            foreach ($dataMerge as $key => $value) {
                $result[$key]['code_brand']     = $value['code_brand'];
                $result[$key]['logo_brand']     = env("APP_API_URL") . $value['logo_brand'];
                $result[$key]['image_brand']    = env("APP_API_URL") . $value['image_brand'];
            }
        }
        return response()->json(['status'  => 'success', 'result' => $result]);
    }
}
