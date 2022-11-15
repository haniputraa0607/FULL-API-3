<?php

namespace Modules\Quest\Http\Controllers;

use App\Http\Models\CrmUserData;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\User;
use App\Jobs\QuestRecipientNotification;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Quest\Entities\Quest;
use Modules\Quest\Entities\QuestBenefit;
use Modules\Quest\Entities\QuestDetail;
use Modules\Quest\Entities\QuestOutletLog;
use Modules\Quest\Entities\QuestProductLog;
use Modules\Quest\Entities\QuestProvinceLog;
use Modules\Quest\Entities\QuestTransactionLog;
use Modules\Quest\Entities\QuestUser;
use Modules\Quest\Entities\QuestUserDetail;
use Modules\Quest\Entities\QuestUserLog;
use Modules\Quest\Entities\QuestUserRedemption;
use App\Http\Models\Deal;
use App\Http\Models\Product;
use Modules\Quest\Entities\QuestContent;
use Modules\Quest\Jobs\AutoclaimQuest;
use Modules\Quest\Http\Requests\StoreRequest;

use function Clue\StreamFilter\fun;

class ApiQuest extends Controller
{
    public $saveImage = "img/quest/";

    public function __construct()
    {
        $this->deals_claim  = "Modules\Deals\Http\Controllers\ApiDealsClaim";
        $this->balance      = "Modules\Balance\Http\Controllers\BalanceController";
        $this->autocrm      = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->hidden_deals = "Modules\Deals\Http\Controllers\ApiHiddenDeals";
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $result = Quest::join('quest_benefits', 'quest_benefits.id_quest', 'quests.id_quest');
        $countTotal = null;

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterList($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'name',
                null,
                'publish_start',
                'publish_end',
                'date_start',
                'benefit_type',
                'id_quest',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }
        $result->orderBy('quests.id_quest', $column['dir'] ?? 'DESC');

        if ($request->page) {
            $result = $result->paginate($request->length ?: 15);
            $time_server = date('Y-m-d H:i:s');
            $result->each(function ($item) use ($time_server) {
                $item->images = array_map(function ($item) {
                    return config('url.storage_url_api') . $item;
                }, json_decode($item->images) ?? []);
                $item->time_server = $time_server;
            });
            $result = $result->toArray();
            if (is_null($countTotal)) {
                $countTotal = $result['total'];
            }
            // needed by datatables
            $result['recordsTotal'] = $countTotal;
            $result['recordsFiltered'] = $result['total'];
        } else {
            $result = $result->get();
        }
        return MyHelper::checkGet($result);
    }

    public function filterList($query, $rules, $operator = 'and')
    {
        $newRule = [];
        foreach ($rules as $var) {
            $rule = [$var['operator'] ?? '=',$var['parameter']];
            if ($rule[0] == 'like') {
                $rule[1] = '%' . $rule[1] . '%';
            }
            $newRule[$var['subject']][] = $rule;
        }

        $where = $operator == 'and' ? 'where' : 'orWhere';
        $subjects = ['name', 'benefit_type', 'date_start'];
        foreach ($subjects as $subject) {
            if ($rules2 = $newRule[$subject] ?? false) {
                foreach ($rules2 as $rule) {
                    $query->$where($subject, $rule[0], $rule[1]);
                }
            }
        }
        if ($rules2 = $newRule['publish_date'] ?? false) {
            foreach ($rules2 as $rule) {
                if (in_array($rule[0], ['<', '<='])) {
                    $query->$where('publish_start', $rule[0], $rule[1]);
                } elseif (in_array($rule[0], ['>', '>='])) {
                    $query->$where('publish_end', $rule[0], $rule[1]);
                } else {
                    $query->$where(function ($query2) use ($rule) {
                        $query2->where('publish_start', '<=', $rule[1]);
                        $query2->where('publish_end', '>=', $rule[1]);
                    });
                }
            }
        }

        if ($rules2 = $newRule['status'] ?? false) {
            foreach ($rules2 as $rule) {
                $query->$where(function ($query1) use ($rule) {
                    foreach ($rule[1] ?? [] as $rule1) {
                        switch ($rule1) {
                            case 'pending':
                                $query1->orWhere(function ($query2) {
                                    $query2->whereNull('stop_at')
                                        ->where('is_complete', 0);
                                });
                                break;

                            case 'not_started':
                                $query1->orWhere(function ($query2) {
                                    $query2->whereNull('stop_at')
                                        ->where('is_complete', 1)
                                        ->where('date_start', '>', date('Y-m-d H:i:s'));
                                });
                                break;

                            case 'on_going':
                                $query1->orWhere(function ($query2) {
                                    $query2->whereNull('stop_at')
                                        ->where('is_complete', 1)
                                        ->where('date_start', '<=', date('Y-m-d H:i:s'))
                                        ->where('publish_end', '>=', date('Y-m-d H:i:s'));
                                });
                                break;

                            case 'end':
                                $query1->orWhere(function ($query2) {
                                    $query2->whereNull('stop_at')
                                        ->where('is_complete', 1)
                                        ->where('date_start', '<=', date('Y-m-d H:i:s'))
                                        ->where('publish_end', '<', date('Y-m-d H:i:s'));
                                });
                                break;

                            case 'stop':
                                $query1->orWhereNotNull('stop_at');
                                break;
                        }
                    }
                });
            }
        }
        if ($rules2 = $newRule['benefit_deals'] ?? false) {
            foreach ($rules2 as $rule) {
                $query->{$where . 'In'}('quest_benefits.id_deals', $rule[1]);
            }
        }
        if ($rules2 = $newRule['benefit_point'] ?? false) {
            foreach ($rules2 as $rule) {
                $query->$where(function ($query2) use ($rule) {
                    $query2->where('quest_benefits.benefit_type', 'point');
                    $query2->where('quest_benefits.value', $rule[0], $rule[1]);
                });
            }
        }
        if ($rules2 = $newRule['user_type'] ?? false) {
            foreach ($rules2 as $rule) {
                if ($rule[1] == 'with_filter') {
                    $query->{$where . 'NotNull'}('user_rule_subject');
                } else {
                    $query->{$where . 'Null'}('user_rule_subject');
                }
            }
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function store(StoreRequest $request)
    {
        $post = $request->json()->all();

        DB::beginTransaction();

        $upload = MyHelper::uploadPhotoStrict($post['quest']['image'], $this->saveImage, 500, 500);

        if (isset($upload['status']) && $upload['status'] == "success") {
            $post['quest']['image'] = $upload['path'];
        } else {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Failed to upload image']
            ]);
        }

        $post['quest']['publish_start']     = date('Y-m-d H:i', strtotime($post['quest']['publish_start']));
        $post['quest']['date_start']        = date('Y-m-d H:i', strtotime($post['quest']['date_start']));
        if (!is_null($post['quest']['publish_end'] ?? null)) {
            $post['quest']['publish_end']   = date('Y-m-d H:i', strtotime($post['quest']['publish_end']));
        }
        if (!is_null($post['quest']['date_end'] ?? null)) {
            if (strtotime($post['quest']['date_end']) < strtotime($post['quest']['publish_end'])) {
                return [
                    'status'   => 'fail',
                    'messages' => ['Quest date end should be after or equal publish end']
                ];
            }
            $post['quest']['date_end']      = date('Y-m-d H:i', strtotime($post['quest']['date_end']));
        }

        try {
            if ($post['quest']['user_rule_type'] == 'all') {
                $post['quest']['user_rule_subject'] = null;
                $post['quest']['user_rule_operator'] = null;
                $post['quest']['user_rule_parameter'] = null;
            }
            $quest = Quest::create($post['quest']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'    => 'fail',
                'message'   => 'Add Quest Group Failed',
                'error'     => $e->getMessage()
            ]);
        }

        if ($quest_benefit = $post['quest_benefit'] ?? false) {
            if ($quest_benefit['benefit_type'] == 'point') {
                QuestBenefit::create([
                    'id_quest' => $quest->id_quest,
                    'benefit_type' => 'point',
                    'value' => $quest_benefit['value'],
                    'id_deals' => null,
                    'autoclaim_benefit' => $quest_benefit['autoclaim_benefit'] ?? 0
                ]);
            } elseif ($quest_benefit['benefit_type'] == 'voucher') {
                QuestBenefit::create([
                    'id_quest' => $quest->id_quest,
                    'benefit_type' => 'voucher',
                    'value' => $quest_benefit['value'],
                    'id_deals' => $quest_benefit['id_deals'],
                    'autoclaim_benefit' => $quest_benefit['autoclaim_benefit'] ?? 0
                ]);
            }
        }

