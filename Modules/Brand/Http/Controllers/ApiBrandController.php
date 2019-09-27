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
