<?php

namespace Modules\Achievement\Http\Controllers;

use App\Http\Models\Membership;
use App\Http\Models\Transaction;
use App\Http\Models\User;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Achievement\Entities\AchievementCategory;
use Modules\Achievement\Entities\AchievementDetail;
use Modules\Achievement\Entities\AchievementGroup;
use Modules\Achievement\Entities\AchievementOutletLog;
use Modules\Achievement\Entities\AchievementProductLog;
use Modules\Achievement\Entities\AchievementProgress;
use Modules\Achievement\Entities\AchievementProvinceLog;
use Modules\Achievement\Entities\AchievementUser;
use Modules\Achievement\Entities\AchievementUserLog;

class ApiReportAchievement extends Controller
{
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

        return response()->json(MyHelper::checkGet($data->paginate(25)));
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
                    if($getDataBadge){
                        foreach ($getDataBadge as $key => $value) {
                            $data[$key]['logo_badge'] = env('STORAGE_URL_API') . $value['logo_badge'];
                        }
                    }

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

            $length = 10;
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

        return MyHelper::checkGet($data->paginate(25));
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
                    })->paginate(25);

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

    function reportMembership(Request $request){
        $post = $request->json()->all();
        $data = Membership::where('membership_type', 'achievement')
            ->select('memberships.*', DB::raw('(Select COUNT(id) from users where users.id_membership = memberships.id_membership) as total_user'));

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject']) && !empty($row['parameter'])){
                        if($row['subject'] == 'membership_name'){
                            if($row['operator'] == '='){
                                $data->where('memberships.membership_name', $row['parameter']);
                            }else{
                                $data->where('memberships.membership_name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'membership_rule'){
                            $data->where('memberships.min_total_achievement', $row['operator'], $row['parameter']);
                        }

                        if($row['subject'] == 'total_user'){
                            $data->whereIn('memberships.id_membership', function ($sub) use ($row){
                                $sub->select('u.id_membership')->from('users as u')
                                    ->groupBy('u.id_membership')
                                    ->havingRaw('COUNT(u.id) '.$row['operator'].' '.$row['parameter']);
                            });
                        }

                    }
                }
            }else{
                $data->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject']) && !empty($row['parameter'])){
                            if($row['subject'] == 'membership_name'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('memberships.membership_name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('memberships.membership_name', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'membership_rule'){
                                $subquery->orWhere('memberships.min_total_achievement', $row['operator'], $row['parameter']);
                            }

                            if($row['subject'] == 'total_user'){
                                $subquery->orWhereIn('memberships.id_membership', function ($sub) use ($row){
                                    $sub->select('u.id_membership')->from('users as u')
                                        ->groupBy('u.id_membership')
                                        ->havingRaw('COUNT(u.id) '.$row['operator'].' '.$row['parameter']);
                                });
                            }
                        }
                    }
                });
            }
        }

        $data = $data->paginate(25);

        if($data){
            foreach ($data as $key => $value) {
                $data[$key]['membership_image'] = env('STORAGE_URL_API') . $value['membership_image'];
            }
        }
        return response()->json(MyHelper::checkGet($data));
    }

    function reportDetailMembership(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_membership']) && !empty($post['id_membership'])){
            $data = Membership::where('id_membership', $post['id_membership'])->first();
            if($data){
                $data['membership_image'] = env('STORAGE_URL_API') . $data['membership_image'];
            }
            return response()->json(MyHelper::checkGet($data));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    function reportListUserMembership(Request $request){
        $post = $request->json()->all();

        if(isset($post['id_membership']) && !empty($post['id_membership'])){
            $length = 20;
            if(isset($post['length'])){
                $length = (int)$post['length'];
            }

            $data = User::where('id_membership', $post['id_membership'])
                ->select('users.name as 0', 'users.phone as 1', 'users.email as 2',
                    DB::raw('(Select DATE_FORMAT(um.created_at, "%d %b %Y %H:%i:%s") from users_memberships um where um.id_membership = users.id_membership and um.id_user = users.id order by um.created_at asc limit 1) as "3"'),
                    DB::raw('FORMAT(users.balance,0) as "4"'));

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

                            if($row['subject'] == 'email'){
                                if($row['operator'] == '='){
                                    $data->where('users.email', $row['parameter']);
                                }else{
                                    $data->where('users.email', 'like', '%'.$row['parameter'].'%');
                                }
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

                                if($row['subject'] == 'email'){
                                    if($row['operator'] == '='){
                                        $subquery->orWhere('users.email', $row['parameter']);
                                    }else{
                                        $subquery->orWhere('users.email', 'like', '%'.$row['parameter'].'%');
                                    }
                                }
                            }
                        }
                    });
                }
            }

            $data = $data->paginate($length);

            return response()->json(MyHelper::checkGet($data));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

}
