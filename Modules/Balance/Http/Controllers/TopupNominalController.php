<?php

namespace Modules\Balance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use App\Http\Models\TopupNominal;

class TopupNominalController extends Controller
{
    public function list(Request $request)
    {
        $post = $request->json()->all();
        $list = TopupNominal::get()->toArray();
        return response()->json(MyHelper::checkGet($list));
    }

    public function add(Request $request)
    {
        $post = $request->json()->all();
        $create = TopupNominal::create($post);
        return response()->json(MyHelper::checkCreate($create));
    }

    public function update(Request $request)
    {
        $post = $request->json()->all();
        $data = [
            'type'          => $post['type'],
            'nominal_bayar' => $post['nominal_bayar'],
            'nominal_topup' => $post['nominal_topup']
        ];

        $update = TopupNominal::where('id_topup_nominal', $post['id'])->update($data);
        return response()->json(MyHelper::checkUpdate($update));
    }

    public function delete(Request $request)
    {
        $post = $request->json()->all();
        $delete = TopupNominal::where('id_topup_nominal', $post['id'])->delete();
        return response()->json(MyHelper::checkDelete($delete));
    }
}
