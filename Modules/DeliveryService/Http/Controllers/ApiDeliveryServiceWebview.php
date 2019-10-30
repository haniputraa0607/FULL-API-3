<?php

namespace Modules\DeliveryService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Setting;
use Modules\DeliveryService\Entities\DeliveryServiceArea;

use App\Lib\MyHelper;

class ApiDeliveryServiceWebview extends Controller
{
    public function detailWebview(Request $request)
    {
        $bearer = $request->header('Authorization');
        
        if ($bearer == "") {
            return view('error', ['msg' => 'Unauthenticated']);
        }
        
        $head = Setting::select('value AS head', 'value_text AS description')->where('key', 'delivery_services')->get()->first();
        $content = Setting::select('value AS head_content', 'value_text AS description_content')->where('key', 'delivery_service_content')->get()->first();
        $area = DeliveryServiceArea::get()->toArray();

        $data['result'] = ['head' => $head, 'content' => $content, 'area' => $area];
        
        return view('deliveryservice::webview.detail', $data);
    }
}
