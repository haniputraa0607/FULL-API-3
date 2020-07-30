<?php

namespace Modules\Achievement\Http\Controllers;

use App\Http\Models\Membership;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\User;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Achievement\Entities\AchievementCategory;
use Modules\Achievement\Entities\AchievementDetail;
use Modules\Achievement\Entities\AchievementGroup;
use Modules\Achievement\Entities\AchievementOutletLog;
use Modules\Achievement\Entities\AchievementProductLog;
use Modules\Achievement\Entities\AchievementProgress;
use Modules\Achievement\Entities\AchievementProvinceLog;
use Modules\Achievement\Entities\AchievementUser;
use Modules\Achievement\Entities\AchievementUserLog;

class ApiAchievement extends Controller
{
    public $saveImage = "img/achievement/";
    public $saveImageDetail = "img/achievement/detail/";

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $data = AchievementGroup::select('achievement_groups.id_achievement_group', 'achievement_categories.name as category_name', 'achievement_groups.name', 'date_start', 'date_end', 'publish_start', 'publish_end')->leftJoin('achievement_categories', 'achievement_groups.id_achievement_category', '=', 'achievement_categories.id_achievement_category');
        if ($request->post('keyword')) {
            $data->where('achievement_groups.name', 'like', "%{$request->post('keyword')}%");
        }
        return MyHelper::checkGet($data->paginate());
    }

    function reportAchievement(Request $request){
        $post = $request->json()->all();
        $data = AchievementGroup::leftJoin('achievement_categories', 'achievement_groups.id_achievement_category', '=', 'achievement_categories.id_achievement_category');

        if(isset($post['date_start']) && !empty($post['date_start']) &&
            isset($post['date_end']) && !empty($post['date_end'])) {
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $data = $data->select('achievement_groups.id_achievement_group', 'achievement_categories.name as category_name', 'achievement_groups.name',
                'achievement_groups.description', 'date_start', 'date_end', 'publish_start', 'publish_end',
                DB::raw("(SELECT GROUP_CONCAT(ad.name SEPARATOR ', ') FROM achievement_details ad WHERE ad.id_achievement_group = achievement_groups.id_achievement_group) as achievement_badge"),
                DB::raw("(SELECT COUNT(DISTINCT au.id_user) from achievement_users au 
                join achievement_details ad2 on ad2.id_achievement_detail = au.id_achievement_detail
                where ad2.id_achievement_group = achievement_groups.id_achievement_group and
                DATE(au.date) >= '".$start_date."' and DATE(au.date) <= '".$end_date."') as total_user"));

            $data = $data->where(function($query) use($start_date, $end_date){
                $query->orWhereRaw('(DATE(date_start) >= "'.$start_date.'" AND DATE(date_start) <= "'.$end_date.'")')
                    ->orWhereRaw('(DATE(date_end) >= "'.$start_date.'" AND DATE(date_start) <= "'.$end_date.'")')
                    ->orWhereRaw('date_end is null');
            });
        }else{
            $data = $data->select('achievement_groups.id_achievement_group', 'achievement_categories.name as category_name', 'achievement_groups.name',
                'achievement_groups.description', 'date_start', 'date_end', 'publish_start', 'publish_end',
                DB::raw("(SELECT GROUP_CONCAT(ad.name SEPARATOR ', ') FROM achievement_details ad WHERE ad.id_achievement_group = achievement_groups.id_achievement_group) as achievement_badge"),
                DB::raw("(SELECT COUNT(DISTINCT au.id_user) from achievement_users au 
                join achievement_details ad2 on ad2.id_achievement_detail = au.id_achievement_detail
                where ad2.id_achievement_group = achievement_groups.id_achievement_group) as total_user"));
        }

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'achievement_name' && !empty($row['parameter'])){
                            if($row['operator'] == '='){
                                $data->where('achievement_groups.name', $row['parameter']);
                            }else{
                                $data->where('achievement_groups.name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'rule_achievement'){
                            $data->whereIn('achievement_groups.id_achievement_group', function ($sub) use ($row){
                                $sub->select('a_detail.id_achievement_group')->from('achievement_details as a_detail');

                                if($row['operator'] == 'product'){
                                    $sub->where(function($q){
                                        $q->whereNotNull('id_product')->orWhereNotNull('product_total');
                                    });
                                }

                                if($row['operator'] == 'outlet'){
                                    $sub->where(function($q){
                                        $q->whereNotNull('id_outlet')->orWhereNotNull('different_outlet');
                                    });
                                }

                                if($row['operator'] == 'province'){
                                    $sub->where(function($q){
                                        $q->whereNotNull('id_province')->orWhereNotNull('different_province');
                                    });
                                }

                                if($row['operator'] == 'trx_nominal'){
                                    $sub->whereNotNull('trx_nominal');
                                }

                                if($row['operator'] == 'trx_total'){
                                    $sub->whereNotNull('trx_total');
                                }
                            });
                        }

                        if($row['subject'] == 'badge_name' && !empty($row['parameter'])){
                            $data->whereIn('achievement_groups.id_achievement_group', function ($sub) use ($row){
                                $sub->select('a_detail.id_achievement_group')->from('achievement_details as a_detail');

                                if($row['operator'] == '='){
                                    $sub->where('a_detail.name', $row['parameter']);
                                }else{
                                    $sub->where('a_detail.name', 'like', '%'.$row['parameter'].'%');
                                }
                            });
                        }

                        if($row['subject'] == 'number_of_user_badge' && !empty($row['parameter'])){
                            $data->whereIn('achievement_groups.id_achievement_group', function ($sub) use ($row){
                                $sub->select('a_detail.id_achievement_group')->from('achievement_users as a_users')
                                    ->join('achievement_details as a_detail', 'a_users.id_achievement_detail', 'a_detail.id_achievement_detail')
                                    ->groupBy('a_users.id_achievement_detail')
                                    ->havingRaw('COUNT(a_users.id_user) '.$row['operator'].' '.$row['parameter']);
                            });
                        }

                        if($row['subject'] == 'number_of_user_achievement' && !empty($row['parameter'])){
                            $data->whereIn('achievement_groups.id_achievement_group', function ($sub) use ($row){
                                $sub->select('a_detail.id_achievement_group')->from('achievement_users as a_users')
                                    ->join('achievement_details as a_detail', 'a_users.id_achievement_detail', 'a_detail.id_achievement_detail')
                                    ->groupBy('a_detail.id_achievement_group')
                                    ->havingRaw('COUNT(DISTINCT a_users.id_user) '.$row['operator'].' '.$row['parameter']);
                            });
                        }
                    }
                }
            }else{
                $data->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject']) && !empty($row['parameter'])){
                            if($row['subject'] == 'achievement_name' && !empty($row['parameter'])){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('achievement_groups.name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('achievement_groups.name', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'badge_name' && !empty($row['parameter'])){
                                $subquery->orWhereIn('achievement_groups.id_achievement_group', function ($sub) use ($row){
                                    $sub->select('a_detail.id_achievement_group')->from('achievement_details as a_detail');

                                    if($row['operator'] == '='){
                                        $sub->where('a_detail.name', $row['parameter']);
                                    }else{
                                        $sub->where('a_detail.name', 'like', '%'.$row['parameter'].'%');
                                    }
                                });
                            }

                            if($row['subject'] == 'number_of_user_badge' && !empty($row['parameter'])){
                                $subquery->orWhereIn('achievement_groups.id_achievement_group', function ($sub) use ($row){
                                    $sub->select('a_detail.id_achievement_group')->from('achievement_users as a_users')
                                        ->join('achievement_details as a_detail', 'a_users.id_achievement_detail', 'a_detail.id_achievement_detail')
                                        ->groupBy('a_users.id_achievement_detail')
                                        ->havingRaw('COUNT(a_users.id_user) '.$row['operator'].' '.$row['parameter']);
                                });
                            }

                            if($row['subject'] == 'number_of_user_achievement' && !empty($row['parameter'])){
                                $subquery->orWhereIn('achievement_groups.id_achievement_group', function ($sub) use ($row){
                                    $sub->select('a_detail.id_achievement_group')->from('achievement_users as a_users')
                                        ->join('achievement_details as a_detail', 'a_users.id_achievement_detail', 'a_detail.id_achievement_detail')
                                        ->groupBy('a_detail.id_achievement_group')
                                        ->havingRaw('COUNT(DISTINCT a_users.id_user) '.$row['operator'].' '.$row['parameter']);
                                });
                            }

                            if($row['subject'] == 'rule_achievement'){
                                $subquery->orWhereIn('achievement_groups.id_achievement_group', function ($sub) use ($row){
                                    $sub->select('a_detail.id_achievement_group')->from('achievement_details as a_detail');

                                    if($row['operator'] == 'product'){
                                        $sub->where(function($q){
                                            $q->whereNotNull('id_product')->orWhereNotNull('product_total');
                                        });
                                    }

                                    if($row['operator'] == 'outlet'){
                                        $sub->where(function($q){
                                            $q->whereNotNull('id_outlet')->orWhereNotNull('different_outlet');
                                        });
                                    }

                                    if($row['operator'] == 'province'){
                                        $sub->where(function($q){
                                            $q->whereNotNull('id_province')->orWhereNotNull('different_province');
                                        });
                                    }

                                    if($row['operator'] == 'trx_nominal'){
                                        $sub->whereNotNull('trx_nominal');
                                    }

                                    if($row['operator'] == 'trx_total'){
                                        $sub->whereNotNull('trx_total');
                                    }
                                });
                            }
                        }
                    }
                });
            }
        }

        return response()->json(MyHelper::checkGet($data->paginate(30)));
    }

    function reportDetailAchievement(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_achievement_group']) && !empty($post['id_achievement_group'])){
            $id = $post['id_achievement_group'];
            $getDataAchivement = AchievementGroup::leftJoin('achievement_categories', 'achievement_groups.id_achievement_category', '=', 'achievement_categories.id_achievement_category')
                ->select('achievement_groups.id_achievement_group', 'achievement_categories.name as category_name', 'achievement_groups.name',
                    'date_start', 'date_end', 'publish_start', 'publish_end',
                    DB::raw("(SELECT COUNT(DISTINCT au.id_user) from achievement_users au 
                join achievement_details ad2 on ad2.id_achievement_detail = au.id_achievement_detail
                where ad2.id_achievement_group = achievement_groups.id_achievement_group) as total_user"))
                ->where('achievement_groups.id_achievement_group', MyHelper::decSlug($id))->first();

            if($getDataAchivement){
                $getDataBadge = AchievementDetail::where('achievement_details.id_achievement_group',  MyHelper::decSlug($id))
                    ->select('achievement_details.*',
                        DB::raw("(SELECT COUNT(DISTINCT au.id_user) from achievement_users au
                                        where au.id_achievement_detail = achievement_details.id_achievement_detail) as total_badge_user"))
                    ->with('product', 'outlet', 'province')->get()->toArray();

                if($getDataBadge){

                    return response()->json(
                        [
                            'status' => 'success',
                            'result' => [
                                'data_achievement' => $getDataAchivement,
                                'data_badge' => $getDataBadge
                            ]
                        ]
                    );

                }

                return response()->json(MyHelper::checkGet($getDataBadge));
            }
            return response()->json(MyHelper::checkGet($getDataAchivement));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Data incompleted']]);
        }
    }

    function listUserAchivement(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_achievement_group']) && !empty($post['id_achievement_group'])) {
            $id = $post['id_achievement_group'];

            $length = 8;
            if(isset($post['length'])){
                $length = (int)$post['length'];
            }

            $getDataListUser = AchievementUser::join('users', 'users.id', 'achievement_users.id_user')
                ->groupBy('id_user')
                ->whereIn('achievement_users.id_achievement_detail', function ($sub) use($id){
                    $sub->select('achievement_details.id_achievement_detail')
                        ->from('achievement_details')
                        ->join('achievement_groups', 'achievement_groups.id_achievement_group', 'achievement_details.id_achievement_group')
                        ->where('achievement_groups.id_achievement_group', MyHelper::decSlug($id));
                })
                ->select('users.name as 0', 'users.phone as 1', 'users.email as 2',
                    'users.phone as 3', 'users.phone as 4', 'users.phone as 5', 'achievement_users.id_user')
                ->with(['achievement_detail'=>function ($que) use($id){
                    $que->join('achievement_groups', 'achievement_groups.id_achievement_group', 'achievement_details.id_achievement_group')
                        ->where('achievement_groups.id_achievement_group', MyHelper::decSlug($id))
                        ->select('achievement_details.*', 'achievement_users.*');
                }])->paginate($length);

            return response()->json(MyHelper::checkGet($getDataListUser));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Data incompleted']]);
        }
    }

    function reportUser(Request $request)
    {
        $post = $request->json()->all();

        $data = AchievementUser::join('users', 'users.id', 'achievement_users.id_user')
            ->join('memberships', 'users.id_membership', 'memberships.id_membership')
            ->groupBy('achievement_users.id_user');

        if(isset($post['date_start']) && !empty($post['date_start']) &&
            isset($post['date_end']) && !empty($post['date_end'])){
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $data->whereDate('achievement_users.date', '>=', $start_date)
                ->whereDate('achievement_users.date', '<=', $end_date)
                ->select('users.id', 'users.name', 'users.phone', 'memberships.membership_name',
                DB::raw('(Select COUNT(au.id_user) from achievement_users au where DATE(au.date) >= "'.$start_date.'" AND DATE(au.date) <= "'.$end_date.'"
                AND au.id_user = achievement_users.id_user) as total'));
        }else{
            $data = $data->select('users.id', 'users.name', 'users.phone', 'memberships.membership_name',
                DB::raw('COUNT(achievement_users.id_user) as total'));
        }

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject']) && !empty($row['parameter'])){
                        if($row['subject'] == 'name'){
                            if($row['operator'] == '='){
                                $data->where('users.name', $row['parameter']);
                            }else{
                                $data->where('users.name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'phone'){
                            if($row['operator'] == '='){
                                $data->where('users.phone', $row['parameter']);
                            }else{
                                $data->where('users.phone', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'achievement_total'){
                            $data->havingRaw('COUNT(achievement_users.id_user) '.$row['operator'].' '.$row['parameter']);
                        }

                    }
                }
            }else{
                $data->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject']) && !empty($row['parameter'])){
                            if($row['subject'] == 'name'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('users.name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('users.name', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'phone'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('users.phone', $row['parameter']);
                                }else{
                                    $subquery->orWhere('users.phone', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'achievement_total'){
                                $subquery->orHavingRaw('COUNT(achievement_users.id_user) '.$row['operator'].' '.$row['parameter']);
                            }
                        }
                    }
                });
            }
        }

        return MyHelper::checkGet($data->paginate(30));
    }

    function reportDetailUser(Request $request){
        $post = $request->json()->all();

        if(isset($post['phone']) && !empty($post['phone'])){
            $dataUser = User::where('phone',$post['phone'])
                ->leftJoin('cities', 'cities.id_city', '=', 'users.id_city')
                ->join('memberships', 'users.id_membership', 'memberships.id_membership')
                ->select('users.id', 'users.name', 'users.phone', 'users.email', 'users.created_at', 'memberships.membership_name',
                    'cities.city_name', 'users.job', 'users.gender', 'users.balance',
                    DB::raw('(Select SUM(balance) from log_balances as lb where lb.id_user = users.id and balance >= 0) as accumulation_point'))
                ->first();
            if($dataUser){
                $listAchievement = AchievementGroup::leftJoin('achievement_categories', 'achievement_groups.id_achievement_category', '=', 'achievement_categories.id_achievement_category')
                    ->select('achievement_groups.id_achievement_group', 'achievement_categories.name as category_name', 'achievement_groups.name', 'date_start',
                        'date_end', 'publish_start', 'publish_end',
                        DB::raw('(Select ad1.name from achievement_details ad1
                                    join  achievement_users au1 on au1.id_achievement_detail = ad1.id_achievement_detail
                                    where ad1.id_achievement_group = achievement_groups.id_achievement_group
                                    and au1.id_user = '.$dataUser['id'].' order by date desc limit 1) as last_badged'),
                        DB::raw('(Select au2.date from achievement_details ad2
                                    join  achievement_users au2 on au2.id_achievement_detail = ad2.id_achievement_detail
                                    where ad2.id_achievement_group = achievement_groups.id_achievement_group
                                    and au2.id_user = '.$dataUser['id'].' order by date asc limit 1) as date_first_badge')
                    )
                    ->whereIn('achievement_groups.id_achievement_group', function ($sub) use ($post, $dataUser){
                        $sub->select('achievement_details.id_achievement_group')
                            ->from('achievement_details')
                            ->join('achievement_users', 'achievement_users.id_achievement_detail', 'achievement_details.id_achievement_detail')
                            ->where('achievement_users.id_user', $dataUser['id']);
                    })->paginate(30);

                return response()->json([
                    'status' => 'success',
                    'result' => [
                        'data_user' => $dataUser,
                        'list_achievement' => $listAchievement
                    ]
                ]);
            }else{
                return response()->json(MyHelper::checkGet($dataUser));
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Data incompleted']]);
        }
    }

    function reportDetailBadgeUser(Request $request){
        $post = $request->json()->all();
        $data = AchievementDetail::join('achievement_users', 'achievement_users.id_achievement_detail', 'achievement_details.id_achievement_detail')
            ->join('users', 'users.id', 'achievement_users.id_user')
            ->join('achievement_groups', 'achievement_groups.id_achievement_group', 'achievement_details.id_achievement_group')
            ->leftJoin('achievement_categories', 'achievement_groups.id_achievement_category', '=', 'achievement_categories.id_achievement_category')
            ->select('achievement_groups.id_achievement_group', 'users.name as user_name', 'users.phone', 'users.email',
                'achievement_details.*', 'achievement_users.date',
                'achievement_categories.name as category_name',
                'achievement_groups.publish_start',
                'achievement_groups.created_at as achivement_created_at',
                'achievement_groups.date_start',
                'achievement_groups.date_end',
                'achievement_groups.name as achivement_name')
            ->where('achievement_groups.id_achievement_group',MyHelper::decSlug($post['id_achievement_group']))
            ->where('users.phone', $post['phone'])
            ->with('product', 'outlet', 'province')
            ->get()->toArray();

        if($data){
            foreach ($data as $key => $value) {
                $data[$key]['logo_badge'] = env('STORAGE_URL_API') . $value['logo_badge'];
            }
        }
        return response()->json(MyHelper::checkGet($data));
    }

    public function reportAch(Request $request)
    {
        $data = AchievementGroup::select('achievement_groups.id_achievement_group', 'achievement_categories.name as category_name', 'achievement_groups.name', 'date_start', 'date_end', DB::raw('COALESCE((
            SELECT COUNT(*) from achievement_user_logs
            JOIN achievement_details ON achievement_user_logs.id_achievement_detail = achievement_details.id_achievement_detail
            WHERE achievement_details.id_achievement_group = achievement_groups.id_achievement_group
            GROUP BY achievement_details.id_achievement_group
        ), 0 ) AS total_user'))->leftJoin('achievement_categories', 'achievement_groups.id_achievement_category', '=', 'achievement_categories.id_achievement_category');

        if (!is_null($request->post('ach_filter'))) {
            switch ($request->post('filter_by')) {
                case 'name':
                    $data->where('achievement_groups.name', 'like', "%{$request->post('ach_filter')}%");
                    break;
                case 'email':
                    $data->where('users.email', 'like', "%{$request->post('ach_filter')}%");
                    break;
            }
        }
        return MyHelper::checkGet($data->paginate());
    }

    public function reportMembership(Request $request)
    {
        $data = Membership::select('memberships.*', DB::raw('COALESCE((
            SELECT COUNT(*) from users_memberships
            WHERE users_memberships.id_membership = memberships.id_membership
        ), 0 ) AS total_user'));
        if ($request->post('keyword')) {
            $data->where('memberships.name', 'like', "%{$request->post('keyword')}%");
        }
        return MyHelper::checkGet($data->paginate());
    }

    public function category(Request $request)
    {
        return [
            'status' => 'success',
            'data' => AchievementCategory::get()->toArray(),
        ];
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create(Request $request)
    {
        $post = $request->json()->all();

        if (!file_exists($this->saveImage)) {
            mkdir($this->saveImage, 0777, true);
        }
        if (!file_exists($this->saveImageDetail)) {
            mkdir($this->saveImageDetail, 0777, true);
        }

        DB::beginTransaction();

        if (isset($request['id_achievement_group'])) {
            $request->validate([
                'detail.*.name' => 'required',
                'detail.*.logo_badge' => 'required',
            ]);
        } else {
            $request->validate([
                'category.name' => 'required',
                'group.name' => 'required',
                'group.publish_start' => 'required',
                'group.date_start' => 'required',
                'group.description' => 'required',
                'rule_total' => 'required',
                'group.logo_badge_default' => 'required',
                'detail.*.name' => 'required',
                'detail.*.logo_badge' => 'required',
            ]);

            $upload = MyHelper::uploadPhotoStrict($post['group']['logo_badge_default'], $this->saveImage, 500, 500);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $post['group']['logo_badge_default'] = $upload['path'];
            } else {
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Failed to upload image'],
                ]);
            }

            try {
                $category = AchievementCategory::where('name', $post['category']['name']);
                if ($category->exists()) {
                    $post['group']['id_achievement_category'] = $category->first()->id_achievement_category;
                } else {
                    $post['group']['id_achievement_category'] = AchievementCategory::create($post['category'])->id_achievement_category;
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Get or Add Category Achievement Failed',
                    'error' => $e->getMessage(),
                ]);
            }

            $post['group']['publish_start'] = date('Y-m-d H:i', strtotime($post['group']['publish_start']));
            $post['group']['date_start'] = date('Y-m-d H:i', strtotime($post['group']['date_start']));
            if (!is_null($post['group']['publish_end'])) {
                $post['group']['publish_end'] = date('Y-m-d H:i', strtotime($post['group']['publish_end']));
            }
            if (!is_null($post['group']['date_end'])) {
                $post['group']['date_end'] = date('Y-m-d H:i', strtotime($post['group']['date_end']));
            }

            try {
                $group = AchievementGroup::create($post['group']);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Add Achievement Group Failed',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (isset($post['detail'])) {
            try {
                foreach ($post['detail'] as $key => $value) {
                    $uploadDetail = MyHelper::uploadPhotoStrict($value['logo_badge'], $this->saveImageDetail, 500, 500);

                    if (isset($uploadDetail['status']) && $uploadDetail['status'] == "success") {
                        $value['logo_badge'] = $uploadDetail['path'];
                    } else {
                        return response()->json([
                            'status' => 'fail',
                            'messages' => ['Failed to upload image'],
                        ]);
                    }

                    if (isset($request['id_achievement_group'])) {
                        $value['id_achievement_group'] = MyHelper::decSlug($request['id_achievement_group']);
                    } else {
                        $value['id_achievement_group'] = MyHelper::decSlug($group->id_achievement_group);
                    }

                    switch ($post['rule_total']) {
                        case 'nominal_transaction':
                            $value['trx_nominal'] = $value['value_total'];
                            $value['id_product'] = $post['rule']['id_product'];
                            $value['product_total'] = $post['rule']['product_total'];
                            $value['id_outlet'] = $post['rule']['id_outlet'];
                            $value['id_province'] = $post['rule']['id_province'];
                            unset($value['value_total']);
                            break;
                        case 'total_product':
                            $value['product_total'] = $value['value_total'];
                            $value['trx_nominal'] = $post['rule']['trx_nominal'];
                            $value['id_product'] = $post['rule']['id_product'];
                            $value['id_outlet'] = $post['rule']['id_outlet'];
                            $value['id_province'] = $post['rule']['id_province'];
                            unset($value['value_total']);
                            break;
                        case 'total_transaction':
                            $value['trx_total'] = $value['value_total'];
                            $value['trx_nominal'] = $post['rule']['trx_nominal'];
                            $value['id_product'] = $post['rule']['id_product'];
                            $value['product_total'] = $post['rule']['product_total'];
                            $value['id_outlet'] = $post['rule']['id_outlet'];
                            $value['id_province'] = $post['rule']['id_province'];
                            unset($value['value_total']);
                            break;
                        case 'total_outlet':
                            $value['different_outlet'] = $value['value_total'];
                            $value['trx_nominal'] = $post['rule']['trx_nominal'];
                            $value['id_product'] = $post['rule']['id_product'];
                            $value['product_total'] = $post['rule']['product_total'];
                            $value['id_outlet'] = $post['rule']['id_outlet'];
                            $value['id_province'] = $post['rule']['id_province'];
                            unset($value['value_total']);
                            break;
                        case 'total_province':
                            $value['different_province'] = $value['value_total'];
                            $value['trx_nominal'] = $post['rule']['trx_nominal'];
                            $value['id_product'] = $post['rule']['id_product'];
                            $value['product_total'] = $post['rule']['product_total'];
                            $value['id_outlet'] = $post['rule']['id_outlet'];
                            $value['id_province'] = $post['rule']['id_province'];
                            unset($value['value_total']);
                            break;
                    }
                    $achievementDetail[$key] = AchievementDetail::create($value);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Add Achievement Detail Failed',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (isset($post['group']) && date('Y-m-d H:i', strtotime($post['group']['date_start'])) <= date('Y-m-d H:i')) {
            $getUser = User::select('id')->get()->toArray();
            foreach ($getUser as $key => $value) {
                self::checkAchievement($value['id'], $achievementDetail, $post['rule_total']);
            }
        }

        DB::commit();

        if (isset($request['id_achievement_group'])) {
            return response()->json([
                'status' => 'success',
                'message' => 'Add Achievement Success',
                'data' => $request['id_achievement_group'],
            ]);
        } else {
            return response()->json([
                'status' => 'success',
                'message' => 'Add Achievement Success',
                'data' => $group->id_achievement_group,
            ]);
        }
    }

    public static function checkAchievement($idUser, $detailAchievement, $rules)
    {
        $getUser = User::where('id', $idUser)->first();

        if ($getUser->complete_profile != 0) {
            foreach ($detailAchievement as $achievement) {
                switch ($rules) {
                    case 'nominal_transaction':
                        $sumTrx = Transaction::select(DB::raw('COALESCE(SUM(transactions.transaction_grandtotal), 0) as total'))
                        ->where('transactions.id_user', $idUser);

                        if (!is_null($achievement['id_product'])) {
                            $sumTrx = $sumTrx->join('transaction_products', 'transactions.id_transaction', 'transaction_products.id_transaction')
                            ->where('transaction_products.id_product', $achievement['id_product']);
                            if (!is_null($achievement['product_total'])) {
                                $sumTrx = $sumTrx->where('transaction_products.transaction_product_qty','>=', $achievement['product_total']);
                            }
                            $sumTrx = $sumTrx->groupBy('transaction_products.id_product');
                        }
                        if (!is_null($achievement['id_outlet'])) {
                            $sumTrx = $sumTrx->where('transactions.id_outlet', $achievement['id_outlet']);
                        }
                        if (!is_null($achievement['id_province'])) {
                            $sumTrx = $sumTrx->join('outlets', 'transactions.id_outlet', 'outlets.id_outlet')
                            ->join('cities', 'outlets.id_city', 'cities.id_city')
                            ->where('cities.id_province', $achievement['id_province']);
                        }

                        $sumTrx = $sumTrx->first();

                        if ($sumTrx) {
                            $sumTrx = $sumTrx->total;
                        } else {
                            $sumTrx = 0;
                        }

                        if ((int) $sumTrx >= (int) $achievement['trx_nominal']) {
                            AchievementProgress::updateOrCreate([
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                            ], [
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                                'progress' => $achievement['trx_nominal'],
                                'end_progress' => $achievement['trx_nominal'],
                            ]);
                        } else {
                            $ach_progress = AchievementGroup::select(DB::raw('SUM(achievement_progress.end_progress - achievement_progress.progress) as total'))
                            ->join('achievement_details', 'achievement_groups.id_achievement_group', 'achievement_details.id_achievement_group')
                            ->join('achievement_progress', 'achievement_details.id_achievement_detail', 'achievement_progress.id_achievement_detail')
                            ->where([
                                'achievement_progress.id_user'              => $idUser,
                                'achievement_groups.id_achievement_group'   => $achievement['id_achievement_group']
                            ])
                            ->groupBy('achievement_details.id_achievement_detail')
                            ->orderBy('achievement_details.id_achievement_detail', 'DESC')->first();

                            if ($ach_progress) {
                                if ((int) $ach_progress->total == 0) {
                                    AchievementProgress::updateOrCreate([
                                        'id_achievement_detail' => $achievement['id_achievement_detail'],
                                        'id_user' => $idUser,
                                    ], [
                                        'id_achievement_detail' => $achievement['id_achievement_detail'],
                                        'id_user' => $idUser,
                                        'progress' => $sumTrx,
                                        'end_progress' => $achievement['trx_nominal'],
                                    ]);
                                }
                            } else {
                                AchievementProgress::updateOrCreate([
                                    'id_achievement_detail' => $achievement['id_achievement_detail'],
                                    'id_user' => $idUser,
                                ], [
                                    'id_achievement_detail' => $achievement['id_achievement_detail'],
                                    'id_user' => $idUser,
                                    'progress' => $sumTrx,
                                    'end_progress' => $achievement['trx_nominal'],
                                ]);
                            }
                        }
                        break;
                    case 'total_transaction':
                        $countTrx = Transaction::select(DB::raw('COALESCE(COUNT(transactions.id_transaction), 0) as total'))
                        ->where('transactions.id_user', $idUser);

                        if (!is_null($achievement['trx_nominal'])) {
                            $countTrx = $countTrx->where('transactions.transaction_grandtotal', '>=', $achievement['trx_nominal']);
                        }
                        if (!is_null($achievement['id_product'])) {
                            $countTrx = $countTrx->join('transaction_products', 'transactions.id_transaction', 'transaction_products.id_transaction')
                            ->where('transaction_products.id_product', $achievement['id_product']);
                            if (!is_null($achievement['product_total'])) {
                                $countTrx = $countTrx->where('transaction_products.transaction_product_qty','>=', $achievement['product_total']);
                            }
                        }
                        if (!is_null($achievement['id_outlet'])) {
                            $countTrx = $countTrx->where('transactions.id_outlet', $achievement['id_outlet']);
                        }
                        if (!is_null($achievement['id_province'])) {
                            $countTrx = $countTrx->join('outlets', 'transactions.id_outlet', 'outlets.id_outlet')
                            ->join('cities', 'outlets.id_city', 'cities.id_city')
                            ->where('cities.id_province', $achievement['id_province']);
                        }

                        $countTrx = $countTrx->first();

                        if ($countTrx) {
                            $countTrx = $countTrx->total;
                        } else {
                            $countTrx = 0;
                        }

                        if ((int) $countTrx >= (int) $achievement['trx_total']) {
                            AchievementProgress::updateOrCreate([
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                            ], [
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                                'progress' => $achievement['trx_total'],
                                'end_progress' => $achievement['trx_total'],
                            ]);
                        } else {
                            $ach_progress = AchievementGroup::select(DB::raw('COALESCE(SUM(achievement_progress.end_progress - achievement_progress.progress), 0) as total'))
                            ->join('achievement_details', 'achievement_groups.id_achievement_group', 'achievement_details.id_achievement_group')
                            ->join('achievement_progress', 'achievement_details.id_achievement_detail', 'achievement_progress.id_achievement_detail')
                            ->where([
                                'achievement_progress.id_user'              => $idUser,
                                'achievement_groups.id_achievement_group'   => $achievement['id_achievement_group']
                            ])
                            ->groupBy('achievement_details.id_achievement_detail')
                            ->orderBy('achievement_details.id_achievement_detail', 'DESC')->first();
                            
                            if ($ach_progress) {
                                if ((int) $ach_progress->total == 0) {
                                    AchievementProgress::updateOrCreate([
                                        'id_achievement_detail' => $achievement['id_achievement_detail'],
                                        'id_user' => $idUser,
                                    ], [
                                        'id_achievement_detail' => $achievement['id_achievement_detail'],
                                        'id_user' => $idUser,
                                        'progress' => (int) $countTrx,
                                        'end_progress' => $achievement['trx_total'],
                                    ]);
                                }
                            } else {
                                AchievementProgress::updateOrCreate([
                                    'id_achievement_detail' => $achievement['id_achievement_detail'],
                                    'id_user' => $idUser,
                                ], [
                                    'id_achievement_detail' => $achievement['id_achievement_detail'],
                                    'id_user' => $idUser,
                                    'progress' => (int) $countTrx,
                                    'end_progress' => $achievement['trx_total'],
                                ]);
                            }
                        }
                        break;
                    case 'total_product':
                        $sumProd = Transaction::select(DB::raw('COALESCE(SUM(transaction_products.transaction_product_qty), 0) as total'))
                        ->where('transactions.id_user', $idUser)
                        ->join('transaction_products', 'transactions.id_transaction', 'transaction_products.id_transaction');

                        if (!is_null($achievement['trx_nominal'])) {
                            $sumProd = $sumProd->where('transactions.transaction_grandtotal', '>=', $achievement['trx_nominal']);
                        }
                        if (!is_null($achievement['id_outlet'])) {
                            $sumProd = $sumProd->where('transactions.id_outlet', $achievement['id_outlet']);
                        }
                        if (!is_null($achievement['id_province'])) {
                            $sumProd = $sumProd->join('outlets', 'transactions.id_outlet', 'outlets.id_outlet')
                            ->join('cities', 'outlets.id_city', 'cities.id_city')
                            ->where('cities.id_province', $achievement['id_province']);
                        }
                        if (!is_null($achievement['id_product'])) {
                            $sumProd = $sumProd->where('transaction_products.id_product', $achievement['id_product'])
                            ->groupBy('transaction_products.id_product');
                        }

                        $sumProd = $sumProd->first();

                        if ($sumProd) {
                            $sumProd = $sumProd->total;
                        } else {
                            $sumProd = 0;
                        }
    
                        if ((int) $sumProd >= (int) $achievement['product_total']) {
                            AchievementProgress::updateOrCreate([
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                            ], [
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                                'progress' => $achievement['product_total'],
                                'end_progress' => $achievement['product_total'],
                            ]);
                        } else {
                            $ach_progress = AchievementGroup::select(DB::raw('SUM(achievement_progress.end_progress - achievement_progress.progress) as total'))
                            ->join('achievement_details', 'achievement_groups.id_achievement_group', 'achievement_details.id_achievement_group')
                            ->join('achievement_progress', 'achievement_details.id_achievement_detail', 'achievement_progress.id_achievement_detail')
                            ->where([
                                'achievement_progress.id_user'              => $idUser,
                                'achievement_groups.id_achievement_group'   => $achievement['id_achievement_group']
                            ])
                            ->groupBy('achievement_details.id_achievement_detail')
                            ->orderBy('achievement_details.id_achievement_detail', 'DESC')->first();

                            if ($ach_progress) {
                                if ((int) $ach_progress->total == 0) {
                                    AchievementProgress::updateOrCreate([
                                        'id_achievement_detail' => $achievement['id_achievement_detail'],
                                        'id_user' => $idUser,
                                    ], [
                                        'id_achievement_detail' => $achievement['id_achievement_detail'],
                                        'id_user' => $idUser,
                                        'progress' => (int) $sumProd,
                                        'end_progress' => $achievement['product_total'],
                                    ]);
                                }
                            } else {
                                AchievementProgress::updateOrCreate([
                                    'id_achievement_detail' => $achievement['id_achievement_detail'],
                                    'id_user' => $idUser,
                                ], [
                                    'id_achievement_detail' => $achievement['id_achievement_detail'],
                                    'id_user' => $idUser,
                                    'progress' => (int) $sumProd,
                                    'end_progress' => $achievement['product_total'],
                                ]);
                            }
                        }
                        break;
                    case 'total_outlet':
                        $countOutlet = Transaction::select(DB::raw('COALESCE(COUNT(DISTINCT transactions.id_outlet), 0) as total'))
                        ->where('transactions.id_user', $idUser);

                        if (!is_null($achievement['trx_nominal'])) {
                            $countOutlet = $countOutlet->where('transactions.transaction_grandtotal', '>=', $achievement['trx_nominal']);
                        }
                        if (!is_null($achievement['id_province'])) {
                            $countOutlet = $countOutlet->join('outlets', 'transactions.id_outlet', 'outlets.id_outlet')
                            ->join('cities', 'outlets.id_city', 'cities.id_city')
                            ->where('cities.id_province', $achievement['id_province']);
                        }
                        if (!is_null($achievement['id_product'])) {
                            $countOutlet = $countOutlet->join('transaction_products', 'transactions.id_transaction', 'transaction_products.id_transaction')
                            ->where('transaction_products.id_product', $achievement['id_product']);
                            if (!is_null($achievement['product_total'])) {
                                $countOutlet = $countOutlet->where('transaction_products.transaction_product_qty', '>=', $achievement['product_total']);
                            }
                        }

                        $countOutlet = $countOutlet->first();

                        if ($countOutlet) {
                            $countOutlet = $countOutlet->total;
                        } else {
                            $countOutlet = 0;
                        }
    
                        if ((int) $countOutlet >= (int) $achievement['different_outlet']) {
                            AchievementProgress::updateOrCreate([
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                            ], [
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                                'progress' => $achievement['different_outlet'],
                                'end_progress' => $achievement['different_outlet'],
                            ]);
                        } else {
                            $ach_progress = AchievementGroup::select(DB::raw('SUM(achievement_progress.end_progress - achievement_progress.progress) as total'))
                            ->join('achievement_details', 'achievement_groups.id_achievement_group', 'achievement_details.id_achievement_group')
                            ->join('achievement_progress', 'achievement_details.id_achievement_detail', 'achievement_progress.id_achievement_detail')
                            ->where([
                                'achievement_progress.id_user'              => $idUser,
                                'achievement_groups.id_achievement_group'   => $achievement['id_achievement_group']
                            ])
                            ->groupBy('achievement_details.id_achievement_detail')
                            ->orderBy('achievement_details.id_achievement_detail', 'DESC')->first();

                            if ($ach_progress) {
                                if ((int) $ach_progress->total == 0) {
                                    AchievementProgress::updateOrCreate([
                                        'id_achievement_detail' => $achievement['id_achievement_detail'],
                                        'id_user' => $idUser,
                                    ], [
                                        'id_achievement_detail' => $achievement['id_achievement_detail'],
                                        'id_user' => $idUser,
                                        'progress' => (int) $countOutlet,
                                        'end_progress' => $achievement['different_outlet'],
                                    ]);
                                }
                            } else {
                                AchievementProgress::updateOrCreate([
                                    'id_achievement_detail' => $achievement['id_achievement_detail'],
                                    'id_user' => $idUser,
                                ], [
                                    'id_achievement_detail' => $achievement['id_achievement_detail'],
                                    'id_user' => $idUser,
                                    'progress' => (int) $countOutlet,
                                    'end_progress' => $achievement['different_outlet'],
                                ]);
                            }
                        }
                        break;
                    case 'total_province':
                        $countProvince = Transaction::select(DB::raw('COALESCE(COUNT(DISTINCT cities.id_province), 0) as total'))
                        ->where('transactions.id_user', $idUser)
                        ->join('outlets', 'transactions.id_outlet', 'outlets.id_outlet')
                        ->join('cities', 'outlets.id_city', 'cities.id_city');
    
                        if (!is_null($achievement['trx_nominal'])) {
                            $countProvince = $countProvince->where('transactions.transaction_grandtotal', '>=', $achievement['trx_nominal']);
                        }
                        if (!is_null($achievement['id_outlet'])) {
                            $countProvince = $countProvince->where('transactions.id_outlet', $achievement['id_outlet']);
                        }
                        if (!is_null($achievement['id_product'])) {
                            $countProvince = $countProvince->join('transaction_products', 'transactions.id_transaction', 'transaction_products.id_transaction')
                            ->where('transaction_products.id_product', $achievement['id_product']);
                            if (!is_null($achievement['product_total'])) {
                                $countProvince = $countProvince->where('transaction_products.transaction_product_qty','>=', $achievement['product_total']);
                            }
                        }
    
                        $countProvince = $countProvince->first();

                        if ($countProvince) {
                            $countProvince = $countProvince->total;
                        } else {
                            $countProvince = 0;
                        }
    
                        if ((int) $countProvince >= (int) $achievement['different_province']) {
                            AchievementProgress::updateOrCreate([
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                            ], [
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                                'progress' => $achievement['different_province'],
                                'end_progress' => $achievement['different_province'],
                            ]);
                        } else {
                            $ach_progress = AchievementGroup::select(DB::raw('SUM(achievement_progress.end_progress - achievement_progress.progress) as total'))
                            ->join('achievement_details', 'achievement_groups.id_achievement_group', 'achievement_details.id_achievement_group')
                            ->join('achievement_progress', 'achievement_details.id_achievement_detail', 'achievement_progress.id_achievement_detail')
                            ->where([
                                'achievement_progress.id_user'              => $idUser,
                                'achievement_groups.id_achievement_group'   => $achievement['id_achievement_group']
                            ])
                            ->groupBy('achievement_details.id_achievement_detail')
                            ->orderBy('achievement_details.id_achievement_detail', 'DESC')->first();

                            if ($ach_progress) {
                                if ((int) $ach_progress->total == 0) {
                                    AchievementProgress::updateOrCreate([
                                        'id_achievement_detail' => $achievement['id_achievement_detail'],
                                        'id_user' => $idUser,
                                    ], [
                                        'id_achievement_detail' => $achievement['id_achievement_detail'],
                                        'id_user' => $idUser,
                                        'progress' => (int) $countProvince,
                                        'end_progress' => $achievement['different_province'],
                                    ]);
                                }
                            } else {
                                AchievementProgress::updateOrCreate([
                                    'id_achievement_detail' => $achievement['id_achievement_detail'],
                                    'id_user' => $idUser,
                                ], [
                                    'id_achievement_detail' => $achievement['id_achievement_detail'],
                                    'id_user' => $idUser,
                                    'progress' => (int) $countProvince,
                                    'end_progress' => $achievement['different_province'],
                                ]);
                            }
                        }
                        break;
                }

                $ach_progress = AchievementGroup::select(DB::raw('SUM(achievement_progress.end_progress - achievement_progress.progress) as total'))
                ->join('achievement_details', 'achievement_groups.id_achievement_group', 'achievement_details.id_achievement_group')
                ->join('achievement_progress', 'achievement_details.id_achievement_detail', 'achievement_progress.id_achievement_detail')
                ->where([
                    'achievement_progress.id_user'              => $idUser,
                    'achievement_groups.id_achievement_group'   => $achievement['id_achievement_group']
                ])
                ->groupBy('achievement_details.id_achievement_detail')
                ->orderBy('achievement_details.id_achievement_detail', 'DESC')->first();

                if ($ach_progress) {
                    if ((int) $ach_progress->total == 0) {
                        AchievementUser::updateOrCreate([
                            'id_achievement_detail' => $achievement['id_achievement_detail'],
                            'id_user' => $idUser,
                        ], [
                            'id_achievement_detail' => $achievement['id_achievement_detail'],
                            'id_user' => $idUser,
                            'json_rule' => json_encode([
                                'id_product' => $achievement['id_product'],
                                'product_total' => $achievement['product_total'],
                                'trx_nominal' => $achievement['trx_nominal'],
                                'trx_total' => $achievement['trx_total'],
                                'id_outlet' => $achievement['id_outlet'],
                                'different_outlet' => $achievement['different_outlet'],
                                'id_province' => $achievement['id_province'],
                                'different_province' => $achievement['different_province'],
                            ]),
                            'json_rule_enc' => MyHelper::encrypt2019(json_encode([
                                'id_product' => $achievement['id_product'],
                                'product_total' => $achievement['product_total'],
                                'trx_nominal' => $achievement['trx_nominal'],
                                'trx_total' => $achievement['trx_total'],
                                'id_outlet' => $achievement['id_outlet'],
                                'different_outlet' => $achievement['different_outlet'],
                                'id_province' => $achievement['id_province'],
                                'different_province' => $achievement['different_province'],
                            ])),
                            'date' => date('Y-m-d H:i:s'),
                        ]);
                    }
                } else {
                    AchievementUser::updateOrCreate([
                        'id_achievement_detail' => $achievement['id_achievement_detail'],
                        'id_user' => $idUser,
                    ], [
                        'id_achievement_detail' => $achievement['id_achievement_detail'],
                        'id_user' => $idUser,
                        'json_rule' => json_encode([
                            'id_product' => $achievement['id_product'],
                            'product_total' => $achievement['product_total'],
                            'trx_nominal' => $achievement['trx_nominal'],
                            'trx_total' => $achievement['trx_total'],
                            'id_outlet' => $achievement['id_outlet'],
                            'different_outlet' => $achievement['different_outlet'],
                            'id_province' => $achievement['id_province'],
                            'different_province' => $achievement['different_province'],
                        ]),
                        'json_rule_enc' => MyHelper::encrypt2019(json_encode([
                            'id_product' => $achievement['id_product'],
                            'product_total' => $achievement['product_total'],
                            'trx_nominal' => $achievement['trx_nominal'],
                            'trx_total' => $achievement['trx_total'],
                            'id_outlet' => $achievement['id_outlet'],
                            'different_outlet' => $achievement['different_outlet'],
                            'id_province' => $achievement['id_province'],
                            'different_province' => $achievement['different_province'],
                        ])),
                        'date' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
        return ['status' => 'success'];
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function detailAjax(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Add Achievement Success',
            'data' => AchievementGroup::where('id_achievement_group', MyHelper::decSlug($request['id_achievement_group']))->first(),
        ]);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show(Request $request)
    {
        try {
            $data['group'] = AchievementGroup::where('id_achievement_group', MyHelper::decSlug($request['id_achievement_group']))->first();
            $data['category'] = AchievementCategory::select('name')->where('id_achievement_category', $data['group']->id_achievement_category)->first();
            $data['detail'] = AchievementDetail::with('product', 'outlet', 'province')->where('id_achievement_group', MyHelper::decSlug($request['id_achievement_group']))->get()->toArray();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'fail',
                'message' => 'Get Achievement Detail Failed',
                'error' => $e->getMessage(),
            ]);
        }

        $data['group']['logo_badge_default'] = config('url.storage_url_api') . $data['group']['logo_badge_default'];
        foreach ($data['detail'] as $key => $value) {
            $data['detail'][$key]['logo_badge'] = config('url.storage_url_api') . $value['logo_badge'];
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('achievement::edit');
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

        if (isset($post['logo_badge'])) {
            $uploadDetail = MyHelper::uploadPhotoStrict($post['logo_badge'], $this->saveImageDetail, 500, 500);

            if (isset($uploadDetail['status']) && $uploadDetail['status'] == "success") {
                $post['logo_badge'] = $uploadDetail['path'];
            } else {
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Failed to upload image'],
                ]);
            }
        }

        DB::beginTransaction();
        try {
            AchievementDetail::where('id_achievement_detail', $post['id_achievement_detail'])->update($post);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'fail',
                'message' => 'Update Achievement Detail Failed',
                'error' => $e->getMessage(),
            ]);
        }
        DB::commit();

        return response()->json([
            'status' => 'success',
        ]);
    }

    public function updateAch(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['group']['logo_badge_default'])) {
            $uploadDetail = MyHelper::uploadPhotoStrict($post['group']['logo_badge_default'], $this->saveImageDetail, 500, 500);

            if (isset($uploadDetail['status']) && $uploadDetail['status'] == "success") {
                $post['group']['logo_badge_default'] = $uploadDetail['path'];
            } else {
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Failed to upload image'],
                ]);
            }
        }
        
        $post['group']['id_achievement_category'] = $post['category']['name'];

        DB::beginTransaction();
        try {
            AchievementGroup::where('id_achievement_group', MyHelper::decSlug($post['id_achievement_group']))->update($post['group']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'fail',
                'message' => 'Update Achievement Failed',
                'error' => $e->getMessage(),
            ]);
        }
        DB::commit();
        
        return response()->json([
            'status' => 'success',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        DB::beginTransaction();

        try {
            if (isset($request['id_achievement_group'])) {
                AchievementGroup::where('id_achievement_group', MyHelper::decSlug($request['id_achievement_group']))->delete();
            } else {
                AchievementDetail::where('id_achievement_detail', $request['id_achievement_detail'])->delete();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'fail',
                'message' => 'Get Achievement Detail Failed',
                'error' => $e->getMessage(),
            ]);
        }

        DB::commit();

        return response()->json([
            'status' => 'success',
        ]);
    }

    public function detailAchievement(Request $request)
    {
        $getAchievement = AchievementCategory::with('achievement_group')->get()->toArray();

        $totalProgress      = 0;
        $totalEndProgress   = 0;
        $kA                 = 0;
        foreach ($getAchievement as $category) {
            if (count($category['achievement_group']) > 0) {
                $result['category'][$kA] = [
                    'id_achievement_category' => $category['id_achievement_category'],
                    'name' => $category['name'],
                    'description' => $category['description']
                ];
                $catProgress    = 0;
                $catEndProgress = 0;
                foreach ($category['achievement_group'] as $keyAchGroup => $group) {
                    $result['category'][$kA]['achievement'][$keyAchGroup] = [
                        'id_achievement_group' => MyHelper::decSlug($group['id_achievement_group']),
                        'name' => $group['name'],
                        'logo_achievement' => config('url.storage_url_api') . $group['logo_badge_default'],
                        'description' => $group['description'],
                    ];

                    $getAchievementDetail = AchievementDetail::where([
                        'id_achievement_group' => MyHelper::decSlug($group['id_achievement_group']),
                    ])->get()->toArray();
                    $achProgress    = 0;
                    $achEndProgress = 0;
                    foreach ($getAchievementDetail as $keyAchDetail => $detail) {
                        $result['category'][$kA]['achievement'][$keyAchGroup]['badge'][$keyAchDetail] = [
                            'id_achievement_detail' => $detail['id_achievement_detail'],
                            'name' => $detail['name'],
                            'logo_badge' => config('url.storage_url_api') . $detail['logo_badge'],
                        ];
                        $getAchievementProgress = AchievementProgress::where([
                            'id_user' => Auth::user()->id,
                            'id_achievement_detail' => $detail['id_achievement_detail'],
                        ])->first();

                        if ($getAchievementProgress) {
                            $badgePercentProgress = ($getAchievementProgress->progress == 0) ? 0 : $getAchievementProgress->progress / $getAchievementProgress->end_progress;
                            $result['category'][$kA]['achievement'][$keyAchGroup]['badge'][$keyAchDetail]['progress']            = $getAchievementProgress->progress;
                            $result['category'][$kA]['achievement'][$keyAchGroup]['badge'][$keyAchDetail]['end_progress']        = $getAchievementProgress->end_progress;
                            $result['category'][$kA]['achievement'][$keyAchGroup]['badge'][$keyAchDetail]['progress_percent']    = $badgePercentProgress;
                        } else {
                            $badgePercentProgress = 0;
                            $result['category'][$kA]['achievement'][$keyAchGroup]['badge'][$keyAchDetail]['progress']            = 0;
                            switch ($group['order_by']) {
                                case 'nominal_transaction':
                                    $result['category'][$kA]['achievement'][$keyAchGroup]['badge'][$keyAchDetail]['end_progress']        = $detail['trx_nominal'];
                                    break;
                                case 'total_product':
                                    $result['category'][$kA]['achievement'][$keyAchGroup]['badge'][$keyAchDetail]['end_progress']        = $detail['product_total'];
                                    break;
                                case 'total_transaction':
                                    $result['category'][$kA]['achievement'][$keyAchGroup]['badge'][$keyAchDetail]['end_progress']        = $detail['trx_total'];
                                    break;
                                case 'total_outlet':
                                    $result['category'][$kA]['achievement'][$keyAchGroup]['badge'][$keyAchDetail]['end_progress']        = $detail['different_outlet'];
                                    break;
                                case 'total_province':
                                    $result['category'][$kA]['achievement'][$keyAchGroup]['badge'][$keyAchDetail]['end_progress']        = $detail['different_province'];
                                    break;
                            }
                            $result['category'][$kA]['achievement'][$keyAchGroup]['badge'][$keyAchDetail]['progress_percent']    = 0;
                        }
                        if ($badgePercentProgress == 1) {
                            $achProgress = $achProgress + 1;
                        }
                        $achEndProgress = $achEndProgress + 1;
                    }
                    $achPercentProgress = ($achProgress == 0) ? 0 : $achProgress / $achEndProgress;
                    $result['category'][$kA]['achievement'][$keyAchGroup]['progress']            = $achProgress;
                    $result['category'][$kA]['achievement'][$keyAchGroup]['end_progress']        = $achEndProgress;
                    $result['category'][$kA]['achievement'][$keyAchGroup]['progress_percent']    = $achPercentProgress;
                    $result['category'][$kA]['achievement'][$keyAchGroup]['progress_text']       = $group['progress_text'];

                    if ($achPercentProgress > 0) {
                        $catProgress = $catProgress + 1;
                    }
                    $catEndProgress = $catEndProgress + 1;

                    if ($achPercentProgress > 0) {
                        $totalProgress = $totalProgress + 1;
                    }
                    $totalEndProgress = $totalEndProgress + 1;
                }
                $result['category'][$kA]['progress']     = $catProgress;
                $result['category'][$kA]['end_progress'] = $catEndProgress;
                $kA++;
            }
        }

        $result['progress']     = $catProgress;
        $result['end_progress'] = $catEndProgress;
        return response()->json(MyHelper::checkGet($result));
    }
}
