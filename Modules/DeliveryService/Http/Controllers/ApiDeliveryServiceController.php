<?php

namespace Modules\DeliveryService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use Modules\DeliveryService\Entities\DeliveryServiceArea;
use App\Lib\MyHelper;
use DB;

class ApiDeliveryServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('deliveryservice::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('deliveryservice::create');
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
        return view('deliveryservice::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit()
    {
        return view('deliveryservice::edit');
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

    public function detailWebview()
    {
        $head = Setting::select('value AS head', 'value_text AS description')->where('key', 'delivery_services')->get()->first();
        $content = Setting::select('value AS head_content', 'value_text AS description_content')->where('key', 'delivery_service_content')->get()->first();
        $area = DeliveryServiceArea::get()->toArray();

        $result = ['head' => $head, 'content' => $content, 'area' => $area];

        return response()->json(['status'  => 'success', 'result' => $result]);
    }
}
