<?php

namespace Modules\Brand\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use DB;

use Modules\Brand\Http\Requests\SyncBrand;
use Modules\Brand\Entities\Brand;
use Modules\POS\Http\Controllers\ApiPOS;

class ApiSyncBrandController extends Controller
{
    function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function syncBrand(SyncBrand $request)
    {
        $post = $request->json()->all();

        $api = ApiPOS::checkApi($post['api_key'], $post['api_secret']);
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
                        'messages' => 'fail to sync'
                    ]);
                }
            } else {
                $data['brand_active'] = $value['brand_active']??0;
                $save = Brand::create($data);
                $countSave = $countSave + 1;
                if (!$save) {
                    DB::rollBack();
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => 'fail to sync'
                    ]);
                }
            }
        }

        DB::commit();
        return response()->json([
            'status' => 'success', 'result' => ['inserted' => $countSave, 'updated' => $countUpdate]
        ]);
    }
}
