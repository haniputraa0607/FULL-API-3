<?php

namespace Modules\Autocrm\Http\Controllers;

use App\Http\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Autocrm;
use App\Http\Models\AutocrmRule;
use App\Http\Models\AutocrmRuleParent;
use App\Http\Models\User;
use App\Http\Models\WhatsappContent;
use Modules\Autocrm\Entities\AutoresponseCode;
use Modules\Autocrm\Entities\AutoresponseCodeList;
use Modules\Autocrm\Entities\AutoresponseCodePaymentMethod;
use Modules\Autocrm\Entities\AutoresponseCodeTransactionType;
use Modules\Autocrm\Http\Requests\CreateCron;
use Modules\Autocrm\Http\Requests\UpdateCron;
use App\Lib\MyHelper;
use Validator;
use DB;
use App\Lib\SendMail as Mail;

class ApiAutoresponseWithCode extends Controller
{
    public function list(Request $request)
    {
        $post = $request->json()->all();
        $list = AutoresponseCode::with(['transaction_type', 'payment_method']);

        if (
            isset($post['date_start']) && !empty($post['date_start']) &&
            isset($post['date_end']) && !empty($post['date_end'])
        ) {
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $list = $list->whereRaw('(DATE(autoresponse_code_periode_start) >= "' . $start_date . '" AND DATE(autoresponse_code_periode_start) <= "' . $end_date . '" AND DATE(autoresponse_code_periode_end) >= "' . $start_date . '" AND DATE(autoresponse_code_periode_end) <= "' . $end_date . '")');
        }

        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $rule = 'and';
            if (isset($post['rule'])) {
                $rule = $post['rule'];
            }

            if ($rule == 'and') {
                foreach ($post['conditions'] as $row) {
                    if (isset($row['subject'])) {
                        if ($row['subject'] == 'autoresponse_code_name') {
                            if ($row['operator'] == '=') {
                                $list->where('autoresponse_code_name', $row['parameter']);
                            } else {
                                $list->where('autoresponse_code_name', 'like', '%' . $row['parameter'] . '%');
                            }
                        }

                        if ($row['subject'] == 'autoresponse_transaction_type') {
                            if ($row['operator'] == 'All') {
                                $list->where('is_all_transaction_type', 1);
                            } else {
                                $list->whereIn('autoresponse_codes.id_autoresponse_code', function ($subQuery) use ($row) {
                                    $subQuery->select('id_autoresponse_code')
                                        ->from('autoresponse_code_transaction_types')
                                        ->where('autoresponse_code_transaction_type', $row['operator']);
                                });
                            }
                        }

                        if ($row['subject'] == 'autoresponse_payment_method') {
                            if ($row['operator'] == 'All') {
                                $list->where('is_all_payment_method', 1);
                            } else {
                                $list->whereIn('autoresponse_codes.id_autoresponse_code', function ($subQuery) use ($row) {
                                    $subQuery->select('id_autoresponse_code')
                                        ->from('autoresponse_code_payment_methods')
                                        ->where('autoresponse_code_payment_method', $row['operator']);
                                });
                            }
                        }
                    }
                }
            } else {
                $list->where(function ($subquery) use ($post) {
                    foreach ($post['conditions'] as $row) {
                        if (isset($row['subject'])) {
                            if ($row['subject'] == 'autoresponse_code_name') {
                                if ($row['operator'] == '=') {
                                    $subquery->orWhere('autoresponse_code_name', $row['parameter']);
                                } else {
                                    $subquery->orWhere('autoresponse_code_name', 'like', '%' . $row['parameter'] . '%');
                                }
                            }

                            if ($row['subject'] == 'autoresponse_transaction_type') {
                                if ($row['operator'] == 'All') {
                                    $subquery->orWhere('is_all_transaction_type', 1);
                                } else {
                                    $subquery->orWhereIn('autoresponse_codes.id_autoresponse_code', function ($subQuery) use ($row) {
                                        $subQuery->select('id_autoresponse_code')
                                            ->from('autoresponse_code_transaction_types')
                                            ->where('autoresponse_code_transaction_type', $row['operator']);
                                    });
                                }
                            }

                            if ($row['subject'] == 'autoresponse_payment_method') {
                                if ($row['operator'] == 'All') {
                                    $subquery->orWhere('is_all_payment_method', 1);
                                } else {
                                    $subquery->orWhereIn('autoresponse_codes.id_autoresponse_code', function ($subQuery) use ($row) {
                                        $subQuery->select('id_autoresponse_code')
                                            ->from('autoresponse_code_payment_methods')
                                            ->where('autoresponse_code_payment_method', $row['operator']);
                                    });
                                }
                            }
                        }
                    }
                });
            }
        }

