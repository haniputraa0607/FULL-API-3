<?php

namespace Modules\Plastic\Http\Controllers;

use App\Http\Models\TransactionProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use Modules\Plastic\Entities\PlasticType;
use Modules\Plastic\Entities\PlasticTypeOutlet;
use Modules\Plastic\Entities\PlasticTypeOutletGroup;
use Validator;
use Hash;
use DB;
use Mail;

class ApiPlasticTypeController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->outlet_group_filter = "Modules\Outlet\Http\Controllers\ApiOutletGroupFilterController";
    }

    public function index()
    {
        $data = PlasticType::orderBy('plastic_type_order', 'asc')
            ->with(['outlet_group'])
            ->get()->toArray();
        return response()->json(MyHelper::checkGet($data));
    }

    public function store(Request $request)
    {
        $post = $request->json()->all();
        if (isset($post['plastic_type_name']) && !empty($post['plastic_type_name'])) {
            DB::beginTransaction();
            $create = PlasticType::create(['plastic_type_name' => $post['plastic_type_name']]);

            if (!$create) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed create plastic type']]);
            }

            $dataInsert = [];
            foreach ($post['group_filter'] as $group) {
                $dataInsert[] = [
                    'id_plastic_type' => $create['id_plastic_type'],
                    'id_outlet_group' => $group
                ];
            }

            $insert = PlasticTypeOutletGroup::insert($dataInsert);
            if (!$insert) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed insert plastic type outlet group']]);
            }

            $insertOutlet = $this->syncPlasticTypeOutlet($create['id_plastic_type']);
            if (!$insertOutlet) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed insert plastic type outlet']]);
            }

            DB::commit();
            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function detail(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_plastic_type']) && !empty($post['id_plastic_type'])) {
            $detail = PlasticType::where('id_plastic_type', $post['id_plastic_type'])
                ->with(['outlet_group'])->first();

            return response()->json(MyHelper::checkGet($detail));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function update(Request $request)
    {
        $post = $request->json()->all();
        if (
            isset($post['id_plastic_type']) && !empty($post['id_plastic_type'])
            && isset($post['plastic_type_name']) && !empty($post['plastic_type_name'])
        ) {
            DB::beginTransaction();
            $update = PlasticType::where('id_plastic_type', $post['id_plastic_type'])->update(['plastic_type_name' => $post['plastic_type_name']]);

            if (!$update) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed update plastic type']]);
            }

            $dataInsert = [];
            PlasticTypeOutletGroup::where('id_plastic_type', $post['id_plastic_type'])->delete();
            foreach ($post['group_filter'] as $group) {
                $dataInsert[] = [
                    'id_plastic_type' => $post['id_plastic_type'],
                    'id_outlet_group' => $group
                ];
            }

            $insert = PlasticTypeOutletGroup::insert($dataInsert);
            if (!$insert) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed insert plastic type outlet group']]);
            }

            $insertOutlet = $this->syncPlasticTypeOutlet($post['id_plastic_type']);
            if (!$insertOutlet) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed insert plastic type outlet']]);
            }

            DB::commit();
            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted data']]);
        }
    }

    public function destroy(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_plastic_type']) && !empty($post['id_plastic_type'])) {
            $check = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                    ->where('id_plastic_type', $post['id_plastic_type'])->first();
            if (!empty($check)) {
                return response()->json(['status' => 'fail', 'messages' => ['Plastic Type already use']]);
            }

            $delete = PlasticType::where('id_plastic_type', $post['id_plastic_type'])->delete();
            PlasticTypeOutlet::where('id_plastic_type', $post['id_plastic_type'])->delete();
            PlasticTypeOutletGroup::where('id_plastic_type', $post['id_plastic_type'])->delete();
            return response()->json(MyHelper::checkDelete($delete));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function position(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_plastic_type']) && !empty($post['id_plastic_type'])) {
            foreach ($post['id_plastic_type'] as $key => $id) {
                PlasticType::where('id_plastic_type', $id)->update(['plastic_type_order' => $key + 1]);
            }
            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function syncPlasticTypeOutlet($id_plastic_type)
    {
        $getOutletGroup = PlasticTypeOutletGroup::where('id_plastic_type', $id_plastic_type)->pluck('id_outlet_group')->toArray();
        $tmpOutlet = [];

        foreach ($getOutletGroup as $group) {
            $outlet = app($this->outlet_group_filter)->outletGroupFilter($group);
            if (!empty($outlet)) {
                $outlets = array_column($outlet, 'id_outlet');
                $tmpOutlet = array_merge($tmpOutlet, $outlets);
            }
        }
        $tmpOutlet = array_unique($tmpOutlet);
        $dataInsert = [];
        PlasticTypeOutlet::where('id_plastic_type', $id_plastic_type)->delete();
        foreach ($tmpOutlet as $id_outlet) {
            $dataInsert[] = [
                'id_plastic_type' => $id_plastic_type,
                'id_outlet' => $id_outlet
            ];
        }
        $insert = PlasticTypeOutlet::insert($dataInsert);

        return $insert;
    }
}
