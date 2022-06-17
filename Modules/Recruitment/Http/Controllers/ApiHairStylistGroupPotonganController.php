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
use Modules\Recruitment\Http\Requests\CreatePotongan;
use Modules\Recruitment\Http\Requests\CreatePotonganDefault;
use Modules\Recruitment\Http\Requests\UpdateDefaultPotongan;
use Modules\Recruitment\Http\Requests\InviteHS;
use Image;
use Modules\Recruitment\Entities\HairstylistGroup;
use Modules\Recruitment\Entities\HairstylistGroupCommission;
use Modules\Recruitment\Entities\HairstylistGroupPotongan;
use Modules\Recruitment\Entities\HairstylistGroupPotonganDefault;
use App\Http\Models\Product;
use Modules\Recruitment\Http\Requests\UpdatePotongan;

class ApiHairStylistGroupPotonganController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
//        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }
    public function create(CreatePotongan $request)
    {
        $data = HairstylistGroupPotongan::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_potongans"   =>  $request->id_hairstylist_group_default_potongans,
                ])->first();
        if($data){
            $store = HairstylistGroupPotongan::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_potongans"   =>  $request->id_hairstylist_group_default_potongans,
                ])->update([
                    "value"   =>  $request->value,
                    "formula"   =>  $request->formula,
                    "code"   =>  $request->code,
                ]);
        }else{
        $store = HairstylistGroupPotongan::create([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_potongans"   =>  $request->id_hairstylist_group_default_potongans,
                    "value"   =>  $request->value,
                    "formula"   =>  $request->formula,
                    "code"   =>  $request->code,
                ]);
        }
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update(UpdatePotongan $request)
    {
        $store = HairstylistGroupPotongan::where(array('id_hairstylist_group_potongan'=>$request->id_hairstylist_group_potongan))->update([
                    "value"   =>  $request->value,
                    "formula"   =>  $request->formula,
                    "code"   =>  $request->code,
                ]);
        if($store){
            $store =HairstylistGroupPotongan::where(array('id_hairstylist_group_potongan'=>$request->id_hairstylist_group_potongan))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail(Request $request)
    {
        if($request->id_hairstylist_group_potongan){
        $store = HairstylistGroupPotongan::where(array('id_hairstylist_group_potongan'=>$request->id_hairstylist_group_potongan))
                  ->join('hairstylist_group_default_potongans','hairstylist_group_default_potongans.id_hairstylist_group_default_potongans','hairstylist_group_potongans.id_hairstylist_group_default_potongans')
                  ->select('hairstylist_group_default_potongans.name','hairstylist_group_potongans.*')  
                  ->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete(Request $request)
    {
        if($request->id_hairstylist_group_default_potongans && $request->id_hairstylist_group){
        $store = HairstylistGroupPotongan::where(array('id_hairstylist_group_default_potongans'=>$request->id_hairstylist_group_default_potongans,'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
        if($store){
        $store = HairstylistGroupPotongan::where(array('id_hairstylist_group_default_potongans'=>$request->id_hairstylist_group_default_potongans,'id_hairstylist_group'=>$request->id_hairstylist_group))->delete();
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
            $potongan = HairstylistGroupPotonganDefault::get();
            foreach ($potongan as $value) {
                $value['default']    = 0;
                $value['default_formula'] = $value['formula'];
                $value['default_value'] = $value['value'];
                $insen = HairstylistGroupPotongan::where(array('id_hairstylist_group_default_potongans'=>$value['id_hairstylist_group_default_potongans'],'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
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
    public function list_potongan(Request $request) {
        if($request->id_hairstylist_group){
            $data = array();
            $potongan = HairstylistGroupPotongan::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))->get();
            foreach ($potongan as $value) {
                $insen = HairstylistGroupPotonganRumus::where(array('id_hairstylist_group_potongan'=>$value['id_hairstylist_group_potongan'],'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
                if(!$insen){
                    array_push($data,$value);
                }
            }
            return MyHelper::checkGet($data);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function list_rumus_potongan(Request $request) {
         if($request->id_hairstylist_group){
             $list = array();
             $data = HairstylistGroupPotonganDefault::all();
             foreach ($data as $value) {
                 $cek = HairstylistGroupPotongan::where(array('id_hairstylist_group'=>$request->id_hairstylist_group,'id_hairstylist_group_default_potongans'=>$value['id_hairstylist_group_default_potongans']))->first();
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
     public function create_default(CreatePotonganDefault $request)
    {
        $store = HairstylistGroupPotonganDefault::create([
                    "name"   =>  $request->name,
                    "code"   => $request->code,
                    "value"   =>  $request->value,
                    "formula"   =>  $request->formula,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update_default(UpdateDefaultPotongan $request)
    {
        $store = HairstylistGroupPotonganDefault::where(array('id_hairstylist_group_default_potongans'=>$request->id_hairstylist_group_default_potongans))->update([
                    "name"   =>  $request->name,
                    "code"   => $request->code,
                    "value"   =>  $request->value,
                    "formula"   =>  $request->formula,
                ]);
        if($store){
            $store = HairstylistGroupPotonganDefault::where(array('id_hairstylist_group_default_potongans'=>$request->id_hairstylist_group_default_potongans))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail_default(Request $request)
    {
        if($request->id_hairstylist_group_default_potongans){
        $store = HairstylistGroupPotonganDefault::where(array('id_hairstylist_group_default_potongans'=>$request->id_hairstylist_group_default_potongans))->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete_default(Request $request)
    {
        if($request->id_hairstylist_group_default_potongans){
        $store = HairstylistGroupPotonganDefault::where(array('id_hairstylist_group_default_potongans'=>$request->id_hairstylist_group_default_potongans))->delete();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    function index_default(Request $request) 
    {
    	$post = $request->json()->all();
        $data = HairstylistGroupPotonganDefault::Select('hairstylist_group_default_potongans.*');
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
    public function list_potongan_default(Request $request) {
        if($request->id_hairstylist_group){
            $data = array();
            $potongan = HairstylistGroupPotonganDefault::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))->get();
            foreach ($potongan as $value) {
                $insen = HairstylistGroupPotongan::where(array('id_hairstylist_group_default_potongans'=>$value['id_hairstylist_group_default_potongans'],'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
                if(!$insen){
                    array_push($data,$value);
                }
            }
            return MyHelper::checkGet($data);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    
}
