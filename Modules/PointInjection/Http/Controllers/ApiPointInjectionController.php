<?php

namespace Modules\PointInjection\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

use App\Http\Models\User;
use Modules\PointInjection\Entities\PointInjection;
use Modules\PointInjection\Entities\PointInjectionReport;
use Modules\PointInjection\Entities\PointInjectionUser;
use Modules\PointInjection\Jobs\UserPointInjection;
use App\Http\Models\Outlet;
use App\Http\Models\News;
use App\Http\Models\OauthAccessToken;
use App\Http\Models\LogBackendError;

use App\Lib\MyHelper;
use App\Lib\PushNotificationHelper;
use DB;
use Modules\PointInjection\Entities\PivotPointInjection;

class ApiPointInjectionController extends Controller
{
    function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->balance  = "Modules\Balance\Http\Controllers\BalanceController";
        $this->user     = "Modules\Users\Http\Controllers\ApiUser";
        $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }

    function generateDate($date)
    {
        $datetimearr    = explode(' - ', $date);
        $datearr        = explode(' ', $datetimearr[0]);
        $date           = date("Y-m-d", strtotime($datearr[2] . ", " . $datearr[1] . " " . $datearr[0]));
        return $date . " " . $datetimearr[1] . ":00";
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post = $request->json()->all();

        $query = PointInjection::with(['user']);
        $filter = null;
        $count = (new PointInjection)->newQuery();

        if (isset($post['order_field']) && isset($post['order_method'])) {
            $query = $query->orderBy($post['order_field'], $post['order_method']);
        }

        if (isset($post['campaign_name']) && $post['campaign_name'] != "") {
            $query = $query->where('campaign_name', 'like', '%' . $post['campaign_name'] . '%');
            $count = $count->where('campaign_name', 'like', '%' . $post['campaign_name'] . '%');
        }

        if ($request->json('rule')) {
            $filter = $this->filterList($query, $request);
            $this->filterList($count, $request);
        }

        $query = $query->skip($post['skip'])->take($post['take'])->get()->toArray();
        $count = $count->get()->count();

        if (isset($query) && !empty($query)) {
            $result = [
                'status'  => 'success',
                'result'  => $query,
                'count'  => $count
            ];
        } else {
            $result = [
                'status'  => 'fail',
                'message'  => ['No Campaign']
            ];
        }
        if ($filter) {
            $result = array_merge($result, $filter);
        }
        return response()->json($result);
    }

    protected function filterList($query, $request)
    {
        $allowed = array(
            'operator' => ['=', 'like', '<', '>', '<=', '>='],
            'subject' => ['campaign_name', 'promo_title', 'code_type', 'prefix_code', 'number_last_code', 'total_code', 'date_start', 'date_end', 'is_all_outlet', 'promo_type', 'used_code', 'id_outlet', 'id_product', 'id_user'],
            'mainSubject' => ['campaign_name', 'promo_title', 'code_type', 'prefix_code', 'number_last_code', 'total_code', 'date_start', 'date_end', 'is_all_outlet', 'promo_type', 'used_code']
        );
        $request->validate([
            'operator' => 'required|in:or,and',
            'rule.*.subject' => 'required|in:' . implode(',', $allowed['subject']),
            'rule.*.operator' => 'in:' . implode(',', $allowed['operator']),
            'rule.*.parameter' => 'required'
        ]);
        $return = [];
        $where = $request->json('operator') == 'or' ? 'orWhere' : 'where';
        if ($request->json('date_start')) {
            $query->where('date_start', '>=', $request->json('date_start'));
        }
        if ($request->json('date_end')) {
            $query->where('date_end', '<=', $request->json('date_end'));
        }
        $rule = $request->json('rule');
        foreach ($rule as $value) {
            if (in_array($value['subject'], $allowed['mainSubject'])) {
                if (!in_array($value['subject'], $allowed['subject'])) {
                    continue;
                }
                if (!(isset($value['operator']) && $value['operator'] && in_array($value['operator'], $allowed['operator']))) {
                    $value['operator'] = '=';
                }
                if ($value['operator'] == 'like') {
                    $query->$where($value['subject'], $value['operator'], '%' . $value['parameter'] . '%');
                } else {
                    $query->$where($value['subject'], $value['operator'], $value['parameter']);
                }
            } else {
                switch ($value['subject']) {
                    case 'id_outlet':
                        if ($value['parameter'] == '0') {
                            $query->$where('is_all_outlet', '1');
                        } else {
                            $query->leftJoin('promo_campaign_outlets', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_outlets.id_promo_campaign');
                            $query->$where(function ($query) use ($value) {
                                $query->where('promo_campaign_outlets.id_outlet', $value['parameter']);
                                $query->orWhere('is_all_outlet', '1');
                            });
                        }
                        break;

                    case 'id_user':
                        $query->leftJoin('promo_campaign_user_filters', 'promo_campaign_user_filters.id_promo_campaign', '=', 'promo_campaigns.id_promo_campaign');
                        switch ($value['parameter']) {
                            case 'all user':
                                $query->$where('promo_campaign_user_filters.subject', 'all_user');
                                break;

                            case 'new user':
                                $query->$where(function ($query) {
                                    $query->where('promo_campaign_user_filters.subject', 'count_transaction');
                                    $query->where('promo_campaign_user_filters.parameter', '0');
                                });
                                break;

                            case 'existing user':
                                $query->$where(function ($query) {
                                    $query->where('promo_campaign_user_filters.subject', 'count_transaction');
                                    $query->where('promo_campaign_user_filters.parameter', '1');
                                });
                                break;

                            default:
                                # code...
                                break;
                        }
                        break;

                    case 'id_product':
                        $query->leftJoin('promo_campaign_buyxgety_product_requirements', 'promo_campaign_buyxgety_product_requirements.id_promo_campaign', '=', 'promo_campaigns.id_promo_campaign');
                        $query->leftJoin('promo_campaign_product_discounts', 'promo_campaign_product_discounts.id_promo_campaign', '=', 'promo_campaigns.id_promo_campaign');
                        $query->leftJoin('promo_campaign_tier_discount_products', 'promo_campaign_tier_discount_products.id_promo_campaign', '=', 'promo_campaigns.id_promo_campaign');
                        if ($value['parameter'] == '0') {
                            $query->$where(function ($query) {
                                $query->where('promo_type', 'Product discount');
                                $query->where('promo_campaign_product_discounts.id_product', null);
                            });
                        } else {
                            $query->$where(DB::raw('IF(promo_type=\'Product discount\',promo_campaign_product_discounts.id_product,IF(promo_type=\'Tier discount\',promo_campaign_tier_discount_products.id_product,promo_campaign_buyxgety_product_requirements.id_product))'), $value['parameter']);
                        }
                        break;

                    default:
                        # code...
                        break;
                }
            }
            $return[] = $value;
        }
        return ['filter' => $return, 'filter_operator' => $request->json('operator')];
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('pointinjection::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();

        if (isset($post['id_point_injection'])) {
            $request->validate([
                'title'     => 'required',
                'send_type' => 'required'
            ]);
            $checkDate = PointInjection::where('id_point_injection', $post['id_point_injection'])->get()->first();
            if (implode(' ', [$checkDate['start_date'], $checkDate['send_time']]) <= date("Y-m-d H:i:s", strtotime("+10 minutes", strtotime(date("Y-m-d H:i:s"))))) {
                $result = [
                    'status'  => 'fail',
                    'message' => "Point Injection Started, You Can't Update!"
                ];
                DB::rollBack();
                return response()->json($result);
            }
        } else {
            $request->validate([
                'title'     => 'required',
                'send_type' => 'required',
                'list_user' => 'required'
            ]);

            $post['created_by'] = $user['id'];
        }

        foreach ($post as $key => $value) {
            if ($value == null) {
                unset($post[$key]);
            }
        }

        DB::beginTransaction();

        if ($post['send_type'] == 'One Time') {
            $request->validate([
                'point_injection_one_time_send_at'     => 'required',
                'total_point'                           => 'required'
            ]);
            $sendTime = $this->generateDate($post['point_injection_one_time_send_at']);
            if ($sendTime <= date("Y-m-d H:i:s")) {
                $post['start_date'] = date("Y-M-d");
                $post['send_time']  = date("H:00:00", strtotime("+1 hours"));
            } else {
                $sendTime = explode(' ', $this->generateDate($post['point_injection_one_time_send_at']));
                $post['start_date'] = $sendTime[0];
                $post['send_time']  = $sendTime[1];
            }
            $postPointInjection = [
                'title'         => $post['title'],
                'send_type'     => $post['send_type'],
                'start_date'    => $post['start_date'],
                'send_time'     => $post['send_time'],
                'point'         => $post['total_point'],
                'total_point'   => $post['total_point']
            ];
        } elseif ($post['send_type'] == 'Daily') {
            $request->validate([
                'point_injection_send_at'   => 'required',
                'duration_day'              => 'required',
                'point'                     => 'required'
            ]);
            $sendTime = $this->generateDate(implode(' - ', [$post['point_injection_send_at'], $post['point_injection_send_time']]));
            if ($sendTime <= date("Y-m-d H:i:s")) {
                $post['start_date'] = date("Y-m-d");
                $post['send_time']  = date("H:00:00", strtotime(explode(' ', $this->generateDate(implode(' - ', [$post['point_injection_send_at'], $post['point_injection_send_time']])))[1] . " +1 hours"));
            } else {
                $sendTime = explode(' ', $this->generateDate(implode(' - ', [$post['point_injection_send_at'], $post['point_injection_send_time']])));
                $post['start_date'] = $sendTime[0];
                $post['send_time']  = $sendTime[1];
            }
            $postPointInjection = [
                'title'         => $post['title'],
                'send_type'     => $post['send_type'],
                'start_date'    => $post['start_date'],
                'send_time'     => $post['send_time'],
                'duration'      => $post['duration_day'],
                'point'         => $post['point'],
                'total_point'   => $post['duration_day'] * $post['point']
            ];
        }

        if (isset($post['point_injection_media'])) {
            foreach ($post['point_injection_media'] as $key => $value) {
                if ($value == 'Push Notification') {
                    $request->validate([
                        'point_injection_push_subject'  => 'required',
                        'point_injection_push_content'  => 'required',
                        'point_injection_push_clickto'  => 'required',
                    ]);
                    $postMedia = [
                        'point_injection_media_push'    => 1,
                        'point_injection_push_subject'  => $post['point_injection_push_subject'],
                        'point_injection_push_content'  => $post['point_injection_push_content'],
                        'point_injection_push_clickto'  => $post['point_injection_push_clickto'],
                    ];
                    if (isset($post['point_injection_push_id_reference'])) {
                        $postMedia['point_injection_push_id_reference'] = $post['point_injection_push_id_reference'];
                    } else {
                        $postMedia['point_injection_push_id_reference'] = null;
                    }
                    if (isset($post['point_injection_push_link'])) {
                        $postMedia['point_injection_push_link'] = $post['point_injection_push_link'];
                    } else {
                        $postMedia['point_injection_push_link'] = null;
                    }
                    if (isset($post['point_injection_push_image'])) {
                        $upload = MyHelper::uploadPhoto($post['point_injection_push_image'], $path = 'img/push/', 600);

                        if ($upload['status'] == "success") {
                            $postMedia['point_injection_push_image'] = $upload['path'];
                        } else {
                            $result = [
                                'status'    => 'fail',
                                'messages'    => ['Update Push Notification Image failed.']
                            ];
                            return response()->json($result);
                        }
                    }
                }
            }
        } else {
            $postMedia = [
                'point_injection_media_push'        => 0,
                'point_injection_push_subject'      => null,
                'point_injection_push_content'      => null,
                'point_injection_push_clickto'      => null,
                'point_injection_push_id_reference' => null,
                'point_injection_push_link'         => null,
                'point_injection_push_image'        => null,
            ];
        }

        $postPointInjection = array_merge($postPointInjection, $postMedia);

        if (isset($post['id_point_injection'])) {
            if (!isset($post['point_injection_push_image'])) {
                $getMasterData = PointInjection::where('id_point_injection', $post['id_point_injection'])->get()->toArray()[0];
                $postMedia['point_injection_push_image'] = $getMasterData['point_injection_push_image'];
            }
            try {
                PointInjection::where('id_point_injection', $post['id_point_injection'])->updateWithUserstamps($postPointInjection);

                $subject = 'Update Point Injection';
                $content = '<table id="table-content">';
                $content .= '<tr>';
                $content .= '<td colspan="4">Info</td>';
                $content .= '</tr>';
                $content .= '<tr>';
                $content .= '<td>Point Injection Title</td>';
                $content .= '<td>'.$post['title'].'</td>';
                $content .= '</tr>';
                $content .= '<tr>';
                $content .= '<td>Point Injection Type Send</td>';
                $content .= '<td>'.$post['send_type'].'</td>';
                $content .= '</tr>';
                $content .= '<tr>';
                $content .= '<td>Date Time to Send</td>';
                if(isset($post['point_injection_send_at'])){
                    $content .= '<td>'.$post['point_injection_send_at'].' '.$post['point_injection_send_time'].'</td>';
                }else{
                    $content .= '<td>'.$post['point_injection_one_time_send_at'].'</td>';
                }
                $content .= '</tr>';
                $content .= '<tr>';
                $content .= '<td>Total Point</td>';
                $content .= '<td>'.number_format($post['total_point']??$post['point']).'</td>';
                $content .= '</tr>';
                $content .= '</table>';
                $send = app($this->autocrm)->sendForwardEmail('Point Injection', $subject, $content);
                $result = ['status'  => 'success'];
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Update Point Injection Failed'
                ];
                DB::rollBack();
                return response()->json($result);
            }
            $getUser = PointInjectionUser::where('id_point_injection', $post['id_point_injection'])->get()->toArray();
            if (isset($post['phone_number'])) {
                foreach ($post['phone_number'] as $value) {
                    $userData[] = User::where('phone', '=', $value)->get()->toArray()[0];
                }
                foreach ($userData as $key => $value) {
                    if (count($value) < 1) {
                        unset($userData[$key]);
                    } else {
                        $userData[$key] = ['id_point_injection' => $post['id_point_injection'], 'id_user' => $value['id'], 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
                        PointInjectionUser::updateOrCreate(['id_point_injection' => $post['id_point_injection'], 'id_user' => $value['id']]);
                    }
                }
                $userData = array_merge($getUser, $userData);
            } else {
                $userData = $getUser;
            }

            if ($post['send_type'] == 'One Time') {
                $postPointInjection['duration'] = 1;
            }
            $jobData = [];
            $index = 0;
            $countJobData = 0;
            for ($i = 0; $i < count($userData); $i++) {
                for ($n = 0; $n < $postPointInjection['duration']; $n++) {
                    if ($countJobData % 100 == 0) {
                        $pivotData = [];
                        $index++;
                    }
                    if (isset($postMedia)) {
                        $pivotData[] = [
                            'point_injection_media_push'        => $postMedia['point_injection_media_push'],
                            'point_injection_push_subject'      => $postMedia['point_injection_push_subject'],
                            'point_injection_push_content'      => $postMedia['point_injection_push_content'],
                            'point_injection_push_clickto'      => $postMedia['point_injection_push_clickto'],
                            'point_injection_push_id_reference' => $postMedia['point_injection_push_id_reference'],
                            'point_injection_push_link'         => $postMedia['point_injection_push_link'],
                            'point_injection_push_image'        => $postMedia['point_injection_push_image'],
                            'id_point_injection'                => $post['id_point_injection'],
                            'id_user'                           => $userData[$i]['id_user'],
                            'send_time'                         => implode(' ', [date('Y-m-d', strtotime($postPointInjection['start_date'] . " +" . $n . " days")), $postPointInjection['send_time']]),
                            'point'                             => $postPointInjection['point'],
                            'created_at'                        => date('Y-m-d H:i:s'),
                            'updated_at'                        => date('Y-m-d H:i:s')
                        ];
                    } else {
                        $pivotData[] = ['id_point_injection' => $postPointInjection['id_point_injection'], 'id_user' => $userData[$i]['id_user'], 'send_time' => implode(' ', [date('Y-m-d', strtotime($postPointInjection['start_date'] . " +" . $n . " days")), $postPointInjection['send_time']]), 'point' => $postPointInjection['point'], 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
                    }
                    $jobData[$index] = $pivotData;
                    $countJobData++;
                }
            }

            PivotPointInjection::where('id_point_injection', $post['id_point_injection'])->delete();
            foreach ($jobData as $value) {
                UserPointInjection::dispatch($value)->allOnConnection('database');
            }

            DB::commit();
            return response()->json(['status'  => 'success', 'result' => $post['id_point_injection']]);
        } else {
            if (!isset($post['point_injection_push_image'])) {
                $postMedia['point_injection_push_image'] = null;
            }
            $postPointInjection['created_by'] = $post['created_by'];
            try {
                $insertPointInjection = PointInjection::create($postPointInjection);
                $postPointInjection['id_point_injection'] = $insertPointInjection['id_point_injection'];

                $subject = 'Create Point Injection';
                $content = '<table id="table-content">';
                $content .= '<tr>';
                $content .= '<td colspan="4">Info</td>';
                $content .= '</tr>';
                $content .= '<tr>';
                $content .= '<td>Point Injection Title</td>';
                $content .= '<td>'.$post['title'].'</td>';
                $content .= '</tr>';
                $content .= '<tr>';
                $content .= '<td>Point Injection Type Send</td>';
                $content .= '<td>'.$post['send_type'].'</td>';
                $content .= '</tr>';
                $content .= '<tr>';
                $content .= '<td>Date Time to Send</td>';
                if(isset($post['point_injection_send_at'])){
                    $content .= '<td>'.$post['point_injection_send_at'].' '.$post['point_injection_send_time'].'</td>';
                }else{
                    $content .= '<td>'.$post['point_injection_one_time_send_at'].'</td>';
                }
                $content .= '</tr>';
                $content .= '<tr>';
                $content .= '<td>Total Point</td>';
                $content .= '<td>'.number_format($post['total_point']??$post['point']).'</td>';
                $content .= '</tr>';
                $content .= '</table>';
                $send = app($this->autocrm)->sendForwardEmail('Point Injection', $subject, $content);
                $result = ['status'  => 'success'];
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Create Point Injection Failed'
                ];
                DB::rollBack();
                return response()->json($result);
            }

            if ($post['list_user'] == 'Upload CSV' || $post['list_user'] == "Filter Phone") {
                $request->validate([
                    'phone_number'  => 'required'
                ]);
                foreach ($post['phone_number'] as $value) {
                    $userData[] = User::where('phone', '=', $value)->get()->toArray();
                }
                foreach ($userData as $key => $value) {
                    if (count($value) < 1) {
                        unset($userData[$key]);
                    } else {
                        $userData[$key] = [
                        	'id_point_injection' => $postPointInjection['id_point_injection'], 
                        	'id_user' => $value[0]['id'], 
                        	'created_at' => date('Y-m-d H:i:s'), 
                        	'updated_at' => date('Y-m-d H:i:s'),
				            'created_by' => Auth::id(),
				            'updated_by' => Auth::id()
                        ];
                    }
                }
            } elseif ($post['list_user'] == 'Filter User') {
                $users = app($this->user)->UserFilter($post['conditions']);
                if ($users['status'] == 'success') {
                    foreach ($users['result'] as $key => $value) {
                        $userData[$key] = [
                        	'id_point_injection' => $postPointInjection['id_point_injection'], 
                        	'id_user' => $value['id'], 
                        	'created_at' => date('Y-m-d H:i:s'), 
                        	'updated_at' => date('Y-m-d H:i:s'),
				            'created_by' => Auth::id(),
				            'updated_by' => Auth::id()
                        ];
                    }
                }
                $postRules = MyHelper::insertCondition('point_injection', $postPointInjection['id_point_injection'], $post['conditions']);
                $result = ['point_injection_rules' => $postRules['data']];
                $postPointInjection = array_merge($postPointInjection, $result);
            }

            if (!isset($userData) || $userData == null) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Create Point Injection Failed, No User Data'
                ];
                DB::rollBack();
                return response()->json($result);
            }

            try {
                PointInjectionUser::insert($userData);
                $result = ['status'  => 'success'];
            } catch (\Exception $e) {
                $result = [
                    'status'  => 'fail',
                    'message' => 'Insert User Failed or User Not Found'
                ];
                DB::rollBack();
                return response()->json($result);
            }

            if ($post['send_type'] == 'One Time') {
                $postPointInjection['duration'] = 1;
            }
            $userData = array_values($userData);
            $jobData = [];
            $index = 0;
            $countJobData = 0;
            for ($i = 0; $i < count($userData); $i++) {
                for ($n = 0; $n < $postPointInjection['duration']; $n++) {
                    if ($countJobData % 100 == 0) {
                        $pivotData = [];
                        $index++;
                    }
                    if (isset($postMedia)) {
                        $pivotData[] = [
                            'point_injection_media_push'        => $postMedia['point_injection_media_push'],
                            'point_injection_push_subject'      => $postMedia['point_injection_push_subject'],
                            'point_injection_push_content'      => $postMedia['point_injection_push_content'],
                            'point_injection_push_clickto'      => $postMedia['point_injection_push_clickto'],
                            'point_injection_push_id_reference' => $postMedia['point_injection_push_id_reference'],
                            'point_injection_push_link'         => $postMedia['point_injection_push_link'],
                            'point_injection_push_image'        => $postMedia['point_injection_push_image'],
                            'id_point_injection'                => $postPointInjection['id_point_injection'],
                            'title'                             => $postPointInjection['title'],
                            'id_user'                           => $userData[$i]['id_user'],
                            'send_time'                         => implode(' ', [date('Y-m-d', strtotime($postPointInjection['start_date'] . " +" . $n . " days")), $postPointInjection['send_time']]),
                            'point'                             => $postPointInjection['point'],
                            'created_at'                        => date('Y-m-d H:i:s'),
                            'updated_at'                        => date('Y-m-d H:i:s')
                        ];
                    } else {
                        $pivotData[] = ['id_point_injection' => $postPointInjection['id_point_injection'], 'id_user' => $userData[$i]['id_user'], 'send_time' => implode(' ', [date('Y-m-d', strtotime($postPointInjection['start_date'] . " +" . $n . " days")), $postPointInjection['send_time']]), 'point' => $postPointInjection['point'], 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
                    }
                    $jobData[$index] = $pivotData;
                    $countJobData++;
                }
            }

            foreach ($jobData as $value) {
                UserPointInjection::dispatch($value)->allOnConnection('database');
            }

            DB::commit();
            return response()->json(['status'  => 'success', 'result' => $postPointInjection]);
        }
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show()
    {
        return view('pointinjection::show');
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function review(Request $request)
    {
        $post = $request->json()->all();

        $pointInjection = PointInjection::with(['user', 'point_injection_rule_parents.rules'])->where('id_point_injection', $post['id_field'])->get()->toArray()[0];
        $query = PointInjectionUser::with(['user'])->where('id_point_injection', $post['id_field']);
        $count = (new PointInjectionUser)->where('id_point_injection', $post['id_field'])->newQuery();

        $query = $query->skip($post['skip'])->take($post['take'])->get()->toArray();
        $count = $count->get()->count();

        if (isset($query) && !empty($query)) {
            $pointInjection['point_injection_users'] = $query;
            $result = [
                'status'  => 'success',
                'result'  => $pointInjection,
                'count'  => $count
            ];
        } else {
            $result = [
                'status'  => 'fail',
                'message'  => ['No User List']
            ];
        }

        return response()->json($result);
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function getPointInjection()
    {
        $log = MyHelper::logCron('Point Injection');
        try{
            $dateNow = date('Y-m-d H:00:00');
            $arrTmp = [];
            $countSuccess = 0;
            $countFail = 0;
            $pointInjection = PivotPointInjection::where('send_time', '<=', $dateNow)
                ->get()->toArray();

            if ($pointInjection == null) {
                $result = [
                    'status'  => 'fail',
                    'message'  => ['No data']
                ];
                $log->success(['No data']);
                return response()->json($result);
            }
            foreach ($pointInjection as $valueUser) {
                //add to table report first
                $createReport = [
                    'id_point_injection' => $valueUser['id_point_injection'],
                    'id_user' => $valueUser['id_user'],
                    'point' => $valueUser['point'],
                    'status' => 'Failed',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                PointInjectionReport::insert($createReport);

                DB::beginTransaction();
                $insertDataLogCash = app($this->balance)->addLogBalance($valueUser['id_user'], $valueUser['point'], $valueUser['id_point_injection'], 'Point Injection', 0);

                if($insertDataLogCash == false){
                    DB::rollback();
                    $countFail++;
                    continue;
                }

                $addTotalPoint = PointInjectionUser::where('id_user', $valueUser['id_user'])->where('id_point_injection', $valueUser['id_point_injection'])->increment('total_point', $valueUser['point']);
                if(!$addTotalPoint){
                    DB::rollback();
                    $countFail++;
                    continue;
                }

                $check = array_search($valueUser['id_point_injection'], array_column($arrTmp, 'id_point_injection'));
                if($check === false){
                    $getInfo = PointInjection::where('id_point_injection', $valueUser['id_point_injection'])->first();
                    $arrTmp[] = [
                        'id_point_injection' => $valueUser['id_point_injection'],
                        'title' => $getInfo['title'],
                        'send_type' => $getInfo['send_type'],
                        'send_time' => date('d M Y', strtotime($getInfo['start_date'])).' '.date('H:i', strtotime($getInfo['send_time'])),
                        'total_point' => $getInfo['total_point'],
                        'users_count' => 1
                    ];
                }else{
                    $arrTmp[$check]['users_count'] = $arrTmp[$check]['users_count'] + 1;
                }
                //update status report to success
                PointInjectionReport::where('id_user', $valueUser['id_user'])->where('id_point_injection', $valueUser['id_point_injection'])
                    ->update(['status' => 'Success']);

                $delPointInjection = PivotPointInjection::where('id', $valueUser['id'])->delete();

                if(!$delPointInjection){
                    DB::rollback();
                    $countFail++;
                    continue;
                }

                DB::commit();
                $countSuccess++;

                if ($valueUser['point_injection_media_push'] == 1) {
                    $dataOptional          = [];
                    $image = null;
                    if (isset($valueUser['point_injection_push_image']) && $valueUser['point_injection_push_image'] != null) {
                        $dataOptional['image'] = env('AWS_URL') . $valueUser['point_injection_push_image'];
                        $image = env('AWS_URL') . $valueUser['point_injection_push_image'];
                    }

                    if (isset($valueUser['point_injection_push_clickto']) && $valueUser['point_injection_push_clickto'] != null) {
                        $dataOptional['type'] = $valueUser['point_injection_push_clickto'];
                    } else {
                        $dataOptional['type'] = 'Home';
                    }

                    if (isset($valueUser['point_injection_push_link']) && $valueUser['point_injection_push_link'] != null) {
                        if ($dataOptional['type'] == 'Link')
                            $dataOptional['link'] = $valueUser['point_injection_push_link'];
                        else
                            $dataOptional['link'] = null;
                    } else {
                        $dataOptional['link'] = null;
                    }

                    if (isset($valueUser['point_injection_push_id_reference']) && $valueUser['point_injection_push_id_reference'] != null) {
                        $dataOptional['id_reference'] = (int) $valueUser['point_injection_push_id_reference'];
                    } else {
                        $dataOptional['id_reference'] = 0;
                    }

                    if ($valueUser['point_injection_push_clickto'] == 'News' && $valueUser['point_injection_push_id_reference'] != null) {
                        $news = News::find($valueUser['point_injection_push_id_reference']);
                        if ($news) {
                            $dataOptional['news_title'] = $news->news_title;
                        }
                        $dataOptional['url'] = env('APP_URL') . 'news/webview/' . $valueUser['point_injection_push_id_reference'];
                    }

                    if ($valueUser['point_injection_push_clickto'] == 'Order' && $valueUser['point_injection_push_id_reference'] != null) {
                        $outlet = Outlet::find($valueUser['point_injection_push_id_reference']);
                        if ($outlet) {
                            $dataOptional['news_title'] = $outlet->outlet_name;
                        }
                    }

                    //push notif logout
                    if ($valueUser['point_injection_push_clickto'] == 'Logout') {
                        $user = User::find($valueUser['id_user']);
                        if ($user) {
                            //delete token
                            $del = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
                                ->where('oauth_access_tokens.user_id', $user['id'])->where('oauth_access_token_providers.provider', 'users')->delete();
                        }
                    }

                    $deviceToken = PushNotificationHelper::searchDeviceToken("id", $valueUser['id_user']);

                    $subject = app($this->autocrm)->TextReplace($valueUser['point_injection_push_subject'], $valueUser['id_user'], ['point_injection_title' => $valueUser['title'], 'geting_point_injection' => $valueUser['point']], 'id');
                    $content = app($this->autocrm)->TextReplace($valueUser['point_injection_push_content'], $valueUser['id_user'], ['point_injection_title' => $valueUser['title'], 'geting_point_injection' => $valueUser['point']], 'id');

                    if (!empty($deviceToken)) {
                        if (isset($deviceToken['token']) && !empty($deviceToken['token'])) {
                            $push = PushNotificationHelper::sendPush($deviceToken['token'], $subject, $content, $image, $dataOptional);
                        }
                    }
                }
            }

            //proccess send email to admin
            foreach ($arrTmp as $val){
                $subject = 'Send Point Injection To User';
                $content = '<table id="table-content">';
                $content .= '<tr>';
                $content .= '<td colspan="4">Info</td>';
                $content .= '</tr>';
                $content .= '<tr>';
                $content .= '<td>Point Injection Title</td>';
                $content .= '<td>'.$val['title'].'</td>';
                $content .= '</tr>';
                $content .= '<tr>';
                $content .= '<td>Point Injection Type Send</td>';
                $content .= '<td>'.$val['send_type'].'</td>';
                $content .= '</tr>';
                $content .= '<tr>';
                $content .= '<td>Date Time to Send</td>';
                $content .= '<td>'.$val['send_time'].'</td>';
                $content .= '</tr>';
                $content .= '<tr>';
                $content .= '<td>Total Point</td>';
                $content .= '<td>'.number_format($val['total_point']).'</td>';
                $content .= '</tr>';
                $content .= '<tr>';
                $content .= '<td>Count User to Send</td>';
                $content .= '<td>'.number_format($val['users_count']).'</td>';
                $content .= '</tr>';
                $content .= '</table>';
                $send = app($this->autocrm)->sendForwardEmail('Point Injection', $subject, $content);
            }

            $log->success([
                'success' => $countSuccess,
                'fail'    => $countFail
            ]);
            return response()->json([
                'success' => $countSuccess,
                'fail'    => $countFail
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
            LogBackendError::logExceptionMessage("ApiPointInjection=>" . $e->getMessage(), $e);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit()
    {
        return view('pointinjection::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    {
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy(Request $request)
    {
        $post = $request->json()->all();

        DB::beginTransaction();
        try {
            PointInjection::where('id_point_injection', $post['id_point_injection'])->delete();
            $result = [
                'status'  => 'success',
                'message' => ['Delete Point Injection Success']
            ];
        } catch (\Exception $e) {
            $result = [
                'status'  => 'fail',
                'message' => 'Delete Point Injection Failed or Point Injection Not Found'
            ];
            DB::rollBack();
            return response()->json($result);
        }
        DB::commit();
        return response()->json($result);
    }
}
