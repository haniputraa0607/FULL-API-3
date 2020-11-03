<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\ProductModifier;
use App\Http\Models\ProductModifierGlobalPrice;
use App\Http\Models\ProductModifierPrice;
use App\Jobs\RefreshVariantTree;
use App\Lib\MyHelper;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Brand\Entities\Brand;
use Modules\Product\Entities\ProductModifierGroup;
use Modules\Product\Entities\ProductModifierGroupPivot;
use Modules\ProductVariant\Entities\ProductVariant;
use App\Http\Models\Product;

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
                        'code' => MyHelper::createrandom(5).strtotime(date('Y-m-d H:i:s')),
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
            RefreshVariantTree::dispatch(['type' => 'refresh_product'])->allOnConnection('database');
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
                        $dtUpdate = [
                            'text' => $modifier['name'],
                            'product_modifier_visibility' => (isset($modifier['visibility']) ? 'Visible': 'Hidden'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                        if(!empty($modifier['new_code'])){
                            $dtUpdate['code'] = $modifier['new_code'];
                        }
                        $update = ProductModifier::where('code', $modifier['code'])->update($dtUpdate);
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
                            'code' => MyHelper::createrandom(5).strtotime(date('Y-m-d H:i:s')),
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
            RefreshVariantTree::dispatch(['type' => 'refresh_product'])->allOnConnection('database');
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
            RefreshVariantTree::dispatch(['type' => 'refresh_product'])->allOnConnection('database');
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

    public function export(Request $request){
        $arr = [];
        $data = ProductModifierGroup::with(['product_modifier_group_pivots', 'product_modifier'])->get()->toArray();

        foreach ($data as $dt){
            $prod = [];
            $var = [];
            foreach($dt['product_modifier_group_pivots'] as $pmgp) {
                if(!empty($pmgp['id_product'])){
                    $prod[] = $pmgp['product_code'];
                }
                if(!empty($pmgp['product_variant_name'])){
                    $var[] = $pmgp['product_variant_name'];
                }
            }

            $mod = [];
            if(!empty($dt['product_modifier'])){
                foreach ($dt['product_modifier'] as $m){
                    $mod[] = $m['text'].'('.$m['code'].')';
                }
            }

            $arr[] = [
                'product_modifier_group_name' => $dt['product_modifier_group_name'],
                'product' => implode(',',$prod),
                'variant' => implode(',',$var),
                'modifier' => implode(',',$mod)
            ];
        }

        return response()->json(MyHelper::checkGet($arr));
    }

    public function import(Request $request){
        $post = $request->json()->all();
        $result = [
            'updated' => 0,
            'create' => 0,
            'no_update' => 0,
            'invalid' => 0,
            'failed' => 0,
            'more_msg' => [],
            'more_msg_extended' => []
        ];
        $data = $post['data'][0]??[];

        foreach ($data as $key => $value) {
            if(empty($value['product_modifier_group_name'])){
                $result['invalid']++;
                continue;
            }

            if(empty($value['modifier'])){
                $result['invalid']++;
                continue;
            }

            DB::beginTransaction();
            $modifierGroup = ProductModifierGroup::where(['product_modifier_group_name' => $value['product_modifier_group_name']])->first();
            if($modifierGroup){
                $update = ProductModifierGroup::where(['product_modifier_group_name' => $value['product_modifier_group_name']])->update(['product_modifier_group_name' => $value['product_modifier_group_name']]);
                if(!$update){
                    $result['no_update']++;
                    DB::rollback();
                }else{
                    $result['updated']++;
                }
                $id_product_modifier_group = $modifierGroup['id_product_modifier_group'];
            }else{
                $create = ProductModifierGroup::create(['product_modifier_group_name' => $value['product_modifier_group_name']]);
                if(!$create){
                    DB::rollback();
                }else{
                    $result['create']++;
                }
                $id_product_modifier_group = $create['id_product_modifier_group'];
            }

            if($id_product_modifier_group){
                $dataInsertModifierGroupPivot = [];
                if(!empty($value['product'])){
                    $code = explode(",", $value['product']);
                    $getProduct = Product::whereIn('product_code', $code)->pluck('id_product')->toArray();
                    foreach ($getProduct as $p){
                        $dataInsertModifierGroupPivot[] = [
                            'id_product_modifier_group' => $id_product_modifier_group,
                            'id_product' => $p
                        ];
                    }
                }elseif(!empty($value['variant'])){
                    $variant = explode(",", $value['variant']);
                    $getVariant = ProductVariant::whereIn('product_variant_name', $variant)->pluck('id_product_variant')->toArray();
                    foreach ($getVariant as $v){
                        $dataInsertModifierGroupPivot[] = [
                            'id_product_modifier_group' => $id_product_modifier_group,
                            'id_product_variant' => $v
                        ];
                    }
                }

                $del = ProductModifierGroupPivot::where('id_product_modifier_group', $id_product_modifier_group)->delete();
                if($dataInsertModifierGroupPivot){
                    $create = ProductModifierGroupPivot::insert($dataInsertModifierGroupPivot);
                    if(!$create){
                        DB::rollback();
                        if($modifierGroup){
                            $result['updated']--;
                        }else{
                            $result['create']--;
                        }
                        $result['failed']++;
                        $result['more_msg_extended'][] = "Failed insert group pivot";
                        continue;
                    }
                }else{
                    DB::rollback();
                    if($modifierGroup){
                        $result['updated']--;
                    }else{
                        $result['create']--;
                    }
                    $result['failed']++;
                    $result['more_msg_extended'][] = "Failed insert group pivot";
                    continue;
                }

                if(!empty($value['modifier'])){
                    $insertModifier = [];
                    $modifiers = explode(",", $value['modifier']);
                    foreach ($modifiers as $modifier){
                        $check = strpos($modifier,"(");
                        if($check !== false){
                            $codeMod = substr($modifier,$check+1,strlen($modifier));
                            $codeMod = str_replace(')',"", $codeMod);
                            $name = substr($modifier,0,$check);
                            $getModifier = ProductModifier::where('code', $codeMod)->first();
                            $dataMod = [
                                'id_product_modifier_group' => $id_product_modifier_group,
                                'modifier_type' => 'Modifier Group',
                                'type' => 'Modifier Group',
                                'text' => $name,
                                'product_modifier_visibility' => 'Visible',
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ];
                            if($getModifier){
                                $update = ProductModifier::where('code', $codeMod)->update($dataMod);
                                if(!$create){
                                    DB::rollback();
                                    $result['failed']++;
                                    $result['more_msg_extended'][] = "Failed update modifier with code ".$code;
                                }
                            }else{
                                $dataMod['code'] = (empty($codeMod) ? MyHelper::createrandom(5).strtotime(date('Y-m-d H:i:s')) : $codeMod);
                                $create = ProductModifier::create($dataMod);
                                if(!$create){
                                    DB::rollback();
                                    $result['failed']++;
                                    $result['more_msg_extended'][] = "Failed create modifier ".$dataMod['text'];
                                }
                            }
                        }else{
                            $create = ProductModifier::create([
                                'id_product_modifier_group' => $id_product_modifier_group,
                                'modifier_type' => 'Modifier Group',
                                'type' => 'Modifier Group',
                                'code' => MyHelper::createrandom(5).strtotime(date('Y-m-d H:i:s')),
                                'text' => $modifier,
                                'product_modifier_visibility' => 'Visible',
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);

                            if(!$create){
                                DB::rollback();
                                $result['failed']++;
                                $result['more_msg_extended'][] = "Failed create modifier ".$modifier;
                            }
                        }
                    }
                }
            }
            DB::commit();
        }
        RefreshVariantTree::dispatch(['type' => 'refresh_product'])->allOnConnection('database');
        $response = [];

        if($result['updated']){
            $response[] = 'Update '.$result['updated'].' product modifier group';
        }
        if($result['create']){
            $response[] = 'Create '.$result['create'].' new product modifier group';
        }
        if($result['no_update']){
            $response[] = $result['no_update'].' product modifier group not updated';
        }
        if($result['failed']){
            $response[] = 'Failed create '.$result['failed'].' product modifier group';
        }
        $response = array_merge($response,$result['more_msg_extended']);
        return MyHelper::checkGet($response);
    }

    public function exportPrice(){
        $subquery = str_replace('?','0',ProductModifierGlobalPrice::select(\DB::raw('id_product_modifier,product_modifier_price as global_price'))
            ->groupBy('id_product_modifier')
            ->toSql());
        $different_outlet = Outlet::select('outlet_code','id_product_modifier','product_modifier_price')
            ->leftJoin('product_modifier_prices','outlets.id_outlet','=','product_modifier_prices.id_outlet')
            ->where('outlet_different_price',1)->get();
        $do = MyHelper::groupIt($different_outlet,'outlet_code',null,function($key,&$val){
            $val = MyHelper::groupIt($val,'id_product_modifier');
            return $key;
        });
        $data = ProductModifier::select('product_modifiers.id_product_modifier','code as modifier_group_code','text as name','global_prices.global_price')
            ->leftJoin('product_modifier_brands','product_modifier_brands.id_product_modifier','=','product_modifiers.id_product_modifier')
            ->leftJoin(DB::raw('('.$subquery.') as global_prices'),'product_modifiers.id_product_modifier','=','global_prices.id_product_modifier')
            ->whereIn('type', ['Modifier Group'])
            ->orderBy('type')
            ->orderBy('text')
            ->orderBy('product_modifiers.id_product_modifier')
            ->distinct()
            ->get();
        foreach ($data as $key => &$product) {
            $inc = 0;
            foreach ($do as $outlet_code => $x) {
                $inc++;
                $product['price_'.$outlet_code] = $x[$product['id_product_modifier']][0]['product_modifier_price']??'';
            }
            unset($product['id_product_modifier']);
        }

        return MyHelper::checkGet($data);
    }

    public function importPrice(Request $request){
        $post = $request->json()->all();
        $result = [
            'updated' => 0,
            'create' => 0,
            'no_update' => 0,
            'invalid' => 0,
            'not_found' => 0,
            'failed' => 0,
            'more_msg' => [],
            'more_msg_extended' => []
        ];
        $data = $post['data'][0]??[];

        $global_outlets = Outlet::select('id_outlet','outlet_code')->where([
            'outlet_different_price' => 0
        ])->get();
        foreach ($data as $key => $value) {
            if(empty($value['modifier_group_code'])){
                $result['invalid']++;
                continue;
            }

            if(empty($value['name'])){
                unset($value['name']);
            }else{
                $value['text'] = $value['name'];
                unset($value['name']);
            }
            if(empty($value['type'])){
                unset($value['type']);
            }
            if(empty($value['global_price'])){
                unset($value['global_price']);
            }
            $product = ProductModifier::select('product_modifiers.*')->where('code',$value['modifier_group_code'])->first();
            if(!$product){
                $result['not_found']++;
                $result['more_msg_extended'][] = "Modifier group with code {$value['modifier_group_code']} not found";
                continue;
            }
            $update1 = $product->update($value);
            if($update1){
                $result['updated']++;
            }else{
                $result['no_update']++;
            }
            if($value['global_price']??false){
                $update = ProductModifierGlobalPrice::updateOrCreate([
                    'id_product_modifier' => $product->id_product_modifier],[
                    'product_modifier_price'=>$value['global_price']
                ]);
                if($update){
                    $result['updated']++;
                }else{
                    if($update !== 0){
                        $result['fail']++;
                        $result['more_msg_extended'][] = "Failed set global price for product modifier {$value['code']}";
                    }
                }
            }
            foreach ($value as $col_name => $col_value) {
                if(!$col_value){
                    continue;
                }
                if(strpos($col_name, 'price_') !== false){
                    $outlet_code = str_replace('price_', '', $col_name);
                    $pp = ProductModifierPrice::join('outlets','outlets.id_outlet','=','product_modifier_prices.id_outlet')
                        ->where([
                            'outlet_code' => $outlet_code,
                            'id_product_modifier' => $product->id_product_modifier
                        ])->first();
                    if($pp){
                        $update = $pp->update(['product_modifier_price'=>$col_value]);
                    }else{
                        $id_outlet = Outlet::select('id_outlet')->where('outlet_code',$outlet_code)->pluck('id_outlet')->first();
                        if(!$id_outlet){
                            $result['fail']++;
                            $result['more_msg_extended'][] = "Failed create new price for product modifier {$value['code']} at outlet $outlet_code failed";
                            continue;
                        }
                        $update = ProductModifierPrice::create([
                            'id_outlet' => $id_outlet,
                            'id_product_modifier' => $product->id_product_modifier,
                            'product_modifier_price'=>$col_value
                        ]);
                    }
                    if($update){
                        $result['updated']++;
                    }else{
                        $result['fail']++;
                        $result['more_msg_extended'][] = "Failed set price for product modifier {$value['code']} at outlet $outlet_code failed";
                    }
                }
            }
        }
        RefreshVariantTree::dispatch(['type' => 'refresh_product'])->allOnConnection('database');
        $response = [];

        if($result['updated']){
            $response[] = 'Update '.$result['updated'].' product modifier group';
        }
        if($result['create']){
            $response[] = 'Create '.$result['create'].' new product modifier group';
        }
        if($result['no_update']){
            $response[] = $result['no_update'].' product modifier group not updated';
        }
        if($result['failed']){
            $response[] = 'Failed create '.$result['failed'].' product modifier group';
        }
        $response = array_merge($response,$result['more_msg_extended']);
        return MyHelper::checkGet($response);
    }
}
