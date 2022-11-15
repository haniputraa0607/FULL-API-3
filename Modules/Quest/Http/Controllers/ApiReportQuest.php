<?php

namespace Modules\Quest\Http\Controllers;

use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\User;
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
use Modules\Quest\Http\Requests\StoreRequest;
use Excel;

class ApiReportQuest extends Controller
{
    public function __construct()
    {
        $this->balance      = "Modules\Balance\Http\Controllers\BalanceController";
        $this->autocrm      = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }

    public function list(Request $request)
    {
        $list = Quest::join('quest_benefits', 'quests.id_quest', 'quest_benefits.id_quest')
                ->leftJoin('quest_users', 'quests.id_quest', 'quest_users.id_quest')
                ->select(
                    'quests.*',
                    'quest_benefits.benefit_type',
                    DB::raw('
						COUNT(DISTINCT quest_users.id_user) as total_user
					')
                )
                ->groupBy('quests.id_quest');

        if (isset($request->conditions) && !empty($request->conditions)) {
            $list = $this->filterList($list, $request);
        }

        if ($request->date_start && $request->date_end) {
            $date_start = date('Y-m-d', strtotime($request->date_start));
            $date_end = date('Y-m-d', strtotime($request->date_end));
            $list->where(function ($q) use ($date_start, $date_end) {
                $q->whereBetween('quests.date_start', [$date_start, $date_end])
                    ->orWhereBetween('quests.date_end', [$date_start, $date_end])
                    ->orWhereNull('quests.date_end');
            });
        }

        $list = $list->orderBy('quests.id_quest', 'desc')->paginate(10)->toArray();

        foreach ($list['data'] ?? [] as $key => $value) {
            $status = 'Not Started';
            if ($value['stop_at']) {
                $status = 'Stopped';
            } elseif (!is_null($value['publish_end']) && $value['publish_end'] < date('Y-m-d H:i:s') && $value['is_complete']) {
                $status = 'Ended';
            } elseif ($value['publish_start'] < date('Y-m-d H:i:s') && $value['is_complete']) {
                $status = 'Started';
            }
            $list['data'][$key]['status'] = $status;
            $list['data'][$key]['id_quest'] = MyHelper::encSlug($value['id_quest']);
        }

        $result = MyHelper::checkGet($list);

        return $result;
    }

    public function detail(Request $request)
    {
        $id_quest = MyHelper::decSlug($request->id_quest);

        if (!$id_quest) {
            return MyHelper::checkGet([], "Quest tidak ditemukan");
        }

        $info = Quest::where('quests.id_quest', $id_quest)
                ->leftJoin('quest_users', 'quests.id_quest', 'quest_users.id_quest')
                ->select(
                    'quests.*',
                    DB::raw('
						COUNT(DISTINCT quest_users.id_user) as total_user,
						(SELECT COUNT(*) FROM quest_details where id_quest = ' . $id_quest . ') as total_rule
					')
                )
                ->groupBy('quests.id_quest')
                ->with('quest_benefit', 'quest_benefit.deals')
                ->first();

        if (!$info) {
            return MyHelper::checkGet([], "Quest tidak ditemukan");
        }

        $info->applyShortDescriptionTextReplace();

        $user_complete  = QuestUserDetail::select('id_user')
                        ->where('id_quest', $id_quest)
                        ->where('is_done', '1')
                        ->groupBy(['id_user'])
                        ->having(DB::raw('count(is_done)'), '=', $info['total_rule']);

        $info['total_user_complete'] = DB::table(DB::raw('(' . $user_complete->toSql() . ') AS user_complete'))
                ->mergeBindings($user_complete->getQuery())
                ->count();

        $rule = QuestDetail::where('quest_details.id_quest', $id_quest)
                ->leftJoin('quest_user_details', 'quest_user_details.id_quest_detail', 'quest_details.id_quest_detail')
                ->groupBy('quest_details.id_quest_detail')
                ->select(
                    'quest_details.*',
                    DB::raw('COUNT(CASE WHEN quest_user_details.is_done = 1 THEN quest_user_details.id_user END) as user_complete')
                )
                ->with('product', 'outlet', 'province')
                ->get();

        if ($info['id_quest'] ?? false) {
            $info['id_quest_enc'] = MyHelper::encSlug($info['id_quest']);
        }

        $result = [
            'info'  => $info,
            'rule'  => $rule
        ];

        return MyHelper::checkGet($result);
    }

    public function listUser(Request $request)
    {
        $id_quest = MyHelper::decSlug($request->id_quest);
        $date_now = date("Y-m-d H:i:s");

        $list = QuestUserDetail::where('quest_user_details.id_quest', $id_quest)
                ->join('quest_users', 'quest_users.id_quest_user', 'quest_user_details.id_quest_user')
                ->with([
                    'user.quest_user_redemption' => function ($q) use ($id_quest) {
                        $q->where('id_quest', $id_quest);
                    },
                    'user' => function ($q) {
                        $q->select('id', 'name', 'phone', 'email');
                    }
                ])
                ->select(
                    'quest_user_details.*',
                    'quest_users.is_done as complete_status',
                    DB::raw('
						CASE WHEN quest_users.is_done = 1 THEN quest_users.date END as date_complete,
						COUNT(quest_user_details.is_done) as total_rule,
						COUNT(
							CASE WHEN quest_user_details.is_done = 1 THEN 1 END
						) as total_done,
						CASE WHEN quest_users.is_done = 1 THEN "complete" 
							 WHEN quest_users.date_end < "' . $date_now . '" THEN "expired"
						ELSE "on going" END as quest_status
					')
                )
                ->groupBy('quest_user_details.id_user');

        if ($request->export) {
            $list = $list->get()->toArray();
            $temp = $list;
        } else {
            $list = $list->paginate($request->length ?? 10)->toArray();
            $temp = $list['data'];
        }

        if ($list) {
            $data = [];
            foreach ($temp as $key => $value) {
                if ($value['user']['quest_user_redemption'][0]['redemption_status'] ?? false) {
                    $benefit_status = 'claimed';
                } else {
                    $benefit_status = 'not claimed';
                }

                $data[] = [
                    $value['user']['name'],
                    $value['user']['phone'],
                    $value['user']['email'],
                    $value['created_at'] ? date('d F Y H:i', strtotime($value['created_at'])) : null,
                    $value['date_complete'] ? date('d F Y H:i', strtotime($value['date_complete'])) : null,
                    ($value['user']['quest_user_redemption'][0]['redemption_date'] ?? false) ? date('d F Y H:i', strtotime($value['user']['quest_user_redemption'][0]['redemption_date'])) : null,
                    $value['quest_status'],
                    $benefit_status,
                    $value['total_done']
                ];
            }

            if ($request->export) {
                $list = $data;
            } else {
                $list['data'] = $data;
            }
        }

        return MyHelper::checkGet($list);
    }

    public function filterList($query, $request)
    {
        $post = $request->json()->all();


        $query = $query->where(function ($subquery) use ($post) {

            $rule = 'and';
            if (isset($post['rule'])) {
                $rule = $post['rule'];
            }

            if ($rule == 'and') {
                $where = 'where';
                $whereIn = 'whereIn';
            } else {
                $where = 'orWhere';
                $whereIn = 'orWhereIn';
            }

            foreach ($post['conditions'] as $row) {
                if (isset($row['subject'])) {
                    if ($row['subject'] == 'quest_name' && !empty($row['parameter'])) {
                        if ($row['operator'] == '=') {
                            $subquery->$where('quests.name', $row['parameter']);
                        } else {
                            $subquery->$where('quests.name', 'like', '%' . $row['parameter'] . '%');
                        }
                    }

                    if ($row['subject'] == 'number_of_user_quest' && !empty($row['parameter'])) {
                        $subquery->$whereIn('quests.id_quest', function ($sub) use ($row) {
                            $sub->select('q_detail.id_quest')->from('quest_user_details as q_users')
                                ->join('quest_details as q_detail', 'q_users.id_quest_detail', 'q_detail.id_quest_detail')
                                ->groupBy('q_detail.id_quest')
                                ->havingRaw('COUNT(DISTINCT q_users.id_user) ' . $row['operator'] . ' ' . $row['parameter']);
                        });
                    }

                    if ($row['subject'] == 'rule_quest' && !empty($row['operator'])) {
                        $subquery->$whereIn('quests.id_quest', function ($sub) use ($row) {
                            $sub->select('q_detail.id_quest')->from('quest_details as q_detail');

                            if ($row['operator'] == 'product') {
                                $sub->where(function ($q) {
                                    $q->whereNotNull('id_product')->orWhereNotNull('product_total');
                                });
                            }

                            if ($row['operator'] == 'outlet') {
                                $sub->where(function ($q) {
                                    $q->whereNotNull('id_outlet')->orWhereNotNull('different_outlet');
                                });
                            }

                            if ($row['operator'] == 'province') {
                                $sub->where(function ($q) {
                                    $q->whereNotNull('id_province')->orWhereNotNull('different_province');
                                });
                            }

                            if ($row['operator'] == 'trx_nominal') {
                                $sub->whereNotNull('trx_nominal');
                            }

                            if ($row['operator'] == 'trx_total') {
                                $sub->whereNotNull('trx_total');
                            }
                        });
                    }

                    if ($row['subject'] == 'benefit_quest' && !empty($row['operator'])) {
                        $subquery->$where('quest_benefits.benefit_type', $row['operator']);
                    }
                }
            }
        });

        return $query;
    }
}
