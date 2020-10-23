<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Models\ProductModifier;
use App\Http\Models\ProductModifierBrand;
use App\Http\Models\ProductModifierGlobalPrice;
use App\Http\Models\ProductModifierPrice;
use App\Http\Models\ProductModifierDetail;
use App\Http\Models\ProductModifierProduct;
use App\Http\Models\ProductModifierProductCategory;
use App\Lib\MyHelper;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Brand\Entities\BrandOutlet;
use Modules\Product\Entities\ProductModifierGroup;
use Modules\Product\Entities\ProductModifierGroupPivot;
use Modules\Product\Http\Requests\Modifier\CreateRequest;
use Modules\Product\Http\Requests\Modifier\ShowRequest;
use Modules\Product\Http\Requests\Modifier\UpdateRequest;

class ApiProductModifierGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post   = $request->json()->all();
        $modifier_group = ProductModifierGroup::with(['product_modifier_group_pivots', 'product_modifier']);

        if(isset($post['id_product_modifier_group']) && !empty($post['id_product_modifier_group'])){
            $modifier_group = $modifier_group->where('id_product_modifier_group', $post['id_product_modifier_group'])->first();
            return MyHelper::checkGet($modifier_group);
        }

        if(isset($post['rule']) && !empty($post['rule'])){
            $rule = 'and';
            if(isset($post['operator'])){
                $rule = $post['operator'];
            }

            if($rule == 'and'){
                foreach ($post['rule'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'text'){
                            if($row['operator'] == '='){
                                $modifier_group->where('product_modifier_group_name', $row['parameter']);
                            }else{
                                $modifier_group->where('product_modifier_group_name', 'like', '%'.$row['parameter'].'%');
                            }
                        }
                    }
                }
            }else{
                $modifier_group->where(function ($subquery) use ($post){
                    foreach ($post['rule'] as $row){
                        if(isset($row['subject'])){
                            if($row['operator'] == '='){
                                $subquery->orWhere('product_modifier_group_name', $row['parameter']);
                            }else{
                                $subquery->orWhere('product_modifier_group_name', 'like', '%'.$row['parameter'].'%');
                            }
                        }
                    }
                });
            }
        }

        if(isset($post['page'])){
            $modifier_group = $modifier_group->paginate(25);
        }else{
            $modifier_group = $modifier_group->get()->toArray();
        }

        return MyHelper::checkGet($modifier_group);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $post = $request->json()->all();

        if(isset($post['text']) && isset($post['data_modifier']) && !empty($post['data_modifier'])){
            DB::beginTransaction();
            $create = ProductModifierGroup::create([
                'product_modifier_group_name' => $post['text']
            ]);
            if(!$create){
                DB::rollback();
                return [
                    'status'   => 'fail',
                    'messages' => ['Failed create product modifier group'],
                ];
            }
            $id_product_modifier_group = $create['id_product_modifier_group'];
            $dataInsertModifierGroupPivot = [];
            if(isset($post['id_product_variant'])){
                $dataInsertModifierGroupPivot['id_product_modifier_group'] = $id_product_modifier_group;
                $dataInsertModifierGroupPivot['id_product_variant'] = $post['id_product_variant'];
            }

            if(isset($post['id_product'])){
                foreach ($post['id_product'] as $p){
                    $dataInsertModifierGroupPivot[] = [
                        'id_product_modifier_group' => $id_product_modifier_group,
                        'id_product' => $p
                    ];
                }
            }

            if($dataInsertModifierGroupPivot){
                $create = ProductModifierGroupPivot::insert($dataInsertModifierGroupPivot);
                if(!$create){
                    DB::rollback();
                    return [
                        'status'   => 'fail',
                        'messages' => ['Failed create product modifier group pivot'],
                    ];
                }
            }

            if(!empty($post['data_modifier'])){
                $insertModifier = [];
                foreach ($post['data_modifier'] as $modifier){
                    $insertModifier[] = [
                        'id_product_modifier_group' => $id_product_modifier_group,
                        'modifier_type' => 'Modifier Group',
                        'type' => 'Modifier Group',
                        'code' => 'GENERATEBYSYSTEM_'.MyHelper::createrandom(5),
                        'text' => $modifier['name'],
                        'product_modifier_visibility' => (isset($modifier['visibility']) ? 'Visible': 'Hidden'),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }

                if($insertModifier){
                    $create = ProductModifier::insert($insertModifier);

                    if(!$create){
                        DB::rollback();
                        return [
                            'status'   => 'fail',
                            'messages' => ['Failed create product modifier'],
                        ];
                    }
                }
            }
            DB::commit();
            return response()->json(MyHelper::checkCreate($create));
        }else{
            return response()->json([ 'status'   => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function update(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_product_modifier_group']) && !empty($post['id_product_modifier_group'])){
            DB::beginTransaction();
            $update = ProductModifierGroup::where('id_product_modifier_group', $post['id_product_modifier_group'])->update([
                'product_modifier_group_name' => $post['text']
            ]);
            if(!$update){
                DB::rollback();
                return [
                    'status'   => 'fail',
                    'messages' => ['Failed update product modifier group'],
                ];
            }

            $delete = ProductModifierGroupPivot::where('id_product_modifier_group', $post['id_product_modifier_group'])->delete();
            if(!$delete){
                DB::rollback();
                return [
                    'status'   => 'fail',
                    'messages' => ['Failed delete product modifier group pivot'],
                ];
            }

            $id_product_modifier_group = $post['id_product_modifier_group'];
            $dataInsertModifierGroupPivot = [];
            if(isset($post['id_product_variant'])){
                $dataInsertModifierGroupPivot['id_product_modifier_group'] = $id_product_modifier_group;
                $dataInsertModifierGroupPivot['id_product_variant'] = $post['id_product_variant'];
            }

            if(isset($post['id_product'])){
                foreach ($post['id_product'] as $p){
                    $dataInsertModifierGroupPivot[] = [
                        'id_product_modifier_group' => $id_product_modifier_group,
                        'id_product' => $p
                    ];
                }
            }

            if($dataInsertModifierGroupPivot){
                $create = ProductModifierGroupPivot::insert($dataInsertModifierGroupPivot);
                if(!$create){
                    DB::rollback();
                    return [
                        'status'   => 'fail',
                        'messages' => ['Failed create product modifier group pivot'],
                    ];
                }
            }

            if(!empty($post['data_modifier'])){
                $insertModifier = [];
                foreach ($post['data_modifier'] as $modifier){
                    if(!isset($modifier['name']) && isset($modifier['code']) && !empty($modifier['code'])){
                        $delete = ProductModifier::where('code', $modifier['code'])->delete();
                        if(!$delete){
                            DB::rollback();
                            return [
                                'status'   => 'fail',
                                'messages' => ['Failed delete product modifier'],
                            ];
                        }
                    }elseif (isset($modifier['name']) && !empty($modifier['name']) && isset($modifier['code']) && !empty($modifier['code'])){
                        $update = ProductModifier::where('code', $modifier['code'])->update(
                                [
                                    'text' => $modifier['name'],
                                    'product_modifier_visibility' => (isset($modifier['visibility']) ? 'Visible': 'Hidden'),
                                    'updated_at' => date('Y-m-d H:i:s')
                                ]
                        );
                        if(!$update){
                            DB::rollback();
                            return [
                                'status'   => 'fail',
                                'messages' => ['Failed update product modifier'],
                            ];
                        }
                    }else{
                        $update = ProductModifier::create([
                            'id_product_modifier_group' => $id_product_modifier_group,
                            'modifier_type' => 'Modifier Group',
                            'type' => 'Modifier Group',
                            'code' => 'GENERATEBYSYSTEM_'.MyHelper::createrandom(5),
                            'text' => $modifier['name'],
                            'product_modifier_visibility' => (isset($modifier['visibility']) ? 'Visible': 'Hidden'),
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);

                        if(!$update){
                            DB::rollback();
                            return [
                                'status'   => 'fail',
                                'messages' => ['Failed create product modifier'],
                            ];
                        }
                    }
                }
            }
            DB::commit();
            return response()->json(MyHelper::checkUpdate($create));
        }else{
            return response()->json([ 'status'   => 'fail', 'messages' => ['Incompleted Data ID']]);
        }
    }

    function destroy(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_product_modifier_group']) && !empty($post['id_product_modifier_group'])){
            DB::beginTransaction();
            $delete = ProductModifier::where('id_product_modifier_group', $post['id_product_modifier_group'])->delete();
            if(!$delete){
                DB::rollback();
                return [
                    'status'   => 'fail',
                    'messages' => ['Failed delete product modifier'],
                ];
            }

            $delete = ProductModifierGroup::where('id_product_modifier_group', $post['id_product_modifier_group'])->delete();
            if(!$delete){
                DB::rollback();
                return [
                    'status'   => 'fail',
                    'messages' => ['Failed delete product modifier group'],
                ];
            }

            DB::commit();
            return response()->json(MyHelper::checkDelete($delete));
        }else{
            return response()->json([ 'status'   => 'fail', 'messages' => ['Incompleted Data ID']]);
        }
    }

    public function listPrice(Request $request)
    {
        $post      = $request->json()->all();
        $id_outlet = $request->json('id_outlet');
        if ($id_outlet) {
            $data = ProductModifier::join('product_modifier_groups', 'product_modifier_groups.id_product_modifier_group', 'product_modifiers.id_product_modifier_group')
                ->select('product_modifier_groups.product_modifier_group_name', 'product_modifiers.id_product_modifier', 'product_modifiers.code', 'product_modifiers.text', 'product_modifier_prices.product_modifier_price')
                ->where('type', 'Modifier Group')
                ->leftJoin('product_modifier_prices', function ($join) use ($id_outlet) {
                    $join->on('product_modifiers.id_product_modifier', '=', 'product_modifier_prices.id_product_modifier');
                    $join->where('product_modifier_prices.id_outlet', '=', $id_outlet);
                })->where(function ($query) use ($id_outlet) {
                    $query->where('product_modifier_prices.id_outlet', $id_outlet);
                    $query->orWhereNull('product_modifier_prices.id_outlet');
                })->groupBy('product_modifiers.id_product_modifier');
        } else {
            $data = ProductModifier::join('product_modifier_groups', 'product_modifier_groups.id_product_modifier_group', 'product_modifiers.id_product_modifier_group')
                ->where('type', 'Modifier Group')
                ->select('product_modifier_groups.product_modifier_group_name',  'product_modifiers.id_product_modifier', 'product_modifiers.code', 'product_modifiers.text', 'product_modifier_global_prices.product_modifier_price')->leftJoin('product_modifier_global_prices', function ($join) use ($id_outlet) {
                    $join->on('product_modifiers.id_product_modifier', '=', 'product_modifier_global_prices.id_product_modifier');
                })->groupBy('product_modifiers.id_product_modifier');
        }
        if ($post['rule'] ?? false) {
            $filter = $this->filterList($data, $post['rule'], $post['operator'] ?? 'and');
        } else {
            $filter = [];
        }

        if ($request->page) {
            $data = $data->paginate(10);
        } else {
            $data = $data->get();
        }
        return MyHelper::checkGet($data) + $filter;
    }

    public function listDetail(Request $request)
    {
        $post      = $request->json()->all();
        $id_outlet = $request->json('id_outlet');
        $data      = ProductModifier::join('product_modifier_groups', 'product_modifier_groups.id_product_modifier_group', 'product_modifiers.id_product_modifier_group')
            ->leftJoin('product_modifier_details', function ($join) use ($id_outlet) {
                $join->on('product_modifiers.id_product_modifier', '=', 'product_modifier_details.id_product_modifier');
                $join->where('product_modifier_details.id_outlet', '=', $id_outlet);
            })->where(function ($query) use ($id_outlet) {
                $query->where('product_modifier_details.id_outlet', $id_outlet);
                $query->orWhereNull('product_modifier_details.id_outlet');
            })->groupBy('product_modifiers.id_product_modifier')
            ->where('type', 'Modifier Group')
            ->select('product_modifier_groups.product_modifier_group_name', 'product_modifiers.id_product_modifier', 'product_modifiers.code', 'product_modifiers.text', 'product_modifier_details.product_modifier_visibility', 'product_modifier_details.product_modifier_status', 'product_modifier_details.product_modifier_stock_status');

        if ($post['rule'] ?? false) {
            $filter = $this->filterList($data, $post['rule'], $post['operator'] ?? 'and');
        } else {
            $filter = [];
        }

        if ($request->page) {
            $data = $data->paginate(10);
        } else {
            $data = $data->get();
        }
        return MyHelper::checkGet($data) + $filter;
    }

    public function filterList($query, $rules, $operator = 'and')
    {
        $newRule = [];
        $total   = $query->count();
        foreach ($rules as $var) {
            $rule = [$var['operator'] ?? '=', $var['parameter'] ?? ''];
            if ($rule[0] == 'like') {
                $rule[1] = '%' . $rule[1] . '%';
            }
            $newRule[$var['subject']][] = $rule;
        }
        $where    = $operator == 'and' ? 'where' : 'orWhere';
        $subjects = ['text', 'visibility', 'product_modifier_visibility'];
        foreach ($subjects as $subject) {
            if ($rules2 = $newRule[$subject] ?? false) {
                foreach ($rules2 as $rule) {
                    $query->$where($subject, $rule[0], $rule[1]);
                }
            }
        }
        $filtered = $query->count();
        return ['total' => $total, 'filtered' => $filtered];
    }
}
