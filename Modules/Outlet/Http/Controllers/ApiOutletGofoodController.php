<?php

namespace Modules\Outlet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Outlet;
use App\Lib\MyHelper;

class ApiOutletGofoodController extends Controller
{
    public function listOutletGofood(Request $request)
    {
        $post = $request->json()->all();

        $list = Outlet::whereNotNull('deep_link')->get();
        $list = $list->map(function ($item, $key) use ($post) {
            $data['deep_link'] = $item->deep_link;
            $data['outlet'] = $item->outlet_name;
            $data['km'] = $this->count($post['latitude'], $post['longitude'], $item->outlet_latitude, $item->outlet_longitude);
            return $data;
        });

        $list->all();

        if (empty($list)) {
            return response()->json(['status' => 'fail', 'messages' => ['Outlet empty']]);
        }

        $colek = collect($list->toArray());
        $sorted = $colek->sortBy('km');

        $dataReturn = [
            'status' => 'success',
            'result' => $sorted->values()->all(),
        ];

        return response()->json($dataReturn);
    }

    function count($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }
}
