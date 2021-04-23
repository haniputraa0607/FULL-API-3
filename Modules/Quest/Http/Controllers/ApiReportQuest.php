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
use Modules\Quest\Entities\QuestUserLog;
use Modules\Quest\Entities\QuestUserRedemption;
use App\Http\Models\Deal;
use App\Http\Models\Product;
use Modules\Quest\Entities\QuestContent;

use Modules\Quest\Http\Requests\StoreRequest;

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
					'quests.id_quest',
					'quests.name',
					'quests.date_start',
					'quests.date_end',
					'quests.short_description',
					'quest_benefits.benefit_type',
					DB::raw('
						COUNT(DISTINCT quest_users.id_user) as total_user
					'),
					'quests.is_complete',
				)
				->groupBy('quest_users.id_quest')
				->paginate(10);

		$list = $list->toArray();

		foreach ($list['data'] ?? [] as $key => $value) {
			$status = 'Not Started';
			if ($value['date_start'] < date('Y-m-d H:i:s') && $value['is_complete']){
				$status = 'Started';
			}elseif (!is_null($value['date_end']) && $value['date_end'] > date('Y-m-d H:i:s') && $value['is_complete']){
				$status = 'Ended';
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

    	$info = Quest::where('quests.id_quest', $id_quest)
				->leftJoin('quest_users', 'quests.id_quest', 'quest_users.id_quest')
				->select(
					'quests.*',
					DB::raw('
						COUNT(DISTINCT quest_users.id_user) as total_user
					')
				)
				->groupBy('quest_users.id_quest')
				->with('quest_benefit', 'quest_benefit.deals')
				->first();

		$info['total_rule'] = QuestDetail::where('id_quest', $id_quest)->count();
		$user_complete 	= QuestUser::select('id_user')
						->where('id_quest', $id_quest)
						->where('is_done', '1')
						->groupBy(['id_user'])
						->having(DB::raw('count(is_done)'), '=', $info['total_rule']);

		$info['total_user_complete'] = DB::table(DB::raw('('.$user_complete->toSql().') AS user_complete'))
		        ->mergeBindings($user_complete->getQuery())
		        ->count();

		$rule = QuestDetail::where('quest_details.id_quest', $id_quest)
				->leftJoin('quest_users', 'quest_users.id_quest_detail', 'quest_details.id_quest_detail')
				->where('quest_users.is_done', '1')
				->groupBy('quest_details.id_quest_detail')
				->select('quest_details.*', DB::raw('COUNT(quest_users.id_user) as user_complete'))
				->with('product','outlet','province')
				->get();

		if ($info['id_quest'] ?? false) {
			$info['id_quest_enc'] = MyHelper::encSlug($info['id_quest']);
		}

		$result = [
			'info' 	=> $info,
			'rule'	=> $rule
		];

    	return MyHelper::checkGet($result);
    }

    public function listUser(Request $request)
    {
    	$id_quest = MyHelper::decSlug($request->id_quest);

		$list = QuestUser::where('quest_users.id_quest', $id_quest)
				->with([
					'user.quest_user_redemption' => function($q) use ($id_quest){
						$q->where('id_quest', $id_quest);
					},
					'user' => function($q) {
						$q->select('id','name', 'phone', 'email');
					}
				])
				->select(
					'quest_users.*',
					DB::raw('
						MAX(date) as date_complete,
						COUNT(quest_users.is_done) as total_rule,
						COUNT(
							CASE WHEN quest_users.is_done = 1 THEN 1 END
						) as total_done,
						CASE WHEN COUNT(quest_users.is_done) = COUNT(CASE WHEN quest_users.is_done = 1 THEN 1 END) THEN "complete" 
						ELSE "on going"
						END as quest_status
					')
				)
				->groupBy('quest_users.id_user')
				->paginate($request->length ?? 10)
				->toArray();

		if ($list) {
			$data = [];
			foreach ($list['data'] ?? [] as $key => $value) {

				if ($value['user']['quest_user_redemption'][0]['redemption_status'] ?? false) {
					$benefit_status = 'claimed';
				}else{
					$benefit_status = 'not claimed';
				}

				$data[] = [
					$value['user']['name'],
					$value['user']['phone'],
					$value['user']['email'],
					date('d F Y H:i', strtotime($value['created_at'])),
					date('d F Y H:i', strtotime($value['date_complete'])),
					date('d F Y H:i', strtotime($value['user']['quest_user_redemption'][0]['redemption_date'] ?? null)),
					$value['quest_status'],
					$benefit_status,
					$value['total_done']
				];
			}
			$list['data'] = $data;
		}

		return MyHelper::checkGet($list);
    }
}