        if (isset($post['detail'])) {
            try {
                foreach ($post['detail'] as $key => $value) {
                    if (isset($request['id_quest'])) {
                        $value['id_quest']   = $request['id_quest'];
                    } else {
                        $value['id_quest']   = $quest->id_quest;
                    }

                    if (!($value['id_outlet'] ?? false)) {
                        unset($value['id_outlet']);
                    }

                    $questDetail[$key] = QuestDetail::create($value);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status'    => 'fail',
                    'message'   => 'Add Quest Detail Failed',
                    'error'     => $e->getMessage()
                ]);
            }
        }

        QuestContent::insert([
            [
                'id_quest' => $quest->id_quest,
                'title' => 'Syarat & Ketentuan',
                'content' => '',
                'order' => 1,
                'is_active' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id_quest' => $quest->id_quest,
                'title' => 'Hadiah',
                'content' => '',
                'order' => 2,
                'is_active' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ],
        ]);

        // if (isset($post['quest']) && date('Y-m-d H:i', strtotime($post['quest']['date_start'])) <= date('Y-m-d H:i')) {
        //     $getUser = User::select('id')->get()->toArray();
        //     $this->quest = $quest;
        //     foreach ($getUser as $key => $value) {
        //         $this->checkQuest($quest,$value['id'], $questDetail);
        //     }
        // }

        DB::commit();

        if (isset($request['id_quest'])) {
            return response()->json([
                'status'    => 'success',
                'message'   => 'Add Quest Success',
                'data'      => $request['id_quest']
            ]);
        } else {
            return response()->json([
                'status'    => 'success',
                'message'   => 'Add Quest Success',
                'data'      => $quest->id_quest
            ]);
        }
    }

    public function triggerAutoclaim($quest)
    {
        if ($quest->autoclaim_quest) {
            $users = User::select('users.id')
                ->where('phone_verified', 1)
                ->where('is_suspended', 0)
                ->where('complete_profile', 1);

            if ($quest->user_rule_subject && $quest->user_rule_operator) {
                $users->join('crm_user_data', 'crm_user_data.id_user', 'users.id_user')
                    ->where('crm_user_data.' . $quest->user_rule_subject, $quest->user_rule_operator, $quest->user_rule_parameter);
            }

            $users->chunk(500, function ($users2) use ($quest) {
                    AutoclaimQuest::dispatch($quest, $users2->pluck('id'))->allOnConnection('quest_autoclaim');
            });
        }
    }

    public function triggerManualAutoclaim(Request $request)
    {
        $quest = Quest::find($request->id_quest);
        if (!$quest) {
            return [
                'status' => 'fail',
                'messages' => ['Quest not found']
            ];
        }

        $jobRunning = \DB::table('questjobs')->where('queue', 'quest_autoclaim')->exists();

        if ($jobRunning) {
            return [
                'status' => 'fail',
                'messages' => ['Autoclaim in progess']
            ];
        }

        if ($quest->autoclaim_quest) {
            $total = 0;
            $users = User::leftJoin('quest_users', function ($join) use ($quest) {
                    $join->on('quest_users.id_user', 'users.id')
                        ->where('quest_users.id_quest', $quest->id_quest);
            })
                ->select('users.id')
                ->where('phone_verified', 1)
                ->where('is_suspended', 0)
                ->where('complete_profile', 1)
                ->whereNull('quest_users.id_quest_user');

            if ($quest->user_rule_subject && $quest->user_rule_operator) {
                $users->join('crm_user_data', 'crm_user_data.id_user', 'users.id_user')
                    ->where('crm_user_data.' . $quest->user_rule_subject, $quest->user_rule_operator, $quest->user_rule_parameter);
            }

            $users->chunk(500, function ($users2) use ($quest, &$total) {
                    $total += $users2->count();
                    AutoclaimQuest::dispatch($quest, $users2->pluck('id'))->allOnConnection('quest_autoclaim');
            });
            if (!$total) {
                return [
                    'status' => 'fail',
                    'messages' => [
                        "all users have received the quest"
                    ]
                ];
            }
            return [
                'status' => 'success',
                'messages' => [
                    "Reclaiming quest for $total users"
                ]
            ];
        }

        return [
            'status' => 'fail',
            'messages' => ['Not an autoclaim quest']
        ];
    }

    public function storeQuestDetail(Request $request)
    {
        $quest = Quest::find($request->id_quest);
        if (!$quest) {
            return MyHelper::checkGet($quest);
        }

        if ($quest->is_complete) {
            return MyHelper::checkGet($quest, ['Quest not editable']);
        }
        foreach ($request->detail as $detail) {
            $detail['id_quest'] = $quest->id_quest;
            if (!($detail['id_outlet'] ?? false)) {
                unset($detail['id_outlet']);
            }
            $create = QuestDetail::create($detail);
        }
        return MyHelper::checkCreate($create);
    }

    public function checkQuest($quest, $idUser, $detailQuest)
    {
        $questPassed = 0;
        foreach ($detailQuest as $keyQuest => $quest) {
            $getTrxUser = Transaction::with('outlet.city.province', 'allProductTransaction')->where(['transactions.id_user' => $idUser, 'transactions.transaction_payment_status' => 'Completed'])->get()->toArray();

            if ($questPassed == $keyQuest) {
                $totalTrx       = 0;
                $totalOutlet    = [];
                $totalProvince  = [];
                foreach ($getTrxUser as $user) {
                    $trxNominalStatus = false;
                    if (!is_null($quest['trx_nominal'])) {
                        if ((int) $quest['trx_nominal'] <= $user['transaction_grandtotal']) {
                            $trxNominalStatus = true;
                        } else {
                            $trxNominalStatus = false;
                        }
                    } else {
                        $trxNominalStatus = true;
                    }

                    $trxProductStatus = false;
                    $trxTotalProductStatus = false;
                    if (!is_null($quest['id_product']) || !is_null($quest['product_total'])) {
                        foreach ($user['all_product_transaction'] as $product) {
                            if (!is_null($quest['id_product'])) {
                                if ((int) $quest['id_product'] == $product['id_product']) {
                                    $trxProductStatus = true;
                                    if (!is_null($quest['product_total'])) {
                                        if ((int) $quest['product_total'] <= $product['transaction_product_qty']) {
                                            QuestProductLog::updateOrCreate([
                                                'id_quest'                  => $quest['id_quest'],
                                                'id_quest_detail'           => $quest['id_quest_detail'],
                                                'id_user'                   => $idUser,
                                                'id_product'                => $product['id_product'],
                                                'product_total'             => $quest['transaction_product_qty'],
                                                'id_transaction'            => $user['id_transaction']
                                            ], [
                                                'id_quest'                  => $quest['id_quest'],
                                                'id_quest_detail'           => $quest['id_quest_detail'],
                                                'id_user'                   => $idUser,
                                                'id_product'                => $product['id_product'],
                                                'product_total'             => $quest['transaction_product_qty'],
                                                'id_transaction'            => $user['id_transaction'],
                                                'json_rule'                 => json_encode([
                                                    'id_product'            => $quest['id_product'],
                                                    'product_total'         => $quest['product_total'],
                                                    'trx_nominal'           => $quest['trx_nominal'],
                                                    'trx_total'             => $quest['trx_total'],
                                                    'id_outlet'             => $quest['id_outlet'],
                                                    'different_outlet'      => $quest['different_outlet'],
                                                    'id_province'           => $quest['id_province'],
                                                    'different_province'    => $quest['different_province']
                                                ]),
                                                'json_rule_enc'             => MyHelper::encrypt2019(json_encode([
                                                    'id_product'            => $quest['id_product'],
                                                    'product_total'         => $quest['product_total'],
                                                    'trx_nominal'           => $quest['trx_nominal'],
                                                    'trx_total'             => $quest['trx_total'],
                                                    'id_outlet'             => $quest['id_outlet'],
                                                    'different_outlet'      => $quest['different_outlet'],
                                                    'id_province'           => $quest['id_province'],
                                                    'different_province'    => $quest['different_province']
                                                ])),
                                                'date'                      => date('Y-m-d H:i:s')
                                            ]);
                                            $trxTotalProductStatus = true;
                                            break;
                                        } else {
                                            $trxTotalProductStatus = false;
                                            break;
                                        }
                                    } else {
                                        QuestProductLog::updateOrCreate([
                                            'id_quest'                  => $quest['id_quest'],
                                            'id_quest_detail'           => $quest['id_quest_detail'],
                                            'id_user'                   => $idUser,
                                            'id_product'                => $product['id_product'],
                                            'id_transaction'            => $user['id_transaction']
                                        ], [
                                            'id_quest'                  => $quest['id_quest'],
                                            'id_quest_detail'           => $quest['id_quest_detail'],
                                            'id_user'                   => $idUser,
                                            'id_product'                => $product['id_product'],
                                            'id_transaction'            => $user['id_transaction'],
                                            'json_rule'                 => json_encode([
                                                'id_product'            => $quest['id_product'],
                                                'product_total'         => $quest['product_total'],
                                                'trx_nominal'           => $quest['trx_nominal'],
                                                'trx_total'             => $quest['trx_total'],
                                                'id_outlet'             => $quest['id_outlet'],
                                                'different_outlet'      => $quest['different_outlet'],
                                                'id_province'           => $quest['id_province'],
                                                'different_province'    => $quest['different_province']
                                            ]),
                                            'json_rule_enc'             => MyHelper::encrypt2019(json_encode([
                                                'id_product'            => $quest['id_product'],
                                                'product_total'         => $quest['product_total'],
                                                'trx_nominal'           => $quest['trx_nominal'],
                                                'trx_total'             => $quest['trx_total'],
                                                'id_outlet'             => $quest['id_outlet'],
                                                'different_outlet'      => $quest['different_outlet'],
                                                'id_province'           => $quest['id_province'],
                                                'different_province'    => $quest['different_province']
                                            ])),
                                            'date'                      => date('Y-m-d H:i:s')
                                        ]);
                                        $trxTotalProductStatus = true;
                                        break;
                                    }
                                } else {
                                    $trxProductStatus = false;
                                }
                            } else {
                                $trxProductStatus = true;
                                break;
                            }
                        }
                    } else {
                        $trxProductStatus = true;
                        $trxTotalProductStatus = true;
                    }

                    $trxOutletStatus = false;
                    if (!is_null($quest['id_outlet'])) {
                        if ((int) $quest['id_outlet'] == $user['id_outlet']) {
                            QuestOutletLog::updateOrCreate([
                                'id_quest'                  => $quest['id_quest'],
                                'id_quest_detail'           => $quest['id_quest_detail'],
                                'id_user'                   => $idUser,
                                'id_outlet'                 => $user['id_outlet'],
                                'id_transaction'            => $user['id_transaction']
                            ], [
                                'id_quest'                  => $quest['id_quest'],
                                'id_quest_detail'           => $quest['id_quest_detail'],
                                'id_user'                   => $idUser,
                                'id_outlet'                 => $user['id_outlet'],
                                'id_transaction'            => $user['id_transaction'],
                                'json_rule'                 => json_encode([
                                    'id_product'            => $quest['id_product'],
                                    'product_total'         => $quest['product_total'],
                                    'trx_nominal'           => $quest['trx_nominal'],
                                    'trx_total'             => $quest['trx_total'],
                                    'id_outlet'             => $quest['id_outlet'],
                                    'different_outlet'      => $quest['different_outlet'],
                                    'id_province'           => $quest['id_province'],
                                    'different_province'    => $quest['different_province']
                                ]),
                                'json_rule_enc'             => MyHelper::encrypt2019(json_encode([
                                    'id_product'            => $quest['id_product'],
                                    'product_total'         => $quest['product_total'],
                                    'trx_nominal'           => $quest['trx_nominal'],
                                    'trx_total'             => $quest['trx_total'],
                                    'id_outlet'             => $quest['id_outlet'],
                                    'different_outlet'      => $quest['different_outlet'],
                                    'id_province'           => $quest['id_province'],
                                    'different_province'    => $quest['different_province']
                                ])),
                                'date'                      => date('Y-m-d H:i:s')
                            ]);
                            $trxOutletStatus = true;
                            break;
                        } else {
                            $trxOutletStatus = false;
                        }
                    } else {
                        $trxOutletStatus = true;
                    }

                    $trxProvinceStatus = false;
                    if (!is_null($quest['id_province'])) {
                        if ((int) $quest['id_province'] == $user['outlet']['city']['province']['id_province']) {
                            QuestProvinceLog::updateOrCreate([
                                'id_quest'                  => $quest['id_quest'],
                                'id_quest_detail'           => $quest['id_quest_detail'],
                                'id_user'                   => $idUser,
                                'id_transaction'            => $user['id_transaction'],
                                'id_province'               => $user['outlet']['city']['province']['id_province']
                            ], [
                                'id_quest_detail'           => $quest['id_quest_detail'],
                                'id_user'                   => $idUser,
                                'id_transaction'            => $user['id_transaction'],
                                'id_province'               => $user['outlet']['city']['province']['id_province'],
                                'json_rule'                 => json_encode([
                                    'id_product'            => $quest['id_product'],
                                    'product_total'         => $quest['product_total'],
                                    'trx_nominal'           => $quest['trx_nominal'],
                                    'trx_total'             => $quest['trx_total'],
                                    'id_outlet'             => $quest['id_outlet'],
                                    'different_outlet'      => $quest['different_outlet'],
                                    'id_province'           => $quest['id_province'],
                                    'different_province'    => $quest['different_province']
                                ]),
                                'json_rule_enc'             => MyHelper::encrypt2019(json_encode([
                                    'id_product'            => $quest['id_product'],
                                    'product_total'         => $quest['product_total'],
                                    'trx_nominal'           => $quest['trx_nominal'],
                                    'trx_total'             => $quest['trx_total'],
                                    'id_outlet'             => $quest['id_outlet'],
                                    'different_outlet'      => $quest['different_outlet'],
                                    'id_province'           => $quest['id_province'],
                                    'different_province'    => $quest['different_province']
                                ])),
                                'date'                      => date('Y-m-d H:i:s')
                            ]);
                            $trxProvinceStatus = true;
                            break;
                        } else {
                            $trxProvinceStatus = false;
                        }
                    } else {
                        $trxProvinceStatus = true;
                    }

                    if ($trxNominalStatus == true && $trxProductStatus == true && $trxTotalProductStatus == true && $trxOutletStatus == true && $trxProvinceStatus == true) {
                        $totalTrx = $totalTrx + 1;
                    }

                    $totalOutlet[]      = $user['id_outlet'];
                    $totalProvince[]    = $user['outlet']['city']['province']['id_province'];
                }

                if (!is_null($quest['different_outlet'])) {
                    if (count(array_unique($totalOutlet)) >= (int) $quest['different_outlet']) {
                        QuestUserLog::updateOrCreate([
                            'id_quest_detail'           => $quest['id_quest_detail'],
                            'id_user'                   => $idUser,
                        ], [
                            'id_quest_detail'           => $quest['id_quest_detail'],
                            'id_user'                   => $idUser,
                            'json_rule'                 => json_encode([
                                'id_product'            => $quest['id_product'],
                                'product_total'         => $quest['product_total'],
                                'trx_nominal'           => $quest['trx_nominal'],
                                'trx_total'             => $quest['trx_total'],
                                'id_outlet'             => $quest['id_outlet'],
                                'different_outlet'      => $quest['different_outlet'],
                                'id_province'           => $quest['id_province'],
                                'different_province'    => $quest['different_province']
                            ]),
                            'json_rule_enc'             => MyHelper::encrypt2019(json_encode([
                                'id_product'            => $quest['id_product'],
                                'product_total'         => $quest['product_total'],
                                'trx_nominal'           => $quest['trx_nominal'],
                                'trx_total'             => $quest['trx_total'],
                                'id_outlet'             => $quest['id_outlet'],
                                'different_outlet'      => $quest['different_outlet'],
                                'id_province'           => $quest['id_province'],
                                'different_province'    => $quest['different_province']
                            ])),
                            'date'                      => date('Y-m-d H:i:s')
                        ]);
                        $questPassed = $questPassed + 1;
                        continue;
                    } else {
                        if ($questPassed - 1 < 0) {
                            $quest = null;
                        } else {
                            $quest = $detailQuest[$questPassed - 1];
                        }
                        break;
                    }
                }

                if (!is_null($quest['different_province'])) {
                    if (count(array_unique($totalProvince)) >= (int) $quest['different_province']) {
                        QuestUserLog::updateOrCreate([
                            'id_quest_detail'           => $quest['id_quest_detail'],
                            'id_user'                   => $idUser,
                        ], [
                            'id_quest_detail'           => $quest['id_quest_detail'],
                            'id_user'                   => $idUser,
                            'json_rule'                 => json_encode([
                                'id_product'            => $quest['id_product'],
                                'product_total'         => $quest['product_total'],
                                'trx_nominal'           => $quest['trx_nominal'],
                                'trx_total'             => $quest['trx_total'],
                                'id_outlet'             => $quest['id_outlet'],
                                'different_outlet'      => $quest['different_outlet'],
                                'id_province'           => $quest['id_province'],
                                'different_province'    => $quest['different_province']
                            ]),
                            'json_rule_enc'             => MyHelper::encrypt2019(json_encode([
                                'id_product'            => $quest['id_product'],
                                'product_total'         => $quest['product_total'],
                                'trx_nominal'           => $quest['trx_nominal'],
                                'trx_total'             => $quest['trx_total'],
                                'id_outlet'             => $quest['id_outlet'],
                                'different_outlet'      => $quest['different_outlet'],
                                'id_province'           => $quest['id_province'],
                                'different_province'    => $quest['different_province']
                            ])),
                            'date'                      => date('Y-m-d H:i:s')
                        ]);
                        $questPassed = $questPassed + 1;
                        continue;
                    } else {
                        if ($questPassed - 1 < 0) {
                            $quest = null;
                        } else {
                            $quest = $detailQuest[$questPassed - 1];
                        }
                        break;
                    }
                }

                if (!is_null($quest['trx_total'])) {
                    if ($totalTrx >= (int) $quest['trx_total']) {
                        QuestUserLog::updateOrCreate([
                            'id_quest_detail'           => $quest['id_quest_detail'],
                            'id_user'                   => $idUser,
                        ], [
                            'id_quest_detail'           => $quest['id_quest_detail'],
                            'id_user'                   => $idUser,
                            'json_rule'                 => json_encode([
                                'id_product'            => $quest['id_product'],
                                'product_total'         => $quest['product_total'],
                                'trx_nominal'           => $quest['trx_nominal'],
                                'trx_total'             => $quest['trx_total'],
                                'id_outlet'             => $quest['id_outlet'],
                                'different_outlet'      => $quest['different_outlet'],
                                'id_province'           => $quest['id_province'],
                                'different_province'    => $quest['different_province']
                            ]),
                            'json_rule_enc'             => MyHelper::encrypt2019(json_encode([
                                'id_product'            => $quest['id_product'],
                                'product_total'         => $quest['product_total'],
                                'trx_nominal'           => $quest['trx_nominal'],
                                'trx_total'             => $quest['trx_total'],
                                'id_outlet'             => $quest['id_outlet'],
                                'different_outlet'      => $quest['different_outlet'],
                                'id_province'           => $quest['id_province'],
                                'different_province'    => $quest['different_province']
                            ])),
                            'date'                      => date('Y-m-d H:i:s')
                        ]);
                        $questPassed = $questPassed + 1;
                        continue;
                    } else {
                        if ($questPassed - 1 < 0) {
                            $quest = null;
                        } else {
                            $quest = $detailQuest[$questPassed - 1];
                        }
                        break;
                    }
                }
            } else {
                if ($questPassed - 1 < 0) {
                    $quest = null;
                }
                break;
            }
        }

        if ($quest != null) {
            QuestUser::updateOrCreate([
                'id_quest_detail'           => $quest['id_quest_detail'],
                'id_user'                   => $idUser,
            ], [
                'id_quest'                  => $quest['id_quest'],
                'id_quest_detail'           => $quest['id_quest_detail'],
                'id_user'                   => $idUser,
                'json_rule'                 => json_encode([
                    'id_product'            => $quest['id_product'],
                    'product_total'         => $quest['product_total'],
                    'trx_nominal'           => $quest['trx_nominal'],
                    'trx_total'             => $quest['trx_total'],
                    'id_outlet'             => $quest['id_outlet'],
                    'different_outlet'      => $quest['different_outlet'],
                    'id_province'           => $quest['id_province'],
                    'different_province'    => $quest['different_province']
                ]),
                'json_rule_enc'             => MyHelper::encrypt2019(json_encode([
                    'id_product'            => $quest['id_product'],
                    'product_total'         => $quest['product_total'],
                    'trx_nominal'           => $quest['trx_nominal'],
                    'trx_total'             => $quest['trx_total'],
                    'id_outlet'             => $quest['id_outlet'],
                    'different_outlet'      => $quest['different_outlet'],
                    'id_province'           => $quest['id_province'],
                    'different_province'    => $quest['different_province']
                ])),
                'date'                      => date('Y-m-d H:i:s')
            ]);
        }

        return ['status' => 'success'];
    }

    /**
     * Update Quest Progress
     * @param  int $id_transaction id transaction
     * @return bool                 true/false
     */
    public function updateQuestProgress($id_transaction)
    {
        $transaction = Transaction::with(['allProductTransaction' => function ($q) {
                    $q->select('transaction_products.*', 'products.*', 'brand_product.id_product_category')
                        ->join('products', 'products.id_product', 'transaction_products.id_product')
                        ->leftJoin('brand_product', 'products.id_product', 'brand_product.id_product');
        }, 'outlet', 'outlet.city'])->find($id_transaction);
        if (!$transaction) {
            return false;
        }
        // get all user quests
        $quests = QuestUser::join('quest_user_details', 'quest_users.id_quest_user', 'quest_user_details.id_quest_user')
            ->join('quest_details', 'quest_details.id_quest_detail', 'quest_user_details.id_quest_detail')
            ->where('quest_users.id_user', $transaction->id_user)
            ->where('quest_user_details.is_done', 0)
            ->where('quest_users.date_start', '<=', date('Y-m-d H:i:s'))
            ->where('quest_users.date_end', '>=', date('Y-m-d H:i:s'))
            ->get();

        foreach ($quests as $quest) {
            if (
                ($quest->id_outlet && $quest->id_outlet != $transaction->id_outlet) ||
                ($quest->id_province && $quest->id_province != ($transaction->outlet->city->id_province ?? null)) ||
                ($quest->id_product && !$transaction->allProductTransaction->pluck('id_product')->contains($quest->id_product)) ||
                ($quest->id_product_category && !$transaction->allProductTransaction->pluck('id_product_category')->contains($quest->id_product_category))
            ) {
                continue;
            }

            if ($quest->id_outlet_group) {
                $outlets = app('\Modules\Outlet\Http\Controllers\ApiOutletGroupFilterController')->outletGroupFilter($quest->id_outlet_group);
                $id_outlets = array_column($outlets, 'id_outlet');
                if (!in_array($transaction->id_outlet, $id_outlets)) {
                    continue;
                }
            }

            $quest_rule = $quest->quest_rule;
            if (!$quest_rule) {
                if ($quest->different_outlet) {
                    $quest_rule = 'total_outlet';
                } elseif ($quest->different_province) {
                    $quest_rule = 'total_province';
                } elseif ($quest->trx_total) {
                    $quest_rule = 'total_transaction';
                } elseif ($quest->id_product && $quest->product_total) {
                    $quest_rule = 'total_product';
                } else {
                    $quest_rule = 'nominal_transaction';
                }
            }

            // check absolute rule
            if (
                ($quest_rule !== 'nominal_transaction' && $transaction->transaction_grandtotal < ($quest->trx_nominal ?: 0)) ||
                ($quest_rule !== 'total_product' && $transaction->allProductTransaction->count() < ($quest->product_total ?: 0))
            ) {
                continue;
            }

            \DB::beginTransaction();
            // outlet
            try {
                if ($quest->id_outlet || $quest->different_outlet) {
                    $questLog = QuestOutletLog::where([
                        'id_quest' => $quest->id_quest,
                        'id_quest_detail' => $quest->id_quest_detail,
                        'id_user' => $transaction->id_user,
                        'id_outlet' => $transaction->id_outlet,
                    ])->first();
                    if ($questLog) {
                        // if ($transaction->created_at <= $questLog->date) {
                        //     \DB::rollBack();
                        //     continue;
                        // }
                        $questLog->update([
                            'count' => $questLog->count + 1,
                            'date' => $transaction->created_at,
                        ]);
                    } else {
                        $questLog = QuestOutletLog::create([
                            'id_quest' => $quest->id_quest,
                            'id_quest_detail' => $quest->id_quest_detail,
                            'id_user' => $transaction->id_user,
                            'id_outlet' => $transaction->id_outlet,
                            'count' => 1,
                            'date' => $transaction->created_at,
                        ]);
                    }
                }

                // product
                if ($quest->id_product_category || $quest->different_product_category || $quest->id_product || $quest->product_total) {
                    $transaction->load(['allProductTransaction' => function ($q) {
                        $q->join('products', 'products.id_product', 'transaction_products.id_product');
                    }]);
                    $has_product = 0;
                    foreach ($transaction->allProductTransaction as $transaction_product) {
                        if (
                            (
                                $quest->id_product == $transaction_product->id_product
                                && (
                                    !$quest->id_product_variant_group
                                    || $quest->id_product_variant_group == $transaction_product->id_product_variant_group
                                )
                            )
                            || $quest->id_product_category == $transaction_product->id_product_category
                            || $quest->different_product_category
                            || (!$quest->id_product && $quest->product_total)
                        ) {
                            $questLog = QuestProductLog::where([
                                'id_quest' => $quest->id_quest,
                                'id_quest_detail' => $quest->id_quest_detail,
                                'id_user' => $transaction->id_user,
                                'id_transaction' => $transaction->id_transaction,
                                'id_product' => $transaction_product->id_product,
                                'id_product_variant_group' => $transaction_product->id_product_variant_group,
                                'id_product_category' => $transaction_product->id_product_category,
                            ])->first();
                            if ($questLog) {
                                // if ($transaction->created_at <= $questLog->date) {
                                //     \DB::rollBack();
                                //     continue;
                                // }
                                $questLog->update([
                                    'product_total' => $questLog->product_total + $transaction_product->transaction_product_qty,
                                    'product_nominal' => $questLog->product_total + ($transaction_product->transaction_product_subtotal - $transaction_product->transaction_product_discount_all),
                                    'date' => $transaction->created_at,
                                ]);
                            } else {
                                $questLog = QuestProductLog::create([
                                    'id_quest' => $quest->id_quest,
                                    'id_quest_detail' => $quest->id_quest_detail,
                                    'id_user' => $transaction->id_user,
                                    'id_transaction' => $transaction->id_transaction,
                                    'id_product' => $transaction_product->id_product,
                                    'id_product_variant_group' => $transaction_product->id_product_variant_group,
                                    'id_product_category' => $transaction_product->id_product_category,
                                    'product_total' => $transaction_product->transaction_product_qty,
                                    'product_nominal' => ($transaction_product->transaction_product_subtotal - $transaction_product->transaction_product_discount_all),
                                    'date' => $transaction->created_at,
                                ]);
                            }
                            $has_product = 1;
                        }
                    }
                    if (!$has_product) {
                        \DB::rollBack();
                        continue;
                    }
                }

                // province
                if ($quest->id_province || $quest->different_province) {
                    if (($transaction->outlet->city->id_province ?? null) && ($quest->id_province == $transaction->outlet->city->id_province || $quest->different_province)) {
                        $questLog = QuestProvinceLog::updateOrCreate([
                            'id_quest' => $quest->id_quest,
                            'id_quest_detail' => $quest->id_quest_detail,
                            'id_user' => $transaction->id_user,
                            'id_transaction' => $transaction->id_transaction,
                            'id_province' => $transaction->outlet->city->id_province,
                        ], [
                            'date' => $transaction->created_at,
                        ]);
                    }
                }

                // transaction
                if ($quest->trx_nominal || $quest->trx_total) {
                    $questLog = QuestTransactionLog::where([
                        'id_quest' => $quest->id_quest,
                        'id_quest_detail' => $quest->id_quest_detail,
                        'id_user' => $transaction->id_user,
                        'id_transaction' => $transaction->id_transaction,
                        'id_outlet' => $transaction->id_outlet,
                    ])->first();
                    if ($questLog) {
                        // if ($transaction->created_at <= $questLog->date) {
                        //     \DB::rollBack();
                        //     continue;
                        // }
                        $questLog->update([
                            'transaction_total' => $questLog->transaction_total + 1,
                            'transaction_nominal' => $questLog->transaction_nominal + $transaction->transaction_grandtotal,
                            'date' => $transaction->created_at,
                        ]);
                    } else {
                        $questLog = QuestTransactionLog::create([
                            'id_quest' => $quest->id_quest,
                            'id_quest_detail' => $quest->id_quest_detail,
                            'id_user' => $transaction->id_user,
                            'id_transaction' => $transaction->id_transaction,
                            'transaction_total' => 1,
                            'transaction_nominal' => $transaction->transaction_grandtotal,
                            'date' => $transaction->created_at,
                            'id_outlet' => $transaction->id_outlet,
                        ]);
                    }
                }
                $this->checkQuestDetailCompleted($quest);
                \DB::commit();
            } catch (\Exception $e) {
                \DB::rollBack();
                throw $e;
            }
        }

        $quest_masters = Quest::whereIn('quests.id_quest', $quests->pluck('id_quest'))
            ->get()
            ->each(function ($quest) use ($transaction) {
                $this->checkQuestCompleted($quest, $transaction->id_user, true);
            });
        return true;
    }

    /**
     * Check Quest Progress Completed & give benefits
     * @param  Quest $questDetail Quest Model joined quest_details and quest_users
     * @return bool              true/false
     */
    public function checkQuestDetailCompleted($questDetail)
    {
        if ($questDetail->different_product_category) {
            if (QuestProductLog::where(['id_quest_detail' => $questDetail->id_quest_detail, 'id_user' => $questDetail->id_user])->distinct('id_product_category')->count() < $questDetail->different_product_category) {
                return false;
            }
        }

        if ($questDetail->product_total) {
            if (QuestProductLog::where(['id_quest_detail' => $questDetail->id_quest_detail, 'id_user' => $questDetail->id_user])->select('product_total')->sum('product_total') < $questDetail->product_total) {
                return false;
            }
        }

        if ($questDetail->trx_total) {
            if (QuestTransactionLog::where(['id_quest_detail' => $questDetail->id_quest_detail, 'id_user' => $questDetail->id_user])->select('transaction_total')->sum('transaction_total') < $questDetail->trx_total) {
                return false;
            }
        }

        if ($questDetail->trx_nominal) {
            if (QuestTransactionLog::where(['id_quest_detail' => $questDetail->id_quest_detail, 'id_user' => $questDetail->id_user])->select('transaction_nominal')->sum('transaction_nominal') < $questDetail->trx_nominal) {
                return false;
            }
        }

        if ($questDetail->different_outlet) {
            if (QuestOutletLog::where(['id_quest_detail' => $questDetail->id_quest_detail, 'id_user' => $questDetail->id_user])->count() < $questDetail->different_outlet) {
                return false;
            }
        }

        if ($questDetail->different_province) {
            if (QuestProvinceLog::where(['id_quest_detail' => $questDetail->id_quest_detail, 'id_user' => $questDetail->id_user])->distinct('id_province')->count() < $questDetail->different_province) {
                return false;
            }
        }

        QuestUserDetail::where(['id_quest_user_detail' => $questDetail->id_quest_user_detail])->update(['is_done' => 1, 'date' => date('Y-m-d H:i:s')]);
        return true;
    }

    public function checkQuestCompleted($quest, $id_user, $auto = false, &$errors = [], &$benefit = null)
    {
        $id_reference = null;
        if (is_numeric($quest)) {
            $quest = Quest::where('quests.id_quest', $quest)->join('quest_users', 'quest_users.id_quest', 'quests.id_quest')->first();
        }

        if (!$quest) {
            $errors[] = 'Quest tidak ditemukan atau belum diklaim';
            return false;
        }

        $questIncomplete = QuestUserDetail::where(['is_done' => 0, 'id_quest' => $quest->id_quest, 'id_user' => $id_user])->exists();
        if ($questIncomplete) {
            $errors[] = 'Quest belum selesai';
            return false;
        }

        $user = User::find($id_user);
        if (!$user) {
            goto flag;
        }

        // first check? done? send notif
        if (!(QuestUser::where(['id_quest' => $quest->id_quest, 'id_user' => $id_user])->pluck('is_done')->first())) {
            $autocrm = app($this->autocrm)->SendAutoCRM(
                'Quest Completed',
                $user->phone,
                [
                    'id_quest'           => $quest->id_quest,
                    'quest_name'         => $quest->name,
                ]
            );
        }

        // update is_done status
        QuestUser::where(['id_quest' => $quest->id_quest, 'id_user' => $id_user])->update(['is_done' => 1]);

        $benefit =  QuestBenefit::with('deals')->where(['id_quest' => $quest->id_quest])->first();
        if (!$benefit) {
            goto flag;
        }

        $redemption = QuestUserRedemption::where(['id_quest' => $quest->id_quest, 'id_user' => $id_user, 'redemption_status' => 1])->first();
        if ($redemption) {
            $errors[] = 'Hadiah sudah di klaim';
            return false;
        }


        if (!$benefit->autoclaim_benefit && $auto) {
            // not autoclaim
            return false;
        }

        if ($benefit->benefit_type == 'point') {
            $log_balance = app($this->balance)->addLogBalance($id_user, $benefit->value, $quest->id_quest, 'Quest Benefit', 0);
            $benefit->log_balance = $log_balance;
            $id_reference = $log_balance->id_log_balance;
            // addLogBalance
            $autocrm = app($this->autocrm)->SendAutoCRM(
                'Receive Quest Point',
                $user->phone,
                [
                    'id_quest'           => $quest->id_quest,
                    'id_log_balance'     => $id_reference,
                    'quest_name'         => $quest->name,
                    'point_received'     => MyHelper::requestNumber($benefit->value, '_POINT'),
                ]
            );
        } elseif ($benefit->benefit_type == 'voucher') {
            $deals = Deal::where('id_deals', $benefit->id_deals)->first();
            if (!$deals) {
                goto flag;
            }

            // inject Voucher
            $count = 0;
            $total_voucher = $deals['deals_total_voucher'];
            $total_claimed = $deals['deals_total_claimed'];
            $total_benefit = $benefit->value ?: 1;

            for ($i = 0; $i < $total_benefit; $i++) {
                if ($total_voucher > $total_claimed || $total_voucher === 0) {
                    $generateVoucher = app($this->hidden_deals)->autoClaimedAssign($deals, [$id_user]);
                    $benefit->deals->deals_voucher = $generateVoucher;
                    $id_reference = $generateVoucher->id_deals_user;
                    $count++;
                    app($this->deals_claim)->updateDeals($deals);
                    $deals = Deal::where('id_deals', $deals->id_deals)->first();
                    $total_claimed = $deals['deals_total_claimed'];
                } else {
                    break;
                }
            }

            if ($total_voucher && $total_voucher <= $total_claimed) {
                // set inactive quest
                $quest->update([
                    'stop_at' => date('Y-m-d H:i:s'),
                    'stop_reason' => 'voucher runs out'
                ]);

                //send notification bulk for user
                QuestRecipientNotification::dispatch(['id_quest' => $quest->id_quest, 'deals' => $deals])->allOnConnection('quest');
            }

            if ($count) {
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Receive Quest Voucher',
                    $user->phone,
                    [
                        'id_quest'           => $quest->id_quest,
                        'id_deals_user'      => $id_reference,
                        'count_voucher'      => (string) $count,
                        'deals_title'        => $deals->deals_title,
                        'quest_name'         => $quest->name,
                        'voucher_qty'        => (string) $count,
                    ]
                );
            } else {
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Quest Voucher Runs Out',
                    $user->phone,
                    [
                        'count_voucher'      => (string) $count,
                        'deals_title'        => $deals->deals_title,
                        'quest_name'         => $quest->name,
                        'voucher_qty'        => (string) $total_benefit,
                    ]
                );
            }
        }

        flag:
        QuestUserRedemption::updateOrCreate([
            'id_quest' => $quest->id_quest,
            'id_user' => $id_user
        ], [
            'redemption_status' => 1,
            'id_reference' => $id_reference,
            'benefit_type' => $benefit->benefit_type,
            'redemption_date' => date('Y-m-d H:i:s')
        ]);
        $quest->update(['benefit_claimed' => QuestUserRedemption::where('id_quest', $quest->id_quest)->where('redemption_status', 1)->count()]);
        return true;
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show(Request $request)
    {
        try {
            $data['quest']  = Quest::with('quest_detail', 'quest_detail.product', 'quest_detail.outlet', 'quest_detail.outlet_group', 'quest_detail.province', 'quest_contents', 'quest_benefit', 'quest_benefit.deals')->where('id_quest', $request['id_quest'])->first();
            $data['quest']['short_description_ori'] = $data['quest']['short_description'];
            $data['quest']->applyShortDescriptionTextReplace();
            $data['quest']['short_description_formatted'] = $data['quest']['short_description'];
            $data['quest']['short_description'] = $data['quest']['short_description_ori'];
            $data['quest']['id_quest_encripted'] = MyHelper::encSlug($data['quest']['id_quest']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'    => 'fail',
                'messages'   => ['Get Quest Detail Failed'],
                'error'     => $e->getMessage()
            ]);
        }

        $data['quest']['image']    = config('url.storage_url_api') . $data['quest']['image'];

        return response()->json([
            'status'    => 'success',
            'data'      => $data
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('quest::edit');
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

        DB::beginTransaction();
        try {
            QuestDetail::where('id_quest_detail', $post['id_quest_detail'])->update([
                'name' => $post['name'] ?? '',
                'short_description' => $post['short_description'] ?? '',
                'quest_rule' => $post['quest_rule'] ?? null,
                'id_product' => $post['id_product'] ?? null,
                'id_product_variant_group' => $post['id_product_variant_group'] ?? null,
                'product_total' => $post['product_total'] ?? null,
                'trx_nominal' => $post['trx_nominal'] ?? null,
                'trx_total' => $post['trx_total'] ?? null,
                'id_product_category' => $post['id_product_category'] ?? null,
                'id_outlet' => ($post['id_outlet'] ?? false) ?: null,
                'id_outlet_group' => $post['id_outlet_group'] ?? null,
                'id_province' => $post['id_province'] ?? null,
                'different_category_product' => $post['different_product_category'] ?? null,
                'different_outlet' => $post['different_outlet'] ?? null,
                'different_province' => $post['different_province'] ?? null,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'    => 'fail',
                'message'   => 'Update Quest Detail Failed',
                'error'     => $e->getMessage()
            ]);
        }
        DB::commit();

        return response()->json([
            'status'    => 'success'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        if ($request->id_quest_detail) {
            $quest = QuestDetail::where(['id_quest_detail' => $request->id_quest_detail])->join('quests', 'quest_details.id_quest', 'quests.id_quest')->first();
        } else {
            $quest = Quest::find($request->id_quest);
        }
        if (!$quest) {
            return MyHelper::checkGet($quest);
        }
        if ($quest->is_complete) {
            return [
                'status' => 'fail',
                'messages' => ['Quest cannot be deleted']
            ];
        }
        $delete = $quest->delete();
        return MyHelper::checkDelete($delete);
    }

    public function list(Request $request)
    {
        if (!$request->user()->complete_profile) {
            return [
                'status' => 'fail',
                'code' => 'profile_incomplete',
                'messages' => ['Lengkapi profil untuk mengikuti misi']
            ];
        }
        $id_user = $request->user()->id;
        $dataNotAvailableQuest = $this->userRuleNotAvailableQuest($id_user);

        $quests = Quest::select('quests.id_quest', 'quest_users.id_user', 'name', 'image as image_url', 'quests.date_start', 'quests.date_end', 'short_description', 'description', \DB::raw('(CASE WHEN id_quest_user IS NOT NULL THEN 1 ELSE 0 END) as quest_claimed, (CASE WHEN quest_users.date_start IS NOT NULL THEN quest_users.date_start ELSE quests.date_start END) as date_start, (CASE WHEN quest_users.date_end IS NOT NULL THEN quest_users.date_end ELSE quests.publish_end END) as date_end'))
            ->where(function ($query) {
                $query->where('quests.quest_limit', 0)
                    ->orWhere(function ($query2) {
                        $query2->whereColumn('quests.quest_limit', '>', 'quests.quest_claimed');
                    });
            })
            ->whereNull('quests.stop_at')
            ->leftJoin('quest_users', function ($q) use ($id_user) {
                $q->on('quest_users.id_quest', 'quests.id_quest')
                    ->where('quest_users.id_user', $id_user);
            })
            ->leftJoin('quest_user_redemptions', function ($q) use ($id_user) {
                $q->on('quest_users.id_quest', 'quest_user_redemptions.id_quest')
                    ->where('quest_user_redemptions.id_user', $id_user);
            })
            ->where(function ($query) {
                $query->where('quest_user_redemptions.redemption_status', '<>', 1)
                    ->orWhereNull('quest_user_redemptions.redemption_status');
            })
            ->where('publish_start', '<=', date('Y-m-d H:i:s'))
            ->where(function ($query) {
                $query->where(function ($query2) {
                    $query2->where('publish_end', '>=', date('Y-m-d H:i:s'))
                        ->whereNull('quest_users.id_user')
                        ->where('quests.autoclaim_quest', 0);
                })
                    ->orWhere(function ($query2) {
                        // claimed
                        $query2->whereNotNull('quest_users.id_user')
                            // not expired
                            ->whereRaw('(CASE WHEN quest_users.date_end IS NOT NULL THEN quest_users.date_end ELSE quests.date_end END) >= "' . date('Y-m-d H:i:s') . '"');
                    });
            })
            ->where('is_complete', 1);

        if ($request->completed && $request->ongoing && $request->available) {
            // do nothing
        } elseif ($request->completed) {
            $quests->where('quest_users.is_done', 1);
        } elseif ($request->ongoing) {
            $quests->whereNotNull('quest_users.id_quest_user')
                ->where('quest_users.is_done', 0);
        } elseif ($request->available) {
            $quests->whereNull('quest_users.id_quest_user');
        }

        $date_start = $request->date_start ? date('Y-m-d', strtotime($request->date_start)) : null;
        $date_end = $request->date_end ?  date('Y-m-d', strtotime($request->date_end)) : null;
        if ($date_start) {
            $quests->whereDate(\DB::raw('(CASE WHEN quest_users.date_end IS NOT NULL THEN quest_users.date_end ELSE quests.publish_end END)'), '>=', $date_start);
        }
        if ($date_end) {
            $quests->whereDate(\DB::raw('(CASE WHEN quest_users.date_start IS NOT NULL THEN quest_users.date_start ELSE quests.date_start END)'), '<=', $date_end);
        }

        if (!empty($dataNotAvailableQuest)) {
            $quests = $quests->whereNotIn('quests.id_quest', $dataNotAvailableQuest);
        }
        if ($request->page) {
            $quests = $quests->paginate();
        } else {
            $quests = $quests->get();
        }

        $time_server = date('Y-m-d H:i:s');
        $quests->each(function ($item) use ($time_server) {
            $item->applyShortDescriptionTextReplace();
            $item->append('contents', 'text_label', 'progress');
            $item->makeHidden(['date_start', 'quest_contents', 'id_user', 'description']);
            $item->date_end_format = MyHelper::indonesian_date_v2($item['date_end'], 'd F Y');
            $item->time_server = $time_server;
        });

        $result = $quests->toArray();
        return MyHelper::checkGet($result, "Belum ada misi saat ini.\nTunggu misi selanjutnya");
    }

    public function userRuleNotAvailableQuest($id_user)
    {
        //get quest with rule
        $userRule = Quest::whereNotNull('user_rule_subject')
            ->select('id_quest', 'user_rule_subject', 'user_rule_parameter', 'user_rule_operator')
            ->get()->toArray();
        $notAvailableQuest = [];

        foreach ($userRule as $val) {
            if (!empty($val['user_rule_subject']) && !empty($val['user_rule_operator']) && !empty($val['user_rule_parameter'])) {
                $check = CrmUserData::where($val['user_rule_subject'], $val['user_rule_operator'], $val['user_rule_parameter'])
                    ->where('id_user', $id_user)->get()->toArray();
                $checkClaim = QuestUser::where('id_user', $id_user)->where('id_quest', $val['id_quest'])->first();
                if (empty($check) && empty($checkClaim)) {
                    $notAvailableQuest[] = $val['id_quest'];
                }
            }
        }

        return $notAvailableQuest;
    }

    public function takeMission(Request $request)
    {
        if (!$request->user()->complete_profile) {
            return [
                'status' => 'fail',
                'code' => 'profile_incomplete',
                'messages' => ['Lengkapi profil untuk mengikuti misi']
            ];
        }
        $id_user = $request->user()->id;
        return $this->doTakeMission($id_user, $request->id_quest);
    }

    public function doTakeMission($id_user, $id_quest)
    {
        $quest = Quest::select('quests.*', 'id_quest_user')
            ->leftJoin('quest_users', function ($q) use ($id_user) {
                $q->on('quest_users.id_quest', 'quests.id_quest')
                    ->where('id_user', $id_user);
            })
            ->where('quests.id_quest', $id_quest)
            ->where('is_complete', 1)
            ->first();
        if (!$quest) {
            return [
                'status' => 'fail',
                'messages' => ['Quest not found']
            ];
        }
        if ($quest['id_quest_user']) {
            return [
                'status' => 'fail',
                'messages' => [
                    'Misi sudah diambil'
                ]
            ];
        }

        if ($quest->publish_end < date('Y-m-d H:i:s')) {
            return [
                'status' => 'fail',
                'messages' => [
                    'Misi sudah berakhir'
                ]
            ];
        }

        if ($quest->quest_limit && $quest->quest_limit <= $quest->quest_claimed) {
            return [
                'status' => 'fail',
                'messages' => [
                    'Misi sudah mencapai limit klaim'
                ]
            ];
        }

        if (!empty($quest['user_rule_subject'])) {
            $check = CrmUserData::where($quest['user_rule_subject'], $quest['user_rule_operator'], $quest['user_rule_parameter'])
                ->where('id_user', $id_user)->get()->toArray();

            if (empty($check)) {
                return [
                    'status' => 'fail',
                    'messages' => [
                        'Quest ini tidak berlaku untuk user Anda'
                    ]
                ];
            }
        }

        $questDetail = QuestDetail::where(['id_quest' => $quest->id_quest])->get();
        $toCreate = [
            'id_quest' => $quest->id_quest,
            'id_user' => $id_user,
            'date_start' => $quest->date_start,
            'date_end' => $quest->date_end,
        ];
        if (!$toCreate['date_end']) {
            if (strtotime($toCreate['date_start']) > time()) {
                $toCreate['date_end'] = date('Y-m-d H:i:s', strtotime($toCreate['date_start']) + (86400 * $quest->max_complete_day));
            } else {
                $toCreate['date_end'] = date('Y-m-d H:i:s', time() + (86400 * $quest->max_complete_day));
            }
        }
        $questUser = QuestUser::create($toCreate);
        if (!$questUser) {
            return [
                'status' => 'fail',
                'messages' => ['Failed create quest user']
            ];
        }
        $questDetail->each(function ($detail) use ($questUser) {
            QuestUserDetail::updateOrCreate([
                'id_quest' => $questUser->id_quest,
                'id_quest_user' => $questUser->id_quest_user,
                'id_quest_detail' => $detail->id_quest_detail,
                'id_user' => $questUser->id_user,
            ]);
        });
        $quest->update(['quest_claimed' => QuestUser::where('id_quest', $quest->id_quest)->count()]);
        return [
            'status' => 'success',
            'result' => [
                'id_quest' => $quest->id_quest
            ],
        ];
    }

    public function claimBenefit(Request $request)
    {
        $claim = $this->checkQuestCompleted($request->id_quest, $request->user()->id, false, $errors, $quest_benefit);

        if ($claim) {
            $benefit = [
                'type' => $quest_benefit->benefit_type
            ];
            if ($quest_benefit->benefit_type == 'voucher') {
                $benefit['text'] = $quest_benefit->deals->deals_title;
                if (!$quest_benefit->deals->deals_voucher) {
                    return [
                        'status' => 'fail',
                        'messages' => ['Voucher habis']
                    ];
                }
                $benefit['id'] = $quest_benefit->deals->deals_voucher->id_deals_user;
            } else {
                $benefit['text'] = MyHelper::requestNumber($quest_benefit->value, '_POINT') . ' ' . env('POINT_NAME', 'Points');
                $benefit['id'] = $quest_benefit->log_balance->id_log_balance;
            }
            return ['status' => 'success', 'result' => ['benefit' => $benefit]];
        }
        return [
            'status' => 'fail',
            'messages' => $errors ?? [
                'Failed claim benefit'
            ]
        ];
    }

    public function me(Request $request)
    {
        $id_user = $request->user()->id;
        $date_start = $request->date_start ? date('Y-m-d', strtotime($request->date_start)) : null;
        $date_end = $request->date_end ?  date('Y-m-d', strtotime($request->date_end)) : null;
        $quests = Quest::select('quests.id_quest', 'name', 'image as image_url', 'short_description', 'quest_users.date_start', 'quest_users.id_user', 'redemption_date', \DB::raw('COALESCE(redemption_status, 0) as claimed_status, (CASE WHEN quest_user_redemptions.redemption_status = 1 THEN quest_user_redemptions.redemption_date WHEN quests.stop_at is not null and quests.stop_at < quest_users.date_end THEN quests.stop_at ELSE quest_users.date_end END) as date_end'))
            ->where('is_complete', 1)
            ->where(function ($query) {
                $query->where('quest_user_redemptions.redemption_status', 1);
                $query->orWhere('quest_users.date_end', '<', date('Y-m-d H:i:s'));
                $query->orWhereNotNull('quests.stop_at', '<', 'quest_users.date_end');
            })
            ->groupBy('quests.id_quest')
            ->join('quest_users', function ($q) use ($id_user) {
                $q->on('quest_users.id_quest', 'quests.id_quest')
                    ->where('id_user', $id_user);
            })
            ->leftJoin('quest_user_redemptions', function ($join) {
                $join->on('quest_user_redemptions.id_quest', 'quest_users.id_quest')
                    ->whereColumn('quest_user_redemptions.id_user', 'quest_users.id_user');
            });

        if ($request->completed && $request->expired) {
            // do nothing
        } elseif ($request->expired) {
            $quests->where(function ($query) {
                    $query->where('quest_users.date_end', '<', date('Y-m-d H:i:s'))
                        ->orWhere('quests.stop_at', '<', date('Y-m-d H:i:s'));
            })
                ->where(function ($query) {
                    $query->where('quest_user_redemptions.redemption_status', 0)
                        ->orWhereNull('quest_user_redemptions.redemption_status');
                });
        } else {
            $quests->where('quest_user_redemptions.redemption_status', 1);
        }

        if ($date_start) {
            $quests->whereDate(\DB::raw('(CASE WHEN quest_user_redemptions.redemption_status = 1 THEN quest_user_redemptions.redemption_date WHEN quests.stop_at is not null and quests.stop_at < quest_users.date_end THEN quests.stop_at ELSE quest_users.date_end END)'), '>=', $date_start);
        }
        if ($date_end) {
            $quests->whereDate(\DB::raw('(CASE WHEN quest_user_redemptions.redemption_status = 1 THEN quest_user_redemptions.redemption_date WHEN quests.stop_at is not null and quests.stop_at < quest_users.date_end THEN quests.stop_at ELSE quest_users.date_end END)'), '<=', $date_end);
        }

        $quests->orderBy('date_end', 'desc');

        if ($request->page) {
            $quests = $quests->paginate();
        } else {
            $quests = $quests->get();
        }

        $time_server = date('Y-m-d H:i:s');
        $quests->each(function ($item) use ($time_server) {
            $item->applyShortDescriptionTextReplace();
            $item->append(['progress', 'text_label']);
            $item->makeHidden(['date_start', 'id_user', 'redemption_date']);
            $item->date_end_format = MyHelper::indonesian_date_v2($item['date_end'], 'd F Y');
            $item->time_server = $time_server;
            if ($item->claimed_status) {
                $item->text_label = [
                    'text' => 'Selesai pada ' . MyHelper::indonesian_date_v2($item['redemption_date'], 'd F Y'),
                    'code' => 2
                ];
            }
        });

        $result = $quests->toArray();
        return MyHelper::checkGet($result, "Belum ada misi saat ini.\nMulai sebuah misi baru");
    }

    public function detail(Request $request)
    {
        $id_user = $request->user()->id;
        $quest = Quest::select('quests.id_quest', 'quest_users.id_quest_user', 'quest_users.id_user', 'name', 'image as image_url', 'description', 'short_description', 'stop_reason', \DB::raw('(CASE WHEN quest_users.id_quest_user IS NOT NULL THEN 1 ELSE 0 END) as quest_claimed, (CASE WHEN quest_users.date_start IS NOT NULL THEN quest_users.date_start ELSE quests.date_start END) as date_start, (CASE WHEN quests.stop_at IS NOT NULL THEN quests.stop_at WHEN quest_users.date_end IS NOT NULL THEN quest_users.date_end ELSE quests.publish_end END) as date_end, 1 as text_label'))
            ->with(['quest_benefit', 'quest_benefit.deals'])
            ->leftJoin('quest_users', function ($q) use ($id_user) {
                $q->on('quest_users.id_quest', 'quests.id_quest')
                    ->where('id_user', $id_user);
            })
            ->leftJoin('quest_user_details', 'quest_users.id_quest_user', 'quest_user_details.id_quest_user')
            ->where('quests.id_quest', $request['id_quest'] ?? $request->id_quest)
            ->first();
        if (!$quest) {
            return MyHelper::checkGet([], "Quest tidak ditemukan");
        }

        if (!$quest->quest_benefit) {
            return [
                'status' => 'fail',
                'messages' => ['Data Rusak']
            ];
        }

        $quest->applyShortDescriptionTextReplace();

        $benefit = [
            'type' => $quest->quest_benefit->benefit_type
        ];
        if ($quest->quest_benefit->benefit_type == 'voucher') {
            $benefit['id_deals'] = $quest->quest_benefit->id_deals;
            $benefit['total_voucher'] = $quest->quest_benefit->value;
            $benefit['text'] = $quest->quest_benefit->deals->deals_title;
        } else {
            $benefit['point_nominal'] = $quest->quest_benefit->value;
            $benefit['text'] = MyHelper::requestNumber($quest->quest_benefit->value, '_POINT') . ' ' . env('POINT_NAME', 'Points');
        }

        $quest->append(['progress', 'contents', 'user_redemption']);
        $quest->makeHidden(['quest_contents', 'description', 'quest_benefit', 'id_quest_user']);
        $result = $quest->toArray();
        $result['date_start_format'] = MyHelper::indonesian_date_v2($result['date_start'], 'd F Y, H:i');
        $result['date_end_format'] = MyHelper::indonesian_date_v2($result['date_end'], 'd F Y, H:i');
        $result['is_count'] = strtotime($result['date_start']) <= time() ? 1 : 0;
        $result['time_server'] = date('Y-m-d H:i:s');
        $result['time_to_end'] = strtotime($result['date_end']) - time();
        $result['benefit'] = $benefit;
        $result['claimed_status'] = $result['user_redemption']['redemption_status'] ?? 0;

        if ($result['claimed_status']) {
            $result['text_label'] = [
                'text' => 'Selesai pada ' . MyHelper::indonesian_date_v2($result['user_redemption']['redemption_date'], 'd F Y'),
                'code' => 2
            ];
        }

        if ($result['text_label']['code'] == -1) {
            $result['progress']['complete'] = 0;
            $result['text_label']['text'] = str_replace('Berakhir', 'Tantangan telah berakhir', $result['text_label']['text']) . $result['text_label']['stop_reason'];
        }

        if ($result['text_label']['code'] == 0) {
            $result['text_label']['text'] = 'Dimulai pada ' . $result['date_start_format'];
        }

        $result['benefit']['id_reference'] = $result['user_redemption']['id_reference'] ?? null;

        $details = QuestDetail::select('name', 'short_description', 'is_done', 'quest_details.id_quest', 'quest_details.id_quest_detail', 'quest_rule', 'id_product_category', 'different_category_product', 'id_product', 'id_product_variant_group', 'product_total', 'trx_nominal', 'trx_total', 'id_outlet', 'id_province', 'different_outlet', 'different_province', 'id_quest_user_detail', 'id_quest_user', 'id_user')
            ->where(['quest_details.id_quest' => $quest->id_quest])
            ->leftJoin('quest_user_details', function ($query) use ($id_user) {
                $query->on('quest_details.id_quest_detail', 'quest_user_details.id_quest_detail')
                    ->where(['id_user' => $id_user]);
            })
            ->get();

        $details->each(function ($item) {
            $item->append('progress');
            $item->makeHidden(['id_quest', 'id_quest_detail', 'quest_rule', 'id_product_category', 'different_category_product', 'id_product', 'id_product_variant_group', 'product_total', 'trx_nominal', 'trx_total', 'id_outlet', 'id_province', 'different_outlet', 'different_province', 'id_quest_user_detail', 'id_quest_user', 'id_user']);
        });

        $result['details'] = $details;

        return MyHelper::checkGet($result, "Quest tidak ditemukan");
    }

    public function listDeals(Request $request)
    {
        $result = Deal::select('id_deals', 'deals_title')
            ->where('deals_end', '>=', date('Y-m-d H:i:s'))
            ->where('step_complete', '1')
            ->get()
            ->toArray();
        return MyHelper::checkGet($result);
    }

    public function listQuestVoucher()
    {
        $result = Deal::select('id_deals', 'deals_title', 'deals_voucher_type', 'deals_total_voucher', 'deals_total_claimed', 'deals_voucher_expired', 'deals_voucher_duration')
            ->where('step_complete', '1')
            ->where('deals_type', 'Quest')
            ->where(function ($q) {
                $q->whereNull('deals_voucher_expired')
                    ->orWhere('deals_voucher_expired', '>=', date('Y-m-d H:i:s'));
            })
            ->get()
            ->toArray();
        return MyHelper::checkGet($result);
    }

    public function listProduct(Request $request)
    {
        $result = Product::select('id_product', 'product_name', 'product_code')
            ->with(['product_variant_group' => function ($relation) {
                $relation->select(
                    'product_variant_groups.id_product',
                    'product_variant_groups.id_product_variant_group',
                    \DB::raw('GROUP_CONCAT(product_variant_name) as product_variants')
                )
                    ->groupBy('id_product_variant_group');
            }])
            ->get()
            ->toArray();
        return MyHelper::checkGet($result);
    }

    public function updateContent(Request $request)
    {
        $quest = Quest::find($request->id_quest);
        if (!$quest) {
            return MyHelper::checkGet([], 'Quest tidak ditemukan');
        }
        $quest->update([
            'description' => $request->quest['description'],
        ]);
        $content_order = array_flip($request->content_order ?: []);
        $id_contents = [];
        foreach ($request->content ?: [] as $idx => $content) {
            if ($content['id_quest_content'] ?? false) {
                $quest_content = QuestContent::where('id_quest_content', $content['id_quest_content'])->update([
                    'id_quest' => $quest->id_quest,
                    'title' => $content['title'],
                    'content' => $content['content'] ?: '',
                    'is_active' => ($content['is_active'] ?? false) ? '1' : '0',
                    'order' => ($content_order[$idx] ?? false) ? $content_order[$idx] + 1 : 0,
                ]);
                $id_contents[] = $content['id_quest_content'];
            } else {
                $quest_content = QuestContent::create([
                    'id_quest' => $quest->id_quest,
                    'title' => $content['title'],
                    'content' => $content['content'] ?: '',
                    'is_active' => ($content['is_active'] ?? false) ? '1' : '0',
                    'order' => ($content_order[$idx] ?? false) ? $content_order[$idx] + 1 : 0,
                ]);
                $id_contents[] = $quest_content->id_quest_content;
            }
        }
        QuestContent::where(['id_quest' => $quest->id_quest])->whereNotIn('id_quest_content', $id_contents)->delete();
        return [
            'status' => 'success'
        ];
    }

    public function updateQuest(Request $request)
    {
        $quest = Quest::find($request->id_quest);
        if (!$quest) {
            return MyHelper::checkGet([], 'Quest tidak ditemukan');
        }
        if ($quest->is_complete) {
            return MyHelper::checkGet([], 'Quest not editable');
        }
        $toUpdate = $request->quest;

        if ($toUpdate['image'] ?? false) {
            $upload = MyHelper::uploadPhotoStrict($toUpdate['image'], $this->saveImage, 500, 500);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $toUpdate['image'] = $upload['path'];
            } else {
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Failed to upload image']
                ]);
            }
        } else {
            unset($toUpdate['image']);
        }

        if (!($toUpdate['date_end'] ?? false)) {
            $toUpdate['date_end'] = null;
        } else {
            if (strtotime($toUpdate['date_end']) < strtotime($toUpdate['publish_end'])) {
                return [
                    'status'   => 'fail',
                    'messages' => ['Quest date end should be after or equal publish end']
                ];
            }
        }

        if (!($toUpdate['max_complete_day'] ?? false)) {
            $toUpdate['max_complete_day'] = null;
        }

        if (!($toUpdate['quest_limit'] ?? false)) {
            $toUpdate['quest_limit'] = null;
        }

        $toUpdate['publish_start']     = date('Y-m-d H:i', strtotime($toUpdate['publish_start']));
        $toUpdate['date_start']        = date('Y-m-d H:i', strtotime($toUpdate['date_start']));
        if (!is_null($toUpdate['publish_end'] ?? null)) {
            $toUpdate['publish_end']   = date('Y-m-d H:i', strtotime($toUpdate['publish_end']));
        }
        if (!is_null($toUpdate['date_end'] ?? null)) {
            $toUpdate['date_end']      = date('Y-m-d H:i', strtotime($toUpdate['date_end']));
        }


        $update = $quest->update($toUpdate);
        return MyHelper::checkUpdate($update);
    }

    public function updateBenefit(Request $request)
    {
        $quest = Quest::with('quest_benefit')->find($request->id_quest);
        if (!$quest) {
            return MyHelper::checkGet([], 'Quest tidak ditemukan');
        }
        if ($quest->is_complete) {
            return MyHelper::checkGet([], 'Quest not editable');
        }
        $toUpdate = $request->quest_benefit;

        $update = $quest->quest_benefit->update($toUpdate);
        return MyHelper::checkUpdate($update);
    }

    public function start(Request $request)
    {
        $quest = Quest::with('quest_benefit')->find($request->id_quest);
        if (!$quest) {
            return MyHelper::checkGet([], 'Quest tidak ditemukan');
        }
        if ($quest->is_complete) {
            return MyHelper::checkGet([], 'Quest not editable');
        }
        $this->triggerAutoclaim($quest);
        $update = $quest->update(['is_complete' => 1]);
        return MyHelper::checkUpdate($update);
    }

    public function status(Request $request)
    {
        $id_user = $request->user()->id;
        $dataNotAvailableQuest = $this->userRuleNotAvailableQuest($id_user);

        $quests = Quest::select('quests.id_quest', 'quest_users.id_user', 'name', 'image as image_url', 'quests.date_start', 'quests.date_end', 'short_description', 'description', \DB::raw('(CASE WHEN id_quest_user IS NOT NULL THEN 1 ELSE 0 END) as quest_claimed, (CASE WHEN quest_users.date_start IS NOT NULL THEN quest_users.date_start ELSE quests.date_start END) as date_start, (CASE WHEN quest_users.date_end IS NOT NULL THEN quest_users.date_end ELSE quests.publish_end END) as date_end'))
            ->where(function ($query) {
 // limit belum habis atau sudah di klaim
                $query->where('quests.quest_limit', 0)
                    ->orWhere(function ($query2) {
                        $query2->whereColumn('quests.quest_limit', '>', 'quests.quest_claimed');
                    })
                    ->whereNotNull('quest_users.id_user');
            })
            ->whereNull('quests.stop_at') // belum berakhir
            ->leftJoin('quest_users', function ($q) use ($id_user) {
                $q->on('quest_users.id_quest', 'quests.id_quest')
                    ->where('quest_users.id_user', $id_user);
            })
            ->leftJoin('quest_user_redemptions', function ($q) use ($id_user) {
                $q->on('quest_users.id_quest', 'quest_user_redemptions.id_quest')
                    ->where('quest_user_redemptions.id_user', $id_user);
            })
            ->where(function ($query) {
 // hadiah belum diambil
                $query->where('quest_user_redemptions.redemption_status', '<>', 1)
                    ->orWhereNull('quest_user_redemptions.redemption_status');
            })
            ->where('publish_start', '<=', date('Y-m-d H:i:s')) // sudah di publish
            ->where(function ($query) {
                $query->where(function ($query2) {
                    $query2->where('publish_end', '>=', date('Y-m-d H:i:s')) // masih aktif
                        ->whereNull('quest_users.id_user') // belum diklaim
                        ->where('quests.autoclaim_quest', 0); // bukan quest autoclaim
                })
                    ->orWhere(function ($query2) {
                        // claimed
                        $query2->whereNotNull('quest_users.id_user') // sudah di klaim
                            // not expired
                            ->whereRaw('(CASE WHEN quest_users.date_end IS NOT NULL THEN quest_users.date_end ELSE quests.date_end END) >= "' . date('Y-m-d H:i:s') . '"'); // belum expired
                    });
            })
            ->where('is_complete', 1);

        if (!empty($dataNotAvailableQuest)) {
            $quests = $quests->whereNotIn('quests.id_quest', $dataNotAvailableQuest);
        }
        $myQuest = $quests->count();
        if ($myQuest) {
            $result = [
                'status' => 'success',
                'result' => [
                    'total_quest' => $myQuest,
                ]
            ];
        }

        $complete_profile = $request->user()->complete_profile;
        $questAutoClaim = null;
        if (!$complete_profile) {
            $questAutoClaim = Quest::where('autoclaim_quest', 1)
                            ->whereNull('stop_at')->where('is_complete', 1)
                            ->where('publish_start', '<=', date('Y-m-d H:i:s'))
                            ->where('publish_end', '>=', date('Y-m-d H:i:s'))->count();
            if ($questAutoClaim) {
                $result = [
                    'status' => 'success',
                    'result' => [
                        'total_quest' => $questAutoClaim,
                    ]
                ];
            }
        }

        if (empty($myQuest) && empty($questAutoClaim)) {
            $result = [
                'status' => 'fail',
                'messages' => ['Belum ada misi yang berjalan']
            ];
        }

        if (!$complete_profile) {
            $result['code'] = 'profile_incomplete';
            if ($myQuest || $questAutoClaim) {
                $result['messages'] = ['Lengkapi Profile Kamu Sekarang'];
            } else {
                $result['messages'][] = 'Lengkapi Profile Kamu Sekarang';
            }
        }
        return $result;
    }

    public function listAllQuest()
    {
        $list = Quest::where('is_complete', 1)->get()->toArray();
        return response()->json(MyHelper::checkGet($list));
    }

    public function autoclaimQuest($id_user)
    {
        $quests = Quest::where('autoclaim_quest', 1)->whereNull('stop_at')->where('is_complete', 1)->where('publish_end', '>=', date('Y-m-d H:i:s'))->get();
        foreach ($quests as $quest) {
            $this->doTakeMission($id_user, $quest->id_quest);
        }
    }
}