        $list = $list->paginate(30);
        return response()->json(MyHelper::checkGet($list));
    }

    public function store(Request $request)
    {
        $post = $request->json()->all();

        if (
            isset($post['autoresponse_code_name']) && !empty($post['autoresponse_code_name']) &&
            isset($post['autoresponse_code_periode_start']) && !empty($post['autoresponse_code_periode_start']) &&
            isset($post['autoresponse_code_periode_end']) && !empty($post['autoresponse_code_periode_end'])
        ) {
            $dateStart = date('Y-m-d', strtotime($post['autoresponse_code_periode_start']));
            $dateEnd = date('Y-m-d', strtotime($post['autoresponse_code_periode_end']));

            //check another periode
            $checPeriod = AutoresponseCode::leftJoin('autoresponse_code_list', 'autoresponse_code_list.id_autoresponse_code', 'autoresponse_codes.id_autoresponse_code')
                            ->whereRaw(
                                "(autoresponse_code_periode_start BETWEEN '" . $dateStart . "' AND '" . $dateEnd . "' 
                                    OR '" . $dateStart . "' BETWEEN autoresponse_code_periode_start AND autoresponse_code_periode_end)"
                            )
                            ->where(function ($q) use ($post) {
                                $q->where('is_all_payment_method', 1)
                                    ->orwhereIn('autoresponse_codes.id_autoresponse_code', function ($sub) use ($post) {
                                        $sub->select('id_autoresponse_code')
                                            ->from('autoresponse_code_payment_methods')
                                            ->whereIn('autoresponse_code_payment_method', $post['autoresponse_code_payment_method']);
                                    });
                            })
                            ->where('autoresponse_codes.is_stop', 0)
                            ->whereNull('id_user')
                            ->groupBy('autoresponse_codes.id_autoresponse_code')
                            ->pluck('autoresponse_code_name')->toArray();

            if (!empty($checPeriod)) {
                return response()->json(['status' => 'fail', 'messages' => ['Same period with : ' . implode(',', $checPeriod)]]);
            }

            DB::beginTransaction();

            $isAllTransactionType = 0;
            $isAllPaymentMethod = 0;

            if (in_array('All', $post['autoresponse_code_transaction_type'])) {
                $isAllTransactionType = 1;
            }

            if (in_array('All', $post['autoresponse_code_payment_method'])) {
                $isAllPaymentMethod = 1;
            }

            $dataMain = [
                'autoresponse_code_name' => $post['autoresponse_code_name'],
                'autoresponse_code_periode_start' => $dateStart,
                'autoresponse_code_periode_end' => $dateEnd,
                'is_all_transaction_type' => $isAllTransactionType,
                'is_all_payment_method' => $isAllPaymentMethod
            ];

            $create = AutoresponseCode::create($dataMain);

            if (!$create) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed create main autoresponse with code']]);
            }

            if ($isAllTransactionType == 0 && !empty($post['autoresponse_code_transaction_type'])) {
                $arrTransactionType = [];
                foreach ($post['autoresponse_code_transaction_type'] as $value) {
                    $arrTransactionType[] = [
                        'id_autoresponse_code' => $create['id_autoresponse_code'],
                        'autoresponse_code_transaction_type' => $value,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }
                $insert = AutoresponseCodeTransactionType::insert($arrTransactionType);
                if (!$insert) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed insert transaction type']]);
                }
            }

            if ($isAllPaymentMethod == 0 && !empty($post['autoresponse_code_payment_method'])) {
                $arrPaymentMethod = [];
                foreach ($post['autoresponse_code_payment_method'] as $value) {
                    $arrPaymentMethod[] = [
                        'id_autoresponse_code' => $create['id_autoresponse_code'],
                        'autoresponse_code_payment_method' => $value,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }
                $insert = AutoresponseCodePaymentMethod::insert($arrPaymentMethod);
                if (!$insert) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed insert payemnt method']]);
                }
            }

            if (empty($post['codes']) && empty($post['data_import'][0])) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['List code and import code can not be empty. Please fill in one of the data. ']]);
            }

            $list_codes = [];
            if (!empty($post['codes'])) {
                $list_codes = explode(',', str_replace("\r\n", ',', $post['codes']));
            }

            $import_codes = [];
            if (!empty($post['data_import'][0])) {
                $import_codes = array_column($post['data_import'][0], 'list_codes');
            }
            $codes = array_merge($list_codes, $import_codes);
            $arrCode = [];
            foreach ($codes as $value) {
                $arrCode[] = [
                    'id_autoresponse_code' => $create['id_autoresponse_code'],
                    'autoresponse_code' => $value,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }

            if (!empty($arrCode)) {
                $insert = AutoresponseCodeList::insert($arrCode);
                if (!$insert) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed insert list code']]);
                }
            }

            DB::commit();
            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function detail(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_autoresponse_code']) && !empty($post['id_autoresponse_code'])) {
            $detail = AutoresponseCode::where('id_autoresponse_code', $post['id_autoresponse_code'])
                ->with(['transaction_type', 'payment_method'])
                ->first();

            if (!empty($detail)) {
                $detail['code_list'] = AutoresponseCodeList::leftJoin('users', 'users.id', 'autoresponse_code_list.id_user')
                                    ->leftJoin('transactions', 'transactions.id_transaction', 'autoresponse_code_list.id_transaction')
                                    ->where('autoresponse_code_list.id_autoresponse_code', $post['id_autoresponse_code'])
                                    ->select('autoresponse_code_list.*', 'users.name', 'users.phone', 'transactions.transaction_receipt_number')->get()->toArray();
            }
            return response()->json(MyHelper::checkGet($detail));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function update(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_autoresponse_code']) && !empty($post['id_autoresponse_code'])) {
            $dateStart = date('Y-m-d', strtotime($post['autoresponse_code_periode_start']));
            $dateEnd = date('Y-m-d', strtotime($post['autoresponse_code_periode_end']));

            $isAllTransactionType = 0;
            $isAllPaymentMethod = 0;

            if (in_array('All', $post['autoresponse_code_transaction_type'])) {
                $isAllTransactionType = 1;
            }

            if (in_array('All', $post['autoresponse_code_payment_method'])) {
                $isAllPaymentMethod = 1;
            }

            if ($isAllPaymentMethod == 1) {
                $checPeriod = AutoresponseCode::leftJoin('autoresponse_code_list', 'autoresponse_code_list.id_autoresponse_code', 'autoresponse_codes.id_autoresponse_code')
                    ->whereRaw(
                        "(autoresponse_code_periode_start BETWEEN '" . $dateStart . "' AND '" . $dateEnd . "' 
                                    OR '" . $dateStart . "' BETWEEN autoresponse_code_periode_start AND autoresponse_code_periode_end)"
                    )
                    ->where('autoresponse_codes.is_stop', 0)
                    ->whereNotIn('autoresponse_codes.id_autoresponse_code', [$post['id_autoresponse_code']])
                    ->whereNull('id_user')
                    ->groupBy('autoresponse_codes.id_autoresponse_code')
                    ->pluck('autoresponse_code_name')->toArray();

                if (!empty($checPeriod)) {
                    return response()->json(['status' => 'fail', 'messages' => ['Same period with : ' . implode(',', $checPeriod)]]);
                }
            }
            //check another periode
            $checPeriod = AutoresponseCode::leftJoin('autoresponse_code_list', 'autoresponse_code_list.id_autoresponse_code', 'autoresponse_codes.id_autoresponse_code')
                ->whereRaw(
                    "(autoresponse_code_periode_start BETWEEN '" . $dateStart . "' AND '" . $dateEnd . "' 
                                    OR '" . $dateStart . "' BETWEEN autoresponse_code_periode_start AND autoresponse_code_periode_end)"
                )
                ->where(function ($q) use ($post) {
                    $q->where('is_all_payment_method', 1)
                        ->orwhereIn('autoresponse_codes.id_autoresponse_code', function ($sub) use ($post) {
                            $sub->select('id_autoresponse_code')
                                ->from('autoresponse_code_payment_methods')
                                ->whereIn('autoresponse_code_payment_method', $post['autoresponse_code_payment_method']);
                        });
                })
                ->where('autoresponse_codes.is_stop', 0)
                ->whereNotIn('autoresponse_codes.id_autoresponse_code', [$post['id_autoresponse_code']])
                ->whereNull('id_user')
                ->groupBy('autoresponse_codes.id_autoresponse_code')
                ->pluck('autoresponse_code_name')->toArray();

            if (!empty($checPeriod)) {
                return response()->json(['status' => 'fail', 'messages' => ['Same period with : ' . implode(',', $checPeriod)]]);
            }

            DB::beginTransaction();

            $dataMain = [
                'autoresponse_code_name' => $post['autoresponse_code_name'],
                'autoresponse_code_periode_start' => $dateStart,
                'autoresponse_code_periode_end' => $dateEnd,
                'is_all_transaction_type' => $isAllTransactionType,
                'is_all_payment_method' => $isAllPaymentMethod
            ];

            $create = AutoresponseCode::where('id_autoresponse_code', $post['id_autoresponse_code'])->update($dataMain);

            if (!$create) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed udpate main autoresponse with code']]);
            }

            //delete old data
            AutoresponseCodeTransactionType::where('id_autoresponse_code', $post['id_autoresponse_code'])->delete();
            AutoresponseCodePaymentMethod::where('id_autoresponse_code', $post['id_autoresponse_code'])->delete();

            if ($isAllTransactionType == 0 && !empty($post['autoresponse_code_transaction_type'])) {
                $arrTransactionType = [];
                foreach ($post['autoresponse_code_transaction_type'] as $value) {
                    $arrTransactionType[] = [
                        'id_autoresponse_code' => $post['id_autoresponse_code'],
                        'autoresponse_code_transaction_type' => $value,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }
                $insert = AutoresponseCodeTransactionType::insert($arrTransactionType);
                if (!$insert) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed insert transaction type']]);
                }
            }

            if ($isAllPaymentMethod == 0 && !empty($post['autoresponse_code_payment_method'])) {
                $arrPaymentMethod = [];
                foreach ($post['autoresponse_code_payment_method'] as $value) {
                    $arrPaymentMethod[] = [
                        'id_autoresponse_code' => $post['id_autoresponse_code'],
                        'autoresponse_code_payment_method' => $value,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }
                $insert = AutoresponseCodePaymentMethod::insert($arrPaymentMethod);
                if (!$insert) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed insert payemnt method']]);
                }
            }

            $list_codes = [];
            if (!empty($post['codes'])) {
                $list_codes = explode(',', str_replace("\r\n", ',', $post['codes']));
            }

            $getCurrentCode = AutoresponseCodeList::where('id_autoresponse_code', $post['id_autoresponse_code'])->pluck('autoresponse_code')->toArray();

            $import_codes = [];
            if (!empty($post['data_import'][0])) {
                $import_codes = array_column($post['data_import'][0], 'list_codes');
            }
            $codes = array_merge($list_codes, $import_codes);
            $arrCode = [];
            foreach ($codes as $value) {
                $arrCode[] = [
                    'id_autoresponse_code' => $post['id_autoresponse_code'],
                    'autoresponse_code' => $value,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }

            if (!empty($arrCode)) {
                AutoresponseCode::where('id_autoresponse_code', $post['id_autoresponse_code'])->update(['is_stop' => 0]);
                $insert = AutoresponseCodeList::insert($arrCode);
                if (!$insert) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed insert list code']]);
                }
            }

            DB::commit();

            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function deleteCode(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_autoresponse_code_list']) && !empty($post['id_autoresponse_code_list'])) {
            $delete = AutoresponseCodeList::where('id_autoresponse_code_list', $post['id_autoresponse_code_list'])
                ->whereNull('id_user')
                ->delete();

            return response()->json(MyHelper::checkDelete($delete));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function deleteAutoresponsecode(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_autoresponse_code']) && !empty($post['id_autoresponse_code'])) {
            $checkCodeActive = AutoresponseCodeList::where('id_autoresponse_code', $post['id_autoresponse_code'])->whereNotNull('id_user')->get()->toArray();

            if (!empty($checkCodeActive)) {
                return response()->json(['status' => 'fail', 'messages' => ['There is a code that has been used']]);
            }

            $delete = AutoresponseCode::where('id_autoresponse_code', $post['id_autoresponse_code'])->delete();

            if ($delete) {
                AutoresponseCodeList::where('id_autoresponse_code', $post['id_autoresponse_code'])->delete();
                AutoresponseCodePaymentMethod::where('id_autoresponse_code', $post['id_autoresponse_code'])->delete();
                AutoresponseCodeTransactionType::where('id_autoresponse_code', $post['id_autoresponse_code'])->delete();
            }

            return response()->json(MyHelper::checkDelete($delete));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function getAvailableCode($id_transaction)
    {
        $currentDate = date('Y-m-d');
        $detailTrx = Transaction::leftJoin('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
            ->leftJoin('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction')
            ->leftJoin('transaction_payment_ipay88s', 'transactions.id_transaction', '=', 'transaction_payment_ipay88s.id_transaction')
            ->leftJoin('transaction_payment_shopee_pays', 'transactions.id_transaction', '=', 'transaction_payment_shopee_pays.id_transaction')
            ->select(
                'transactions.trasaction_type',
                'transactions.id_user',
                'transaction_pickups.pickup_by',
                'transaction_payment_midtrans.payment_type',
                'transaction_payment_ipay88s.payment_method',
                'transaction_payment_midtrans.gross_amount',
                'transaction_payment_shopee_pays.id_transaction_payment_shopee_pay'
            )
            ->where('transactions.id_transaction', $id_transaction)->first();

        $transactionType = ($detailTrx['pickup_by'] == 'Customer' ? 'Pickup Order' : 'Delivery');
        $paymentMethod = '';
        if (!empty($detailTrx['payment_type'])) {
            $paymentMethod = $detailTrx['payment_type'];
        } elseif (!empty($detailTrx['payment_method'])) {
            $paymentMethod = $detailTrx['payment_method'];
        } elseif (!empty($detailTrx['id_transaction_payment_shopee_pay'])) {
            $paymentMethod = 'Shopeepay';
        }

        $getCode = AutoresponseCode::leftJoin('autoresponse_code_list', 'autoresponse_code_list.id_autoresponse_code', 'autoresponse_codes.id_autoresponse_code')
                    ->where('autoresponse_codes.is_stop', 0)
                    ->whereNull('autoresponse_code_list.id_user')
                    ->whereDate('autoresponse_code_periode_start', '<=', $currentDate)
                    ->whereDate('autoresponse_code_periode_end', '>=', $currentDate)
                    ->where(function ($sub) use ($transactionType, $paymentMethod) {
                        $sub->orWhere(function ($q) use ($transactionType, $paymentMethod) {
                            $q->where('is_all_transaction_type', 0)
                                ->whereIn('autoresponse_codes.id_autoresponse_code', function ($subQuery) use ($transactionType) {
                                    $subQuery->select('id_autoresponse_code')
                                        ->from('autoresponse_code_transaction_types')
                                        ->where('autoresponse_code_transaction_type', $transactionType);
                                })
                                ->where('is_all_payment_method', 0)
                                ->whereIn('autoresponse_codes.id_autoresponse_code', function ($subQuery) use ($paymentMethod) {
                                    $subQuery->select('id_autoresponse_code')
                                        ->from('autoresponse_code_payment_methods')
                                        ->where('autoresponse_code_payment_method', $paymentMethod);
                                });
                        });

                        $sub->orWhere(function ($q) use ($paymentMethod) {
                            $q->where('is_all_transaction_type', 1)
                                ->where('is_all_payment_method', 0)
                                ->whereIn('autoresponse_codes.id_autoresponse_code', function ($subQuery) use ($paymentMethod) {
                                    $subQuery->select('id_autoresponse_code')
                                        ->from('autoresponse_code_payment_methods')
                                        ->where('autoresponse_code_payment_method', $paymentMethod);
                                });
                        });

                        $sub->orWhere(function ($q) use ($transactionType) {
                            $q->where('is_all_payment_method', 1)
                                ->where('is_all_transaction_type', 0)
                                ->whereIn('autoresponse_codes.id_autoresponse_code', function ($subQuery) use ($transactionType) {
                                    $subQuery->select('id_autoresponse_code')
                                        ->from('autoresponse_code_transaction_types')
                                        ->where('autoresponse_code_transaction_type', $transactionType);
                                });
                        });

                        $sub->orWhere(function ($q) use ($transactionType) {
                            $q->where('is_all_transaction_type', 1)
                                ->where('is_all_payment_method', 1);
                        });
                    })->first();

        if (empty($getCode)) {
            return [];
        }

        return $getCode;
    }

    public function stopAutoresponse($id_autoresponse_code)
    {
        $checkAvailableCode = AutoresponseCodeList::where('id_autoresponse_code', $id_autoresponse_code)
            ->whereNull('autoresponse_code_list.id_user')->pluck('id_autoresponse_code_list')->toArray();

        if (empty($checkAvailableCode)) {
            AutoresponseCode::where('id_autoresponse_code', $id_autoresponse_code)->update(['is_stop' => 1]);
        }

        return true;
    }
}
