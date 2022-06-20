<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Http\Models\Outlet;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\RequestHairStylist;
use DB;

class ApiRequestHairStylistController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
    }

    public function index(Request $request)
    {
        $post = $request->all();
        $request_mitra = RequestHairStylist::with(['outlet_request','applicant_request']);
        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }
            if($rule == 'and'){
                foreach ($post['conditions'] as $condition){
                    if(isset($condition['subject'])){      

                        if($condition['subject']=='status'){
                            $condition['parameter'] = $condition['operator'];
                            $condition['operator'] = '=';
                        }elseif($condition['subject']=='outlet_name'){
                            $request_mitra = $request_mitra->join('outlets','outlets.id_outlet','=','request_hair_stylists.id_outlet');
                            $condition['subject'] = 'outlets.outlet_name';
                        }
                        
                        if($condition['operator'] == '='){
                            $request_mitra = $request_mitra->where($condition['subject'], $condition['parameter']);
                        }else{
                            $request_mitra = $request_mitra->where($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                        }
                    }
                }
            }else{
                $request_mitra = $request_mitra->where(function ($q) use ($post){
                    foreach ($post['conditions'] as $condition){
                        if(isset($condition['subject'])){

                            if($condition['subject']=='status'){
                                $condition['parameter'] = $condition['operator'];
                                $condition['operator'] = '=';
                            }

                            if($condition['operator'] == '='){
                                $q->orWhere($condition['subject'], $condition['parameter']);
                            }else{
                                $q->orWhere($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                            }
                        }
                    }
                });
            }
        }
        if(isset($post['order']) && isset($post['order_type'])){
            if($post['order']=='outlet_name'){
                $request_mitra = $request_mitra->join('outlets','outlets.id_outlet','=','request_hair_stylists.id_outlet');
                if(isset($post['page'])){
                    $request_mitra = $request_mitra->orderBy('outlets.outlet_name', $post['order_type'])->paginate($request->length ?: 10);
                }else{
                    $request_mitra = $request_mitra->orderBy('outlets.outlet_name', $post['order_type'])->get()->toArray();
                }
            }else{
                if(isset($post['page'])){
                    $request_mitra = $request_mitra->orderBy('request_hair_stylists.'.$post['order'], $post['order_type'])->paginate($request->length ?: 10);
                }else{
                    $request_mitra = $request_mitra->orderBy('request_hair_stylists.'.$post['order'], $post['order_type'])->get()->toArray();
                }
            }
        }else{
            if(isset($post['page'])){
                $request_mitra = $request_mitra->orderBy('request_hair_stylists.created_at', 'desc')->paginate($request->length ?: 10);
            }else{
                $request_mitra = $request_mitra->orderBy('request_hair_stylists.created_at', 'desc')->get()->toArray();
            }
        }
        return MyHelper::checkGet($request_mitra);
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('recruitment::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $post = $request->all();
        if (!empty($post) && !empty($post['id_outlet'])) {
            if (isset($post['id_outlet'])) {
                $data_store['id_outlet'] = $post['id_outlet'];
            }
            if (isset($post['number_of_request'])) {
                $data_store['number_of_request'] = $post['number_of_request'];
            }
            if (isset($post['status'])) {
                $data_store['status'] = $post['status'];
            }
            if (isset($post['id_user'])) {
                $data_store['id_user'] = $post['id_user'];
            }
            if (isset($post['notes'])) {
                $data_store['notes'] = $post['notes'];
            }
            if (isset($post['notes_om'])) {
                $data_store['notes_om'] = $post['notes_om'];
            }
            $cek_outlet = Outlet::where(['id_outlet'=>$data_store['id_outlet']])->first();
            if ($cek_outlet) {
                DB::beginTransaction();
                $store = RequestHairStylist::create($data_store); 
                if(!$store) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed add request hair stylist']]);
                }   
            } else {
                return response()->json(['status' => 'fail', 'messages' => ['Id Outlet not found']]);
            }
            DB::commit();
            return response()->json(MyHelper::checkCreate($store));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_request_hair_stylist']) && !empty($post['id_request_hair_stylist'])){
            $req_hair_stylist = RequestHairStylist::with(['outlet_request','applicant_request'])->where('id_request_hair_stylist', $post['id_request_hair_stylist'])->first();
            $req_hair_stylist['id_hs'] = json_decode($req_hair_stylist['id_hs']??'' , true)['id_hair_stylist'];
            return response()->json(['status' => 'success', 'result' => [
                'request_hair_stylist' => $req_hair_stylist,
            ]]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('recruitment::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request)
    {
        $post = $request->all();
        if (isset($post['id_request_hair_stylist']) && !empty($post['id_request_hair_stylist'])) {
            DB::beginTransaction();
            if (isset($post['id_outlet'])) {
                $data_update['id_outlet'] = $post['id_outlet'];
            }
            if (isset($post['number_of_request'])) {
                $data_update['number_of_request'] = $post['number_of_request'];
            }
            if (isset($post['status'])) {
                $data_update['status'] = $post['status'];
            }
            if (isset($post['id_user'])) {
                $data_store['id_user'] = $post['id_user'];
            }
            if (isset($post['notes'])) {
                $data_update['notes'] = $post['notes'];
            }else{
                $data_update['notes'] = null;
            }
            if (isset($post['id_hs'])) {
                $data_update['id_hs'] = $post['id_hs'];
            }else{
                $data_update['id_hs'] = null;
            }
            $update = RequestHairStylist::where('id_request_hair_stylist', $post['id_request_hair_stylist'])->update($data_update);
            if(!$update){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed update request hair stylist']]);
            }
            DB::commit();
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        $id_request_hair_stylist  = $request->json('id_request_hair_stylist');
        $delete = RequestHairStylist::where('id_request_hair_stylist', $id_request_hair_stylist)->delete();
        return MyHelper::checkDelete($delete);
    }

    public function listOutlet(Request $request){
        $outlet = Outlet::with('location_outlet')->where('outlet_status','active')->where('type', 'Outlet')->get()->toArray();
        if($outlet){
            return response()->json(MyHelper::checkCreate($outlet));
        }else{
            return [];
        }
    }

    public function listOffice(Request $request){
        $outlet = Outlet::with('location_outlet')->where('type', 'Office')->get()->toArray();
        if($outlet){
            return response()->json(MyHelper::checkCreate($outlet));
        }else{
            return [];
        }
    }

    public function listHairStylistsOutlet(Request $request){
        $post = $request->all();
        if (isset($post['id_outlet']) && !empty($post['id_outlet'])) {
            $list = UserHairStylist::where('user_hair_stylist_status','Active')->where('id_outlet',$post['id_outlet'])->get()->toArray();
            return $list;
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
        
    }
}
