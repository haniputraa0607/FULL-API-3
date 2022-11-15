<?php

namespace Modules\Outlet\Http\Controllers;

use App\Jobs\SyncronPlasticTypeOutlet;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Outlet;
use App\Http\Models\OutletDoctor;
use App\Http\Models\OutletDoctorSchedule;
use App\Http\Models\OutletHoliday;
use App\Http\Models\UserOutletApp;
use App\Http\Models\Holiday;
use App\Http\Models\DateHoliday;
use App\Http\Models\OutletPhoto;
use App\Http\Models\City;
use App\Http\Models\User;
use App\Http\Models\UserOutlet;
use App\Http\Models\Configs;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Setting;
use App\Http\Models\OauthAccessToken;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use Modules\Outlet\Entities\OutletGroup;
use Modules\Outlet\Entities\OutletGroupFilterCondition;
use Modules\Outlet\Entities\OutletGroupFilterConditionParent;
use Modules\Outlet\Entities\OutletGroupFilterOutlet;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use  Modules\UserFranchise\Entities\UserFranchise;
use  Modules\Franchise\Entities\UserFranchiseOultet;
use Modules\Outlet\Entities\OutletScheduleUpdate;
use App\Imports\ExcelImport;
use App\Imports\FirstSheetOnlyImport;
use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;
use Excel;
use Storage;
use Modules\Brand\Entities\BrandOutlet;
use Modules\Brand\Entities\Brand;
use Modules\Outlet\Http\Requests\outlet\Upload;
use Modules\Outlet\Http\Requests\outlet\Update;
use Modules\Outlet\Http\Requests\outlet\UpdateStatus;
use Modules\Outlet\Http\Requests\outlet\UpdatePhoto;
use Modules\Outlet\Http\Requests\outlet\UploadPhoto;
use Modules\Outlet\Http\Requests\outlet\Create;
use Modules\Outlet\Http\Requests\outlet\Delete;
use Modules\Outlet\Http\Requests\outlet\DeletePhoto;
use Modules\Outlet\Http\Requests\outlet\Nearme;
use Modules\Outlet\Http\Requests\outlet\Filter;
use Modules\Outlet\Http\Requests\outlet\OutletList;
use Modules\Outlet\Http\Requests\outlet\OutletListOrderNow;
use Modules\Outlet\Http\Requests\UserOutlet\Create as CreateUserOutlet;
use Modules\Outlet\Http\Requests\UserOutlet\Update as UpdateUserOutlet;
use Modules\Outlet\Http\Requests\Holiday\HolidayStore;
use Modules\Outlet\Http\Requests\Holiday\HolidayEdit;
use Modules\Outlet\Http\Requests\Holiday\HolidayUpdate;
use Modules\Outlet\Http\Requests\Holiday\HolidayDelete;
use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\PromoCampaign\Lib\PromoCampaignTools;
use App\Http\Models\Transaction;
use App\Jobs\SendOutletJob;

