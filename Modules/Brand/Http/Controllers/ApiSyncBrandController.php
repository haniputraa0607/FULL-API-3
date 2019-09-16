<?php

namespace Modules\Brand\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use DB;

use Modules\Brand\Http\Requests\SyncBrand;
use Modules\Brand\Entities\Brand;
use App\Http\Models\Setting;

class ApiSyncBrandController extends Controller
{
    function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function syncBrand(SyncBrand $request)
    {
        $post = $request->json()->all();

        $api = $this->checkApi($post['api_key'], $post['api_secret']);
        if ($api['status'] != 'success') {
            return response()->json($api);
        }

        DB::beginTransaction();

        $countSave = 0;
        $countUpdate = 0;
        foreach ($post['brand'] as $key => $value) {
            $data['name_brand'] = $value['name'];
            $data['code_brand'] = strtoupper($value['code']);

            $cekBrand = Brand::where('code_brand', strtoupper($value['code']))->first();

            if ($cekBrand) {
                $update = Brand::where('code_brand', strtoupper($value['code']))->update($data);
                $countUpdate = $countUpdate + 1;
                if (!$update) {
                    DB::rollBack();
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => ['fail to sync']
                    ]);
                }
            } else {
                $save = Brand::create($data);
                $countSave = $countSave + 1;
                if (!$save) {
                    DB::rollBack();
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => ['fail to sync']
                    ]);
                }
            }
        }

        DB::commit();
        return response()->json([
            'status' => 'success', 'result' => ['inserted' => $countSave, 'updated' => $countUpdate]
        ]);
    }

    function checkApi($key, $secret)
    {
        $api_key = Setting::where('key', 'api_key')->first();
        if (empty($api_key)) {
            return ['status' => 'fail', 'messages' => ['api_key not found']];
        }

        $api_key = $api_key['value'];
        if ($api_key != $key) {
            return ['status' => 'fail', 'messages' => ['api_key isn\’t match']];
        }

        $api_secret = Setting::where('key', 'api_secret')->first();
        if (empty($api_secret)) {
            return ['status' => 'fail', 'messages' => ['api_secret not found']];
        }

        $api_secret = $api_secret['value'];
        if ($api_secret != $secret) {
            return ['status' => 'fail', 'messages' => ['api_secret isn\’t match']];
        }

        return ['status' => 'success'];
    }
}
