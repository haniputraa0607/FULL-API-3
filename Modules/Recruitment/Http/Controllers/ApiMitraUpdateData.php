<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Http\Models\OauthAccessToken;
use App\Http\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Outlet;
use App\Http\Models\OutletSchedule;

use Modules\Franchise\Entities\TransactionProduct;
use Modules\Outlet\Entities\OutletTimeShift;

use Modules\Recruitment\Entities\HairstylistLogBalance;
use Modules\Recruitment\Entities\OutletCash;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\HairstylistAnnouncement;
use Modules\Recruitment\Entities\HairstylistInbox;
use Modules\Recruitment\Entities\HairstylistUpdateData;

use Modules\Transaction\Entities\TransactionPaymentCash;
use Modules\UserRating\Entities\UserRating;
use Modules\UserRating\Entities\RatingOption;
use Modules\UserRating\Entities\UserRatingLog;
use Modules\UserRating\Entities\UserRatingSummary;
use App\Http\Models\Transaction;

use Modules\Recruitment\Http\Requests\ScheduleCreateRequest;
use Modules\Recruitment\Entities\OutletCashAttachment;

use App\Lib\MyHelper;
use DB;
use DateTime;
use DateTimeZone;
use Modules\Users\Http\Requests\users_forgot;
use Modules\Users\Http\Requests\users_phone_pin_new_v2;
use PharIo\Manifest\EmailTest;
use Auth;

class ApiMitraUpdateData extends Controller
{
    public function __construct() {
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
    }

    public function listField(Request $request)
    {
    	return MyHelper::checkGet(
	        [
	            'field_list' => [
	                [
	                    'text' => 'Nama',
	                    'value' => 'name',
	                ],
	                [
	                    'text' => 'Nomor Telepon',
	                    'value' => 'phone_number',
	                ],
	                [
	                    'text' => 'Email',
	                    'value' => 'email',
	                ],
	                [
	                    'text' => 'Alamat',
	                    'value' => 'address',
	                ],
	                [
	                    'text' => 'Nomor Rekening',
	                    'value' => 'account_number',
	                ]
	            ]
	        ]
	    );
    }

    public function updateRequest(Request $request)
    {
    	$request->validate([
            'field' => 'string|required',
            'new_value' => 'string|required',
            'notes' => 'string|sometimes|nullable',
        ]);
	
		$create = HairstylistUpdateData::create([
			'id_user_hair_stylist' => $request->user()->id_user_hair_stylist,
			'field' => $request->field,
			'new_value' => $request->new_value,
			'notes' => $request->notes,
		]);

		if (!$create) {
			return [
	            'status' => 'fail',
	            'result' => [
	                'message' => 'Permintaan perubahan data gagal dikirim'
	            ]
	        ];			
		}

        return [
            'status' => 'success',
            'result' => [
                'message' => 'Permintaan perubahan data berhasil dikirim'
            ]
        ];
    }

    public function list(Request $request)
	{
        $post = $request->json()->all();
        $data = HairstylistUpdateData::leftJoin('users as approver', 'approver.id', 'hairstylist_update_datas.approve_by')
        		->join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'hairstylist_update_datas.id_user_hair_stylist')
                ->orderBy('hairstylist_update_datas.created_at', 'desc');

        if (!empty($post['date_start']) && !empty($post['date_end'])) {
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $data->whereDate('hairstylist_update_datas.created_at', '>=', $start_date)->whereDate('hairstylist_update_datas.created_at', '<=', $end_date);
        }

        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $rule = 'and';
            if (isset($post['rule'])) {
                $rule = $post['rule'];
            }