class ApiOutletGroupFilterController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function list()
    {
        $data = OutletGroup::orderBy('updated_at', 'desc')->get()->toArray();
        return response()->json(MyHelper::checkGet($data));
    }

    public function store(Request $request)
    {
        $post = $request->json()->all();

        if (!isset($post['outlets']) && !isset($post['conditions'])) {
            return response()->json(['status' => 'fail', 'messages' => ['Data outlets or conditions can not be empty']]);
        } else {
            $isAllOutlet = 0;

            if (isset($post['outlets']) && in_array("all", $post['outlets'])) {
                $isAllOutlet = 1;
            }

            $dataOutletGroup = [
                'outlet_group_name' => $post['outlet_group_name'],
                'outlet_group_type' => $post['outlet_group_type'],
                'is_all_outlet' => $isAllOutlet
            ];

            DB::beginTransaction();
            $outletGroup = OutletGroup::create($dataOutletGroup);

            if (!$outletGroup) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed Create outlet group']]);
            }

            if ($post['outlet_group_type'] == 'Conditions') {
                foreach ($post['conditions'] as $con) {
                    $rule = $con['rule'];
                    $ruleNext = $con['rule_next'];
                    unset($con['rule']);
                    unset($con['rule_next']);

                    $dataRuleParent = [
                        'id_outlet_group' => $outletGroup['id_outlet_group'],
                        'condition_parent_rule' => $rule,
                        'condition_parent_rule_next' => $ruleNext
                    ];

                    $createConditionParent = OutletGroupFilterConditionParent::create($dataRuleParent);

                    if (!$createConditionParent) {
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Failed Create parent condition']]);
                    }

                    $dataFilter = [];
                    foreach ($con as $con_child) {
                        $dataFilter[] = [
                            'id_outlet_group_filter_condition_parent' => $createConditionParent['id_outlet_group_filter_condition_parent'],
                            'id_outlet_group' => $outletGroup['id_outlet_group'],
                            'outlet_group_filter_subject' => $con_child['subject'],
                            'outlet_group_filter_operator' => $con_child['operator'],
                            'outlet_group_filter_parameter' => $con_child['parameter'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }
                    $insertFilter = OutletGroupFilterCondition::insert($dataFilter);
                    if (!$insertFilter) {
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Failed save outlet group filter conditions']]);
                    }
                }
            } elseif ($isAllOutlet == 0) {
                $dataOutlet = [];
                foreach ($post['outlets'] as $outlet) {
                    $dataOutlet[] = [
                        'id_outlet_group' => $outletGroup['id_outlet_group'],
                        'id_outlet' => $outlet,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }

                $insertOutlet = OutletGroupFilterOutlet::insert($dataOutlet);
                if (!$insertOutlet) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed save outlet group filter outlets']]);
                }
            }

            DB::commit();
            return response()->json(['status' => 'success']);
        }
    }

    public function detail(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_outlet_group']) && !empty($post['id_outlet_group'])) {
            $detail = OutletGroup::where('id_outlet_group', $post['id_outlet_group'])
                        ->with(['outlet_group_filter_outlet'])
                        ->first();
            if (!empty($detail)) {
                $parents = OutletGroupFilterConditionParent::where('id_outlet_group', $post['id_outlet_group'])->get()->toArray();
                foreach ($parents as $key => $p) {
                    $parents[$key]['condition_child'] = OutletGroupFilterCondition::where('id_outlet_group_filter_condition_parent', $p['id_outlet_group_filter_condition_parent'])
                        ->select('outlet_group_filter_subject', 'outlet_group_filter_operator', 'outlet_group_filter_parameter')->get()->toArray();
                }
                $detail['conditions'] = $parents;
                $detail['outlets'] = $this->outletGroupFilter($post['id_outlet_group']);
            }

            return response()->json(MyHelper::checkGet($detail));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function update(Request $request)
    {
        $post = $request->json()->all();

        if (!isset($post['outlets']) && !isset($post['conditions'])) {
            return response()->json(['status' => 'fail', 'messages' => ['Data outlets or conditions can not be empty']]);
        } elseif (isset($post['id_outlet_group']) && !empty($post['id_outlet_group'])) {
            $isAllOutlet = 0;

            if (isset($post['outlets']) && in_array("all", $post['outlets'])) {
                $isAllOutlet = 1;
            }

            $dataOutletGroup = [
                'outlet_group_name' => $post['outlet_group_name'],
                'outlet_group_type' => $post['outlet_group_type'],
                'is_all_outlet' => $isAllOutlet
            ];

            DB::beginTransaction();
            $outletGroup = OutletGroup::where('id_outlet_group', $post['id_outlet_group'])->update($dataOutletGroup);

            if (!$outletGroup) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed Update outlet group']]);
            }

            OutletGroupFilterOutlet::where('id_outlet_group', $post['id_outlet_group'])->delete();
            OutletGroupFilterConditionParent::where('id_outlet_group', $post['id_outlet_group'])->delete();
            OutletGroupFilterCondition::where('id_outlet_group', $post['id_outlet_group'])->delete();

            if ($post['outlet_group_type'] == 'Conditions') {
                foreach ($post['conditions'] as $con) {
                    $rule = $con['rule'];
                    $ruleNext = $con['rule_next'];
                    unset($con['rule']);
                    unset($con['rule_next']);

                    $dataRuleParent = [
                        'id_outlet_group' => $post['id_outlet_group'],
                        'condition_parent_rule' => $rule,
                        'condition_parent_rule_next' => $ruleNext
                    ];

                    $createConditionParent = OutletGroupFilterConditionParent::create($dataRuleParent);

                    if (!$createConditionParent) {
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Failed Create parent condition']]);
                    }

                    $dataFilter = [];
                    foreach ($con as $con_child) {
                        $dataFilter[] = [
                            'id_outlet_group_filter_condition_parent' => $createConditionParent['id_outlet_group_filter_condition_parent'],
                            'id_outlet_group' => $post['id_outlet_group'],
                            'outlet_group_filter_subject' => $con_child['subject'],
                            'outlet_group_filter_operator' => $con_child['operator'],
                            'outlet_group_filter_parameter' => $con_child['parameter'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }
                    $insertFilter = OutletGroupFilterCondition::insert($dataFilter);
                    if (!$insertFilter) {
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Failed save outlet group filter conditions']]);
                    }
                }
            } elseif ($isAllOutlet == 0) {
                $dataOutlet = [];
                foreach ($post['outlets'] as $outlet) {
                    $dataOutlet[] = [
                        'id_outlet_group' => $post['id_outlet_group'],
                        'id_outlet' => $outlet,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }

                $insertOutlet = OutletGroupFilterOutlet::insert($dataOutlet);
                if (!$insertOutlet) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed save outlet group filter outlets']]);
                }
            }

            DB::commit();
            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function destroy(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['id_outlet_group']) && !empty($post['id_outlet_group'])) {
            $delete = OutletGroup::where('id_outlet_group', $post['id_outlet_group'])->delete();
            OutletGroupFilterOutlet::where('id_outlet_group', $post['id_outlet_group'])->delete();
            OutletGroupFilterConditionParent::where('id_outlet_group', $post['id_outlet_group'])->delete();
            OutletGroupFilterCondition::where('id_outlet_group', $post['id_outlet_group'])->delete();

            return response()->json(MyHelper::checkDelete($delete));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function outletGroupFilter($id_outlet_group)
    {
        if (!empty($id_outlet_group)) {
            $getOutletGroup = OutletGroup::where('id_outlet_group', $id_outlet_group)->first();
            if (empty($getOutletGroup)) {
                return [];
            }

            if ($getOutletGroup['outlet_group_type'] == 'Outlets') {
                if ($getOutletGroup['is_all_outlet'] == 1) {
                    $outlets = Outlet::join('cities', 'cities.id_city', '=', 'outlets.id_city')
                        ->join('provinces', 'provinces.id_province', '=', 'cities.id_province')
                        ->where('outlet_status', 'Active')
                        ->select('id_outlet', 'outlet_code', 'outlet_name')->get()->toArray();
                } else {
                    $arrIdOutlet = OutletGroupFilterOutlet::where('id_outlet_group', $id_outlet_group)->pluck('id_outlet')->toArray();
                    $outlets = Outlet::join('cities', 'cities.id_city', '=', 'outlets.id_city')
                        ->join('provinces', 'provinces.id_province', '=', 'cities.id_province')
                        ->whereIn('id_outlet', $arrIdOutlet)->where('outlet_status', 'Active')
                        ->select('id_outlet', 'outlet_code', 'outlet_name')->get()->toArray();
                }

                return $outlets;
            } else {
                $outlets = Outlet::select('id_outlet', 'outlet_code', 'outlet_name')
                    ->join('cities', 'cities.id_city', '=', 'outlets.id_city')
                    ->join('provinces', 'provinces.id_province', '=', 'cities.id_province')
                    ->where('outlet_status', 'Active');

                $conditionParents = OutletGroupFilterConditionParent::where('id_outlet_group', $id_outlet_group)->orderBy('id_outlet_group_filter_condition_parent', 'desc')->get()->toArray();

                $outlets->where(function ($subMaster) use ($conditionParents) {
                    foreach ($conditionParents as $conditionParent) {
                        $ruleNext = 'and';
                        if (isset($conditionParent['condition_parent_rule_next'])) {
                            $ruleNext = $conditionParent['condition_parent_rule_next'];
                        }

                        if ($ruleNext == 'and') {
                            $subMaster->where(function ($sub) use ($conditionParent) {
                                $conditions = OutletGroupFilterCondition::where('id_outlet_group_filter_condition_parent', $conditionParent['id_outlet_group_filter_condition_parent'])->get()->toArray();

                                $rule = 'and';
                                if (isset($conditionParent['condition_parent_rule'])) {
                                    $rule = $conditionParent['condition_parent_rule'];
                                }

                                if ($rule == 'and') {
                                    foreach ($conditions as $row) {
                                        if (isset($row['outlet_group_filter_subject'])) {
                                            if ($row['outlet_group_filter_subject'] == 'province') {
                                                $sub->where('provinces.id_province', $row['outlet_group_filter_operator']);
                                            }

                                            if ($row['outlet_group_filter_subject'] == 'city') {
                                                $sub->where('cities.id_city', $row['outlet_group_filter_operator']);
                                            }

                                            if ($row['outlet_group_filter_subject'] == 'status_franchise') {
                                                $sub->where('status_franchise', $row['outlet_group_filter_operator']);
                                            }

                                            if ($row['outlet_group_filter_subject'] == 'delivery_order') {
                                                $sub->where('delivery_order', $row['outlet_group_filter_operator']);
                                            }

                                            if ($row['outlet_group_filter_subject'] == 'outlet_code' || $row['outlet_group_filter_subject'] == 'outlet_name') {
                                                if ($row['outlet_group_filter_operator'] == '=') {
                                                    $sub->where($row['outlet_group_filter_subject'], $row['outlet_group_filter_parameter']);
                                                } else {
                                                    $sub->where($row['outlet_group_filter_subject'], 'like', '%' . $row['outlet_group_filter_parameter'] . '%');
                                                }
                                            }

                                            if ($row['outlet_group_filter_subject'] == 'brand') {
                                                $sub->whereIn('outlets.id_outlet', function ($query) use ($row) {
                                                    $query->select('id_outlet')
                                                        ->from('brand_outlet')
                                                        ->where('id_brand', $row['outlet_group_filter_operator']);
                                                });
                                            }
                                        }
                                    }
                                } else {
                                    $sub->where(function ($subquery) use ($conditions) {
                                        foreach ($conditions as $row) {
                                            if (isset($row['outlet_group_filter_subject'])) {
                                                if ($row['outlet_group_filter_subject'] == 'province') {
                                                    $subquery->orWhere('provinces.id_province', $row['outlet_group_filter_operator']);
                                                }

                                                if ($row['outlet_group_filter_subject'] == 'city') {
                                                    $subquery->orWhere('cities.id_city', $row['outlet_group_filter_operator']);
                                                }

                                                if ($row['outlet_group_filter_subject'] == 'status_franchise') {
                                                    $subquery->orWhere('status_franchise', $row['outlet_group_filter_operator']);
                                                }

                                                if ($row['outlet_group_filter_subject'] == 'delivery_order') {
                                                    $subquery->orWhere('delivery_order', $row['outlet_group_filter_operator']);
                                                }

                                                if ($row['outlet_group_filter_subject'] == 'outlet_code' || $row['outlet_group_filter_subject'] == 'outlet_name') {
                                                    if ($row['outlet_group_filter_operator'] == '=') {
                                                        $subquery->orWhere($row['outlet_group_filter_subject'], $row['outlet_group_filter_parameter']);
                                                    } else {
                                                        $subquery->orWhere($row['outlet_group_filter_subject'], 'like', '%' . $row['outlet_group_filter_parameter'] . '%');
                                                    }
                                                }

                                                if ($row['outlet_group_filter_subject'] == 'brand') {
                                                    $subquery->orWhereIn('outlets.id_outlet', function ($query) use ($row) {
                                                        $query->select('id_outlet')
                                                            ->from('brand_outlet')
                                                            ->where('id_brand', $row['outlet_group_filter_operator']);
                                                    });
                                                }
                                            }
                                        }
                                    });
                                }
                            });
                        } else {
                            $subMaster->orWhere(function ($sub) use ($conditionParent) {
                                $conditions = OutletGroupFilterCondition::where('id_outlet_group_filter_condition_parent', $conditionParent['id_outlet_group_filter_condition_parent'])->get()->toArray();

                                $rule = 'and';
                                if (isset($conditionParent['condition_parent_rule'])) {
                                    $rule = $conditionParent['condition_parent_rule'];
                                }

                                if ($rule == 'and') {
                                    foreach ($conditions as $row) {
                                        if (isset($row['outlet_group_filter_subject'])) {
                                            if ($row['outlet_group_filter_subject'] == 'province') {
                                                $sub->where('provinces.id_province', $row['outlet_group_filter_operator']);
                                            }

                                            if ($row['outlet_group_filter_subject'] == 'city') {
                                                $sub->where('cities.id_city', $row['outlet_group_filter_operator']);
                                            }

                                            if ($row['outlet_group_filter_subject'] == 'status_franchise') {
                                                $sub->where('status_franchise', $row['outlet_group_filter_operator']);
                                            }

                                            if ($row['outlet_group_filter_subject'] == 'delivery_order') {
                                                $sub->where('delivery_order', $row['outlet_group_filter_operator']);
                                            }

                                            if ($row['outlet_group_filter_subject'] == 'outlet_code' || $row['outlet_group_filter_subject'] == 'outlet_name') {
                                                if ($row['outlet_group_filter_operator'] == '=') {
                                                    $sub->where($row['outlet_group_filter_subject'], $row['outlet_group_filter_parameter']);
                                                } else {
                                                    $sub->where($row['outlet_group_filter_subject'], 'like', '%' . $row['outlet_group_filter_parameter'] . '%');
                                                }
                                            }

                                            if ($row['outlet_group_filter_subject'] == 'brand') {
                                                $sub->whereIn('outlets.id_outlet', function ($query) use ($row) {
                                                    $query->select('id_outlet')
                                                        ->from('brand_outlet')
                                                        ->where('id_brand', $row['outlet_group_filter_operator']);
                                                });
                                            }
                                        }
                                    }
                                } else {
                                    $sub->where(function ($subquery) use ($conditions) {
                                        foreach ($conditions as $row) {
                                            if (isset($row['outlet_group_filter_subject'])) {
                                                if ($row['outlet_group_filter_subject'] == 'province') {
                                                    $subquery->orWhere('provinces.id_province', $row['outlet_group_filter_operator']);
                                                }

                                                if ($row['outlet_group_filter_subject'] == 'city') {
                                                    $subquery->orWhere('cities.id_city', $row['outlet_group_filter_operator']);
                                                }

                                                if ($row['outlet_group_filter_subject'] == 'status_franchise') {
                                                    $subquery->orWhere('status_franchise', $row['outlet_group_filter_operator']);
                                                }

                                                if ($row['outlet_group_filter_subject'] == 'delivery_order') {
                                                    $subquery->orWhere('delivery_order', $row['outlet_group_filter_operator']);
                                                }

                                                if ($row['outlet_group_filter_subject'] == 'outlet_code' || $row['outlet_group_filter_subject'] == 'outlet_name') {
                                                    if ($row['outlet_group_filter_operator'] == '=') {
                                                        $subquery->orWhere($row['outlet_group_filter_subject'], $row['outlet_group_filter_parameter']);
                                                    } else {
                                                        $subquery->orWhere($row['outlet_group_filter_subject'], 'like', '%' . $row['outlet_group_filter_parameter'] . '%');
                                                    }
                                                }

                                                if ($row['outlet_group_filter_subject'] == 'brand') {
                                                    $subquery->orWhereIn('outlets.id_outlet', function ($query) use ($row) {
                                                        $query->select('id_outlet')
                                                            ->from('brand_outlet')
                                                            ->where('id_brand', $row['outlet_group_filter_operator']);
                                                    });
                                                }
                                            }
                                        }
                                    });
                                }
                            });
                        }
                    }
                });

                return $outlets->get()->toArray();
            }
        } else {
            return [];
        }
    }
}
