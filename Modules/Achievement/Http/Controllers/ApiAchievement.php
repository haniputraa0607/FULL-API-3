<?php

namespace Modules\Achievement\Http\Controllers;

use App\Http\Models\Membership;
use App\Http\Models\Transaction;
use App\Http\Models\User;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Achievement\Entities\AchievementCategory;
use Modules\Achievement\Entities\AchievementDetail;
use Modules\Achievement\Entities\AchievementGroup;
use Modules\Achievement\Entities\AchievementOutletLog;
use Modules\Achievement\Entities\AchievementProductLog;
use Modules\Achievement\Entities\AchievementProgress;
use Modules\Achievement\Entities\AchievementProvinceLog;
use Modules\Achievement\Entities\AchievementUser;
use Modules\Achievement\Entities\AchievementUserLog;

class ApiAchievement extends Controller
{
    public $saveImage = "img/achievement/";
    public $saveImageDetail = "img/achievement/detail/";

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $data = AchievementGroup::select('achievement_groups.id_achievement_group', 'achievement_categories.name as category_name', 'achievement_groups.name', 'date_start', 'date_end', 'publish_start', 'publish_end')->leftJoin('achievement_categories', 'achievement_groups.id_achievement_category', '=', 'achievement_categories.id_achievement_category');
        if ($request->post('keyword')) {
            $data->where('achievement_groups.name', 'like', "%{$request->post('keyword')}%");
        }
        return MyHelper::checkGet($data->paginate());
    }

    public function reportUser(Request $request)
    {
        $data = User::select('users.id', 'users.name', 'users.phone', 'memberships.membership_name', DB::raw('COALESCE((
            SELECT COUNT(*) FROM achievement_users
            JOIN achievement_details ON achievement_users.id_achievement_detail = achievement_details.id_achievement_detail
            JOIN achievement_groups ON achievement_details.id_achievement_group = achievement_groups.id_achievement_group
            WHERE achievement_users.id_user = users.id
            GROUP BY achievement_groups.id_achievement_group), 0 ) AS total'))
            ->join('memberships', 'users.id_membership', 'memberships.id_membership');
        if (!is_null($request->post('user_filter'))) {
            switch ($request->post('filter_by')) {
                case 'phone':
                    $data->where('users.phone', 'like', "%{$request->post('user_filter')}%");
                    break;
                case 'email':
                    $data->where('users.email', 'like', "%{$request->post('user_filter')}%");
                    break;
                case 'name':
                    $data->where('users.name', 'like', "%{$request->post('user_filter')}%");
                    break;
            }
        }
        return MyHelper::checkGet($data->paginate());
    }

    public function reportAch(Request $request)
    {
        $data = AchievementGroup::select('achievement_groups.id_achievement_group', 'achievement_categories.name as category_name', 'achievement_groups.name', 'date_start', 'date_end', DB::raw('COALESCE((
            SELECT COUNT(*) from achievement_user_logs
            JOIN achievement_details ON achievement_user_logs.id_achievement_detail = achievement_details.id_achievement_detail
            WHERE achievement_details.id_achievement_group = achievement_groups.id_achievement_group
            GROUP BY achievement_details.id_achievement_group
        ), 0 ) AS total_user'))->leftJoin('achievement_categories', 'achievement_groups.id_achievement_category', '=', 'achievement_categories.id_achievement_category');

        if (!is_null($request->post('ach_filter'))) {
            switch ($request->post('filter_by')) {
                case 'name':
                    $data->where('achievement_groups.name', 'like', "%{$request->post('ach_filter')}%");
                    break;
                case 'email':
                    $data->where('users.email', 'like', "%{$request->post('ach_filter')}%");
                    break;
            }
        }
        return MyHelper::checkGet($data->paginate());
    }

    public function reportMembership(Request $request)
    {
        $data = Membership::select('memberships.*', DB::raw('COALESCE((
            SELECT COUNT(*) from users_memberships
            WHERE users_memberships.id_membership = memberships.id_membership
        ), 0 ) AS total_user'));
        if ($request->post('keyword')) {
            $data->where('memberships.name', 'like', "%{$request->post('keyword')}%");
        }
        return MyHelper::checkGet($data->paginate());
    }

    public function category(Request $request)
    {
        return [
            'status' => 'success',
            'data' => AchievementCategory::get()->toArray(),
        ];
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create(Request $request)
    {
        $post = $request->json()->all();

        if (!file_exists($this->saveImage)) {
            mkdir($this->saveImage, 0777, true);
        }
        if (!file_exists($this->saveImageDetail)) {
            mkdir($this->saveImageDetail, 0777, true);
        }

        DB::beginTransaction();

        if (isset($request['id_achievement_group'])) {
            $request->validate([
                'detail.*.name' => 'required',
                'detail.*.logo_badge' => 'required',
            ]);
        } else {
            $request->validate([
                'category.name' => 'required',
                'group.name' => 'required',
                'group.publish_start' => 'required',
                'group.date_start' => 'required',
                'group.description' => 'required',
                'rule_total' => 'required',
                'group.logo_badge_default' => 'required',
                'detail.*.name' => 'required',
                'detail.*.logo_badge' => 'required',
            ]);

            $upload = MyHelper::uploadPhotoStrict($post['group']['logo_badge_default'], $this->saveImage, 500, 500);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $post['group']['logo_badge_default'] = $upload['path'];
            } else {
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Failed to upload image'],
                ]);
            }

            try {
                $category = AchievementCategory::where('name', $post['category']['name']);
                if ($category->exists()) {
                    $post['group']['id_achievement_category'] = $category->first()->id_achievement_category;
                } else {
                    $post['group']['id_achievement_category'] = AchievementCategory::create($post['category'])->id_achievement_category;
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Get or Add Category Achievement Failed',
                    'error' => $e->getMessage(),
                ]);
            }

            $post['group']['publish_start'] = date('Y-m-d H:i', strtotime($post['group']['publish_start']));
            $post['group']['date_start'] = date('Y-m-d H:i', strtotime($post['group']['date_start']));
            if (!is_null($post['group']['publish_end'])) {
                $post['group']['publish_end'] = date('Y-m-d H:i', strtotime($post['group']['publish_end']));
            }
            if (!is_null($post['group']['date_end'])) {
                $post['group']['date_end'] = date('Y-m-d H:i', strtotime($post['group']['date_end']));
            }

            try {
                $group = AchievementGroup::create($post['group']);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Add Achievement Group Failed',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (isset($post['detail'])) {
            try {
                foreach ($post['detail'] as $key => $value) {
                    $uploadDetail = MyHelper::uploadPhotoStrict($value['logo_badge'], $this->saveImageDetail, 500, 500);

                    if (isset($uploadDetail['status']) && $uploadDetail['status'] == "success") {
                        $value['logo_badge'] = $uploadDetail['path'];
                    } else {
                        return response()->json([
                            'status' => 'fail',
                            'messages' => ['Failed to upload image'],
                        ]);
                    }

                    if (isset($request['id_achievement_group'])) {
                        $value['id_achievement_group'] = MyHelper::decSlug($request['id_achievement_group']);
                    } else {
                        $value['id_achievement_group'] = MyHelper::decSlug($group->id_achievement_group);
                    }

                    switch ($post['rule_total']) {
                        case 'nominal_transaction':
                            $value['trx_nominal'] = $value['value_total'];
                            $value['id_product'] = $post['rule']['id_product'];
                            $value['product_total'] = $post['rule']['product_total'];
                            $value['id_outlet'] = $post['rule']['id_outlet'];
                            $value['id_province'] = $post['rule']['id_province'];
                            unset($value['value_total']);
                            break;
                        case 'total_product':
                            $value['product_total'] = $value['value_total'];
                            $value['trx_nominal'] = $post['rule']['trx_nominal'];
                            $value['id_product'] = $post['rule']['id_product'];
                            $value['id_outlet'] = $post['rule']['id_outlet'];
                            $value['id_province'] = $post['rule']['id_province'];
                            unset($value['value_total']);
                            break;
                        case 'total_transaction':
                            $value['trx_total'] = $value['value_total'];
                            $value['trx_nominal'] = $post['rule']['trx_nominal'];
                            $value['id_product'] = $post['rule']['id_product'];
                            $value['product_total'] = $post['rule']['product_total'];
                            $value['id_outlet'] = $post['rule']['id_outlet'];
                            $value['id_province'] = $post['rule']['id_province'];
                            unset($value['value_total']);
                            break;
                        case 'total_outlet':
                            $value['different_outlet'] = $value['value_total'];
                            $value['trx_nominal'] = $post['rule']['trx_nominal'];
                            $value['id_product'] = $post['rule']['id_product'];
                            $value['product_total'] = $post['rule']['product_total'];
                            $value['id_outlet'] = $post['rule']['id_outlet'];
                            $value['id_province'] = $post['rule']['id_province'];
                            unset($value['value_total']);
                            break;
                        case 'total_province':
                            $value['different_province'] = $value['value_total'];
                            $value['trx_nominal'] = $post['rule']['trx_nominal'];
                            $value['id_product'] = $post['rule']['id_product'];
                            $value['product_total'] = $post['rule']['product_total'];
                            $value['id_outlet'] = $post['rule']['id_outlet'];
                            $value['id_province'] = $post['rule']['id_province'];
                            unset($value['value_total']);
                            break;
                    }
                    
                    $achievementDetail[$key] = AchievementDetail::create($value);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Add Achievement Detail Failed',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (isset($post['group']) && date('Y-m-d H:i', strtotime($post['group']['date_start'])) <= date('Y-m-d H:i')) {
            $getUser = User::select('id')->get()->toArray();
            foreach ($getUser as $key => $value) {
                self::checkAchievement($value['id'], $achievementDetail, $post['rule_total']);
            }
        }

        DB::commit();

        if (isset($request['id_achievement_group'])) {
            return response()->json([
                'status' => 'success',
                'message' => 'Add Achievement Success',
                'data' => $request['id_achievement_group'],
            ]);
        } else {
            return response()->json([
                'status' => 'success',
                'message' => 'Add Achievement Success',
                'data' => $group->id_achievement_group,
            ]);
        }
    }

    public static function checkAchievement($idUser, $detailAchievement, $rules)
    {
        $achievementPassed = 0;
        $achievement = null;
        foreach ($detailAchievement as $keyAch => $achievement) {
            $getTrxUser = Transaction::with('outlet.city.province', 'productTransaction')->where(['transactions.id_user' => $idUser, 'transactions.transaction_payment_status' => 'Completed'])->get()->toArray();

            if ($achievementPassed == $keyAch) {
                $totalTrx = 0;
                $totalOutlet = [];
                $totalProvince = [];
                $totalSumProduct = 0;
                $totalSumTrx = 0;
                foreach ($getTrxUser as $user) {
                    $trxProductStatus = false;
                    $trxTotalProductStatus = false;
                    if (!is_null($achievement['id_product']) || !is_null($achievement['product_total'])) {
                        foreach ($user['product_transaction'] as $product) {
                            if (!is_null($achievement['id_product'])) {
                                if ((int) $achievement['id_product'] == $product['id_product']) {
                                    $trxProductStatus = true;
                                    if (!is_null($achievement['product_total']) && $rules != 'total_product') {
                                        if ((int) $achievement['product_total'] <= $product['transaction_product_qty']) {
                                            AchievementProductLog::updateOrCreate([
                                                'id_achievement_group' => $achievement['id_achievement_group'],
                                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                                'id_user' => $idUser,
                                                'id_product' => $product['id_product'],
                                                'product_total' => $achievement['transaction_product_qty'],
                                                'id_transaction' => $user['id_transaction'],
                                            ], [
                                                'id_achievement_group' => $achievement['id_achievement_group'],
                                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                                'id_user' => $idUser,
                                                'id_product' => $product['id_product'],
                                                'product_total' => $achievement['transaction_product_qty'],
                                                'id_transaction' => $user['id_transaction'],
                                                'json_rule' => json_encode([
                                                    'id_product' => $achievement['id_product'],
                                                    'product_total' => $achievement['product_total'],
                                                    'trx_nominal' => $achievement['trx_nominal'],
                                                    'trx_total' => $achievement['trx_total'],
                                                    'id_outlet' => $achievement['id_outlet'],
                                                    'different_outlet' => $achievement['different_outlet'],
                                                    'id_province' => $achievement['id_province'],
                                                    'different_province' => $achievement['different_province'],
                                                ]),
                                                'json_rule_enc' => MyHelper::encrypt2019(json_encode([
                                                    'id_product' => $achievement['id_product'],
                                                    'product_total' => $achievement['product_total'],
                                                    'trx_nominal' => $achievement['trx_nominal'],
                                                    'trx_total' => $achievement['trx_total'],
                                                    'id_outlet' => $achievement['id_outlet'],
                                                    'different_outlet' => $achievement['different_outlet'],
                                                    'id_province' => $achievement['id_province'],
                                                    'different_province' => $achievement['different_province'],
                                                ])),
                                                'date' => date('Y-m-d H:i:s'),
                                            ]);
                                            $trxTotalProductStatus = true;
                                            break;
                                        } else {
                                            $trxTotalProductStatus = false;
                                            break;
                                        }
                                    } else {
                                        AchievementProductLog::updateOrCreate([
                                            'id_achievement_group' => $achievement['id_achievement_group'],
                                            'id_achievement_detail' => $achievement['id_achievement_detail'],
                                            'id_user' => $idUser,
                                            'id_product' => $product['id_product'],
                                            'id_transaction' => $user['id_transaction'],
                                        ], [
                                            'id_achievement_group' => $achievement['id_achievement_group'],
                                            'id_achievement_detail' => $achievement['id_achievement_detail'],
                                            'id_user' => $idUser,
                                            'id_product' => $product['id_product'],
                                            'id_transaction' => $user['id_transaction'],
                                            'json_rule' => json_encode([
                                                'id_product' => $achievement['id_product'],
                                                'product_total' => $achievement['product_total'],
                                                'trx_nominal' => $achievement['trx_nominal'],
                                                'trx_total' => $achievement['trx_total'],
                                                'id_outlet' => $achievement['id_outlet'],
                                                'different_outlet' => $achievement['different_outlet'],
                                                'id_province' => $achievement['id_province'],
                                                'different_province' => $achievement['different_province'],
                                            ]),
                                            'json_rule_enc' => MyHelper::encrypt2019(json_encode([
                                                'id_product' => $achievement['id_product'],
                                                'product_total' => $achievement['product_total'],
                                                'trx_nominal' => $achievement['trx_nominal'],
                                                'trx_total' => $achievement['trx_total'],
                                                'id_outlet' => $achievement['id_outlet'],
                                                'different_outlet' => $achievement['different_outlet'],
                                                'id_province' => $achievement['id_province'],
                                                'different_province' => $achievement['different_province'],
                                            ])),
                                            'date' => date('Y-m-d H:i:s'),
                                        ]);
                                        $totalSumProduct = $totalSumProduct + $product['transaction_product_qty'];
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
                    if (!is_null($achievement['id_outlet'])) {
                        if ((int) $achievement['id_outlet'] == $user['id_outlet']) {
                            AchievementOutletLog::updateOrCreate([
                                'id_achievement_group' => $achievement['id_achievement_group'],
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                                'id_outlet' => $user['id_outlet'],
                                'id_transaction' => $user['id_transaction'],
                            ], [
                                'id_achievement_group' => $achievement['id_achievement_group'],
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                                'id_outlet' => $user['id_outlet'],
                                'id_transaction' => $user['id_transaction'],
                                'json_rule' => json_encode([
                                    'id_product' => $achievement['id_product'],
                                    'product_total' => $achievement['product_total'],
                                    'trx_nominal' => $achievement['trx_nominal'],
                                    'trx_total' => $achievement['trx_total'],
                                    'id_outlet' => $achievement['id_outlet'],
                                    'different_outlet' => $achievement['different_outlet'],
                                    'id_province' => $achievement['id_province'],
                                    'different_province' => $achievement['different_province'],
                                ]),
                                'json_rule_enc' => MyHelper::encrypt2019(json_encode([
                                    'id_product' => $achievement['id_product'],
                                    'product_total' => $achievement['product_total'],
                                    'trx_nominal' => $achievement['trx_nominal'],
                                    'trx_total' => $achievement['trx_total'],
                                    'id_outlet' => $achievement['id_outlet'],
                                    'different_outlet' => $achievement['different_outlet'],
                                    'id_province' => $achievement['id_province'],
                                    'different_province' => $achievement['different_province'],
                                ])),
                                'date' => date('Y-m-d H:i:s'),
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
                    if (!is_null($achievement['id_province'])) {
                        if ((int) $achievement['id_province'] == $user['outlet']['city']['province']['id_province']) {
                            AchievementProvinceLog::updateOrCreate([
                                'id_achievement_group' => $achievement['id_achievement_group'],
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                                'id_transaction' => $user['id_transaction'],
                                'id_province' => $user['outlet']['city']['province']['id_province'],
                            ], [
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                                'id_transaction' => $user['id_transaction'],
                                'id_province' => $user['outlet']['city']['province']['id_province'],
                                'json_rule' => json_encode([
                                    'id_product' => $achievement['id_product'],
                                    'product_total' => $achievement['product_total'],
                                    'trx_nominal' => $achievement['trx_nominal'],
                                    'trx_total' => $achievement['trx_total'],
                                    'id_outlet' => $achievement['id_outlet'],
                                    'different_outlet' => $achievement['different_outlet'],
                                    'id_province' => $achievement['id_province'],
                                    'different_province' => $achievement['different_province'],
                                ]),
                                'json_rule_enc' => MyHelper::encrypt2019(json_encode([
                                    'id_product' => $achievement['id_product'],
                                    'product_total' => $achievement['product_total'],
                                    'trx_nominal' => $achievement['trx_nominal'],
                                    'trx_total' => $achievement['trx_total'],
                                    'id_outlet' => $achievement['id_outlet'],
                                    'different_outlet' => $achievement['different_outlet'],
                                    'id_province' => $achievement['id_province'],
                                    'different_province' => $achievement['different_province'],
                                ])),
                                'date' => date('Y-m-d H:i:s'),
                            ]);
                            $trxProvinceStatus = true;
                            break;
                        } else {
                            $trxProvinceStatus = false;
                        }
                    } else {
                        $trxProvinceStatus = true;
                    }

                    $trxNominalStatus = false;
                    if (!is_null($achievement['trx_nominal']) && $rules == 'nominal_transaction') {
                        if ((int) $achievement['trx_nominal'] <= $user['transaction_grandtotal']) {
                            $trxNominalStatus = true;
                        } else {
                            $trxNominalStatus = false;
                        }
                    } else {
                        $totalSumTrx = $totalSumTrx + $user['transaction_grandtotal'];
                        $trxNominalStatus = true;
                    }

                    if ($trxNominalStatus == true && $trxProductStatus == true && $trxTotalProductStatus == true && $trxOutletStatus == true && $trxProvinceStatus == true) {
                        $totalTrx = $totalTrx + 1;
                    }

                    $totalOutlet[] = $user['id_outlet'];
                    $totalProvince[] = $user['outlet']['city']['province']['id_province'];
                }

                if ($rules == 'nominal_transaction') {
                    if ($totalSumTrx >= (int) $achievement['trx_nominal']) {
                        AchievementProgress::updateOrCreate([
                            'id_achievement_detail' => $achievement['id_achievement_detail'],
                            'id_user' => $idUser,
                        ], [
                            'id_achievement_detail' => $achievement['id_achievement_detail'],
                            'id_user' => $idUser,
                            'progress' => $achievement['trx_nominal'],
                            'end_progress' => $achievement['trx_nominal'],
                        ]);
                        $achievementPassed = $achievementPassed + 1;
                        continue;
                    } else {
                        AchievementProgress::updateOrCreate([
                            'id_achievement_detail' => $achievement['id_achievement_detail'],
                            'id_user' => $idUser,
                        ], [
                            'id_achievement_detail' => $achievement['id_achievement_detail'],
                            'id_user' => $idUser,
                            'progress' => $totalSumTrx,
                            'end_progress' => $achievement['trx_nominal'],
                        ]);
                        if ($achievementPassed - 1 < 0) {
                            $achievement = null;
                        } else {
                            $achievement = $detailAchievement[$achievementPassed - 1];
                        }
                        break;
                    }
                }

                if ($rules == 'total_product') {
                    if ($totalSumProduct >= (int) $achievement['product_total']) {
                        AchievementProductLog::updateOrCreate([
                            'id_achievement_group' => $achievement['id_achievement_group'],
                            'id_achievement_detail' => $achievement['id_achievement_detail'],
                            'id_user' => $idUser,
                            'id_product' => $product['id_product'],
                            'id_transaction' => $user['id_transaction'],
                        ], [
                            'id_achievement_group' => $achievement['id_achievement_group'],
                            'id_achievement_detail' => $achievement['id_achievement_detail'],
                            'id_user' => $idUser,
                            'id_product' => $product['id_product'],
                            'product_total' => $achievement['transaction_product_qty'],
                            'id_transaction' => $user['id_transaction'],
                            'json_rule' => json_encode([
                                'id_product' => $achievement['id_product'],
                                'product_total' => $achievement['product_total'],
                                'trx_nominal' => $achievement['trx_nominal'],
                                'trx_total' => $achievement['trx_total'],
                                'id_outlet' => $achievement['id_outlet'],
                                'different_outlet' => $achievement['different_outlet'],
                                'id_province' => $achievement['id_province'],
                                'different_province' => $achievement['different_province'],
                            ]),
                            'json_rule_enc' => MyHelper::encrypt2019(json_encode([
                                'id_product' => $achievement['id_product'],
                                'product_total' => $achievement['product_total'],
                                'trx_nominal' => $achievement['trx_nominal'],
                                'trx_total' => $achievement['trx_total'],
                                'id_outlet' => $achievement['id_outlet'],
                                'different_outlet' => $achievement['different_outlet'],
                                'id_province' => $achievement['id_province'],
                                'different_province' => $achievement['different_province'],
                            ])),
                            'date' => date('Y-m-d H:i:s'),
                        ]);
                        AchievementProgress::updateOrCreate([
                            'id_achievement_detail' => $achievement['id_achievement_detail'],
                            'id_user' => $idUser,
                        ], [
                            'id_achievement_detail' => $achievement['id_achievement_detail'],
                            'id_user' => $idUser,
                            'progress' => $achievement['different_outlet'],
                            'end_progress' => $achievement['different_outlet'],
                        ]);
                        $achievementPassed = $achievementPassed + 1;
                        continue;
                    } else {
                        AchievementProgress::updateOrCreate([
                            'id_achievement_detail' => $achievement['id_achievement_detail'],
                            'id_user' => $idUser,
                        ], [
                            'id_achievement_detail' => $achievement['id_achievement_detail'],
                            'id_user' => $idUser,
                            'progress' => $totalSumProduct,
                            'end_progress' => $achievement['different_outlet'],
                        ]);
                        if ($achievementPassed - 1 < 0) {
                            $achievement = null;
                        } else {
                            $achievement = $detailAchievement[$achievementPassed - 1];
                        }
                        break;
                    }
                }

                if (!is_null($achievement['different_outlet'])) {
                    if (count(array_unique($totalOutlet)) >= (int) $achievement['different_outlet']) {
                        AchievementUserLog::updateOrCreate([
                            'id_achievement_detail' => $achievement['id_achievement_detail'],
                            'id_user' => $idUser,
                        ], [
                            'id_achievement_detail' => $achievement['id_achievement_detail'],
                            'id_user' => $idUser,
                            'json_rule' => json_encode([
                                'id_product' => $achievement['id_product'],
                                'product_total' => $achievement['product_total'],
                                'trx_nominal' => $achievement['trx_nominal'],
                                'trx_total' => $achievement['trx_total'],
                                'id_outlet' => $achievement['id_outlet'],
                                'different_outlet' => $achievement['different_outlet'],
                                'id_province' => $achievement['id_province'],
                                'different_province' => $achievement['different_province'],
                            ]),
                            'json_rule_enc' => MyHelper::encrypt2019(json_encode([
                                'id_product' => $achievement['id_product'],
                                'product_total' => $achievement['product_total'],
                                'trx_nominal' => $achievement['trx_nominal'],
                                'trx_total' => $achievement['trx_total'],
                                'id_outlet' => $achievement['id_outlet'],
                                'different_outlet' => $achievement['different_outlet'],
                                'id_province' => $achievement['id_province'],
                                'different_province' => $achievement['different_province'],
                            ])),
                            'date' => date('Y-m-d H:i:s'),
                        ]);
                        if ($rules == 'total_outlet') {
                            AchievementProgress::updateOrCreate([
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                            ], [
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                                'progress' => $achievement['different_outlet'],
                                'end_progress' => $achievement['different_outlet'],
                            ]);
                        }
                        $achievementPassed = $achievementPassed + 1;
                        continue;
                    } else {
                        if ($rules == 'total_outlet') {
                            AchievementProgress::updateOrCreate([
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                            ], [
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                                'progress' => count(array_unique($totalOutlet)),
                                'end_progress' => $achievement['different_outlet'],
                            ]);
                        }
                        if ($achievementPassed - 1 < 0) {
                            $achievement = null;
                        } else {
                            $achievement = $detailAchievement[$achievementPassed - 1];
                        }
                        break;
                    }
                }

                if (!is_null($achievement['different_province'])) {
                    if (count(array_unique($totalProvince)) >= (int) $achievement['different_province']) {
                        AchievementUserLog::updateOrCreate([
                            'id_achievement_detail' => $achievement['id_achievement_detail'],
                            'id_user' => $idUser,
                        ], [
                            'id_achievement_detail' => $achievement['id_achievement_detail'],
                            'id_user' => $idUser,
                            'json_rule' => json_encode([
                                'id_product' => $achievement['id_product'],
                                'product_total' => $achievement['product_total'],
                                'trx_nominal' => $achievement['trx_nominal'],
                                'trx_total' => $achievement['trx_total'],
                                'id_outlet' => $achievement['id_outlet'],
                                'different_outlet' => $achievement['different_outlet'],
                                'id_province' => $achievement['id_province'],
                                'different_province' => $achievement['different_province'],
                            ]),
                            'json_rule_enc' => MyHelper::encrypt2019(json_encode([
                                'id_product' => $achievement['id_product'],
                                'product_total' => $achievement['product_total'],
                                'trx_nominal' => $achievement['trx_nominal'],
                                'trx_total' => $achievement['trx_total'],
                                'id_outlet' => $achievement['id_outlet'],
                                'different_outlet' => $achievement['different_outlet'],
                                'id_province' => $achievement['id_province'],
                                'different_province' => $achievement['different_province'],
                            ])),
                            'date' => date('Y-m-d H:i:s'),
                        ]);
                        if ($rules == 'total_province') {
                            AchievementProgress::updateOrCreate([
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                            ], [
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                                'progress' => $achievement['different_outlet'],
                                'end_progress' => $achievement['different_outlet'],
                            ]);
                        }
                        $achievementPassed = $achievementPassed + 1;
                        continue;
                    } else {
                        if ($rules == 'total_province') {
                            AchievementProgress::updateOrCreate([
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                            ], [
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                                'progress' => count(array_unique($totalProvince)),
                                'end_progress' => $achievement['different_province'],
                            ]);
                        }
                        if ($achievementPassed - 1 < 0) {
                            $achievement = null;
                        } else {
                            $achievement = $detailAchievement[$achievementPassed - 1];
                        }
                        break;
                    }
                }

                if (!is_null($achievement['trx_total'])) {
                    if ($totalTrx >= (int) $achievement['trx_total']) {
                        AchievementUserLog::updateOrCreate([
                            'id_achievement_detail' => $achievement['id_achievement_detail'],
                            'id_user' => $idUser,
                        ], [
                            'id_achievement_detail' => $achievement['id_achievement_detail'],
                            'id_user' => $idUser,
                            'json_rule' => json_encode([
                                'id_product' => $achievement['id_product'],
                                'product_total' => $achievement['product_total'],
                                'trx_nominal' => $achievement['trx_nominal'],
                                'trx_total' => $achievement['trx_total'],
                                'id_outlet' => $achievement['id_outlet'],
                                'different_outlet' => $achievement['different_outlet'],
                                'id_province' => $achievement['id_province'],
                                'different_province' => $achievement['different_province'],
                            ]),
                            'json_rule_enc' => MyHelper::encrypt2019(json_encode([
                                'id_product' => $achievement['id_product'],
                                'product_total' => $achievement['product_total'],
                                'trx_nominal' => $achievement['trx_nominal'],
                                'trx_total' => $achievement['trx_total'],
                                'id_outlet' => $achievement['id_outlet'],
                                'different_outlet' => $achievement['different_outlet'],
                                'id_province' => $achievement['id_province'],
                                'different_province' => $achievement['different_province'],
                            ])),
                            'date' => date('Y-m-d H:i:s'),
                        ]);
                        if ($rules == 'total_transaction') {
                            AchievementProgress::updateOrCreate([
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                            ], [
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                                'progress' => $achievement['trx_total'],
                                'end_progress' => $achievement['trx_total'],
                            ]);
                        }
                        $achievementPassed = $achievementPassed + 1;
                        continue;
                    } else {
                        if ($rules == 'total_transaction') {
                            AchievementProgress::updateOrCreate([
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                            ], [
                                'id_achievement_detail' => $achievement['id_achievement_detail'],
                                'id_user' => $idUser,
                                'progress' => $totalTrx,
                                'end_progress' => $achievement['trx_total'],
                            ]);
                        }
                        if ($achievementPassed - 1 < 0) {
                            $achievement = null;
                        } else {
                            $achievement = $detailAchievement[$achievementPassed - 1];
                        }
                        break;
                    }
                }
            } else {
                if ($achievementPassed - 1 < 0) {
                    $achievement = null;
                }
                break;
            }
        }

        if ($achievement != null) {
            AchievementUser::updateOrCreate([
                'id_achievement_detail' => $achievement['id_achievement_detail'],
                'id_user' => $idUser,
            ], [
                'id_achievement_detail' => $achievement['id_achievement_detail'],
                'id_user' => $idUser,
                'json_rule' => json_encode([
                    'id_product' => $achievement['id_product'],
                    'product_total' => $achievement['product_total'],
                    'trx_nominal' => $achievement['trx_nominal'],
                    'trx_total' => $achievement['trx_total'],
                    'id_outlet' => $achievement['id_outlet'],
                    'different_outlet' => $achievement['different_outlet'],
                    'id_province' => $achievement['id_province'],
                    'different_province' => $achievement['different_province'],
                ]),
                'json_rule_enc' => MyHelper::encrypt2019(json_encode([
                    'id_product' => $achievement['id_product'],
                    'product_total' => $achievement['product_total'],
                    'trx_nominal' => $achievement['trx_nominal'],
                    'trx_total' => $achievement['trx_total'],
                    'id_outlet' => $achievement['id_outlet'],
                    'different_outlet' => $achievement['different_outlet'],
                    'id_province' => $achievement['id_province'],
                    'different_province' => $achievement['different_province'],
                ])),
                'date' => date('Y-m-d H:i:s'),
            ]);
        }

        return ['status' => 'success'];
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show(Request $request)
    {
        try {
            $data['group'] = AchievementGroup::where('id_achievement_group', MyHelper::decSlug($request['id_achievement_group']))->first();
            $data['category'] = AchievementCategory::select('name')->where('id_achievement_category', $data['group']->id_achievement_category)->first();
            $data['detail'] = AchievementDetail::with('product', 'outlet', 'province')->where('id_achievement_group', MyHelper::decSlug($request['id_achievement_group']))->get()->toArray();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'fail',
                'message' => 'Get Achievement Detail Failed',
                'error' => $e->getMessage(),
            ]);
        }

        $data['group']['logo_badge_default'] = env('STORAGE_URL_API') . $data['group']['logo_badge_default'];
        foreach ($data['detail'] as $key => $value) {
            $data['detail'][$key]['logo_badge'] = env('STORAGE_URL_API') . $value['logo_badge'];
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('achievement::edit');
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

        if (isset($post['logo_badge'])) {
            $uploadDetail = MyHelper::uploadPhotoStrict($post['logo_badge'], $this->saveImageDetail, 500, 500);

            if (isset($uploadDetail['status']) && $uploadDetail['status'] == "success") {
                $post['logo_badge'] = $uploadDetail['path'];
            } else {
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Failed to upload image'],
                ]);
            }
        }

        DB::beginTransaction();
        try {
            AchievementDetail::where('id_achievement_detail', $post['id_achievement_detail'])->update($post);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'fail',
                'message' => 'Update Achievement Detail Failed',
                'error' => $e->getMessage(),
            ]);
        }
        DB::commit();

        return response()->json([
            'status' => 'success',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        DB::beginTransaction();

        try {
            if (isset($request['id_achievement_group'])) {
                AchievementGroup::where('id_achievement_group', MyHelper::decSlug($request['id_achievement_group']))->delete();
            } else {
                AchievementDetail::where('id_achievement_detail', $request['id_achievement_detail'])->delete();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'fail',
                'message' => 'Get Achievement Detail Failed',
                'error' => $e->getMessage(),
            ]);
        }

        DB::commit();

        return response()->json([
            'status' => 'success',
        ]);
    }

    public function detailAchievement(Request $request)
    {
        $getAchievement = AchievementCategory::with('achievement_group')->get()->toArray();

        $catProgress    = 0;
        $catEndProgress = 0;
        foreach ($getAchievement as $keyCatAch => $category) {
            $result['category'][$keyCatAch] = [
                'id_achievement_category' => $category['id_achievement_category'],
                'name' => $category['name'],
                'description' => $category['description']
            ];
            foreach ($category['achievement_group'] as $keyAchGroup => $group) {
                $result['category'][$keyCatAch]['achievement'][$keyAchGroup] = [
                    'id_achievement_group' => MyHelper::decSlug($group['id_achievement_group']),
                    'name' => $group['name'],
                    'logo_achievement' => env('STORAGE_URL_API') . $group['logo_badge_default'],
                    'description' => $group['description'],
                ];

                $getAchievementDetail = AchievementDetail::where([
                    'id_achievement_group' => MyHelper::decSlug($group['id_achievement_group']),
                ])->get()->toArray();
                $achProgress    = 0;
                $achEndProgress = 0;
                foreach ($getAchievementDetail as $keyAchDetail => $detail) {
                    $result['category'][$keyCatAch]['achievement'][$keyAchGroup]['badge'][$keyAchDetail] = [
                        'id_achievement_detail' => $detail['id_achievement_detail'],
                        'name' => $detail['name'],
                        'logo_badge' => env('STORAGE_URL_API') . $detail['logo_badge'],
                    ];
                    $getAchievementProgress = AchievementProgress::where([
                        'id_user' => Auth::user()->id,
                        'id_achievement_detail' => $detail['id_achievement_detail'],
                    ])->first();

                    if ($getAchievementProgress) {
                        $badgePercentProgress = ($getAchievementProgress->progress == 0) ? 0 : $getAchievementProgress->progress / $getAchievementProgress->end_progress;
                        $result['category'][$keyCatAch]['achievement'][$keyAchGroup]['badge'][$keyAchDetail]['progress']            = $getAchievementProgress->progress;
                        $result['category'][$keyCatAch]['achievement'][$keyAchGroup]['badge'][$keyAchDetail]['end_progress']        = $getAchievementProgress->end_progress;
                        $result['category'][$keyCatAch]['achievement'][$keyAchGroup]['badge'][$keyAchDetail]['progress_percent']    = $badgePercentProgress;
                    } else {
                        $badgePercentProgress = 0;
                        $result['category'][$keyCatAch]['achievement'][$keyAchGroup]['badge'][$keyAchDetail]['progress']            = 0;
                        switch ($group['order_by']) {
                            case 'nominal_transaction':
                                $result['category'][$keyCatAch]['achievement'][$keyAchGroup]['badge'][$keyAchDetail]['end_progress']        = $detail['trx_nominal'];
                                break;
                            case 'total_product':
                                $result['category'][$keyCatAch]['achievement'][$keyAchGroup]['badge'][$keyAchDetail]['end_progress']        = $detail['product_total'];
                                break;
                            case 'total_transaction':
                                $result['category'][$keyCatAch]['achievement'][$keyAchGroup]['badge'][$keyAchDetail]['end_progress']        = $detail['trx_total'];
                                break;
                            case 'total_outlet':
                                $result['category'][$keyCatAch]['achievement'][$keyAchGroup]['badge'][$keyAchDetail]['end_progress']        = $detail['different_outlet'];
                                break;
                            case 'total_province':
                                $result['category'][$keyCatAch]['achievement'][$keyAchGroup]['badge'][$keyAchDetail]['end_progress']        = $detail['different_province'];
                                break;
                        }
                        $result['category'][$keyCatAch]['achievement'][$keyAchGroup]['badge'][$keyAchDetail]['progress_percent']    = 0;
                    }
                    if ($badgePercentProgress == 1) {
                        $achProgress = $achProgress + 1;
                    }
                    $achEndProgress = $achEndProgress + 1;
                }
                $achPercentProgress = ($achProgress == 0) ? 0 : $achProgress / $achEndProgress;
                $result['category'][$keyCatAch]['achievement'][$keyAchGroup]['progress']            = $achProgress;
                $result['category'][$keyCatAch]['achievement'][$keyAchGroup]['end_progress']        = $achEndProgress;
                $result['category'][$keyCatAch]['achievement'][$keyAchGroup]['progress_percent']    = $achPercentProgress;
                $result['category'][$keyCatAch]['achievement'][$keyAchGroup]['progress_text']       = $group['progress_text'];

                if ($achPercentProgress > 0) {
                    $catProgress = $catProgress + 1;
                }
                $catEndProgress = $catEndProgress + 1;
            }
        }
        $result['progress']     = $catProgress;
        $result['end_progress'] = $catEndProgress;

        return response()->json(MyHelper::checkGet($result));
    }
}
