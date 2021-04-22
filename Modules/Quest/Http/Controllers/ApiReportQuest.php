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
						count(DISTINCT quest_users.id_quest) as total_user
					'),
					'quests.is_complete',
				)
				->groupBy('quests.id_quest')
				->paginate(10);

		$list = $list->toArray();

		foreach ($list['data'] ?? [] as $key => $value) {
			$status = 'Not Started';

			if ($value['date_start'] < date('Y-m-d H:i:s') && $value['is_complete']){
				$status = 'Started';
			}
			elseif (!is_null($value['date_end']) && $value['date_end'] > date('Y-m-d H:i:s') && $value['is_complete']){
				$status = 'Ended';
			}

			$list['data'][$key]['status'] = $status;
		}

		$result = MyHelper::checkGet($list);

		return $result;
    }
}
