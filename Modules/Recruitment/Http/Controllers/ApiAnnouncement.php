<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\HairstylistAnnouncement;
use Modules\Recruitment\Entities\HairstylistAnnouncementRule;
use Modules\Recruitment\Entities\HairstylistAnnouncementRuleParent;
use DB;

class ApiAnnouncement extends Controller
{
	function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function createAnnouncement(Request $request){
		$post = $request->json()->all(); 
		
		$data['content'] 		= $post['announcement_subject'];
		
		if(!empty($post['announcement_date_start'])){
			$datetimearr 				= explode(' - ',$post['announcement_date_start']);
			$datearr 					= explode(' ',$datetimearr[0]);
			$date 						= date("Y-m-d", strtotime($datearr[2].", ".$datearr[1]." ".$datearr[0]));
			$data['date_start'] 	= $date." ".$datetimearr[1].":00";
		} else $data['date_start'] = null;
		
		if(!empty($post['announcement_date_end'])){
			$datetimearr 				= explode(' - ',$post['announcement_date_end']);
			$datearr 					= explode(' ',$datetimearr[0]);
			$date 						= date("Y-m-d", strtotime($datearr[2].", ".$datearr[1]." ".$datearr[0]));
			$data['date_end'] 	= $date." ".$datetimearr[1].":00";
		} else $data['date_end'] = null;

		DB::beginTransaction();
		if (isset($post['id_hairstylist_announcement'])) {
			$queryAnn = HairstylistAnnouncement::where('id_hairstylist_announcement', $post['id_hairstylist_announcement'])->first();
			$queryAnn->update($data);
		} else {
			$queryAnn = HairstylistAnnouncement::create($data);
		}
		if($queryAnn){
			$data = [];
			$data['id_hairstylist_announcement'] = $queryAnn->id_hairstylist_announcement;

			$queryAnnRule = $this->insertCondition('hairstylist_announcement', $data['id_hairstylist_announcement'], $post['rule'], $post['operator']);
			if(isset($queryAnnRule['status']) && $queryAnnRule['status'] == 'success'){
				$resultrule = $queryAnnRule['data'];
			}else{
				DB::rollBack();
				$result = [
					'status'  => 'fail',
					'messages'  => ['Create Announcement Failed']
				];
			}
			$result = [
				'status'  => 'success',
				'result'  => 'Set Announcement & Rule Success',
				'announcement'  => $queryAnn,
				'rule'  => $resultrule
			];
			DB::commit();
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['Create Announcement Failed']
				];
		}
		
