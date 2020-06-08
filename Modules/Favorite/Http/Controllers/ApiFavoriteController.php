<?php

namespace Modules\Favorite\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Favorite\Entities\Favorite;
use Modules\Favorite\Entities\FavoriteModifier;

use Modules\Favorite\Http\Requests\CreateRequest;

use App\Http\Models\Setting;
use App\Http\Models\Outlet;
use App\Http\Models\ProductModifierPrice;
use App\Http\Models\ProductModifierGlobalPrice;


use App\Lib\MyHelper;

class ApiFavoriteController extends Controller
{
    /**
     * Display a listing of favorite for admin panel
     * @return Response
     */
    public function index(Request $request){
        $data = Favorite::with('modifiers','product','outlet');
        // need pagination?
        $id_favorite = $request->json('id_favorite');
        if($id_favorite){
            $data->where('id_favorite',$id_favorite);
        }
        if($request->page&&!$id_favorite){
            $data=$data->paginate(10);
            if(!$data->total()){
                $data=[];
            }
        }elseif($id_favorite){
            $data = $data->first();
        }else{
            $data = $data->get();
        }
        return MyHelper::checkGet($data,'empty');
    }

    /**
     * Display a listing of favorite for mobile apps
     * @return Response
     */
    public function list(Request $request){
        $user = $request->user();
        $id_favorite = $request->json('id_favorite');
        $latitude = $request->json('latitude');
        $longitude = $request->json('longitude');
        $nf = $request->json('number_format')?:'float';
        $favorite = Favorite::where('id_user',$user->id)->join('outlets', 'outlets.id_outlet', 'favorites.id_outlet');
        $select = ['id_favorite','favorites.id_outlet','outlet_different_price','favorites.id_product','id_brand','id_user','notes'];
        $with = [
            'modifiers'=>function($query){
                $query->select('product_modifiers.id_product_modifier','type','code','text','favorite_modifiers.qty');
            }
        ];
        // detail or list
        if(!$id_favorite){

            if ($request->json('id_brand')) {
                $favorite->where('favorites.id_brand',$request->json('id_brand'));
            }

            if ($request->json('id_outlet')) {
                $favorite->where('favorites.id_outlet',$request->json('id_outlet'));
            }

            if ($request->json('topping') == 'used') {
                $favorite->whereRaw('favorites.id_favorite in (select fm.id_favorite from favorite_modifiers fm where fm.id_favorite = favorites.id_favorite)');
            }elseif($request->json('topping') == 'unused'){
                $favorite->whereRaw('favorites.id_favorite not in (select fm.id_favorite from favorite_modifiers fm where fm.id_favorite = favorites.id_favorite)');
            }

            if ($request->json('key_free')) {
                $favorite->whereIn('favorites.id_favorite', function($query) use($request){
                    $query->select('f.id_favorite')
                        ->from('favorites as f')
                        ->join('products', 'products.id_product', 'f.id_product')
                        ->join('outlets', 'outlets.id_outlet', 'f.id_outlet')
                        ->join('brands', 'brands.id_brand', 'f.id_brand')
                        ->where('products.product_name', 'LIKE', '%' . $request->json('key_free') . '%')
                        ->orWhere('outlets.outlet_name', 'LIKE', '%' . $request->json('key_free') . '%')
                        ->orWhere('brands.name_brand', 'LIKE', '%' . $request->json('key_free') . '%');
                });
            }

            if($request->page){
                $data = $favorite->select($select)->with($with)->paginate(10)->toArray();
                $datax = &$data['data'];
            }else{
                $data = $favorite->select($select)->with($with)->get()->toArray();
                $datax = &$data;
            }
            if(count($datax)>=1){
                $datax = MyHelper::groupIt($datax,'id_outlet',function($key,&$val) use ($nf,$data, $request){
                    $total_price = $val['product']['price'];
                    $val['product']['price']=MyHelper::requestNumber($val['product']['price'],$nf);
                    foreach ($val['modifiers'] as &$modifier) {
                        if($val['outlet_different_price']){
                            $price = ProductModifierPrice::select('product_modifier_price')->where([
                                'id_product_modifier' => $modifier['id_product_modifier'],
                                'id_outlet' => $val['id_outlet']
                            ])->pluck('product_modifier_price')->first();                            
                        }else{
                            $price = ProductModifierGlobalPrice::select('product_modifier_price')->where('id_product_modifier', $modifier['id_product_modifier'])->pluck('product_modifier_price')->first();
                        }
                        $modifier['product_modifier_price'] = MyHelper::requestNumber($price,$nf);
                        $total_price+=$price*$modifier['qty'];
                    }
                    $val['product_price_total'] = $total_price;

                    if ($request->json('max_price') && $request->json('min_price')) {
                        if ((int)$request->json('max_price') >= (int)$total_price &&
                            (int)$request->json('min_price') <= (int)$total_price) {
                            return $key;
                        }else{
                            return 'remove';
                        }
                    }else{
                        if (!empty($request->json('max_price')) && (int)$request->json('max_price') < (int)$total_price){
                            return 'remove';
                        }elseif (!empty($request->json('min_price')) && (int)$request->json('min_price') > (int)$total_price){
                            return 'remove';
                        }else{
                            return $key;
                        }
                    }
                },function($key,&$val) use ($latitude,$longitude){
                    if($key == "remove"){
                        return $key;
                    }

                    $outlet = Outlet::select('id_outlet','outlet_code','outlet_name','outlet_address','outlet_latitude','outlet_longitude')->with('today')->find($key)->toArray();
                    $status = app('Modules\Outlet\Http\Controllers\ApiOutletController')->checkOutletStatus($outlet);
                    $outlet['outlet_address']=$outlet['outlet_address']??'';
                    $outlet['status']=$status;
                    if(!empty($latitude)&&!empty($longitude)){
                        $outlet['distance_raw'] = MyHelper::count_distance($latitude,$longitude,$outlet['outlet_latitude'],$outlet['outlet_longitude']);
                        $outlet['distance'] = MyHelper::count_distance($latitude,$longitude,$outlet['outlet_latitude'],$outlet['outlet_longitude'],'K',true);
                    }else{
                        $outlet['distance_raw'] = null;
                        $outlet['distance'] = '';
                    }
                    $val=[
                        'outlet'=>$outlet,
                        'favorites'=>$val
                    ];
                    return $key;
                });
                unset($datax['remove']);
                $datax = array_values($datax);

                if(!empty($latitude)&&!empty($longitude)){
                    usort($datax, function(&$a,&$b){
                        return $a['outlet']['distance_raw'] <=> $b['outlet']['distance_raw'];
                    });
                }
            }else{
                $data = [];
            }
        }else{
            $data = $favorite->select($select)->with($with)->where('id_favorite',$id_favorite)->first();
            if(!$data){
                return MyHelper::checkGet($data);
            }
            $data = $data->toArray();
            $total_price = $data['product']['price'];
            $data['product']['price']=MyHelper::requestNumber($data['product']['price'],$nf);
            foreach ($data['modifiers'] as &$modifier) {
                $price = ProductModifierPrice::select('product_modifier_price')->where([
                    'id_product_modifier' => $modifier['id_product_modifier'],
                    'id_outlet' => $data['id_outlet']
                ])->pluck('product_modifier_price')->first();
                $modifier['product_modifier_price'] = MyHelper::requestNumber($price,$nf);
                $total_price += $price*$modifier['qty'];
            }
            $data['product_price_total'] = $total_price;
        }
        return MyHelper::checkGet($data,'empty');
    }