            if ($rule == 'and') {
                foreach ($post['conditions'] as $row) {
                    if (isset($row['subject'])) {
                        if ($row['subject'] == 'nickname') {
                            if ($row['operator'] == '=') {
                                $data->where('nickname', $row['parameter']);
                            } else {
                                $data->where('nickname', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if ($row['subject'] == 'phone_number') {
                            if ($row['operator'] == '=') {
                                $data->where('phone_number', $row['parameter']);
                            } else {
                                $data->where('phone_number', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if ($row['subject'] == 'fullname') {
                            if ($row['operator'] == '=') {
                                $data->where('fullname', $row['parameter']);
                            } else {
                                $data->where('fullname', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if ($row['subject'] == 'status') {
                        	switch ($row['operator']) {
                        		case 'Approved':
                        			$data->whereNotNull('approve_at');
                        			break;

                        		case 'Rejected':
                        			$data->where(function($q) {
                        				$q->whereNotNull('reject_at');
                        				$q->whereNull('approve_at');
                        			});
                        			break;
                        		
                        		default:
                        			$data->where(function($q) {
                        				$q->whereNull('reject_at');
                        				$q->whereNull('approve_at');
                        			});
                        			break;
                        	}
                        }
                    }
                }
            } else {
            	$data->where(function ($subquery) use ($post) {
            		foreach ($post['conditions'] as $row) {
            			if (isset($row['subject'])) {
            				if ($row['subject'] == 'nickname') {
            					if ($row['operator'] == '=') {
            						$subquery->orWhere('nickname', $row['parameter']);
            					} else {
            						$subquery->orWhere('nickname', 'like', '%'.$row['parameter'].'%');
            					}
            				}

            				if ($row['subject'] == 'phone_number') {
            					if ($row['operator'] == '=') {
            						$subquery->orWhere('phone_number', $row['parameter']);
            					} else {
            						$subquery->orWhere('phone_number', 'like', '%'.$row['parameter'].'%');
            					}
            				}

            				if ($row['subject'] == 'fullname') {
            					if ($row['operator'] == '=') {
            						$subquery->orWhere('fullname', $row['parameter']);
            					} else {
            						$subquery->orWhere('fullname', 'like', '%'.$row['parameter'].'%');
            					}
            				}

            				if($row['subject'] == 'status') {
            					switch ($row['operator']) {
            						case 'Approved':
            							$subquery->orWhereNotNull('approve_at');
            							break;

        							case 'Rejected':
	                        			$data->orWhere(function($q) {
	                        				$q->whereNotNull('reject_at');
	                        				$q->whereNull('approve_at');
	                        			});
	                        			break;

            						default:
            							$subquery->orWhere(function($q) {
            								$q->whereNull('reject_at');
            								$q->whereNull('approve_at');
            							});
            							break;
            					}
            				}
            			}
                    }
                });
            }
        }

        $data = $data->select(
		        	'hairstylist_update_datas.*',
		        	'user_hair_stylist.*', 
		        	'approver.name as approve_by_name',
		        	'hairstylist_update_datas.created_at'
		        )->paginate(10);

        return response()->json(MyHelper::checkGet($data));
    }

    public function detail(Request $request) {
        $post = $request->json()->all();

        if (empty($post['id_hairstylist_update_data'])) {
            return response()->json([
            	'status' => 'fail', 
            	'messages' => ['ID can not be empty']
            ]);
        }

        $detail = HairstylistUpdateData::leftJoin('users as approver', 'approver.id', 'hairstylist_update_datas.approve_by')
        		->join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'hairstylist_update_datas.id_user_hair_stylist')
                ->where('id_hairstylist_update_data', $post['id_hairstylist_update_data'])
                ->select(
		        	'hairstylist_update_datas.*',
		        	'user_hair_stylist.*', 
		        	'approver.name as approve_by_name',
		        	'hairstylist_update_datas.created_at'
		        )
		        ->first();

        if (!$detail) {
        	return MyHelper::checkGet($detail);
        }

        $res = [
        	'detail' => $detail
        ];

        return MyHelper::checkGet($res);
    }

    public function update(Request $request) {
        $post = $request->json()->all();
        if (empty($post['id_hairstylist_update_data'])) {
            return response()->json([
            	'status' => 'fail', 
            	'messages' => ['ID can not be empty']
            ]);
        }

        if (isset($post['update_type'])) {
        	$autocrmTitle = null;
        	if (($post['update_type'] == 'reject')) {
        		$data = [
					'reject_at' => date('Y-m-d H:i:s')
				];
				$autocrmTitle = 'Reject Hairstylist Request Update Data';
        	} elseif (($post['update_type'] == 'approve')) {
	            $data = [
	            	'approve_by' => $request->user()->id,
	            	'approve_at' => date('Y-m-d H:i:s'),
					'reject_at' => null
	            ];
				$autocrmTitle = 'Approve Hairstylist Request Update Data';
        	}

        	$update = HairstylistUpdateData::where('id_hairstylist_update_data', $post['id_hairstylist_update_data'])->update($data);

        	if ($update && $autocrmTitle) {
				$updateData = HairstylistUpdateData::where('id_hairstylist_update_data', $post['id_hairstylist_update_data'])
							->with('user_hair_stylist')->first();
	        	app($this->autocrm)->SendAutoCRM($autocrmTitle, $updateData['user_hair_stylist']['phone_number'] ?? null,
	                $updateData->toArray(), null, false, false, $recipient_type = 'hairstylist', null, true
	            );
        	}
        	return response()->json(MyHelper::checkUpdate($update));
        }

        return response()->json(MyHelper::checkUpdate($save));
    }
}
