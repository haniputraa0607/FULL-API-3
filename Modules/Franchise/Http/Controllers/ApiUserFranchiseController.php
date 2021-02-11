<?php

namespace Modules\Franchise\Http\Controllers;

use App\Http\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Franchise\Entities\UserFranchise;
use App\Lib\MyHelper;
use Modules\Franchise\Entities\UserFranchiseOultet;
use Modules\Franchise\Http\Requests\users_create;

class ApiUserFranchiseController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post = $request->json()->all();
        $list = UserFranchise::orderBy('created_at', 'desc');

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        $list->where($row['subject'], 'like', '%'.$row['parameter'].'%');
                    }
                }
            }else{
                $list->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            $subquery->orWhere($row['subject'], 'like', '%'.$row['parameter'].'%');
                        }
                    }
                });
            }
        }

        $list = $list->paginate(30);

        return response()->json(MyHelper::checkGet($list));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(users_create $request)
    {
        $post = $request->json()->all();

        $check = UserFranchise::where('email', $post['email'])->first();

        if(!$check){
            $dataCreate = [
                'name' => $post['name'],
                'email' => $post['email'],
                'phone' => $post['phone'],
                'password' => bcrypt($post['password']),
                'level' => $post['level']
            ];

            $create = UserFranchise::create($dataCreate);
            if($create){
                UserFranchiseOultet::where('id_user_franchise' , $create['id_user_franchise'])->delete();
                $createUserOutlet = UserFranchiseOultet::create(['id_user_franchise' => $create['id_user_franchise'], 'id_outlet' => $post['id_outlet']]);
            }
            return response()->json(MyHelper::checkCreate($create));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Email already exist']]);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request)
    {
        $post = $request->json()->all();
        if(isset($post['id_user_franchise']) && !empty($post['id_user_franchise'])){
            if(empty($post['password_admin'])){
                return response()->json(['status' => 'fail', 'messages' => ['Your password can not be empty ']]);
            }
            $dataAdmin = UserFranchise::where('id_user_franchise', auth()->user()->id_user_franchise)->first();

            if(!password_verify($post['password_admin'], $dataAdmin['password'])){
                return response()->json(['status' => 'fail', 'message' => 'Wrong input your password']);
            }

            $dataUpdate = [
                'name' => $post['name'],
                'email' => $post['email'],
                'phone' => $post['phone'],
                'level' => $post['level']
            ];

            $update = UserFranchise::where('id_user_franchise', $post['id_user_franchise'])->update($dataUpdate);
            if($update){
                UserFranchiseOultet::where('id_user_franchise' , $post['id_user_franchise'])->delete();
                $createUserOutlet = UserFranchiseOultet::create(['id_user_franchise' => $post['id_user_franchise'], 'id_outlet' => $post['id_outlet']]);
            }
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Detail the specified resource from storage.
     * @param int $id
     * @return Response
     */
    function detail(Request $request){
        $post = $request->json()->all();

        $data = [];
        if(isset($post['email']) && !empty($post['email'])){
            $data = UserFranchise::where('email', $post['email'])->first();
        }elseif (isset($post['id_user_franchise']) && !empty($post['id_user_franchise'])){
            $data = UserFranchise::where('id_user_franchise', $post['id_user_franchise'])->first();
            $data['id_outlet'] = UserFranchiseOultet::where('id_user_franchise', $post['id_user_franchise'])->first()['id_outlet']??NULL;
        }

        return response()->json(MyHelper::checkGet($data));
    }

    function allOutlet(){
        $outlets = Outlet::where('outlet_status', 'Active')->select('id_outlet', 'outlet_code', 'outlet_name')->get()->toArray();
        return response()->json(MyHelper::checkGet($outlets));
    }
}
