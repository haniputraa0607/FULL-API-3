<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\UserHairStylistDocuments;
use Modules\Recruitment\Http\Requests\user_hair_stylist_create;
use Modules\Recruitment\Http\Requests\CreateGroup;
use Modules\Recruitment\Http\Requests\CreateGroupCommission;
use Modules\Recruitment\Http\Requests\UpdateGroupCommission;
use Modules\Recruitment\Http\Requests\CreateInsentif;
use Modules\Recruitment\Http\Requests\CreateInsentifDefault;
use Modules\Recruitment\Http\Requests\UpdateDefaultInsentif;
use Modules\Recruitment\Http\Requests\InviteHS;
use Image;
use Modules\Recruitment\Entities\HairstylistGroup;
use Modules\Recruitment\Entities\HairstylistGroupCommission;
use Modules\Recruitment\Entities\HairstylistGroupInsentif;
use Modules\Recruitment\Entities\HairstylistGroupInsentifDefault;
use App\Http\Models\Product;
use Modules\Recruitment\Http\Requests\UpdateInsentif;

class ApiHairStylistGroupInsentifController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
//        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }
    public function create(CreateInsentif $request)
    {
        $data = HairstylistGroupInsentif::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_insentifs"   =>  $request->id_hairstylist_group_default_insentifs,
                ])->first();
        if($data){
            $store = HairstylistGroupInsentif::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_insentifs"   =>  $request->id_hairstylist_group_default_insentifs,
                ])->update([
                    "value"   =>  $request->value,
                    "formula"   =>  $request->formula,
                    "code"   =>  $request->code,
                ]);
        }else{
        $store = HairstylistGroupInsentif::create([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_insentifs"   =>  $request->id_hairstylist_group_default_insentifs,
                    "value"   =>  $request->value,
                    "formula"   =>  $request->formula,
                    "code"   =>  $request->code,
                ]);
        }
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update(UpdateInsentif $request)
    {
        $store = HairstylistGroupInsentif::where(array('id_hairstylist_group_insentif'=>$request->id_hairstylist_group_insentif))->update([
                    "value"   =>  $request->value,
                    "formula"   =>  $request->formula,
                    "code"   =>  $request->code,
                ]);
        if($store){
            $store = HairstylistGroupInsentif::where(array('id_hairstylist_group_insentif'=>$request->id_hairstylist_group_insentif))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail(Request $request)
    {
        if($request->id_hairstylist_group_insentif){
        $store = HairstylistGroupInsentif::where(array('id_hairstylist_group_insentif'=>$request->id_hairstylist_group_insentif))
                    ->join('hairstylist_group_default_insentifs','hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs','hairstylist_group_insentifs.id_hairstylist_group_default_insentifs')
                    ->select('hairstylist_group_default_insentifs.name','hairstylist_group_insentifs.*')
                    ->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete(Request $request)
    {
        if($request->id_hairstylist_group_default_insentifs && $request->id_hairstylist_group ){
        $store = HairstylistGroupInsentif::where(array('id_hairstylist_group_default_insentifs'=>$request->id_hairstylist_group_default_insentifs,'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
        if($store){
        $store = HairstylistGroupInsentif::where(array('id_hairstylist_group_default_insentifs'=>$request->id_hairstylist_group_default_insentifs,'id_hairstylist_group'=>$request->id_hairstylist_group))->delete();
        }else{
            $store = 1;
        }
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function index(Request $request) {
        if($request->id_hairstylist_group){
            $data = array();
            $insentif = HairstylistGroupInsentifDefault::get();
            foreach ($insentif as $value) {
                $insen = HairstylistGroupInsentif::where(array('id_hairstylist_group_default_insentifs'=>$value['id_hairstylist_group_default_insentifs'],'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
                $value['default_formula'] = $value['formula'];
                $value['default_value'] = $value['value'];
                $value['default']    = 0;
                if($insen){
                   $value['value']      = $insen->value; 
                   $value['formula']    = $insen->formula;
                   $value['code']       = $insen->code;
                   $value['default']    = 1;
                }
                array_push($data,$value);
            }
           return response()->json(MyHelper::checkGet($data));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
   
    public function list_rumus_insentif(Request $request) {
        if($request->id_hairstylist_group){
             $list = array();
             $data = HairstylistGroupInsentifDefault::all();
             foreach ($data as $value) {
                 $cek = HairstylistGroupInsentif::where(array('id_hairstylist_group'=>$request->id_hairstylist_group,'id_hairstylist_group_default_insentifs'=>$value['id_hairstylist_group_default_insentifs']))->first();
                 if($cek){
                     $value['value']   = $cek->value;
                     $value['formula'] = $cek->formula;
                     $value['code']    = $cek->code;
                 }
                 array_push($list,$value);
             }
             return MyHelper::checkGet($list);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function create_default(CreateInsentifDefault $request)
    {
        $store = HairstylistGroupInsentifDefault::create([
                    "name"   =>  $request->name,
                    "code"   => $request->code,
                    "value"   =>  $request->value,
                    "formula"   =>  $request->formula,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update_default(UpdateDefaultInsentif $request)
    {
        $store = HairstylistGroupInsentifDefault::where(array('id_hairstylist_group_default_insentifs'=>$request->id_hairstylist_group_default_insentifs))->update([
                    "name"   =>  $request->name,
                    "code"   => $request->code,
                    "value"   =>  $request->value,
                    "formula"   =>  $request->formula,
                ]);
        if($store){
            $store = HairstylistGroupInsentifDefault::where(array('id_hairstylist_group_default_insentifs'=>$request->id_hairstylist_group_default_insentifs))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail_default(Request $request)
    {
        if($request->id_hairstylist_group_default_insentifs){
        $store = HairstylistGroupInsentifDefault::where(array('id_hairstylist_group_default_insentifs'=>$request->id_hairstylist_group_default_insentifs))->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete_default(Request $request)
    {
        if($request->id_hairstylist_group_default_insentifs){
        $store = HairstylistGroupInsentifDefault::where(array('id_hairstylist_group_default_insentifs'=>$request->id_hairstylist_group_default_insentifs))->delete();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    function index_default(Request $request) 
    {
    	$post = $request->json()->all();
        $data = HairstylistGroupInsentifDefault::Select('hairstylist_group_default_insentifs.*');
        if ($request->json('rule')){
             $this->filterList($data,$request->json('rule'),$request->json('operator')??'and');
        }
        $data = $data->paginate($request->length ?: 10);
        //jika mobile di pagination
        if (!$request->json('web')) {
            $resultMessage = 'Data tidak ada';
            return response()->json(MyHelper::checkGet($data, $resultMessage));
        }
        else{
           
            return response()->json(MyHelper::checkGet($data));
        }
    }
   
    public function filterList($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }
        $where=$operator=='and'?'where':'orWhere';
        $subjects=['name'];
         $i = 1;
        foreach ($subjects as $subject) {
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    if($i<=1){
                    $query->where($subject,$rule[0],$rule[1]);
                    }else{
                    $query->$where($subject,$rule[0],$rule[1]);    
                    }
                    $i++;
                }
            }
        }
    }
    public function list_insentif_default(Request $request) {
        if($request->id_hairstylist_group){
            $data = array();
            $insentif = HairstylistGroupInsentifDefault::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))->get();
            foreach ($insentif as $value) {
                $insen = HairstylistGroupInsentif::where(array('id_hairstylist_group_default_insentifs'=>$value['id_hairstylist_group_default_insentifs'],'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
                if(!$insen){
                    array_push($data,$value);
                }
            }
            return MyHelper::checkGet($data);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    
}
