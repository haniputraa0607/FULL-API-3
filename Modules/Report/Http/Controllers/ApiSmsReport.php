<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Models\User;
use App\Http\Models\LogApiSms;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\Report\Http\Requests\DetailReport;

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use App\Lib\MailQueue as Mail;


class ApiSmsReport extends Controller
{
    function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    function getReport(Request $request){
        $post = $request->json()->all();
        $take = 25;

        $data = LogApiSms::leftJoin('users','users.phone','=','log_api_sms.phone')
            ->select('log_api_sms.id_log_api_sms', 'log_api_sms.request_url', 'log_api_sms.response',
                'log_api_sms.phone', 'log_api_sms.created_at', 'log_api_sms.updated_at',
                'users.name', 'users.email');

        if(isset($post['date_start']) && !empty($post['date_start']) &&
            isset($post['date_end']) && !empty($post['date_end'])){
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $data->whereDate('log_api_sms.created_at', '>=', $start_date)
                ->whereDate('log_api_sms.created_at', '<=', $end_date);
        }

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'name' || $row['subject'] == 'email'){
                            if($row['operator'] == '='){
                                $data->where('users.'.$row['subject'], $row['parameter']);
                            }else{
                                $data->where('users.'.$row['subject'], 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'phone'){
                            if($row['operator'] == '='){
                                $data->where('log_api_sms.phone', $row['parameter']);
                            }else{
                                $data->where('log_api_sms.phone', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'response'){
                            $data->where('log_api_sms.response', 'like', '%='.$row['operator'].'%');
                        }

                        if($row['subject'] == 'status'){
                            if($row['operator'] == 'fail'){
                                $data->where('log_api_sms.response', 'not like', '%=1%');
                            }else{
                                $data->where('log_api_sms.response', 'like', '%=1%');
                            }

                        }
                    }
                }
            }else{
                $data->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'name' || $row['subject'] == 'email'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('users.'.$row['subject'], $row['parameter']);
                                }else{
                                    $subquery->orWhere('users.'.$row['subject'], 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'phone'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('log_api_sms.phone', $row['parameter']);
                                }else{
                                    $subquery->orWhere('log_api_sms.phone', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'response'){
                                $subquery->orWhere('log_api_sms.response', 'like', '%='.$row['operator'].'%');
                            }

                            if($row['subject'] == 'status'){
                                if($row['operator'] == 'fail'){
                                    $subquery->orWhere('log_api_sms.response', 'not like', '%=1%');
                                }else{
                                    $subquery->orWhere('log_api_sms.response', 'like', '%=1%');
                                }

                            }
                        }
                    }
                });
            }
        }

        if(isset($post['export']) && $post['export'] == 1){
            $data = $data->addSelect('log_api_sms.request_body')->get();
        }else{
            $data = $data->paginate($take);
        }
        return response()->json(MyHelper::checkGet($data));
    }

    function getReportDetailRequest(Request $request){
        $post = $request->json()->all();
        $data = LogApiSms::where('log_api_sms.id_log_api_sms', $post['id_log_api_sms'])
            ->select('log_api_sms.*')->first();

        return response()->json(MyHelper::checkGet($data));
    }
}
