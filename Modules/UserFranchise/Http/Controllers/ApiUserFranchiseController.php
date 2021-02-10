<?php

namespace Modules\UserFranchise\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\UserFranchise\Entities\UserFranchise;
use App\Lib\MyHelper;
use Modules\UserFranchise\Http\Requests\users_create;

class ApiUserFranchiseController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post = $request->json()->all();
        $list = UserFranchise::orderBy('created_at', 'desc')->paginate(30);

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'email'){
                            if($row['operator'] == '='){
                                $list->where('email', $row['parameter']);
                            }else{
                                $list->where('email', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'name'){
                            if($row['operator'] == '='){
                                $list->where('name', $row['parameter']);
                            }else{
                                $list->where('name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'phone'){
                            if($row['operator'] == '='){
                                $list->where('phone', $row['parameter']);
                            }else{
                                $list->where('phone', 'like', '%'.$row['parameter'].'%');
                            }
                        }
                    }
                }
            }else{
                $list->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'email'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('email', $row['parameter']);
                                }else{
                                    $subquery->orWhere('email', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'name'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('name', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'phone'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('phone', $row['parameter']);
                                }else{
                                    $subquery->orWhere('phone', 'like', '%'.$row['parameter'].'%');
                                }
                            }
                        }
                    }
                });
            }
        }

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
                'email' => $post['email'],
                'phone' => $post['phone'],
                'password' => bcrypt($post['password']),
                'level' => $post['level']
            ];

            $create = UserFranchise::create($dataCreate);
            return response()->json(MyHelper::checkCreate($create));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Email already exist']]);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('userfranchise::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('userfranchise::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
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

    function detailUserFranchise(Request $request){
        $post = $request->json()->all();

        $data = [];
        if(isset($post['email']) && !empty($post['email'])){
            $data = UserFranchise::where('email', $post['email'])->first();
        }

        return response()->json(MyHelper::checkGet($data));
    }
}
