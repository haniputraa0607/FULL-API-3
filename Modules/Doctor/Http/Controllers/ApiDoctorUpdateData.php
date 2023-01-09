<?php

namespace Modules\Doctor\Http\Controllers;

use App\Http\Models\OauthAccessToken;
use App\Http\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Setting;
use App\Http\Models\Outlet;
use App\Lib\MyHelper;
use DB;
use DateTime;
use DateTimeZone;
use Modules\Doctor\Entities\Doctor;
use Modules\Doctor\Entities\DoctorService;
use Modules\Doctor\Entities\DoctorUpdateData;
use PharIo\Manifest\EmailTest;
use Auth;

class ApiDoctorUpdateData extends Controller
{
    public function __construct()
    {
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
                        'value' => 'doctor_name',
                    ],
                    [
                        'text' => 'Service',
                        'value' => 'doctors_services',
                    ],
                    [
                        'text' => 'Pengalaman Praktik',
                        'value' => 'practice_experience',
                    ],
                    [
                        'text' => 'Alumni',
                        'value' => 'alumni',
                    ],
                    [
                        'text' => 'Nomor STR',
                        'value' => 'registration_certificate_number',
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

        $create = DoctorUpdateData::create([
            'id_doctor' => $request->user()->id_doctor,
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
        $data = DoctorUpdateData::leftJoin('users as approver', 'approver.id', 'doctor_update_datas.approve_by')
                ->join('doctors', 'doctors.id_doctor', 'doctor_update_datas.id_doctor')
                ->orderBy('doctor_update_datas.created_at', 'desc');

        if (!empty($post['date_start']) && !empty($post['date_end'])) {
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $data->whereDate('doctor_update_datas.created_at', '>=', $start_date)->whereDate('doctor_update_datas.created_at', '<=', $end_date);
        }

        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $rule = 'and';
            if (isset($post['rule'])) {
                $rule = $post['rule'];
            }

            if ($rule == 'and') {
                foreach ($post['conditions'] as $row) {
                    if (isset($row['subject'])) {
                        if ($row['subject'] == 'doctor_name') {
                            if ($row['operator'] == '=') {
                                $data->where('doctor_name', $row['parameter']);
                            } else {
                                $data->where('doctor_name', 'like', '%' . $row['parameter'] . '%');
                            }
                        }

                        if ($row['subject'] == 'doctor_phone') {
                            if ($row['operator'] == '=') {
                                $data->where('doctor_phone', $row['parameter']);
                            } else {
                                $data->where('doctor_phone', 'like', '%' . $row['parameter'] . '%');
                            }
                        }

                        if ($row['subject'] == 'status') {
                            switch ($row['operator']) {
                                case 'Approved':
                                    $data->whereNotNull('approve_at');
                                    break;

                                case 'Rejected':
                                    $data->where(function ($q) {
                                        $q->whereNotNull('reject_at');
                                        $q->whereNull('approve_at');
                                    });
                                    break;

                                default:
                                    $data->where(function ($q) {
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
                            if ($row['subject'] == 'doctor_name') {
                                if ($row['operator'] == '=') {
                                    $subquery->orWhere('doctor_name', $row['parameter']);
                                } else {
                                    $subquery->orWhere('doctor_name', 'like', '%' . $row['parameter'] . '%');
                                }
                            }

                            if ($row['subject'] == 'doctor_phone') {
                                if ($row['operator'] == '=') {
                                    $subquery->orWhere('doctor_phone', $row['parameter']);
                                } else {
                                    $subquery->orWhere('doctor_phone', 'like', '%' . $row['parameter'] . '%');
                                }
                            }

                            if ($row['subject'] == 'status') {
                                switch ($row['operator']) {
                                    case 'Approved':
                                        $subquery->orWhereNotNull('approve_at');
                                        break;

                                    case 'Rejected':
                                        $subquery->orWhere(function ($q) {
                                            $q->whereNotNull('reject_at');
                                            $q->whereNull('approve_at');
                                        });
                                        break;

                                    default:
                                        $subquery->orWhere(function ($q) {
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
            'doctor_update_datas.*',
            'doctors.*',
            'approver.name as approve_by_name',
            'doctor_update_datas.created_at'
        )->paginate(10);

        return response()->json(MyHelper::checkGet($data));
    }

    public function detail(Request $request)
    {
        $post = $request->json()->all();

        if (empty($post['id_doctor_update_data'])) {
            return response()->json([
                'status' => 'fail',
                'messages' => ['ID can not be empty']
            ]);
        }

        $detail = DoctorUpdateData::leftJoin('users as approver', 'approver.id', 'doctor_update_datas.approve_by')
                ->join('doctors', 'doctors.id_doctor', 'doctor_update_datas.id_doctor')
                ->where('id_doctor_update_data', $post['id_doctor_update_data'])
                ->select(
                    'doctor_update_datas.*',
                    'doctors.*',
                    'approver.name as approve_by_name',
                    'doctor_update_datas.created_at'
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

    public function update(Request $request)
    {
        $post = $request->json()->all();
        if (empty($post['id_doctor_update_data'])) {
            return response()->json([
                'status' => 'fail',
                'messages' => ['ID can not be empty']
            ]);
        }

        $updateData = DoctorUpdateData::where('id_doctor_update_data', $post['id_doctor_update_data'])
            ->with('doctor')->first();
        if (empty($updateData)) {
            return response()->json([
                'status' => 'fail',
                'messages' => ['Request data not found']
            ]);
        }

        if (isset($post['update_type'])) {
            $autocrmTitle = null;
            if (($post['update_type'] == 'reject')) {
                $data = [
                    'reject_at' => date('Y-m-d H:i:s')
                ];
                $autocrmTitle = 'Reject Doctor Request Update Data';
            } elseif (($post['update_type'] == 'approve')) {
                $data = [
                    'approve_by' => $request->user()->id,
                    'approve_at' => date('Y-m-d H:i:s'),
                    'reject_at' => null
                ];
                $autocrmTitle = 'Approve Doctor Request Update Data';
            }

            $update = DoctorUpdateData::where('id_doctor_update_data', $post['id_doctor_update_data'])->update($data);

            if ($update && $autocrmTitle) {
                app($this->autocrm)->SendAutoCRM(
                    $autocrmTitle,
                    $updateData['doctor']['doctor_phone'] ?? null,
                    [
                        'user_update' => $request->user()->name,
                        'reject_at' => date('Y-m-d H:i:s'),
                        'approve_at' => date('Y-m-d H:i:s')
                    ],
                    null,
                    false,
                    false,
                    'doctor'
                );
            }
            return response()->json(MyHelper::checkUpdate($update));
        }

        return response()->json(MyHelper::checkUpdate($save));
    }
}
