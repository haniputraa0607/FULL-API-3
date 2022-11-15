<?php

namespace Modules\UserRating\Http\Controllers;

use App\Http\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionConsultation;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\UserRating\Entities\UserRating;
use Modules\UserRating\Entities\RatingOption;
use Modules\UserRating\Entities\UserRatingPhoto;
use Modules\UserRating\Entities\UserRatingSummary;
use Modules\Doctor\Entities\Doctor;
use Modules\Transaction\Entities\TransactionProductService;
use App\Lib\MyHelper;
use Modules\UserRating\Entities\UserRatingLog;
use Modules\OutletApp\Http\Controllers\ApiOutletApp;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Favorite\Entities\FavoriteUserHiarStylist;

class ApiUserRatingController extends Controller
{
    public function __construct()
    {
        $this->getNotif         = "Modules\Transaction\Http\Controllers\ApiNotification";
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post = $request->json()->all();
        $data = UserRating::with([
            'transaction' => function ($query) {
                $query->select('id_transaction', 'transaction_receipt_number', 'trasaction_type', 'transaction_grandtotal', 'id_outlet');
            },
            'transaction.outlet' => function ($query) {
                $query->select('id_outlet', 'outlet_code', 'outlet_name');
            },
            'user' => function ($query) {
                $query->select('id', 'name', 'phone');
            },
            'product' => function ($query) {
                $query->select('id_product', 'product_code', 'product_name');
            },
            'doctor' => function ($query) {
                $query->select('id_doctor', 'doctor_name', 'doctor_phone');
            }
        ])->orderBy('user_ratings.created_at', 'desc');

        // if($outlet_code = ($request['outlet_code']??false)){
        //     $data->whereHas('transaction.outlet',function($query) use ($outlet_code){
        //         $query->where('outlet_code',$outlet_code);
        //     });
        // }

        if ($post['rule'] ?? false) {
            $this->filterList($data, $post['rule'], $post['operator'] ?? 'and');
        }

        $data = $data->paginate(10)->toArray();
        return MyHelper::checkGet($data);
    }

    public function filterList($model, $rule, $operator = 'and')
    {
        $newRule = [];
        $where = $operator == 'and' ? 'where' : 'orWhere';
        foreach ($rule as $var) {
            $var1 = ['operator' => $var['operator'] ?? '=','parameter' => $var['parameter'] ?? null];
            if ($var1['operator'] == 'like') {
                $var1['parameter'] = '%' . $var1['parameter'] . '%';
            }
            $newRule[$var['subject']][] = $var1;
        }
        if ($rules = $newRule['review_date'] ?? false) {
            foreach ($rules as $rul) {
                $model->{$where . 'Date'}('created_at', $rul['operator'], $rul['parameter']);
            }
        }
        if ($rules = $newRule['star'] ?? false) {
            foreach ($rules as $rul) {
                $model->$where('rating_value', $rul['operator'], $rul['parameter']);
            }
        }
        if ($rules = $newRule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $model->{$where . 'Has'}('transaction', function ($query) use ($rul) {
                    $query->whereDate('transaction_date', $rul['operator'], $rul['parameter']);
                });
            }
        }
        if ($rules = $newRule['transaction_type'] ?? false) {
            foreach ($rules as $rul) {
                $model->{$where . 'Has'}('transaction', function ($query) use ($rul) {
                    $query->where('transaction_type', $rul['operator'], $rul['parameter']);
                });
            }
        }
        if ($rules = $newRule['transaction_receipt_number'] ?? false) {
            foreach ($rules as $rul) {
                $model->{$where . 'Has'}('transaction', function ($query) use ($rul) {
                    $query->where('transaction_receipt_number', $rul['operator'], $rul['parameter']);
                });
            }
        }
        if ($rules = $newRule['user_name'] ?? false) {
            foreach ($rules as $rul) {
                $model->{$where . 'Has'}('user', function ($query) use ($rul) {
                    $query->where('name', $rul['operator'], $rul['parameter']);
                });
            }
        }
        if ($rules = $newRule['user_phone'] ?? false) {
            foreach ($rules as $rul) {
                $model->{$where . 'Has'}('user', function ($query) use ($rul) {
                    $query->where('phone', $rul['operator'], $rul['parameter']);
                });
            }
        }
        if ($rules = $newRule['user_email'] ?? false) {
            foreach ($rules as $rul) {
                $model->{$where . 'Has'}('user', function ($query) use ($rul) {
                    $query->where('email', $rul['operator'], $rul['parameter']);
                });
            }
        }
        if ($rules = $newRule['outlet'] ?? false) {
            foreach ($rules as $rul) {
                $model->{$where . 'Has'}('transaction.outlet', function ($query) use ($rul) {
                    $query->where('id_outlet', $rul['operator'], $rul['parameter']);
                });
            }
        }

        if ($rules = $newRule['product_name'] ?? false) {
            foreach ($rules as $rul) {
                $model->{$where . 'Has'}('product', function ($query) use ($rul) {
                    $query->where('product_name', $rul['operator'], $rul['parameter']);
                });
            }
        }

