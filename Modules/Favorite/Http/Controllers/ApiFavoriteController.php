<?php

namespace Modules\Favorite\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Favorite\Entities\Favorite;
use Modules\Favorite\Entities\FavoriteModifier;

use App\Http\Models\Outlet;
use App\Http\Models\ProductModifierPrice;

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
        $favorite = Favorite::where('id_user',$user->id);
        $select = ['id_favorite','id_outlet','id_product','id_brand','id_user','product_qty','notes'];
        $with = [
            'modifiers'=>function($query){
                $query->select('product_modifiers.id_product_modifier','type','code','text','favorite_modifiers.qty');
            }
        ];
        // detail or list
        if($request->page&&!$id_favorite){
            $data = Favorite::where('id_user',$user->id)->select($select)->with($with)->paginate(10)->toArray();
            if(count($data['data'])>=1){
                $data['data'] = MyHelper::groupIt($data['data'],'id_outlet',function($key,&$val) use ($nf,$data){
                    $val['product']['price']=MyHelper::requestNumber($val['product']['price'],$nf);
                    foreach ($val['modifiers'] as &$modifier) {
                        $price = ProductModifierPrice::select('product_modifier_price')->where([
                            'id_product_modifier' => $modifier['id_product_modifier'],
                            'id_outlet' => $val['id_outlet']
                        ])->pluck('product_modifier_price')->first();
                        $modifier['price'] = MyHelper::requestNumber($price,$nf);
                    }
                    return $key;
                },function($key,&$val) use ($latitude,$longitude){
                    $outlet = Outlet::select('id_outlet','outlet_name','outlet_address','outlet_latitude','outlet_longitude')->with('today')->find($key)->toArray();
                    $status = app('Modules\Outlet\Http\Controllers\ApiOutletController')->checkOutletStatus($outlet);
                    $outlet['outlet_address']=$outlet['outlet_address']??'';
                    $outlet['status']=$status;
                    $outlet['distance_raw'] = MyHelper::count_distance($latitude,$longitude,$outlet['outlet_latitude'],$outlet['outlet_longitude']);
                    $outlet['distance'] = MyHelper::count_distance($latitude,$longitude,$outlet['outlet_latitude'],$outlet['outlet_longitude'],'K',true);
                    $val=[
                        'outlet'=>$outlet,
                        'favorites'=>$val
                    ];
                    return $key;
                });
                $data['data'] = array_values($data['data']);
                usort($data['data'], function(&$a,&$b){
                    return $a['outlet']['distance_raw'] <=> $b['outlet']['distance_raw'];
                });
            }else{
                $data = [];
            }
        }elseif($id_favorite){
            $data = $favorite->select($select)->with($with)->where('id_favorite',$id_favorite)->first()->toArray();
            $data['product']['price']=number_format($data['product']['price'],0,",",".");
            foreach ($data['modifiers'] as &$modifier) {
                $modifier['price'] = '+ '.number_format($modifier['price'],0,",",".");
            }
        }else{
            //get list favorite product outlet
            $outlets = $favorite->select('id_outlet')->with(['outlet'=>function($query){
                $query->select('id_outlet','outlet_name','outlet_address','outlet_latitude','outlet_longitude');
            }])->groupBy('id_outlet')->get()->pluck('outlet');
            $data=[];
            foreach ($outlets as $outlet) {
                $outlet = $outlet->load('today')->toArray();
                $status = app('Modules\Outlet\Http\Controllers\ApiOutletController')->checkOutletStatus($outlet);
                $outlet['outlet_address']=$outlet['outlet_address']??'';
                $outlet['status']=$status;
                $outlet['distance_raw'] = MyHelper::count_distance($latitude,$longitude,$outlet['outlet_latitude'],$outlet['outlet_longitude']);
                $outlet['distance'] = MyHelper::count_distance($latitude,$longitude,$outlet['outlet_latitude'],$outlet['outlet_longitude'],'K',true);
                $data[]=[
                    'outlet' => $outlet,
                    'favorites' => Favorite::where([
                            ['id_user',$user->id],
                            ['id_outlet',$outlet['id_outlet']]
                        ])->select($select)->with($with)->get()
                ];
            }
            //order by nearest
            usort($data, function($a,$b){
                return $a['outlet']['distance_raw'] <=> $b['outlet']['distance_raw'];
            });
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
    public function store(Request $request){
        $id_user = $request->user()->id;
        $modifiers = $request->json('modifiers');
        // check is already exist
        $data = Favorite::where([
            ['id_outlet',$request->json('id_outlet')],
            ['id_product',$request->json('id_product')],
            ['id_brand',$request->json('id_brand')],
            ['id_user',$id_user],
            ['notes',$request->json('notes')??''],
            ['product_qty',$request->json('product_qty')]
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

        if(!$data){
            \DB::beginTransaction();
            // create favorite
            $insert_data = [
                'id_outlet' => $request->json('id_outlet'),
                'id_brand' => $request->json('id_brand'),
                'id_product' => $request->json('id_product'),
                'product_qty' => $request->json('product_qty'),
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
                    $insert = FavoriteModifier::insert([
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
        return MyHelper::checkCreate($data);
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
