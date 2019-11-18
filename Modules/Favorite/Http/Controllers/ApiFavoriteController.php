<?php

namespace Modules\Favorite\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Favorite\Entities\Favorite;
use Modules\Favorite\Entities\FavoriteModifier;

use App\Lib\MyHelper;

class ApiFavoriteController extends Controller
{
    /**
     * Display a listing of favorite for admin panel
     * @return Response
     */
    public function index(Request $request){
        $data = Favorite::with('modifiers');
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
        $data = Favorite::where('id_user',$user->id)->with('modifiers');
        $id_favorite = $request->json('id_favorite');
        if($id_favorite){
            $data->where('id_favorite',$id_favorite);
        }
        // need pagination?
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
        $modifiers = $request->modifiers;
        // check is already exist
        $data = Favorite::where([
            ['id_outlet',$request->id_outlet],
            ['id_product',$request->id_product],
            ['id_user',$id_user],
            ['notes',$request->notes??''],
            ['product_qty',$request->product_qty]
        ])->where(function($query) use ($modifiers){
            foreach ($modifiers as $id_product_modifier) {
                $query->whereHas('modifiers',function($query) use ($id_product_modifier){
                    $query->where('product_modifiers.id_product_modifier',$id_product_modifier);
                });
            }
        })->having('modifiers_count','=',count($modifiers))->withCount('modifiers')->first();

        if(!$data){
            \DB::beginTransaction();
            // create favorite
            $insert_data = [
                'id_outlet' => $request->id_outlet,
                'id_product' => $request->id_product,
                'product_qty' => $request->product_qty,
                'id_user' => $id_user,
                'notes' => $request->notes?:''];

            $data = Favorite::create($insert_data);
            if($data){
                //insert modifier
                foreach ($modifiers as $id_product_modifier) {
                    $insert = FavoriteModifier::insert([
                        'id_favorite'=>$data->id_favorite,
                        'id_product_modifier'=>$id_product_modifier
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
        $delete = Favorite::where('id_favorite',$request->id_favorite)->delete();
        return MyHelper::checkDelete($delete);
    }
}
