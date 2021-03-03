<?php

namespace Modules\Franchise\Http\Controllers;

use App\Http\Models\Autocrm;
use App\Http\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Franchise\Entities\UserFranchise;
use App\Lib\MyHelper;
use Modules\Franchise\Entities\UserFranchiseOultet;
use Modules\Franchise\Http\Requests\users_create;
use App\Jobs\SendEmailUserFranchiseJob;

class ApiUserFranchiseController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }

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

        if(isset($post['export']) && $post['export'] == 1){
            $list = $list->get()->toArray();
            $result = [];
            foreach ($list as $user){
                $outlet_code = UserFranchiseOultet::join('outlets', 'outlets.id_outlet', 'user_franchise_outlet.id_outlet')
                                ->where('id_user_franchise' , $user['id_user_franchise'])->first()['outlet_code']??NULL;
                $result[] = [
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'level (Super Admin, Admin)' => $user['level'],
                    'outlet_code' => $outlet_code,
                    'status' => $user['user_franchise_status']
                ];
            }
            $list = $result;
        }else{
            $list = $list->paginate(30);
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
            if(isset($post['auto_generate_pin'])){
                $pin = MyHelper::createRandomPIN(6, 'angka');
            }else{
                $pin = $post['pin'];
            }


            $dataCreate = [
                'name' => $post['name'],
                'email' => $post['email'],
                'password' => bcrypt($pin),
                'level' => $post['level'],
                'user_franchise_status' => $post['user_franchise_status']??'Inactive'
            ];

            $create = UserFranchise::create($dataCreate);
            if($create){
                if($post['level'] == 'Admin'){
                    UserFranchiseOultet::where('id_user_franchise' , $create['id_user_franchise'])->delete();
                    $createUserOutlet = UserFranchiseOultet::create(['id_user_franchise' => $create['id_user_franchise'], 'id_outlet' => $post['id_outlet']]);
                }

                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'New User Franchise',
                    $post['email'],
                    [
                        'pin_franchise' => $pin,
                        'email' => $post['email'],
                        'name' => $post['name'],
                        'url' => env('URL_PORTAL_MITRA')
                    ], null, false, false, 'franchise', 1
                );
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
                'level' => $post['level'],
                'user_franchise_status' => $post['user_franchise_status']??'Inactive'
            ];
            $sendCrm = 0;
            if(isset($post['reset_pin'])){
                $pin = MyHelper::createRandomPIN(6, 'angka');
                $dataUpdate['password'] = bcrypt($pin);
                $dataUpdate['first_update_password'] =0;
                $sendCrm = 1;
            }elseif(isset($post['pin']) && !empty($post['pin'])){
                $pin = $post['pin'];
                $dataUpdate['password'] = bcrypt($pin);
                $dataUpdate['first_update_password'] =0;
                $sendCrm = 1;
            }

            $update = UserFranchise::where('id_user_franchise', $post['id_user_franchise'])->update($dataUpdate);
            if($update){
                UserFranchiseOultet::where('id_user_franchise' , $post['id_user_franchise'])->delete();
                if($post['level'] == 'Admin'){
                    $createUserOutlet = UserFranchiseOultet::create(['id_user_franchise' => $post['id_user_franchise'], 'id_outlet' => $post['id_outlet']]);
                }

                if($sendCrm == 1){
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Reset Pin User Franchise',
                        $post['email'],
                        [
                            'pin_franchise' => $pin,
                            'email' => $post['email'],
                            'name' => $post['name'],
                            'url' => env('URL_PORTAL_MITRA')
                        ], null, false, false, 'franchise', 1
                    );
                }
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
    public function destroy(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_user_franchise']) && !empty($post['id_user_franchise'])){
            $dataAdmin = UserFranchise::where('id_user_franchise', auth()->user()->id_user_franchise)->first();
            if($dataAdmin['level'] != 'Super Admin'){
                return response()->json(['status' => 'fail', 'messages' => ["You don't have permission"]]);
            }

            $delete = UserFranchise::where('id_user_franchise', $post['id_user_franchise'])->delete();
            return response()->json(MyHelper::checkDelete($delete));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
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
        }

        if(!empty($data)){
            $data['id_outlet'] = UserFranchiseOultet::where('id_user_franchise' , $data['id_user_franchise'])->first()['id_outlet']??NULL;
        }

        return response()->json(MyHelper::checkGet($data));
    }

    function allOutlet(){
        $outlets = Outlet::where('outlet_status', 'Active')->select('id_outlet', 'outlet_code', 'outlet_name')->get()->toArray();
        return response()->json(MyHelper::checkGet($outlets));
    }

    function autoresponse(Request $request){
        $post = $request->json()->all();

        $crm = Autocrm::where('autocrm_title', $post['title'])->first();
        return response()->json(MyHelper::checkGet($crm));
    }

    function updateAutoresponse(Request $request){
       $update = app($this->autocrm)->updateAutoCrm($request);
       return response()->json($update->original??['status' => 'fail']);
    }

    function updateFirstPin(Request $request){
        $post = $request->json()->all();

        if(isset($post['password']) && !empty($post['password'])){
            if($post['password'] != $post['password2']){
                return response()->json(['status' => 'fail', 'messages' => ["Password don't match"]]);
            }

            $upadte = UserFranchise::where('id_user_franchise', auth()->user()->id_user_franchise)->update(['password' => bcrypt($post['password']), 'first_update_password' => 1]);
            return response()->json(MyHelper::checkUpdate($upadte));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Password can not be empty']]);
        }
    }

    function updateProfile(Request $request){
        $post = $request->json()->all();
        if(empty($post['current_pin'])){
            return response()->json(['status' => 'fail', 'messages' => ['Your pin can not be empty ']]);
        }
        $dataAdmin = UserFranchise::where('id_user_franchise', auth()->user()->id_user_franchise)->first();

        if(!password_verify($post['current_pin'], $dataAdmin['password'])){
            return response()->json(['status' => 'fail', 'message' => 'Wrong input your pin']);
        }

        if(!empty($post['password']) && $post['password'] != $post['password2']){
            return response()->json(['status' => 'fail', 'messages' => ["Pin don't match"]]);
        }
        $checkEmail = UserFranchise::where('email', $post['email'])->first();
        $dataAdmin = UserFranchise::where('id_user_franchise', auth()->user()->id_user_franchise)->first();

        if(empty($dataAdmin)){
            return response()->json(['status' => 'fail', 'messages' => ["User not found"]]);
        }

        if($checkEmail && $checkEmail['id_user_franchise'] != $dataAdmin['id_user_franchise']){
            return response()->json(['status' => 'fail', 'messages' => ["email already use"]]);
        }

        $dataUpdate = [
            'name' => $post['name'],
            'email' => $post['email']
        ];
        if(!empty($post['password'])){
            $dataUpdate['password'] =  bcrypt($post['password']);
        }
        $update = UserFranchise::where('id_user_franchise', $dataAdmin['id_user_franchise'])->update($dataUpdate);

        return response()->json(MyHelper::checkUpdate($update));
    }

    public function import(Request $request){
        $post = $request->json()->all();
        $arrId = [];
        $result = [
            'updated' => 0,
            'create' => 0,
            'failed' => 0,
            'more_msg' => [],
            'more_msg_extended' => []
        ];

        if(empty($post['data'])){
            return response()->json(['status' => 'fail', 'messages' => ['File is empty']]);
        }

        foreach ($post['data'] as $key => $value) {
            $outlet = Outlet::where('outlet_code', $value[3])->first()['id_outlet']??null;
            if($value[2] == 'Admin' && is_null($outlet)){
                $result['failed']++;
                $result['more_msg_extended'][] = "Outlet code  {$value[3]} not found";
                continue;
            }

            $check = UserFranchise::where('email', $value[0])->first();

            if($check){
                $dataUpdate = [
                    'name' => $value[1],
                    'level' => $value[2],
                    'user_franchise_status' => $value[4]
                ];

                $user = UserFranchise::where('id_user_franchise', $check['id_user_franchise'])->update($dataUpdate);

                if(!$user){
                    $result['failed']++;
                    $result['more_msg_extended'][] = "Failed create user  {$value[1]}";
                    continue;
                }else{
                    $result['updated']++;
                }

                UserFranchiseOultet::where('id_user_franchise' , $check['id_user_franchise'])->delete();
                if($value[2] == 'Admin'){
                    UserFranchiseOultet::create(['id_user_franchise' => $check['id_user_franchise'], 'id_outlet' => $outlet]);
                }
            }else{
                $dataCreate = [
                    'email' => $value[0],
                    'name' => $value[1],
                    'level' => $value[2],
                    'user_franchise_status' => $value[4]
                ];

                $user = UserFranchise::create($dataCreate);

                if(!$user){
                    $result['failed']++;
                    $result['more_msg_extended'][] = "Failed create user  {$value[1]}";
                    continue;
                }else{
                    $result['create']++;
                }

                if($value[2] == 'Admin'){
                    UserFranchiseOultet::create(['id_user_franchise' => $user['id_user_franchise'], 'id_outlet' => $outlet]);
                }

                $arrId[] = $user['id_user_franchise'];
            }
        }

        if(!empty($arrId)){
            $arr_chunk = array_chunk($arrId, 20);
            SendEmailUserFranchiseJob::dispatch($arr_chunk)->allOnConnection('database');
        }

        $response = [];

        if($result['updated']){
            $response[] = 'Update '.$result['updated'].' user';
        }
        if($result['create']){
            $response[] = 'Create '.$result['create'].' new user';
        }
        if($result['failed']){
            $response[] = 'Failed create '.$result['failed'].' user';
        }
        $response = array_merge($response,$result['more_msg_extended']);
        return MyHelper::checkGet($response);
    }

    public function resetPassword(Request $request){
        $post = $request->json()->all();

        if(isset($post['email']) && !empty($post['email'])){
            $user = UserFranchise::where('email', $post['email'])->first();
            if(empty($user)){
                return response()->json(['status' => 'fail', 'messages' => ['User not found']]);
            }

            $pin = MyHelper::createRandomPIN(6, 'angka');
            $dataUpdate['password'] = bcrypt($pin);
            $dataUpdate['first_update_password'] =0;
            $update = UserFranchise::where('id_user_franchise', $user['id_user_franchise'])->update($dataUpdate);

            if($update){
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Reset Pin User Franchise',
                    $post['email'],
                    [
                        'pin_franchise' => $pin,
                        'email' => $user['email'],
                        'name' => $user['name'],
                        'url' => env('URL_PORTAL_MITRA')
                    ], null, false, false, 'franchise', 1
                );
            }
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Email can not be empty']]);
        }

    }
}
