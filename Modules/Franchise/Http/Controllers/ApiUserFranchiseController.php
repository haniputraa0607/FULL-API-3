<?php

namespace Modules\Franchise\Http\Controllers;

use App\Http\Models\Autocrm;
use App\Http\Models\Outlet;
use App\Http\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Franchise\Entities\UserFranchise;
use App\Lib\MyHelper;
use Modules\Franchise\Entities\UserFranchiseOultet;
use Modules\Franchise\Http\Requests\UsersCreate;
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
        $list = UserFranchise::whereNotNull('id_user_franchise');
        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $rule = 'and';
            if (isset($post['rule'])) {
                $rule = $post['rule'];
            }

            if ($rule == 'and') {
                foreach ($post['conditions'] as $condition) {
                    if (isset($condition['subject'])) {
                        if ($condition['subject'] == 'level' || $condition['subject'] == 'user_franchise_status') {
                            $list->where($condition['subject'], $condition['operator']);
                        } elseif ($condition['subject'] == 'id_outlet') {
                            $list->whereIn('id_user_franchise', function ($query) use ($condition) {
                                $query->select('id_user_franchise')
                                    ->from('user_franchise_outlet')
                                    ->where('user_franchise_outlet.id_outlet', $condition['operator']);
                            });
                        } else {
                            if ($condition['operator'] == '=') {
                                $list->where($condition['subject'], $condition['parameter']);
                            } else {
                                $list->where($condition['subject'], 'like', '%' . $condition['parameter'] . '%');
                            }
                        }
                    }
                }
            } else {
                $list->where(function ($q) use ($post) {
                    foreach ($post['conditions'] as $condition) {
                        if (isset($condition['subject'])) {
                            if ($condition['subject'] == 'level' || $condition['subject'] == 'user_franchise_status') {
                                $q->orWhere($condition['subject'], $condition['operator']);
                            } elseif ($condition['subject'] == 'id_outlet') {
                                $q->orWhereIn('id_user_franchise', function ($query) use ($condition) {
                                    $query->select('id_user_franchise')
                                        ->from('user_franchise_outlet')
                                        ->where('user_franchise_outlet.id_outlet', $condition['operator']);
                                });
                            } else {
                                if ($condition['operator'] == '=') {
                                    $q->orWhere($condition['subject'], $condition['parameter']);
                                } else {
                                    $q->orWhere($condition['subject'], 'like', '%' . $condition['parameter'] . '%');
                                }
                            }
                        }
                    }
                });
            }
        }

        if (isset($post['export']) && $post['export'] == 1) {
            $list = $list->get()->toArray();
            $result = [];
            foreach ($list as $user) {
                $outlet_code = UserFranchiseOultet::join('outlets', 'outlets.id_outlet', 'user_franchise_outlet.id_outlet')
                                ->where('id_user_franchise', $user['id_user_franchise'])->first()['outlet_code'] ?? null;
                $result[] = [
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'outlet_code' => $outlet_code,
                    'status' => $user['user_franchise_status']
                ];
            }
            $list = $result;
        } else {
            $order = $post['order'] ?? 'created_at';
            $orderType = $post['order_type'] ?? 'desc';

            if ($order != 'outlet') {
                $list = $list->orderBy($order, $orderType)->paginate(30)->toArray();
            } else {
                $list = $list->paginate(30)->toArray();
            }

            foreach ($list['data'] as $key => $val) {
                $outlet = UserFranchiseOultet::join('outlets', 'outlets.id_outlet', 'user_franchise_outlet.id_outlet')
                    ->where('id_user_franchise', $val['id_user_franchise'])->first();
                $list['data'][$key]['outlet_code'] = $outlet['outlet_code'] ?? null;
                $list['data'][$key]['outlet_name'] = $outlet['outlet_name'] ?? null;
            }

            if ($order == 'outlet' && $orderType == 'asc') {
                $data = $list['data'];
                usort($data, function ($a, $b) {
                    return $a['outlet_code'] <=> $b['outlet_code'];
                });
                $list['data'] = $data;
            } elseif ($order == 'outlet' && $orderType == 'desc') {
                $data = $list['data'];
                usort($data, function ($a, $b) {
                    return $a['outlet_code'] < $b['outlet_code'];
                });
                $list['data'] = $data;
            }
        }

        return response()->json(MyHelper::checkGet($list));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(UsersCreate $request)
    {
        $post = $request->json()->all();

        $check = UserFranchise::where('username', $post['username'])->first();

        if (!$check) {
            if (isset($post['auto_generate_pin'])) {
                $pin = MyHelper::createrandom(6);
                ;
            } else {
                $pin = $post['pin'];
            }

            $status = 'Inactive';
            if (!empty($post['user_franchise_status'])) {
                $status = 'Active';
            }

            $post['level'] = $post['level'] ?? 'User Franchise';
            $dataCreate = [
                'username' => $post['username'],
                'name' => $post['name'],
                'email' => $post['email'],
                'password' => bcrypt($pin),
                'level' => $post['level'],
                'user_franchise_status' => $status
            ];

            $create = UserFranchise::create($dataCreate);
            if ($create) {
                $outletCode = null;
                $outletName = null;

                if ($post['level'] == 'User Franchise') {
                    $getOutlet = Outlet::where('id_outlet', $post['id_outlet'])->first();
                    $outletCode = $getOutlet['outlet_code'] ?? null;
                    $outletName = $getOutlet['outlet_name'] ?? null;
                    UserFranchiseOultet::where('id_user_franchise', $create['id_user_franchise'])->delete();
                    $createUserOutlet = UserFranchiseOultet::create(['id_user_franchise' => $create['id_user_franchise'], 'id_outlet' => $post['id_outlet']]);
                }

                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'New User Franchise',
                    $post['username'],
                    [
                        'password' => $pin,
                        'username' => $post['username'],
                        'name' => $post['name'],
                        'url' => env('URL_PORTAL_MITRA'),
                        'outlet_code' => $outletCode,
                        'outlet_name' => $outletName
                    ],
                    null,
                    false,
                    false,
                    'franchise',
                    1
                );
            }
            return response()->json(MyHelper::checkCreate($create));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Username already exist']]);
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
        if (isset($post['id_user_franchise']) && !empty($post['id_user_franchise'])) {
            if (empty($post['password_admin'])) {
                return response()->json(['status' => 'fail', 'messages' => ['Your password can not be empty ']]);
            }
            $dataAdmin = User::where('id', $request->user()->id)->first();

            if (!password_verify($post['password_admin'], $dataAdmin['password'])) {
                return response()->json(['status' => 'fail', 'message' => 'Wrong input your password']);
            }

            $check = UserFranchise::where('username', $post['username'])->whereNotIn('id_user_franchise', [$post['id_user_franchise']])->first();
            if (!empty($check)) {
                return response()->json(['status' => 'fail', 'messages' => ['Username already exist']]);
            }

            $status = 'Inactive';
            if (!empty($post['user_franchise_status'])) {
                $status = 'Active';
            }
            $post['level'] = $post['level'] ?? 'User Franchise';
            $dataUpdate = [
                'username' => $post['username'],
                'name' => $post['name'],
                'email' => $post['email'],
                'level' => $post['level'],
                'user_franchise_status' => $status
            ];

            $sendCrm = 0;
            if (isset($post['reset_pin'])) {
                $pin = MyHelper::createrandom(6);
                $dataUpdate['password'] = bcrypt($pin);
                $dataUpdate['first_update_password'] = 0;
                $sendCrm = 1;
            } elseif (isset($post['pin']) && !empty($post['pin'])) {
                $pin = $post['pin'];
                $dataUpdate['password'] = bcrypt($pin);
                $dataUpdate['first_update_password'] = 0;
                $sendCrm = 1;
            }

            $update = UserFranchise::where('id_user_franchise', $post['id_user_franchise'])->update($dataUpdate);
            if ($update) {
                $outletCode = null;
                $outletName = null;

                UserFranchiseOultet::where('id_user_franchise', $post['id_user_franchise'])->delete();
                if ($post['level'] == 'User Franchise') {
                    $getOutlet = Outlet::where('id_outlet', $post['id_outlet'])->first();
                    $outletCode = $getOutlet['outlet_code'] ?? null;
                    $outletName = $getOutlet['outlet_name'] ?? null;
                    $createUserOutlet = UserFranchiseOultet::create(['id_user_franchise' => $post['id_user_franchise'], 'id_outlet' => $post['id_outlet']]);
                }

                if ($sendCrm == 1) {
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Reset Password User Franchise',
                        $post['username'],
                        [
                            'password' => $pin,
                            'username' => $post['username'],
                            'name' => $post['name'],
                            'url' => env('URL_PORTAL_MITRA'),
                            'outlet_code' => $outletCode,
                            'outlet_name' => $outletName
                        ],
                        null,
                        false,
                        false,
                        'franchise',
                        1
                    );
                }
            }
            return response()->json(MyHelper::checkUpdate($update));
        } else {
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

        if (isset($post['id_user_franchise']) && !empty($post['id_user_franchise'])) {
            $delete = UserFranchise::where('id_user_franchise', $post['id_user_franchise'])->delete();
            return response()->json(MyHelper::checkDelete($delete));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    /**
     * Detail the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function detail(Request $request)
    {
        $post = $request->json()->all();

        $data = [];
        if (isset($post['username']) && !empty($post['username'])) {
            $data = UserFranchise::where('username', $post['username'])->first();
        } elseif (isset($post['id_user_franchise']) && !empty($post['id_user_franchise'])) {
            $data = UserFranchise::where('id_user_franchise', $post['id_user_franchise'])->first();
        }

        if (!empty($data)) {
            $franchiseOutlet = UserFranchiseOultet::join('outlets', 'outlets.id_outlet', 'user_franchise_outlet.id_outlet')
                                ->where('id_user_franchise', $data['id_user_franchise'])->first();
            $data['id_outlet'] = $franchiseOutlet['id_outlet'] ?? null;
            $data['outlet_name'] = $franchiseOutlet['outlet_name'] ?? null;
            $data['outlet_code'] = $franchiseOutlet['outlet_code'] ?? null;
        }

        return response()->json(MyHelper::checkGet($data));
    }

    public function allOutlet()
    {
        $outlets = Outlet::where('outlet_status', 'Active')->select('id_outlet', 'outlet_code', 'outlet_name')->get()->toArray();
        return response()->json(MyHelper::checkGet($outlets));
    }

    public function autoresponse(Request $request)
    {
        $post = $request->json()->all();

        $crm = Autocrm::where('autocrm_title', $post['title'])->first();
        return response()->json(MyHelper::checkGet($crm));
    }

    public function updateAutoresponse(Request $request)
    {
        $update = app($this->autocrm)->updateAutoCrm($request);
        return response()->json($update->original ?? ['status' => 'fail']);
    }

    public function updateFirstPin(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['password']) && !empty($post['password'])) {
            if ($post['password'] != $post['password2']) {
                return response()->json(['status' => 'fail', 'messages' => ["Password don't match"]]);
            }

            $upadte = UserFranchise::where('id_user_franchise', auth()->user()->id_user_franchise)->update(['password' => bcrypt($post['password']), 'first_update_password' => 1]);
            return response()->json(MyHelper::checkUpdate($upadte));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Password can not be empty']]);
        }
    }

    public function updateProfile(Request $request)
    {
        $post = $request->json()->all();
        if (empty($post['current_pin'])) {
            return response()->json(['status' => 'fail', 'messages' => ['Your pin can not be empty ']]);
        }
        $dataAdmin = UserFranchise::where('id_user_franchise', auth()->user()->id_user_franchise)->first();

        if (!password_verify($post['current_pin'], $dataAdmin['password'])) {
            return response()->json(['status' => 'fail', 'message' => 'Wrong input your pin']);
        }

        if (!empty($post['password']) && $post['password'] != $post['password2']) {
            return response()->json(['status' => 'fail', 'messages' => ["Pin don't match"]]);
        }
        $dataAdmin = UserFranchise::where('id_user_franchise', auth()->user()->id_user_franchise)->first();

        if (empty($dataAdmin)) {
            return response()->json(['status' => 'fail', 'messages' => ["User not found"]]);
        }

        $dataUpdate = [
            'name' => $post['name'],
            'email' => $post['email']
        ];
        if (!empty($post['password'])) {
            $dataUpdate['password'] =  bcrypt($post['password']);
        }
        $update = UserFranchise::where('id_user_franchise', $dataAdmin['id_user_franchise'])->update($dataUpdate);

        return response()->json(MyHelper::checkUpdate($update));
    }

    public function import(Request $request)
    {
        $post = $request->json()->all();
        $arrId = [];
        $result = [
            'invalid' => 0,
            'updated' => 0,
            'create' => 0,
            'failed' => 0,
            'more_msg' => [],
            'more_msg_extended' => []
        ];
        $data = $post['data'][0] ?? [];

        if (empty($data)) {
            return response()->json(['status' => 'fail', 'messages' => ['File is empty']]);
        }

        foreach ($data as $key => $value) {
            if (empty($value['username'])) {
                $result['invalid']++;
                continue;
            }

            if (empty($value['outlet_code'])) {
                $result['invalid']++;
                continue;
            }
            $outlet = Outlet::where('outlet_code', $value['outlet_code'])->first()['id_outlet'] ?? null;
            $check = UserFranchise::where('username', $value['username'])->first();

            if ($check) {
                $dataUpdate = [
                    'email' => $value['email'],
                    'name' => $value['name'],
                    'level' => 'User Franchise',
                    'user_franchise_status' => $value['status']
                ];

                $user = UserFranchise::where('id_user_franchise', $check['id_user_franchise'])->update($dataUpdate);

                if (!$user) {
                    $result['failed']++;
                    $result['more_msg_extended'][] = "Failed create user  {$value['username']}";
                    continue;
                } else {
                    $result['updated']++;
                }

                UserFranchiseOultet::where('id_user_franchise', $check['id_user_franchise'])->delete();
                UserFranchiseOultet::create(['id_user_franchise' => $check['id_user_franchise'], 'id_outlet' => $outlet]);

                if (empty($check['password'])) {
                    $arrId[] = $check['id_user_franchise'];
                }
            } else {
                $dataCreate = [
                    'username' => $value['username'],
                    'name' => $value['name'],
                    'email' => $value['email'],
                    'level' => 'User Franchise',
                    'user_franchise_status' => $value['status']
                ];

                $user = UserFranchise::create($dataCreate);

                if (!$user) {
                    $result['failed']++;
                    $result['more_msg_extended'][] = "Failed create user  {$value['username']}";
                    continue;
                } else {
                    $result['create']++;
                }
                UserFranchiseOultet::create(['id_user_franchise' => $user['id_user_franchise'], 'id_outlet' => $outlet]);

                $arrId[] = $user['id_user_franchise'];
            }
        }

        if (!empty($arrId)) {
            $arr_chunk = array_chunk($arrId, 20);
            foreach ($arr_chunk as $datas) {
                SendEmailUserFranchiseJob::dispatch($datas)->allOnConnection('database');
            }
        }

        $response = [];

        if ($result['invalid']) {
            $response[] = 'Invalid ' . $result['invalid'] . ' data';
        }
        if ($result['updated']) {
            $response[] = 'Update ' . $result['updated'] . ' user';
        }
        if ($result['create']) {
            $response[] = 'Create ' . $result['create'] . ' new user';
        }
        if ($result['failed']) {
            $response[] = 'Failed create ' . $result['failed'] . ' user';
        }
        $response = array_merge($response, $result['more_msg_extended']);
        return MyHelper::checkGet($response);
    }

    public function resetPassword(Request $request)
    {
        $post = $request->json()->all();

        if (
            isset($post['email']) && !empty($post['email']) &&
            isset($post['username']) && !empty($post['username'])
        ) {
            $user = UserFranchise::where('email', $post['email'])->where('username', $post['username'])->first();
            if (empty($user)) {
                return response()->json(['status' => 'fail', 'messages' => ['User not found']]);
            }

            $pin = MyHelper::createrandom(6);
            $dataUpdate['password'] = bcrypt($pin);
            $dataUpdate['first_update_password'] = 0;
            $update = UserFranchise::where('id_user_franchise', $user['id_user_franchise'])->update($dataUpdate);

            if ($update) {
                $getOutlet = UserFranchiseOultet::join('outlets', 'outlets.id_outlet', 'user_franchise_outlet.id_outlet')
                                ->where('id_user_franchise', $user['id_user_franchise'])->first();
                $outletCode = $getOutlet['outlet_code'] ?? null;
                $outletName = $getOutlet['outlet_name'] ?? null;
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Reset Password User Franchise',
                    $post['username'],
                    [
                        'password' => $pin,
                        'username' => $user['username'],
                        'name' => $user['name'],
                        'url' => env('URL_PORTAL_MITRA'),
                        'outlet_code' => $outletCode,
                        'outlet_name' => $outletName
                    ],
                    null,
                    false,
                    false,
                    'franchise',
                    1
                );
            }
            return response()->json(MyHelper::checkUpdate($update));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Email can not be empty']]);
        }
    }
}
