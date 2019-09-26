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
        $data = Setting::where('key', 'delivery_services')->get()->first();
        if (!$data) {
            $data['key']        = 'delivery_services';
            $data['value']      = 'Delivery Services';
            $data['value_text'] = null;
            Setting::create($data);
        }
        $content = Setting::where('key', 'delivery_service_content')->get()->first();
        if (!$content) {
            $content['key']        = 'delivery_service_content';
            $content['value']      = 'Big Order Delivery Service';
            $content['value_text'] = null;
            Setting::create($content);
        }
        $area = DeliveryServiceArea::get()->toArray();

        return response()->json([
            'status'    => 'success',
            'result'    => [
                'data'      => $data['value_text'],
                'content'   => $content['value_text'],
                'area'      => $area
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $post = $request->json()->all();

        DB::beginTransaction();

        if (isset($post['value_text'])) {
            $data['value_text'] = $post['value_text'];
            Setting::where('key', 'delivery_services')->update($data);
        } else {
            $data['value_text'] = null;
        }
        if (isset($post['value_text_content'])) {
            $content['value_text'] = $post['value_text_content'];
            Setting::where('key', 'delivery_service_content')->update($content);
        } else {
            $content['value_text'] = null;
        }
        if (isset($post['category-group'])) {
            DeliveryServiceArea::truncate();
            foreach ($post['category-group'] as $value) {
                DeliveryServiceArea::create($value);
            }
        } else {
            DeliveryServiceArea::truncate();
            $post['category-group'] = [];
        }

        DB::commit();
        return response()->json([
            'status'    => 'success',
            'result'    => [
                'data'      => $data['value_text'],
                'content'   => $content['value_text'],
                'area'      => $post['category-group']
            ]
        ]);
    }

    public function detailWebview()
    {
        $head = Setting::select('value AS head', 'value_text AS description')->where('key', 'delivery_services')->get()->first();
        $content = Setting::select('value AS head_content', 'value_text AS description_content')->where('key', 'delivery_service_content')->get()->first();
        $area = DeliveryServiceArea::get()->toArray();

        $result = ['head' => $head, 'content' => $content, 'area' => $area];

        return response()->json(['status'  => 'success', 'result' => $result]);
    }
}