        if ($rules = $newRule['rating_target'] ?? false) {
            foreach ($rules as $rul) {
                if ($rul['parameter'] == 'product') {
                    $model->{$where . 'NotNull'}('user_ratings.id_product');
                } elseif ($rul['parameter'] == 'doctor') {
                    $model->{$where . 'NotNull'}('doctors.id_doctor');
                } else {
                    $model->{$where . 'NotNull'}('user_ratings.id_outlet');
                }
            }
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $post = $request->json()->all();
        $id = $post['id'];
        $user = $request->user();
        $trx = Transaction::where([
            'id_transaction' => $id,
            'id_user' => $request->user()->id
        ])->first();
        if (!$trx) {
            return [
                'status' => 'fail',
                'messages' => ['Transaction not found']
            ];
        }

        $id_outlet = $trx->id_outlet;
        $id_doctor = null;
        $id_transaction_product_service = null;
        if (isset($post['id_doctor'])) {
            if (isset($post['id_doctor'])) {
                $trxService = TransactionConsultation::where('id_transaction', $id)
                            ->where('id_doctor', $post['id_doctor'])
                            ->where('id_transaction_consultation', $post['id_transaction_consultation'])
                            ->first();

                if (!$trxService) {
                    return [
                        'status' => 'fail',
                        'messages' => ['Doctor not found']
                    ];
                }

                $id_doctor = $trxService->id_doctor;
                $id_transaction_consultation = $trxService->id_transaction_consultation;
                $dc = $trxService->doctor;
            } else {
                $dc = Doctor::where('id_doctor', $post['id_doctor'])->first();
                if (!$dc) {
                    return [
                        'status' => 'fail',
                        'messages' => ['Doctor not found']
                    ];
                }
                $id_doctor = $dc->id_doctor;
            }
            $id_outlet = null;
        }

        $max_rating_value = Setting::select('value')->where('key', 'response_max_rating_value_doctor')->pluck('value')->first() ?: 2;
        if ($post['rating_value'] <= $max_rating_value) {
            $trx->load('outlet_name');
            $variables = [
                'receipt_number' => $trx->transaction_receipt_number,
                'outlet_name' => $trx->outlet_name->outlet_name,
                'transaction_date' => date('d F Y H:i', strtotime($trx->transaction_date)),
                'rating_value' => (string) $post['rating_value'],
                'suggestion' => $post['suggestion'] ?? '',
                'question' => $post['option_question'],
                'nickname' => $dc['nickname'],
                'fullname' => $dc['fullname'],
                'selected_option' => implode(',', array_map(function ($var) {
                    return trim($var, '"');
                }, $post['option_value'] ?? []))
            ];
            //app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('User Rating Doctor', $user->phone, $variables,null,true);
        }

        $insert = [
            'id_transaction' => $trx->id_transaction,
            'id_user' => $request->user()->id,
            'id_outlet' => $id_outlet,
            'id_doctor' => $id_doctor,
            'rating_value' => $post['rating_value'],
            'suggestion' => $post['suggestion'] ?? '',
            'option_question' => $post['option_question'],
            'option_value' => implode(',', array_map(function ($var) {
                return trim($var, '"');
            }, $post['option_value'] ?? []))
        ];

        $create = UserRating::updateOrCreate([
            'id_user' => $request->user()->id,
            'id_transaction' => $id,
            'id_outlet' => $id_outlet,
            'id_doctor' => $id_doctor,
            'id_transaction_consultation' => $id_transaction_consultation
        ], $insert);


        if ($id_doctor) {
            $dcRating = UserRating::where('id_doctor', $id_doctor)->get()->toArray();
            if ($dcRating) {
                $totalDcRating = array_sum(array_column($dcRating, 'rating_value')) / count($dcRating);
                Doctor::where('id_doctor', $post['id_doctor'])->update(['total_rating' => $totalDcRating]);
            }
        }

        UserRatingLog::where([
            'id_user' => $request->user()->id_doctor,
            'id_transaction' => $id,
            'id_outlet' => $id_outlet,
            'id_doctor' => $id_doctor,
            'id_transaction_consultation' => $id_transaction_consultation
        ])->delete();

        $unrated = UserRatingLog::where('id_transaction', $trx->id_transaction)->first();
        if (!$unrated) {
            $uncompleteTrx = TransactionConsultation::where('id_transaction', $trx->id_transaction)
                            ->whereNull('completed_at')
                            ->first();

            if (!$uncompleteTrx) {
                (new ApiOutletApp())->insertUserCashback($trx);
            }
            Transaction::where('id_transaction', $trx->id_transaction)->update(['show_rate_popup' => 0]);
        }

        $countRatingValue = UserRating::where([
            'id_outlet' => $id_outlet,
            'id_doctor' => $id_doctor,
            'rating_value' => $post['rating_value']
        ])->count();

        $summaryRatingValue = UserRatingSummary::updateOrCreate([
            'id_outlet' => $id_outlet,
            'id_doctor' => $id_doctor,
            'key' => $post['rating_value'],
            'summary_type' => 'rating_value'
        ], [
            'value' => $countRatingValue
        ]);

        foreach ($post['option_value'] ?? [] as $value) {
            $countOptionValue = UserRating::where([
                'id_outlet' => $id_outlet,
                'id_doctor' => $id_doctor,
                ['option_value', 'like', '%' . $value . '%']
            ])->count();

            $summaryOptionValue = UserRatingSummary::updateOrCreate([
                'id_outlet' => $id_outlet,
                'id_doctor' => $id_doctor,
                'key' => $value,
                'summary_type' => 'option_value'
            ], [
                'value' => $countOptionValue
            ]);
        }

        return MyHelper::checkCreate($create);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show(Request $request)
    {
        $post = $request->json()->all();
        $data = UserRating::with([
            'transaction' => function ($query) {
                $query->select('id_transaction', 'transaction_receipt_number', 'trasaction_type', 'transaction_grandtotal', 'id_outlet');
            },
            'transaction.outlet' => function ($query) {
                $query->select('id_outlet', 'outlet_code', 'outlet_name');
            },
            'user' => function ($query) {
                $query->select('id', 'name', 'phone');
            },
            'product' => function ($query) {
                $query->select('id_product', 'product_code', 'product_name');
            },
            'doctor' => function ($query) {
                $query->select('id_doctor', 'doctor_name', 'doctor_phone');
            }
        ])->where(['id_user_rating' => $post['id']])->first();
        return MyHelper::checkGet($data);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        return MyHelper::checkDelete(UserRating::find($request->json('id_user_rating'))->delete());
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function getDetail(Request $request)
    {
        $post = $request->json()->all();
        $user = clone $request->user();

        if (isset($post['id'])) {
            $id_transaction = $post['id'];
            $user->load('log_popup_user_rating');

            $transaction = Transaction::find($id_transaction);
            if (!$transaction) {
                return [
                    'status' => 'fail',
                    'messages' => ['Transaction not found']
                ];
            }

            $logRatings = UserRatingLog::where('id_transaction', $id_transaction)
                        ->where('id_user', $user->id)
                        ->with('transaction.outlet')
                        ->get();
        } else {
            $user->load('log_popup_user_rating.transaction.outlet');
            $log_popup_user_ratings = $user->log_popup_user_rating;
            $log_popup_user_rating = null;
            $logRatings = [];
            $interval = (Setting::where('key', 'popup_min_interval')->pluck('value')->first() ?: 900);
            $max_date = date('Y-m-d', time() - ((Setting::select('value')->where('key', 'popup_max_days')->pluck('value')->first() ?: 3) * 86400));
            $maxList = Setting::where('key', 'popup_max_list')->pluck('value')->first() ?: 5;

            if (empty($log_popup_user_ratings)) {
                return MyHelper::checkGet([]);
            }

            foreach ($log_popup_user_ratings as $log_pop) {
                if (
                    $log_pop->refuse_count >= (Setting::where('key', 'popup_max_refuse')->pluck('value')->first() ?: 3) ||
                    strtotime($log_pop->last_popup) + $interval > time()
                ) {
                    continue;
                }

                if ($log_popup_user_rating && $log_popup_user_rating->last_popup < $log_pop->last_popup) {
                    continue;
                }

                $log_popup_user_rating = $log_pop;
                $transaction = Transaction::select('id_transaction', 'transaction_receipt_number', 'transaction_date', 'id_outlet')
                ->with(['outlet' => function ($query) {
                    $query->select('outlet_name', 'id_outlet');
                }])
                ->where('id_transaction', $log_popup_user_rating->id_transaction)
                ->where(['id_user' => $user->id])
                ->whereDate('transaction_date', '>', $max_date)
                ->orderBy('transaction_date', 'asc')
                ->first();

                // check if transaction is exist
                if (!$transaction) {
                    // log popup is not valid
                    continue;
                    $log_popup_user_rating->delete();
                    return $this->getDetail($request);
                }

                $log_popup_user_rating->refuse_count++;
                $log_popup_user_rating->last_popup = date('Y-m-d H:i:s');
                $log_popup_user_rating->save();
                $logRatings[] = $log_popup_user_rating;

                if ($maxList <= count($logRatings)) {
                    break;
                }
            }

            if (empty($logRatings)) {
                return MyHelper::checkGet([]);
            }
        }

        $defaultOptions = [
            'question' => Setting::where('key', 'default_rating_question')->pluck('value_text')->first() ?: 'What\'s best from us?',
            'options' => explode(',', Setting::where('key', 'default_rating_options')->pluck('value_text')->first() ?: 'Cleanness,Accuracy,Employee Hospitality,Process Time')
        ];

        $optionOutlet = ['1' => $defaultOptions,'2' => $defaultOptions,'3' => $defaultOptions,'4' => $defaultOptions,'5' => $defaultOptions];
        $ratingOptionOutlet = RatingOption::select('star', 'question', 'options')->where('rating_target', 'outlet')->get();
        foreach ($ratingOptionOutlet as $rt) {
            $stars = explode(',', $rt['star']);
            foreach ($stars as $star) {
                $optionOutlet[$star] = [
                    'question' => $rt['question'],
                    'options' => explode(',', $rt['options'])
                ];
            }
        }
        $optionOutlet = array_values($optionOutlet);

        $optionHs = ['1' => $defaultOptions,'2' => $defaultOptions,'3' => $defaultOptions,'4' => $defaultOptions,'5' => $defaultOptions];
        $ratingOptionHs = RatingOption::select('star', 'question', 'options')->where('rating_target', 'doctor')->get();
        foreach ($ratingOptionHs as $rt) {
            $stars = explode(',', $rt['star']);
            foreach ($stars as $star) {
                $optionHs[$star] = [
                    'question' => $rt['question'],
                    'options' => explode(',', $rt['options'])
                ];
            }
        }
        $optionHs = array_values($optionHs);

        $ratingList = [];
        $title = 'Beri Penilaian';
        $message = "Dapatkan loyalty points dengan memberikan penilaian atas transaksi Anda pada hari:  /n <b>'%date%' di '%outlet_address%'</b>";
        foreach ($logRatings as $key => $log) {
            $rating['id'] = $log['id_transaction'];
            $rating['id_consultation'] = $log['id_consultation'];
            $rating['id_doctor'] = null;
            $rating['detail_doctor'] = null;
            $rating['transaction_receipt_number'] = $log['transaction']['transaction_receipt_number'];
            $rating['transaction_date'] = date('d M Y H:i', strtotime($log['transaction']['transaction_date']));

            $trxDate = MyHelper::dateFormatInd($log['transaction']['transaction_date'], true, false, true);

            $rating['title'] = $title;
            $rating['messages'] = "Silahkan Beri Penilaian Anda:  \n <b>";

            $rating['question_text'] = Setting::where('key', 'rating_question_text')->pluck('value_text')->first() ?: 'How about our Service';
            $rating['rating'] = null;
            $rating['options'] = null;

            if (!empty($log['id_doctor'])) {
                $rating['id_doctor'] = $log['id_doctor'];
                $rating['options'] = $optionHs;

                if (!empty($log['id_transaction_consultation'])) {
                    $service = TransactionConsultation::with('doctor')
                                ->where('id_transaction', $log['id_transaction'])
                                ->where('id_doctor', $log['id_doctor'])
                                ->where('id_transaction_consultation', $log['id_transaction_consultation'])
                                ->first();
                    $dc = $service->doctor;
                } else {
                    $dc = Doctor::where('id_doctor', $log['id_doctor'])->first();
                }

                // $isFavorite = FavoriteUserHiarStylist::where('id_user_hair_stylist',$log['id_user_hair_stylist'])
                //              ->where('id_user', $log['id_user'])
                //              ->first();

                //get specialist
                $specialist = null;
                foreach ($dc->specialists as $key => $spec) {
                    if ($key == 0) {
                        $specialist = $spec->doctor_specialist_name;
                    } else {
                        $specialist = $specialist . ", " . $spec->doctor_specialist_name;
                    }
                }

                $rating['detail_doctor'] = [
                    'doctor_name' => $dc->doctor_name ?? null,
                    'doctor_photo' => $dc->doctor_photo ?? null,
                    // 'is_favorite' => $isFavorite ? 1 : 0
                    'doctor_clinic' => $dc->clinic->doctor_clinic_name ?? null,
                    'doctor_specialist' => $specialist ?? null
                ];
            } else {
                $rating['options'] = $optionOutlet;
            }

            $currentRating = UserRating::where([
                'id_transaction' => $log['id_transaction'],
                'id_user' => $log['id_user'],
                'id_outlet' => $log['id_outlet'],
                'id_doctor' => $log['id_doctor']
            ])
            ->first();

            if ($currentRating) {
                $currentOption = explode(',', $currentRating['option_value']);
                $rating['rating'] = [
                    "rating_value" => $currentRating['rating_value'],
                    "suggestion" => $currentRating['suggestion'],
                    "option_value" => $currentOption
                ];
            }

            $ratingList[] = $rating;
        }

        $result = $ratingList;
        return MyHelper::checkGet($result);
    }

    public function report(Request $request)
    {
        $post = $request->json()->all();
        $showOutlet = 10;
        $counter = UserRating::select(\DB::raw('rating_value,count(id_user_rating) as total'))
        ->join('transactions', 'transactions.id_transaction', '=', 'user_ratings.id_transaction')
        ->groupBy('rating_value');
        $this->applyFilter($counter, $post);
        $counter = $counter->get()->toArray();
        foreach ($counter as &$value) {
            $datax = UserRating::where('rating_value', $value['rating_value'])
                ->join('transactions', 'transactions.id_transaction', '=', 'user_ratings.id_transaction')
                ->with([
                'transaction' => function ($query) {
                    $query->select('id_transaction', 'transaction_receipt_number', 'trasaction_type', 'transaction_grandtotal');
                },
                'user' => function ($query) {
                    $query->select('id', 'name', 'phone');
                },
                'product' => function ($query) {
                    $query->select('id_product', 'product_code', 'product_name');
                },
                'doctor' => function ($query) {
                    $query->select('id_doctor', 'doctor_phone', 'doctor_name');
                }
            ])->take(10);
            $this->applyFilter($datax, $post);
            $value['data'] = $datax->get();
        }
        $outlet5 = UserRating::select(\DB::raw('outlets.id_outlet,outlet_name,outlet_code,user_ratings.rating_value,count(*) as total'))
        ->join('transactions', 'transactions.id_transaction', '=', 'user_ratings.id_transaction')
        ->join('outlets', 'transactions.id_outlet', '=', 'outlets.id_outlet')
        ->where('rating_value', '5')
        ->groupBy('outlets.id_outlet')
        ->orderBy('total', 'desc')
        ->take($showOutlet);
        $this->applyFilter($outlet5, $post);
        for ($i = 4; $i > 0; $i--) {
            $outlet = UserRating::select(\DB::raw('outlets.id_outlet,outlet_name,outlet_code,user_ratings.rating_value,count(*) as total'))
            ->join('transactions', 'transactions.id_transaction', '=', 'user_ratings.id_transaction')
            ->join('outlets', 'transactions.id_outlet', '=', 'outlets.id_outlet')
            ->where('rating_value', $i)
            ->groupBy('outlets.id_outlet')
            ->orderBy('total', 'desc')
            ->take($showOutlet);
            $this->applyFilter($outlet, $post);
            $outlet5->union($outlet);
        }
        $data['rating_item'] = $counter;
        $data['rating_item_count'] = count($counter);
        $data['rating_data'] = $outlet5->get();

        if (!empty($post['id_outlet'])) {
            $data['outlet_name'] = Outlet::where('id_outlet', $post['id_outlet'])->first()['outlet_name'] ?? '';
        }
        return MyHelper::checkGet($data);
    }
    // apply filter photos only/notes_only
    public function applyFilter($model, $rule, $col = 'user_ratings')
    {
        if ($rule['notes_only'] ?? false) {
            $model->whereNotNull($col . '.suggestion');
            $model->where($col . '.suggestion', '<>', '');
        }
        if (($rule['transaction_type'] ?? false) == 'online') {
            $model->where('trasaction_type', 'pickup order');
        } elseif (($rule['transaction_type'] ?? false) == 'offline') {
            $model->where('trasaction_type', 'offline');
        }

        if (($rule['rating_target'] ?? false) == 'product') {
            $model->whereNotNull($col . '.id_product');
            if (!empty($rule['id_outlet'])) {
                $model->where($col . '.id_outlet', $rule['id_outlet']);
            }
        }

        if (($rule['rating_target'] ?? false) == 'doctor') {
            $model->whereNotNull($col . '.id_doctor');
            if (!empty($rule['id_doctor'])) {
                $model->where($col . '.id_outlet', $rule['id_outlet']);
            }
        }

        $model->whereDate($col . '.created_at', '>=', $rule['date_start'])->whereDate($col . '.created_at', '<=', $rule['date_end']);
    }
    public function reportOutlet(Request $request)
    {
        $post = $request->json()->all();
        $data = Outlet::where('outlet_status', 'Active');

        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $rule = 'and';
            if (isset($post['rule'])) {
                $rule = $post['rule'];
            }

            if ($rule == 'and') {
                foreach ($post['conditions'] as $row) {
                    if (isset($row['subject'])) {
                        if ($row['subject'] == 'outlet_total_rating') {
                            $data->where($row['subject'], $row['operator'], $row['parameter']);
                        } else {
                            if ($row['operator'] == '=') {
                                $data->where($row['subject'], $row['parameter']);
                            } else {
                                $data->where($row['subject'], 'like', '%' . $row['parameter'] . '%');
                            }
                        }
                    }
                }
            } else {
                $data->where(function ($subquery) use ($post) {
                    foreach ($post['conditions'] as $row) {
                        if (isset($row['subject'])) {
                            if ($row['subject'] == 'outlet_total_rating') {
                                $subquery->orWhere($row['subject'], $row['operator'], $row['parameter']);
                            } else {
                                if ($row['operator'] == '=') {
                                    $subquery->orWhere($row['subject'], $row['parameter']);
                                } else {
                                    $subquery->orWhere($row['subject'], 'like', '%' . $row['parameter'] . '%');
                                }
                            }
                        }
                    }
                });
            }
        }

        $data = $data->paginate(25);
        return response()->json(MyHelper::checkGet($data));
    }

    public function reportProduct(Request $request)
    {
        $post = $request->json()->all();
        if ($post['id_product'] ?? false) {
            $product = Product::select(\DB::raw('
            	products.id_product,
            	products.product_code,
            	products.product_name,
            	count(f1.id_user_rating) as rating1,
            	count(f2.id_user_rating) as rating2,
            	count(f3.id_user_rating) as rating3,
            	count(f4.id_user_rating) as rating4,
            	count(f5.id_user_rating) as rating5
        	'))
            ->where('products.id_product', $post['id_product'])
            ->join('transaction_products', 'transaction_products.id_product', '=', 'products.id_product')
            ->leftJoin('user_ratings as f1', function ($join) use ($post) {
                $join->on('f1.id_transaction', '=', 'transaction_products.id_transaction')
                ->where('f1.rating_value', '=', '1');
                $this->applyFilter($join, $post, 'f1');
            })
            ->leftJoin('user_ratings as f2', function ($join) use ($post) {
                $join->on('f2.id_transaction', '=', 'transaction_products.id_transaction')
                ->where('f2.rating_value', '=', '2');
                $this->applyFilter($join, $post, 'f2');
            })
            ->leftJoin('user_ratings as f3', function ($join) use ($post) {
                $join->on('f3.id_transaction', '=', 'transaction_products.id_transaction')
                ->where('f3.rating_value', '=', '3');
                $this->applyFilter($join, $post, 'f3');
            })
            ->leftJoin('user_ratings as f4', function ($join) use ($post) {
                $join->on('f4.id_transaction', '=', 'transaction_products.id_transaction')
                ->where('f4.rating_value', '=', '4');
                $this->applyFilter($join, $post, 'f4');
            })
            ->leftJoin('user_ratings as f5', function ($join) use ($post) {
                $join->on('f5.id_transaction', '=', 'transaction_products.id_transaction')
                ->where('f5.rating_value', '=', '5');
                $this->applyFilter($join, $post, 'f5');
            })->first();
            if (!$product) {
                return MyHelper::checkGet($product);
            }
            $data['rating_data'] = $product;
            $counter['data'] = [];
            for ($i = 1; $i <= 5; $i++) {
                $datax = UserRating::where('rating_value', $i)->with([
                    'transaction' => function ($query) {
                        $query->select('id_transaction', 'transaction_receipt_number', 'trasaction_type', 'transaction_grandtotal');
                    },
                    'user' => function ($query) {
                        $query->select('id', 'name', 'phone');
                    },
                    'product' => function ($query) {
                        $query->select('id_product', 'product_code', 'product_name');
                    }
                ])
                ->join('transactions', 'transactions.id_transaction', '=', 'user_ratings.id_transaction')
                ->where('user_ratings.id_product', $product->id_product)
                ->take(10);
                $this->applyFilter($datax, $post);
                $counter['data'][$i] = $datax->get();
            }
            $data['rating_item'] = $counter;
            return MyHelper::checkGet($data);
        } else {
            $dasc = ($post['order'] ?? 'product_name') == 'product_name' ? 'asc' : 'desc';
            $product = Product::select(\DB::raw('
            		products.id_product,
                    products.product_code,
                    products.product_name,
                    count(f1.id_user_rating) as rating1,
                    count(f2.id_user_rating) as rating2,
                    count(f3.id_user_rating) as rating3,
                    count(f4.id_user_rating) as rating4,
                    count(f5.id_user_rating) as rating5
        		'))
            ->join('transaction_products', 'products.id_product', '=', 'transaction_products.id_product')
            ->leftJoin('user_ratings as f1', function ($join) use ($post) {
                $join->on('f1.id_transaction', '=', 'transaction_products.id_transaction')
                ->where('f1.rating_value', '=', '1');
                $this->applyFilter($join, $post, 'f1');
            })
            ->leftJoin('user_ratings as f2', function ($join) use ($post) {
                $join->on('f2.id_transaction', '=', 'transaction_products.id_transaction')
                ->where('f2.rating_value', '=', '2');
                $this->applyFilter($join, $post, 'f2');
            })
            ->leftJoin('user_ratings as f3', function ($join) use ($post) {
                $join->on('f3.id_transaction', '=', 'transaction_products.id_transaction')
                ->where('f3.rating_value', '=', '3');
                $this->applyFilter($join, $post, 'f1');
            })
            ->leftJoin('user_ratings as f4', function ($join) use ($post) {
                $join->on('f4.id_transaction', '=', 'transaction_products.id_transaction')
                ->where('f4.rating_value', '=', '4');
                $this->applyFilter($join, $post, 'f4');
            })
            ->leftJoin('user_ratings as f5', function ($join) use ($post) {
                $join->on('f5.id_transaction', '=', 'transaction_products.id_transaction')
                ->where('f5.rating_value', '=', '5');
                $this->applyFilter($join, $post, 'f5');
            })
            ->orderBy($post['order'] ?? 'product_name', $dasc)
            ->groupBy('products.id_product');
            if ($post['search'] ?? false) {
                $product->where(function ($query) use ($post) {
                    $param = '%' . $post['search'] . '%';
                    $query->where('product_name', 'like', $param)
                    ->orWhere('product_code', 'like', $param);
                });
            }
            return MyHelper::checkGet($product->paginate(15)->toArray());
        }
    }

    public function getRated(Request $request)
    {
        $post = $request->json()->all();
        $user = clone $request->user();

        $logRatings = UserRating::where('id_user', $user->id)
                        ->with('transaction.outlet.brands');

        if (isset($post['id'])) {
            $id_transaction = $post['id'];

            $transaction = Transaction::find($id_transaction);
            if (!$transaction) {
                return [
                    'status' => 'fail',
                    'messages' => ['Transaction not found']
                ];
            }

            $logRatings = $logRatings->where('id_transaction', $id_transaction);

            if (isset($post['id_transaction_product_service'])) {
                $logRatings = $logRatings->where('id_transaction_product_service', $post['id_transaction_product_service']);
            }
        }

        $logRatings = $logRatings->get();

        $ratingList = [];
        foreach ($logRatings as $key => $log) {
            $rating['id'] = $log['id_transaction'];
            $rating['id_transaction_product_service'] = $log['id_transaction_product_service'];
            $rating['id_doctor'] = null;
            $rating['detail_doctor'] = null;
            $rating['transaction_receipt_number'] = $log['transaction']['transaction_receipt_number'];
            $rating['transaction_date'] = date('d M Y H:i', strtotime($log['transaction']['transaction_date']));

            $trxDate = MyHelper::dateFormatInd($log['transaction']['transaction_date'], true, false, true);
            $outletName = $log['transaction']['outlet']['outlet_name'];

            $rating['outlet'] = null;
            if (!empty($log['transaction']['outlet'])) {
                $rating['outlet'] = [
                    'id_outlet' => $log['transaction']['outlet']['id_outlet'],
                    'outlet_code' => $log['transaction']['outlet']['outlet_code'],
                    'outlet_name' => $log['transaction']['outlet']['outlet_name'],
                    'outlet_address' => $log['transaction']['outlet']['outlet_address'],
                    'outlet_latitude' => $log['transaction']['outlet']['outlet_latitude'],
                    'outlet_longitude' => $log['transaction']['outlet']['outlet_longitude']
                ];
            }
            $rating['brand'] = null;
            if (!empty($log['transaction']['outlet']['brands'])) {
                $rating['brand'] = [
                    'id_brand' => $log['transaction']['outlet']['brands'][0]['id_brand'],
                    'brand_code' => $log['transaction']['outlet']['brands'][0]['code_brand'],
                    'brand_name' => $log['transaction']['outlet']['brands'][0]['name_brand'],
                    'brand_logo' => $log['transaction']['outlet']['brands'][0]['logo_brand'],
                    'brand_logo_landscape' => $log['transaction']['outlet']['brands'][0]['logo_landscape_brand']
                ];
            }
            $rating['rating'] = null;

            if (!empty($log['id_doctor'])) {
                $rating['id_doctor'] = $log['id_doctor'];

                if (!empty($log['id_transaction_product_service'])) {
                    $service = TransactionProductService::with('doctor')
                                ->where('id_transaction', $log['id_transaction'])
                                ->where('id_doctor', $log['id_doctor'])
                                ->where('id_transaction_product_service', $log['id_transaction_product_service'])
                                ->first();
                    $dc = $service->doctor;
                } else {
                    $dc = Doctor::where('id_doctor', $log['id_doctor'])->first();
                }

                // $isFavorite = FavoriteUserHiarStylist::where('id_doctor',$log['id_doctor'])
                //              ->where('id_user', $log['id_user'])
                //              ->first();

                $rating['detail_doctor'] = [
                    'nickname' => $dc->nickname ?? null,
                    'fullname' => $dc->fullname ?? null,
                    'doctor_photo' => $dc->doctor_photo ?? null,
                    'is_favorite' => $isFavorite ? 1 : 0
                ];
            }

            $currentRating = $log;

            if ($currentRating) {
                $currentOption = explode(',', $currentRating['option_value']);
                $rating['rating'] = [
                    "rating_value" => $currentRating['rating_value'],
                    "suggestion" => $currentRating['suggestion'],
                    "option_value" => $currentOption
                ];
            }

            $ratingList[] = $rating;
        }

        $result = $ratingList;
        return MyHelper::checkGet($result);
    }

    public function reportDoctor(Request $request)
    {
        $post = $request->json()->all();
        if ($post['id_doctor'] ?? false) {
            $dc = Doctor::select(\DB::raw('
            	doctors.id_doctor,
            	doctors.doctor_phone,
            	doctors.doctor_name,
            	count(f1.id_user_rating) as rating1,
            	count(f2.id_user_rating) as rating2,
            	count(f3.id_user_rating) as rating3,
            	count(f4.id_user_rating) as rating4,
            	count(f5.id_user_rating) as rating5
        	'))
            ->where('doctors.id_doctor', $post['id_doctor'])
            ->join('transaction_consultations', 'doctors.id_doctor', '=', 'transaction_consultations.id_doctor')
            ->leftJoin('user_ratings as f1', function ($join) use ($post) {
                $join->on('f1.id_transaction', '=', 'transaction_consultations.id_transaction')
                ->where('f1.rating_value', '=', '1');
                $this->applyFilter($join, $post, 'f1');
            })
            ->leftJoin('user_ratings as f2', function ($join) use ($post) {
                $join->on('f2.id_transaction', '=', 'transaction_consultations.id_transaction')
                ->where('f2.rating_value', '=', '2');
                $this->applyFilter($join, $post, 'f2');
            })
            ->leftJoin('user_ratings as f3', function ($join) use ($post) {
                $join->on('f3.id_transaction', '=', 'transaction_consultations.id_transaction')
                ->where('f3.rating_value', '=', '3');
                $this->applyFilter($join, $post, 'f3');
            })
            ->leftJoin('user_ratings as f4', function ($join) use ($post) {
                $join->on('f4.id_transaction', '=', 'transaction_consultations.id_transaction')
                ->where('f4.rating_value', '=', '4');
                $this->applyFilter($join, $post, 'f4');
            })
            ->leftJoin('user_ratings as f5', function ($join) use ($post) {
                $join->on('f5.id_transaction', '=', 'transaction_consultations.id_transaction')
                ->where('f5.rating_value', '=', '5');
                $this->applyFilter($join, $post, 'f5');
            })->first();
            if (!$dc) {
                return MyHelper::checkGet($dc);
            }
            $data['rating_data'] = $dc;
            $counter['data'] = [];
            for ($i = 1; $i <= 5; $i++) {
                $datax = UserRating::where('rating_value', $i)->with([
                    'transaction' => function ($query) {
                        $query->select('id_transaction', 'transaction_receipt_number', 'trasaction_type', 'transaction_grandtotal');
                    },
                    'user' => function ($query) {
                        $query->select('id', 'name', 'phone');
                    },
                    'doctor' => function ($query) {
                        $query->select('id_doctor', 'doctor_name', 'doctor_phone');
                    }
                ])
                ->join('transactions', 'transactions.id_transaction', '=', 'user_ratings.id_transaction')
                ->where('user_ratings.id_doctor', $dc->id_doctor)
                ->take(10);
                $this->applyFilter($datax, $post);
                $counter['data'][$i] = $datax->get();
            }
            $data['rating_item'] = $counter;
            return MyHelper::checkGet($data);
        } else {
            $dasc = ($post['order'] ?? 'doctor_name') == 'doctor_name' ? 'asc' : 'desc';
            $dc = Doctor::select(\DB::raw('
            		doctors.id_doctor,
            		doctors.doctor_phone,
            		doctors.doctor_name,
            		count(f1.id_user_rating) as rating1,
            		count(f2.id_user_rating) as rating2,
            		count(f3.id_user_rating) as rating3,
            		count(f4.id_user_rating) as rating4,
            		count(f5.id_user_rating) as rating5
        		'))
            ->join('transaction_consultations', 'doctors.id_doctor', '=', 'transaction_consultations.id_doctor')
            ->leftJoin('user_ratings as f1', function ($join) use ($post) {
                $join->on('f1.id_transaction', '=', 'transaction_consultations.id_transaction')
                ->where('f1.rating_value', '=', '1');
                $this->applyFilter($join, $post, 'f1');
            })
            ->leftJoin('user_ratings as f2', function ($join) use ($post) {
                $join->on('f2.id_transaction', '=', 'transaction_consultations.id_transaction')
                ->where('f2.rating_value', '=', '2');
                $this->applyFilter($join, $post, 'f2');
            })
            ->leftJoin('user_ratings as f3', function ($join) use ($post) {
                $join->on('f3.id_transaction', '=', 'transaction_consultations.id_transaction')
                ->where('f3.rating_value', '=', '3');
                $this->applyFilter($join, $post, 'f1');
            })
            ->leftJoin('user_ratings as f4', function ($join) use ($post) {
                $join->on('f4.id_transaction', '=', 'transaction_consultations.id_transaction')
                ->where('f4.rating_value', '=', '4');
                $this->applyFilter($join, $post, 'f4');
            })
            ->leftJoin('user_ratings as f5', function ($join) use ($post) {
                $join->on('f5.id_transaction', '=', 'transaction_consultations.id_transaction')
                ->where('f5.rating_value', '=', '5');
                $this->applyFilter($join, $post, 'f5');
            })
            ->orderBy($post['order'] ?? 'doctor_name', $dasc)
            ->groupBy('doctors.id_doctor');
            if ($post['search'] ?? false) {
                $hs->where(function ($query) use ($post) {
                    $param = '%' . $post['search'] . '%';
                    $query->where('doctor_name', 'like', $param);
                });
            }
            return MyHelper::checkGet($dc->paginate(15)->toArray());
        }
    }

    public function transactionGetDetail(Request $request)
    {
        $post = $request->json()->all();
        $user = clone $request->user();

        if (isset($post['id'])) {
            $id_transaction = $post['id'];
            $user->load('log_popup_user_rating');

            $transaction = Transaction::find($id_transaction);
            if (!$transaction) {
                return [
                    'status' => 'fail',
                    'messages' => ['Transaction not found']
                ];
            }

            $logRatings = UserRatingLog::where('id_transaction', $id_transaction)
                ->where('id_user', $user->id)
                ->with('transaction.outlet.brands')
                ->get();
        } else {
            $user->load('log_popup_user_rating.transaction.outlet');
            $log_popup_user_ratings = $user->log_popup_user_rating;
            $log_popup_user_rating = null;
            $logRatings = [];
            $interval = (Setting::where('key', 'popup_min_interval')->pluck('value')->first() ?: 900);
            $max_date = date('Y-m-d', time() - ((Setting::select('value')->where('key', 'popup_max_days')->pluck('value')->first() ?: 3) * 86400));
            $maxList = Setting::where('key', 'popup_max_list')->pluck('value')->first() ?: 5;

            if (empty($log_popup_user_ratings)) {
                return MyHelper::checkGet([]);
            }

            foreach ($log_popup_user_ratings as $log_pop) {
                if (
                    $log_pop->refuse_count >= (Setting::where('key', 'popup_max_refuse')->pluck('value')->first() ?: 3) ||
                    strtotime($log_pop->last_popup) + $interval > time()
                ) {
                    continue;
                }

                if ($log_popup_user_rating && $log_popup_user_rating->last_popup < $log_pop->last_popup) {
                    continue;
                }

                $log_popup_user_rating = $log_pop;
                $transaction = Transaction::select('id_transaction', 'transaction_receipt_number', 'transaction_date', 'id_outlet')
                    ->with(['outlet' => function ($query) {
                        $query->select('outlet_name', 'id_outlet');
                    }])
                    ->where('id_transaction', $log_popup_user_rating->id_transaction)
                    ->where(['id_user' => $user->id])
                    ->whereDate('transaction_date', '>', $max_date)
                    ->orderBy('transaction_date', 'asc')
                    ->first();

                // check if transaction is exist
                if (!$transaction) {
                    // log popup is not valid
                    continue;
                    $log_popup_user_rating->delete();
                    return $this->getDetail($request);
                }

                $log_popup_user_rating->refuse_count++;
                $log_popup_user_rating->last_popup = date('Y-m-d H:i:s');
                $log_popup_user_rating->save();
                $logRatings[] = $log_popup_user_rating;

                if ($maxList <= count($logRatings)) {
                    break;
                }
            }

            if (empty($logRatings)) {
                return MyHelper::checkGet([]);
            }
        }

        $defaultOptions = [
            'question' => Setting::where('key', 'default_rating_question')->pluck('value_text')->first() ?: 'What\'s best from us?',
            'options' => explode(',', Setting::where('key', 'default_rating_options')->pluck('value_text')->first() ?: 'Good Product,Fast Response')
        ];

        $optionProduct = ['1' => $defaultOptions,'2' => $defaultOptions,'3' => $defaultOptions,'4' => $defaultOptions,'5' => $defaultOptions];
        $ratingOptionProduct = RatingOption::select('star', 'question', 'options')->where('rating_target', 'product')->get();
        foreach ($ratingOptionProduct as $rt) {
            $stars = explode(',', $rt['star']);
            foreach ($stars as $star) {
                $optionProduct[$star] = [
                    'question' => $rt['question'],
                    'options' => explode(',', $rt['options'])
                ];
            }
        }

        $ratingList = [];
        $title = 'Beri Penilaian';
        $message = "Dapatkan loyalty points dengan memberikan penilaian atas transaksi Anda pada hari:  /n <b>'%date%' di '%outlet_address%'</b>";
        foreach ($logRatings as $key => $log) {
            $rating['id'] = $log['id_transaction'];
            $rating['id_product'] = $log['id_product'];
            $rating['transaction_receipt_number'] = $log['transaction']['transaction_receipt_number'];
            $rating['transaction_date'] = date('d M Y H:i', strtotime($log['transaction']['transaction_date']));

            $trxDate = MyHelper::dateFormatInd($log['transaction']['transaction_date'], true, false, true);
            $outletName = $log['transaction']['outlet']['outlet_name'];
            $rating['title'] = $title;
            $rating['messages'] = "Dapatkan loyalty points dengan memberikan penilaian atas transaksi Anda pada hari:  \n <b>" . $trxDate . " di " . $outletName . "</b>";

            $rating['outlet'] = [
                'id_outlet' => $log['transaction']['outlet']['id_outlet'],
                'outlet_code' => $log['transaction']['outlet']['outlet_code'],
                'outlet_name' => $log['transaction']['outlet']['outlet_name'],
                'outlet_address' => $log['transaction']['outlet']['outlet_address'],
                'outlet_latitude' => $log['transaction']['outlet']['outlet_latitude'],
                'outlet_longitude' => $log['transaction']['outlet']['outlet_longitude']
            ];

            $product = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                ->where('transaction_products.id_product', $log['id_product'])->first();
            $variants = [];
            if (!empty($product['id_product_variant_group'])) {
                $variants = ProductVariantGroup::join('product_variant_pivot', 'product_variant_pivot.id_product_variant_group', 'product_variant_groups.id_product_variant_group')
                    ->join('product_variants', 'product_variants.id_product_variant', 'product_variant_pivot.id_product_variant')
                    ->where('product_variant_groups.id_product_variant_group', $product['id_product_variant_group'])
                    ->pluck('product_variant_name')->toArray();
            }
            $rating['detail_product'] = [
                'product_name' => $product->product_name ?? null,
                'variants' => implode(',', $variants) ?? null
            ];

            $rating['question_text'] = Setting::where('key', 'product_rating_question_text')->pluck('value_text')->first() ?: 'Bagaimana menurutmu produk ini?';
            $rating['rating'] = null;
            $rating['options'] = $optionProduct;

            $currentRating = UserRating::where([
                'id_transaction' => $log['id_transaction'],
                'id_user' => $log['id_user'],
                'id_product' => $log['id_product']
            ])
                ->first();

            if ($currentRating) {
                $currentOption = explode(',', $currentRating['option_value']);
                $rating['rating'] = [
                    "rating_value" => $currentRating['rating_value'],
                    "suggestion" => $currentRating['suggestion'],
                    "option_value" => $currentOption
                ];
            }

            $ratingList[] = $rating;
        }

        $result = $ratingList;
        return MyHelper::checkGet($result);
    }

    public function transactionStore(Request $request)
    {
        $post = $request->all();
        $id = $post['id'];
        $user = $request->user();
        $trx = Transaction::where([
            'id_transaction' => $id,
            'id_user' => $request->user()->id
        ])->first();
        if (!$trx) {
            return [
                'status' => 'fail',
                'messages' => ['Transaction not found']
            ];
        }

        if (!empty($post['images']) && count($post['images']) > 3) {
            return [
                'status' => 'fail',
                'messages' => ['Maximum upload 3 image']
            ];
        }

        if (empty($post['option_question'])) {
            return [
                'status' => 'fail',
                'messages' => ['Option question can not be empty']
            ];
        }

        if (!empty($post['option_value'])) {
            $post['option_value'] = json_decode($post['option_value']);
        }

        $id_outlet = $trx->id_outlet;
        $id_product = null;
        if (!empty($post['id_product'])) {
            $trxProduct = TransactionProduct::where('id_transaction', $id)
                ->where('id_product', $post['id_product'])
                ->first();

            if (!$trxProduct) {
                return [
                    'status' => 'fail',
                    'messages' => ['Product not found']
                ];
            }

            $id_product = $trxProduct->id_product;
            $product = $trxProduct->product;
        } else {
            $product = Product::where('id_product', $id_product)->first();
            if (!$product) {
                return [
                    'status' => 'fail',
                    'messages' => ['Product not found']
                ];
            }
            $id_product = $product->id_product;
        }


        if ($id_product) {
            $max_rating_value = Setting::select('value')->where('key', 'response_max_rating_value_product')->pluck('value')->first() ?: 2;
            if ($post['rating_value'] <= $max_rating_value) {
                $trx->load('outlet_name');
                $variables = [
                    'receipt_number' => $trx->transaction_receipt_number,
                    'outlet_name' => $trx->outlet_name->outlet_name,
                    'transaction_date' => date('d F Y H:i', strtotime($trx->transaction_date)),
                    'rating_value' => (string) $post['rating_value'],
                    'suggestion' => $post['suggestion'] ?? '',
                    'question' => $post['option_question'],
                    'product_name' => Product::where('id_product', $id_product)->first()['product_name'] ?? '',
                    'selected_option' => implode(',', array_map(function ($var) {
                        return trim($var, '"');
                    }, $post['option_value'] ?? []))
                ];
                app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('User Rating Product', $user->phone, $variables, null, true);
            }
        } else {
            $max_rating_value = Setting::select('value')->where('key', 'response_max_rating_value')->pluck('value')->first() ?: 2;
            if ($post['rating_value'] <= $max_rating_value) {
                $trx->load('outlet_name');
                $variables = [
                    'receipt_number' => $trx->transaction_receipt_number,
                    'outlet_name' => $trx->outlet_name->outlet_name,
                    'transaction_date' => date('d F Y H:i', strtotime($trx->transaction_date)),
                    'rating_value' => (string) $post['rating_value'],
                    'suggestion' => $post['suggestion'] ?? '',
                    'question' => $post['option_question'],
                    'selected_option' => implode(',', array_map(function ($var) {
                        return trim($var, '"');
                    }, $post['option_value'] ?? []))
                ];
                app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('User Rating Outlet', $user->phone, $variables, null, true);
            }
        }

        $insert = [
            'id_transaction' => $trx->id_transaction,
            'id_user' => $request->user()->id,
            'id_outlet' => $id_outlet,
            'id_product' => $id_product,
            'rating_value' => $post['rating_value'],
            'suggestion' => $post['suggestion'] ?? '',
            'option_question' => $post['option_question'],
            'option_value' => implode(',', array_map(function ($var) {
                return trim($var, '"');
            }, $post['option_value'] ?? []))
        ];

        $create = UserRating::updateOrCreate([
            'id_user' => $request->user()->id,
            'id_transaction' => $id,
            'id_outlet' => $id_outlet,
            'id_product' => $id_product
        ], $insert);

        if ($create && !empty($post['images'])) {
            $img = [];
            foreach ($post['images'] ?? [] as $image) {
                $encode = base64_encode(fread(fopen($image, "r"), filesize($image)));
                $upload = MyHelper::uploadPhotoAllSize($encode, 'img/user_rating/' . $create['id_user_rating'] . '/');

                if (isset($upload['status']) && $upload['status'] == "success") {
                    $img[] = [
                        'id_user_rating' => $create['id_user_rating'],
                        'user_rating_photo' => $upload['path'],
                        'updated_at' => date('Y-m-d H:i:s'),
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                }
            }

            if (!empty($img)) {
                UserRatingPhoto::insert($img);
            }
        }

        if ($id_product) {
            $productRating = UserRating::where('id_product', $id_product)->get()->toArray();
            if ($productRating) {
                $totalProductRating = array_sum(array_column($productRating, 'rating_value')) / count($productRating);
                Product::where('id_product', $id_product)->update(['total_rating' => $totalProductRating]);
            }
        }

        if ($id_outlet) {
            $outletRating = UserRating::where('id_outlet', $id_outlet)->sum('rating_value');
            $countOutletRating = UserRating::where('id_outlet', $id_outlet)->count();
            if ($outletRating > 0) {
                $totalOutletRating = $outletRating / $countOutletRating;
                Outlet::where('id_outlet', $id_outlet)->update(['outlet_total_rating' => $totalOutletRating]);
            }
        }

        UserRatingLog::where([
            'id_user' => $request->user()->id,
            'id_transaction' => $id,
            'id_product' => $id_product
        ])->delete();

        $unrated = UserRatingLog::where('id_transaction', $trx->id_transaction)->first();
        if (!$unrated) {
            Transaction::where('id_transaction', $trx->id_transaction)->update(['show_rate_popup' => 0]);
        }

        $countRatingValue = UserRating::where([
            'id_outlet' => $id_outlet,
            'id_product' => $id_product,
            'rating_value' => $post['rating_value']
        ])->count();

        $summaryRatingValue = UserRatingSummary::updateOrCreate([
            'id_outlet' => $id_outlet,
            'id_product' => $id_product,
            'key' => $post['rating_value'],
            'summary_type' => 'rating_value'
        ], [
            'value' => $countRatingValue
        ]);

        foreach ($post['option_value'] ?? [] as $value) {
            $countOptionValue = UserRating::where([
                'id_outlet' => $id_outlet,
                'id_product' => $id_product,
                ['option_value', 'like', '%' . $value . '%']
            ])->count();

            $summaryOptionValue = UserRatingSummary::updateOrCreate([
                'id_outlet' => $id_outlet,
                'id_product' => $id_product,
                'key' => $value,
                'summary_type' => 'option_value'
            ], [
                'value' => $countOptionValue
            ]);
        }

        $countRating = UserRating::where('id_transaction', $trx->id_transaction)->count();
        $counTrxProduct = TransactionProduct::where('id_transaction', $trx->id_transaction)->count();
        if ($countRating == $counTrxProduct) {
            $newTrx = Transaction::where('id_transaction', $trx->id_transaction)
                ->with('user.memberships')->first();
            $savePoint = app($this->getNotif)->savePoint($newTrx);
            if (!$savePoint) {
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Failed insert point'],
                ]);
            }
        }

        return MyHelper::checkCreate($create);
    }

    public function transactionGetRated(Request $request)
    {
        $post = $request->json()->all();
        $user = clone $request->user();

        $logRatings = UserRating::where('id_user', $user->id)
            ->with('transaction.outlet');

        if (isset($post['id'])) {
            $id_transaction = $post['id'];

            $transaction = Transaction::find($id_transaction);
            if (!$transaction) {
                return [
                    'status' => 'fail',
                    'messages' => ['Transaction not found']
                ];
            }

            $logRatings = $logRatings->where('id_transaction', $id_transaction);

            if (isset($post['id_product'])) {
                $logRatings = $logRatings->where('id_product', $post['id_product']);
            }
        }

        $logRatings = $logRatings->get();

        $ratingList = [];
        foreach ($logRatings as $key => $log) {
            $rating['id'] = $log['id_transaction'];
            $rating['id_product'] = $log['id_product'];
            $rating['transaction_receipt_number'] = $log['transaction']['transaction_receipt_number'];
            $rating['transaction_date'] = date('d M Y H:i', strtotime($log['transaction']['transaction_date']));

            $rating['outlet'] = null;
            if (!empty($log['transaction']['outlet'])) {
                $rating['outlet'] = [
                    'id_outlet' => $log['transaction']['outlet']['id_outlet'],
                    'outlet_code' => $log['transaction']['outlet']['outlet_code'],
                    'outlet_name' => $log['transaction']['outlet']['outlet_name'],
                    'outlet_address' => $log['transaction']['outlet']['outlet_address'],
                    'outlet_latitude' => $log['transaction']['outlet']['outlet_latitude'],
                    'outlet_longitude' => $log['transaction']['outlet']['outlet_longitude']
                ];
            }
            $rating['rating'] = null;

            if (!empty($log['id_product'])) {
                $product = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                    ->where('transaction_products.id_product', $log['id_product'])->first();
                $variants = [];
                if (!empty($product['id_product_variant_group'])) {
                    $variants = ProductVariantGroup::join('product_variant_pivot', 'product_variant_pivot.id_product_variant_group', 'product_variant_groups.id_product_variant_group')
                        ->join('product_variants', 'product_variants.id_product_variant', 'product_variant_pivot.id_product_variant')
                        ->where('product_variant_groups.id_product_variant_group', $product['id_product_variant_group'])
                        ->pluck('product_variant_name')->toArray();
                }
                $rating['detail_product'] = [
                    'product_name' => $product->product_name ?? null,
                    'variants' => implode(',', $variants) ?? null
                ];

                $rating['question_text'] = Setting::where('key', 'product_rating_question_text')->pluck('value_text')->first() ?: 'Bagaimana menurutmu produk ini?';
            }

            $currentRating = $log;
            $getPhotos = UserRatingPhoto::where('id_user_rating', $currentRating['id_user_rating'])->get()->toArray();
            $photos = [];
            foreach ($getPhotos as $dt) {
                $photos[] = $dt['url_user_rating_photo'];
            }

            if ($currentRating) {
                $currentOption = explode(',', $currentRating['option_value']);
                $rating['rating'] = [
                    "rating_value" => $currentRating['rating_value'],
                    "suggestion" => $currentRating['suggestion'],
                    "option_value" => $currentOption,
                    "photos" => $photos
                ];
            }

            $ratingList[] = $rating;
        }

        $result = $ratingList;
        return MyHelper::checkGet($result);
    }

    public function consultationStore(Request $request)
    {
        $post = $request->all();
        $id = $post['id'];
        $user = $request->user();
        $trx = Transaction::where([
            'id_transaction' => $id,
            'id_user' => $request->user()->id
        ])->first();
        if (!$trx) {
            return [
                'status' => 'fail',
                'messages' => ['Transaction not found']
            ];
        }

        if (!empty($post['images']) && count($post['images']) > 3) {
            return [
                'status' => 'fail',
                'messages' => ['Maximum upload 3 image']
            ];
        }

        if (!empty($post['option_question'])) {
            $post['option_question'] = $post['option_question'];
        }

        if (!empty($post['option_value'])) {
            $post['option_value'] = json_decode($post['option_value']);
        }

        $id_outlet = $trx->id_outlet;
        $id_doctor = null;
        if (!empty($post['id_doctor'])) {
            $trxDoctor = TransactionConsultation::where('id_transaction', $id)
                ->where('id_doctor', $post['id_doctor'])
                ->first();

            if (!$trxDoctor) {
                return [
                    'status' => 'fail',
                    'messages' => ['Doctor not found']
                ];
            }

            $id_doctor = $trxDoctor->id_doctor;
            $doctor = $trxDoctor->doctor;
        } else {
            $doctor = Doctor::where('id_doctor', $id_doctor)->first();
            if (!$doctor) {
                return [
                    'status' => 'fail',
                    'messages' => ['Doctor not found']
                ];
            }
            $id_doctor = $doctor->id_doctor;
        }

        if ($id_doctor) {
            $max_rating_value = Setting::select('value')->where('key', 'response_max_rating_value_doctor')->pluck('value')->first() ?: 2;
            if ($post['rating_value'] <= $max_rating_value) {
                $trx->load('outlet_name');
                $variables = [
                    'receipt_number' => $trx->transaction_receipt_number,
                    'outlet_name' => $trx->outlet_name->outlet_name,
                    'transaction_date' => date('d F Y H:i', strtotime($trx->transaction_date)),
                    'rating_value' => (string) $post['rating_value'],
                    'suggestion' => $post['suggestion'] ?? '',
                    'question' => $post['option_question'] ?? '',
                    'doctor_name' => Doctor::where('id_doctor', $id_doctor)->first()['doctor_name'] ?? '',
                    'selected_option' => implode(',', array_map(function ($var) {
                        return trim($var, '"');
                    }, $post['option_value'] ?? []))
                ];
                app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('User Rating Doctor', $user->phone, $variables, null, true);
            }
        } else {
            $max_rating_value = Setting::select('value')->where('key', 'response_max_rating_value')->pluck('value')->first() ?: 2;
            if ($post['rating_value'] <= $max_rating_value) {
                $trx->load('outlet_name');
                $variables = [
                    'receipt_number' => $trx->transaction_receipt_number,
                    'outlet_name' => $trx->outlet_name->outlet_name,
                    'transaction_date' => date('d F Y H:i', strtotime($trx->transaction_date)),
                    'rating_value' => (string) $post['rating_value'],
                    'suggestion' => $post['suggestion'] ?? '',
                    'question' => $post['option_question'] ?? '',
                    'selected_option' => implode(',', array_map(function ($var) {
                        return trim($var, '"');
                    }, $post['option_value'] ?? []))
                ];
                app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('User Rating Doctor', $user->phone, $variables, null, true);
            }
        }

        $insert = [
            'id_transaction' => $trx->id_transaction,
            'id_user' => $request->user()->id,
            'id_outlet' => $id_outlet,
            'id_transaction_consultation' => $trxDoctor->id_transaction_consultation,
            'id_doctor' => $id_doctor,
            'rating_value' => $post['rating_value'],
            'suggestion' => $post['suggestion'] ?? '',
            'option_question' => $post['option_question'] ?? '',
            'option_value' => implode(',', array_map(function ($var) {
                return trim($var, '"');
            }, $post['option_value'] ?? []))
        ];

        if (isset($post['is_anonymous'])) {
            if ($post['is_anonymous'] == 'true') {
                $insert['is_anonymous'] = 1;
            } else {
                $insert['is_anonymous'] = 0;
            }
        }

        $create = UserRating::updateOrCreate([
            'id_user' => $request->user()->id,
            'id_transaction' => $id,
            'id_outlet' => $id_outlet,
            'id_doctor' => $id_doctor
        ], $insert);

        if ($create && !empty($post['images'])) {
            $img = [];
            foreach ($post['images'] ?? [] as $image) {
                $encode = base64_encode(fread(fopen($image, "r"), filesize($image)));
                $upload = MyHelper::uploadPhotoAllSize($encode, 'img/user_rating/' . $create['id_user_rating'] . '/');

                if (isset($upload['status']) && $upload['status'] == "success") {
                    $img[] = [
                        'id_user_rating' => $create['id_user_rating'],
                        'user_rating_photo' => $upload['path'],
                        'updated_at' => date('Y-m-d H:i:s'),
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                }
            }

            if (!empty($img)) {
                UserRatingPhoto::insert($img);
            }
        }

        if ($id_doctor) {
            $doctorRating = UserRating::where('id_doctor', $id_doctor)->get()->toArray();
            if ($doctorRating) {
                $totalDoctorRating = array_sum(array_column($doctorRating, 'rating_value')) / count($doctorRating);
                Doctor::where('id_doctor', $id_doctor)->update(['total_rating' => $totalDoctorRating]);
            }
        }

        UserRatingLog::where([
            'id_user' => $request->user()->id,
            'id_transaction' => $id,
            'id_doctor' => $id_doctor
        ])->delete();

        $unrated = UserRatingLog::where('id_transaction', $trx->id_transaction)->first();
        if (!$unrated) {
            Transaction::where('id_transaction', $trx->id_transaction)->update(['show_rate_popup' => 0]);
        }

        $countRatingValue = UserRating::where([
            'id_outlet' => $id_outlet,
            'id_doctor' => $id_doctor,
            'rating_value' => $post['rating_value']
        ])->count();

        $summaryRatingValue = UserRatingSummary::updateOrCreate([
            'id_outlet' => $id_outlet,
            'id_doctor' => $id_doctor,
            'key' => $post['rating_value'],
            'summary_type' => 'rating_value'
        ], [
            'value' => $countRatingValue
        ]);

        foreach ($post['option_value'] ?? [] as $value) {
            $countOptionValue = UserRating::where([
                'id_outlet' => $id_outlet,
                'id_doctor' => $id_doctor,
                ['option_value', 'like', '%' . $value . '%']
            ])->count();

            $summaryOptionValue = UserRatingSummary::updateOrCreate([
                'id_outlet' => $id_outlet,
                'id_doctor' => $id_doctor,
                'key' => $value,
                'summary_type' => 'option_value'
            ], [
                'value' => $countOptionValue
            ]);
        }

        $countRating = UserRating::where('id_transaction', $trx->id_transaction)->count();
        $counTrxDoctor = TransactionConsultation::where('id_transaction', $trx->id_transaction)->count();
        if ($countRating == $counTrxDoctor) {
            $newTrx = Transaction::where('id_transaction', $trx->id_transaction)
                ->with('user.memberships')->first();
            $savePoint = app($this->getNotif)->savePoint($newTrx);
            if (!$savePoint) {
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Failed insert point'],
                ]);
            }
        }

        return MyHelper::checkCreate($create);
    }

    public function consultationGetDetail(Request $request)
    {
        $post = $request->json()->all();
        $user = clone $request->user();

        if (isset($post['id'])) {
            $id_transaction = $post['id'];
            $user->load('log_popup_user_rating');

            $transaction = Transaction::find($id_transaction);
            if (!$transaction) {
                return [
                    'status' => 'fail',
                    'messages' => ['Transaction not found']
                ];
            }

            $logRatings = UserRatingLog::where('id_transaction', $id_transaction)
                ->where('id_user', $user->id)
                ->get();
        } else {
            $user->load('log_popup_user_rating.transaction.outlet');
            $log_popup_user_ratings = $user->log_popup_user_rating;
            $log_popup_user_rating = null;
            $logRatings = [];
            $interval = (Setting::where('key', 'popup_min_interval')->pluck('value')->first() ?: 900);
            $max_date = date('Y-m-d', time() - ((Setting::select('value')->where('key', 'popup_max_days')->pluck('value')->first() ?: 3) * 86400));
            $maxList = Setting::where('key', 'popup_max_list')->pluck('value')->first() ?: 5;

            if (empty($log_popup_user_ratings)) {
                return MyHelper::checkGet([]);
            }

            foreach ($log_popup_user_ratings as $log_pop) {
                if (
                    $log_pop->refuse_count >= (Setting::where('key', 'popup_max_refuse')->pluck('value')->first() ?: 3) ||
                    strtotime($log_pop->last_popup) + $interval > time()
                ) {
                    continue;
                }

                if ($log_popup_user_rating && $log_popup_user_rating->last_popup < $log_pop->last_popup) {
                    continue;
                }

                $log_popup_user_rating = $log_pop;
                $transaction = Transaction::select('id_transaction', 'transaction_receipt_number', 'transaction_date', 'id_outlet')
                    ->with(['outlet' => function ($query) {
                        $query->select('outlet_name', 'id_outlet');
                    }])
                    ->where('id_transaction', $log_popup_user_rating->id_transaction)
                    ->where(['id_user' => $user->id])
                    ->whereDate('transaction_date', '>', $max_date)
                    ->orderBy('transaction_date', 'asc')
                    ->first();

                // check if transaction is exist
                if (!$transaction) {
                    // log popup is not valid
                    continue;
                    $log_popup_user_rating->delete();
                    return $this->getDetail($request);
                }

                $log_popup_user_rating->refuse_count++;
                $log_popup_user_rating->last_popup = date('Y-m-d H:i:s');
                $log_popup_user_rating->save();
                $logRatings[] = $log_popup_user_rating;

                if ($maxList <= count($logRatings)) {
                    break;
                }
            }

            if (empty($logRatings)) {
                return MyHelper::checkGet([]);
            }
        }

        $defaultOptions = [
            'question' => Setting::where('key', 'default_rating_question')->pluck('value_text')->first() ?: 'What\'s best from us?',
            'options' => explode(',', Setting::where('key', 'default_rating_options')->pluck('value_text')->first() ?: 'Fast Response')
        ];

        $optionDoctor = ['1' => $defaultOptions,'2' => $defaultOptions,'3' => $defaultOptions,'4' => $defaultOptions,'5' => $defaultOptions];
        $ratingOptionDoctor = RatingOption::select('star', 'question', 'options')->where('rating_target', 'doctor')->get();
        foreach ($ratingOptionDoctor as $rt) {
            $stars = explode(',', $rt['star']);
            foreach ($stars as $star) {
                $optionDoctor[$star] = [
                    'question' => $rt['question'],
                    'options' => explode(',', $rt['options'])
                ];
            }
        }

        $ratingList = [];
        $title = 'Beri Penilaian';
        $message = "Dapatkan loyalty points dengan memberikan penilaian atas transaksi Anda pada hari:  /n <b>'%date%' di '%outlet_address%'</b>";
        foreach ($logRatings as $key => $log) {
            $rating['id'] = $log['id_transaction'];
            $rating['id_doctor'] = $log['id_doctor'];
            $rating['transaction_receipt_number'] = $log['transaction']['transaction_receipt_number'];
            $rating['transaction_date'] = date('d M Y H:i', strtotime($log['transaction']['transaction_date']));

            $trxDate = MyHelper::dateFormatInd($log['transaction']['transaction_date'], true, false, true);
            $outletName = $log['transaction']['outlet']['outlet_name'];
            $rating['title'] = $title;
            $rating['messages'] = "Dapatkan loyalty points dengan memberikan penilaian atas transaksi Anda pada hari:  \n <b>" . $trxDate . " di " . $outletName . "</b>";

            $rating['outlet'] = [
                'id_outlet' => $log['transaction']['outlet']['id_outlet'],
                'outlet_code' => $log['transaction']['outlet']['outlet_code'],
                'outlet_name' => $log['transaction']['outlet']['outlet_name'],
                'outlet_address' => $log['transaction']['outlet']['outlet_address'],
                'outlet_latitude' => $log['transaction']['outlet']['outlet_latitude'],
                'outlet_longitude' => $log['transaction']['outlet']['outlet_longitude']
            ];

            $doctor = TransactionConsultation::join('doctors', 'doctors.id_doctor', 'transaction_consultations.id_doctor')
                ->where('transaction_consultations.id_doctor', $log['id_doctor'])->first();
            $rating['detail_doctor'] = [
                'doctor' => $doctor->doctor_name ?? null,
            ];

            $rating['question_text'] = Setting::where('key', 'doctor_rating_question_text')->pluck('value_text')->first() ?: "Bagaimana pengalaman konsultasi anda?";
            $rating['rating'] = null;
            $rating['options'] = $optionDoctor;

            $currentRating = UserRating::where([
                'id_transaction' => $log['id_transaction'],
                'id_user' => $log['id_user'],
                'id_doctor' => $log['id_doctor']
            ])
            ->first();

            if ($currentRating) {
                $currentOption = explode(',', $currentRating['option_value']);
                $rating['rating'] = [
                    "rating_value" => $currentRating['rating_value'],
                    "suggestion" => $currentRating['suggestion'],
                    "option_value" => $currentOption
                ];
            }

            $ratingList[] = $rating;
        }

        $result = $ratingList;
        return MyHelper::checkGet($result);
    }

    public function consultationGetRated(Request $request)
    {
        $post = $request->json()->all();
        $user = clone $request->user();

        $logRatings = UserRating::where('id_user', $user->id)
            ->with('transaction.outlet');

        if (isset($post['id'])) {
            $id_transaction = $post['id'];

            $transaction = Transaction::find($id_transaction);
            if (!$transaction) {
                return [
                    'status' => 'fail',
                    'messages' => ['Transaction not found']
                ];
            }

            $logRatings = $logRatings->where('id_transaction', $id_transaction);

            if (isset($post['id_doctor'])) {
                $logRatings = $logRatings->where('id_doctor', $post['id_doctor']);
            }
        }

        $logRatings = $logRatings->get();

        $ratingList = [];
        foreach ($logRatings as $key => $log) {
            $rating['id'] = $log['id_transaction'];
            $rating['id_doctor'] = $log['id_doctor'];
            $rating['transaction_receipt_number'] = $log['transaction']['transaction_receipt_number'];
            $rating['transaction_date'] = date('d M Y H:i', strtotime($log['transaction']['transaction_date']));

            $rating['outlet'] = null;
            if (!empty($log['transaction']['outlet'])) {
                $rating['outlet'] = [
                    'id_outlet' => $log['transaction']['outlet']['id_outlet'],
                    'outlet_code' => $log['transaction']['outlet']['outlet_code'],
                    'outlet_name' => $log['transaction']['outlet']['outlet_name'],
                    'outlet_address' => $log['transaction']['outlet']['outlet_address'],
                    'outlet_latitude' => $log['transaction']['outlet']['outlet_latitude'],
                    'outlet_longitude' => $log['transaction']['outlet']['outlet_longitude']
                ];
            }
            $rating['rating'] = null;

            if (!empty($log['id_doctor'])) {
                $doctor = TransactionConsultation::join('doctors', 'doctors.id_doctor', 'transaction_consultations.id_doctor')
                    ->where('transaction_consultations.id_doctor', $log['id_doctor'])->first();
                $rating['detail_doctor'] = [
                    'doctor_name' => $doctor->doctor_name ?? null,
                ];

                $rating['question_text'] = Setting::where('key', 'doctor_rating_question_text')->pluck('value_text')->first() ?: "Bagaimana pengalaman konsultasi anda?";
            }

            $currentRating = $log;
            $getPhotos = UserRatingPhoto::where('id_user_rating', $currentRating['id_user_rating'])->get()->toArray();
            $photos = [];
            foreach ($getPhotos as $dt) {
                $photos[] = $dt['url_user_rating_photo'];
            }

            if ($currentRating) {
                $currentOption = explode(',', $currentRating['option_value']);
                $rating['rating'] = [
                    "rating_value" => $currentRating['rating_value'],
                    "suggestion" => $currentRating['suggestion'],
                    "option_value" => $currentOption,
                    "photos" => $photos
                ];
            }

            $ratingList[] = $rating;
        }

        $result = $ratingList;
        return MyHelper::checkGet($result);
    }
}