    /**
     * Add user favorite 
     * @param Request $request
     * {
     *     'id_outlet'=>'',
     *     'id_product'=>'',
     *     'id_user'=>'',
     *     'notes'=>'',
     *     'product_qty'=>''
     *     'modifiers'=>[id,id,id]
     * }
     * @return Response
     */
    public function store(CreateRequest $request){
        $id_user = $request->user()->id;
        $modifiers = $request->json('modifiers');
        // check is already exist
        $data = Favorite::where([
            ['id_outlet',$request->json('id_outlet')],
            ['id_product',$request->json('id_product')],
            ['id_brand',$request->json('id_brand')],
            ['id_user',$id_user],
            ['notes',$request->json('notes')??'']
        ])->where(function($query) use ($modifiers){
            foreach ($modifiers as $modifier) {
                if(is_array($modifier)){
                    $id_product_modifier = $modifier['id_product_modifier'];
                    $qty = $modifier['qty']??1;
                }else{
                    $id_product_modifier = $modifier;
                    $qty = 1;
                }
                $query->whereHas('favorite_modifiers',function($query) use ($id_product_modifier,$qty){
                    $query->where('favorite_modifiers.id_product_modifier',$id_product_modifier);
                    $query->where('favorite_modifiers.qty',$qty);
                });
            }
        })->having('modifiers_count','=',count($modifiers))->withCount('modifiers')->first();
        $extra['message'] = Setting::select('value_text')->where('key','favorite_already_exists_message')->pluck('value_text')->first()?:'Favorite already exists';
        $new = 0;
        if(!$data){
            $extra['message'] = Setting::select('value_text')->where('key','favorite_add_success_message')->pluck('value_text')->first()?:'Success add favorite';
            $new = 1;
            \DB::beginTransaction();
            // create favorite
            $insert_data = [
                'id_outlet' => $request->json('id_outlet'),
                'id_brand' => $request->json('id_brand'),
                'id_product' => $request->json('id_product'),
                'id_user' => $id_user,
                'notes' => $request->json('notes')?:''];

            $data = Favorite::create($insert_data);
            if($data){
                //insert modifier
                foreach ($modifiers as $modifier) {
                    if(is_array($modifier)){
                        $id_product_modifier = $modifier['id_product_modifier'];
                        $qty = $modifier['qty']??1;
                    }else{
                        $id_product_modifier = $modifier;
                        $qty = 1;
                    }
                    $insert = FavoriteModifier::create([
                        'id_favorite'=>$data->id_favorite,
                        'id_product_modifier'=>$id_product_modifier,
                        'qty' => $qty
                    ]);
                    if(!$insert){
                        \DB::rolBack();
                        return [
                            'status'=>'fail',
                            'messages'=>['Failed insert product modifier']
                        ];
                    }
                }
            }else{
                \DB::rollBack();
                return [
                    'status'=>'fail',
                    'messages'=>['Failed insert product modifier']
                ];
            }
            \DB::commit();
        }
        $data->load('modifiers');
        $data = $data->toArray();
        $data['create_new'] = $new;
        return MyHelper::checkCreate($data)+$extra;
    }

    /**
     * Remove favorite
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request){
        $user = $request->user();
        $delete = Favorite::where([
            ['id_favorite',$request->json('id_favorite')],
            ['id_user',$user->id]
        ])->delete();
        return MyHelper::checkDelete($delete);
    }
}