		return response()->json($result);
	}

	public static function insertCondition($type, $id, $conditions, $rule){
		if($type == 'hairstylist_announcement'){
			$deleteRuleParent = HairstylistAnnouncementRuleParent::where('id_'.$type, $id)->get();
			if(count($deleteRuleParent)>0){
				foreach ($deleteRuleParent as $key => $value) {
					$delete = HairstylistAnnouncementRule::where('id_'.$type.'_rule_parent', $value['id_'.$type.'_rule_parent'])->delete();
				}
				$deleteRuleParent = HairstylistAnnouncementRuleParent::where('id_'.$type, $id)->delete();
			}
		}

		$operatorexception = [
			'id_brand',
			'id_province',
			'id_city',
			'id_outlet',
			'hairstylist_level'
		];

		$data_rule = array();

		$dataRuleParent['id_'.$type] = $id;
		$dataRuleParent[$type.'_rule'] = $ruleParent['rule'] ?? $rule;
		$dataRuleParent[$type.'_rule_next'] = $ruleParent['rule_next'] ?? 'and';

		if ($type == 'hairstylist_announcement') {
			$createRuleParent = HairstylistAnnouncementRuleParent::create($dataRuleParent);
		}

		if(!$createRuleParent){
			return ['status' => 'fail'];
		}

		foreach ($conditions as $i => $row) {
			$condition['id_'.$type.'_rule_parent'] = $createRuleParent['id_'.$type.'_rule_parent'];
			$condition[$type.'_rule_subject'] = $row['subject'];

			if ($row['subject'] == 'all_data') {
				$condition[$type.'_rule_operator'] = "";
            } elseif (in_array($row['subject'], $operatorexception)) {
				$condition[$type.'_rule_operator'] = '=';
			} else {
				$condition[$type.'_rule_operator'] = $row['operator'];
			}

            $condition[$type.'_rule_param_id'] = NULL;
			if ($row['subject'] == 'all_data') {
				$condition[$type.'_rule_param'] = "";
			} else {
				$condition[$type.'_rule_param'] = $row['parameter'];
			}

			$condition['created_at'] =  date('Y-m-d H:i:s');
			$condition['updated_at'] =  date('Y-m-d H:i:s');

			array_push($data_rule, $condition);
		}

		if ($type == 'hairstylist_announcement') {
			$insert = HairstylistAnnouncementRule::insert($data_rule);
		}

		if($insert){
			return ['status' => 'success', 'data' =>  $data_rule];
		}else{
			return ['status' => 'fail'];
		}
	}

	public function listAnnouncement(Request $request)
	{
        $post = $request->json()->all();
        $ann = HairstylistAnnouncement::with('hairstylist_announcement_rule_parents.rules');

        if (!empty($post['date_start']) && !empty($post['date_end'])) {
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $ann->whereDate('date_start', '>=', $start_date)->whereDate('date_end', '<=', $end_date);
        }

        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $rule = 'and';
            if (isset($post['rule'])) {
                $rule = $post['rule'];
            }

            if ($rule == 'and') {
                foreach ($post['conditions'] as $row) {
                    if (isset($row['subject'])) {
                        if ($row['subject'] == 'content') {
                            if ($row['operator'] == '=') {
                                $ann->where('content', $row['parameter']);
                            } else {
                                $ann->where('content', 'like', '%'.$row['parameter'].'%');
                            }
                        }
                    }
                }
            } else {
            	$ann->where(function ($subquery) use ($post) {
            		foreach ($post['conditions'] as $row) {
            			if (isset($row['subject'])) {
            				if ($row['subject'] == 'content') {
            					if ($row['operator'] == '=') {
            						$subquery->orWhere('content', $row['parameter']);
            					} else {
            						$subquery->orWhere('content', 'like', '%'.$row['parameter'].'%');
            					}
            				}
            			}
                    }
                });
            }
        }

        $ann = $ann->paginate(10);

        return response()->json(MyHelper::checkGet($ann));
    }

    public function detailAnnouncement(Request $request){
		$post = $request->json()->all(); 
	
		$ann = HairstylistAnnouncement::with('hairstylist_announcement_rule_parents.rules')
				->where('id_hairstylist_announcement', $post['id_hairstylist_announcement'])
				->first();
		
		if(isset($ann) && !empty($ann)) {
			$result = [
					'status'  => 'success',
					'result'  => $ann
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['No Announcement']
				];
		}
		return response()->json($result);
	}

	public function deleteAnnouncement(Request $request){
		$post = $request->json()->all();

		$checkAnn = HairstylistAnnouncement::where('id_hairstylist_announcement','=',$post['id_hairstylist_announcement'])->first();
		if($checkAnn){
			$delete = HairstylistAnnouncement::where('id_hairstylist_announcement','=',$post['id_hairstylist_announcement'])->delete();
			
			if($delete){
				$result = ['status'	=> 'success',
						   'result'	=> ['Announcement has been deleted']
						  ];
			} else {
				$result = [
						'status'	=> 'fail',
						'messages'	=> ['Delete Failed']
						];
			}
		} else {
			$result = [
						'status'	=> 'fail',
						'messages'	=> ['Announcement Not Found']
						];
		}
		return response()->json($result);
	}

	function hairstylistFilter($conditions = null, $order_field = 'id_user_hair_stylist', $order_method = 'asc', $skip = 0, $take = 99999999999, $keyword = null, $columns = null, $objOnly = false)
    {
    	
        $prevResult = [];
        $finalResult = [];
        $status_all_user = 0;

        $key = 0;
        foreach ($conditions as $key => $cond) {
        	$query = UserHairStylist::leftJoin('outlets', 'outlets.id_outlet', 'user_hair_stylist.id_outlet')
        			->leftJoin('brand_outlet', 'brand_outlet.id_outlet', 'outlets.id_outlet')
            		->leftJoin('cities', 'cities.id_city', 'outlets.id_city')
                    ->leftJoin('provinces', 'provinces.id_province', 'cities.id_province')
                    ->where('user_hair_stylist.user_hair_stylist_status','Active')
                    ->orderBy($order_field, $order_method);

            if ($cond != null) {

                $rule = $cond['rule'];
                unset($cond['rule']);

                $conRuleNext = $cond['rule_next'];
                unset($cond['rule_next']);

                if (isset($cond['rules'])) {
                    $cond = $cond['rules'];
                }

                /*========= Check conditions related to the subject of the transaction =========*/
                $countTrxDate = 0;
                $arr_tmp_product = [];
                $arr_tmp_outlet = [];
                foreach ($cond as $i => $condition) {
                    if($condition['subject'] == 'all_user'){
                        $status_all_user = 1;
                        break 2;
                    }
                }
                /*================================== END check ==================================*/
                $query = $this->queryFilter($cond, $rule, $query);
            }

            $result = array_pluck($query->get()->toArray(), 'id_user_hair_stylist');

            if ($key > 0) {
                if ($ruleNext == 'and') {
                    $prevResult = array_intersect($result, $prevResult);
                } else {
                    $prevResult = array_unique(array_merge($result, $prevResult));
                }
                $ruleNext = $conRuleNext;
            } else {
                $prevResult = $result;
                $ruleNext = $conRuleNext;
            }

            $key++;
        }
        /*============= Final query when condition not null =============*/
        $finalResult = UserHairStylist::leftJoin('outlets', 'outlets.id_outlet', 'user_hair_stylist.id_outlet')
        			->leftJoin('brand_outlet', 'brand_outlet.id_outlet', 'outlets.id_outlet')
            		->leftJoin('cities', 'cities.id_city', 'outlets.id_city')
                    ->leftJoin('provinces', 'provinces.id_province', 'cities.id_province')
                    ->where('user_hair_stylist.user_hair_stylist_status','Active')
                    ->orderBy($order_field, $order_method)
            		->whereIn('user_hair_stylist.id_user_hair_stylist', $prevResult);

        $resultCount = $finalResult->count();
        if ($columns) {
            foreach ($columns as $in=>$c){
                if($c == 'email' || $c == 'nickname' || $c == 'phone_number'){
                    $columns[$in] = 'user_hair_stylist.'.$c;
                }
            }
            $finalResult->select($columns);
        }

        if ($objOnly) {
            return $finalResult;
        }

        $result = $finalResult->skip($skip)->take($take)->get()->toArray();
        if ($result) {
            $response = [
                'status'    => 'success',
                'result'    => $result,
                'total' => $resultCount
            ];
        } else {
            $response = [
                'status'    => 'fail',
                'messages'    => ['hairstylist Not Found']
            ];
        }

        return $response;
    }

    function queryFilter($conditions, $rule, $query)
    {
        foreach ($conditions as $index => $condition) {
        	if (empty($condition['subject'])) {
        		continue;
        	}

            if ($condition['operator'] != '=') {
                $conditionParameter = $condition['operator'];
            }

            /*============= All query with rule 'AND' ==================*/
            if ($rule == 'and') {
            	if (in_array($condition['subject'], ['id_brand', 'id_outlet','id_province','id_city','hairstylist_level'])) {
            		switch ($condition['subject']) {
            			case 'id_brand':
                    		$var = "brand_outlet.id_brand";
            				break;

        				case 'id_province':
                    		$var = "provinces.id_province";
            				break;

            			case 'id_city':
                    		$var = "cities.id_city";
            				break;

        				case 'hairstylist_level':
                    		$var = "user_hair_stylist.level";
            				break;

            			case 'id_outlet':
                    		$var = "outlets.id_outlet";
            				break;
            			
            			default:
            				continue 2;
            				break;
            		}

                    $query = $query->where($var, '=', $condition['parameter']);
                } elseif (in_array($condition['subject'], ['phone_number'])) {
                    $var = "user_hair_stylist." . $condition['subject'];

                    if ($condition['operator'] == 'like')
                        $query = $query->where($var, 'like', '%' . $condition['parameter'] . '%');
                    elseif (strtoupper($condition['operator']) == 'WHERE IN')
                        $query = $query->whereIn($var, explode(',', $condition['parameter']));
                    else
                        $query = $query->where($var, '=', $condition['parameter']);
                }

            }
            /*====================== End IF ============================*/

            /*============= All query with rule 'OR' ==================*/
            else {
            	if (in_array($condition['subject'], ['id_brand', 'id_outlet','id_province','id_city','hairstylist_level'])) {
            		switch ($condition['subject']) {
            			case 'id_brand':
                    		$var = "brand_outlet.id_brand";
            				break;

        				case 'id_province':
                    		$var = "provinces.id_province";
            				break;

            			case 'id_city':
                    		$var = "cities.id_city";
            				break;

        				case 'hairstylist_level':
                    		$var = "user_hair_stylist.level";
            				break;

            			case 'id_outlet':
                    		$var = "outlets.id_outlet";
            				break;
            			
            			default:
            				continue 2;
            				break;
            		}

                    $query = $query->orWhere($var, '=', $condition['parameter']);
                } elseif (in_array($condition['subject'], ['phone_number'])) {
	                $var = "user_hair_stylist." . $condition['subject'];

	                if ($condition['operator'] == 'like')
	                    $query = $query->orWhere($var, 'like', '%' . $condition['parameter'] . '%');
	                elseif (strtoupper($condition['operator']) == 'WHERE IN')
	                    $query = $query->orWhereIn($var, explode(',', $condition['parameter']));
	                else
	                    $query = $query->orWhere($var, '=', $condition['parameter']);
	            }
            } 
            /*====================== End ELSE ============================*/
        }

        return $query;
    }
}
