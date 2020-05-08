<?php

namespace Modules\Achievement\Http\Controllers;

use App\Http\Models\Transaction;
use App\Http\Models\User;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Achievement\Entities\AchievementCategory;
use Modules\Achievement\Entities\AchievementDetail;
use Modules\Achievement\Entities\AchievementGroup;
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
    public function category(Request $request)
    {
        return [
            'status'    => 'success',
            'data'      => AchievementCategory::get()->toArray()
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
                'detail.*.name'             => 'required',
                'detail.*.logo_badge'       => 'required'
            ]);
        } else {
            $request->validate([
                'category.name'             => 'required',
                'group.name'                => 'required',
                'group.publish_start'       => 'required',
                'group.date_start'          => 'required',
                'group.description'         => 'required',
                'group.order_by'            => 'required',
                'group.logo_badge_default'  => 'required',
                'detail.*.name'             => 'required',
                'detail.*.logo_badge'       => 'required'
            ]);

            $upload = MyHelper::uploadPhotoStrict($post['group']['logo_badge_default'], $this->saveImage, 500, 500);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $post['group']['logo_badge_default'] = $upload['path'];
            } else {
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Failed to upload image']
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
                    'status'    => 'fail',
                    'message'   => 'Get or Add Category Achievement Failed',
                    'error'     => $e->getMessage()
                ]);
            }

            $post['group']['publish_start']     = date('Y-m-d H:i', strtotime($post['group']['publish_start']));
            $post['group']['date_start']        = date('Y-m-d H:i', strtotime($post['group']['date_start']));
            if (!is_null($post['group']['publish_end'])) {
                $post['group']['publish_end']   = date('Y-m-d H:i', strtotime($post['group']['publish_end']));
            }
            if (!is_null($post['group']['date_end'])) {
                $post['group']['date_end']      = date('Y-m-d H:i', strtotime($post['group']['date_end']));
            }

            try {
                $group = AchievementGroup::create($post['group']);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status'    => 'fail',
                    'message'   => 'Add Achievement Group Failed',
                    'error'     => $e->getMessage()
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
                            'status'   => 'fail',
                            'messages' => ['Failed to upload image']
                        ]);
                    }

                    if (isset($request['id_achievement_group'])) {
                        $value['id_achievement_group']   = MyHelper::decSlug($request['id_achievement_group']);
                    } else {
                        $value['id_achievement_group']   = MyHelper::decSlug($group->id_achievement_group);
                    }
                    $achievementDetail[$key] = AchievementDetail::create($value);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status'    => 'fail',
                    'message'   => 'Add Achievement Detail Failed',
                    'error'     => $e->getMessage()
                ]);
            }
        }
        
        if (date('Y-m-d H:i', strtotime($post['group']['date_start'])) <= date('Y-m-d H:i')) {
            $getUser = User::select('id')->get()->toArray();
            foreach ($getUser as $key => $value) {
                self::checkAchievement($value['id'], $achievementDetail);
            }
        }

        DB::commit();

        if (isset($request['id_achievement_group'])) {
            return response()->json([
                'status'    => 'success',
                'message'   => 'Add Achievement Success',
                'data'      => MyHelper::encSlug($request['id_achievement_group'])
            ]);
        } else {
            return response()->json([
                'status'    => 'success',
                'message'   => 'Add Achievement Success',
                'data'      => MyHelper::encSlug($group->id_achievement_group)
            ]);
        }
    }

    public static function checkAchievement($idUser, $detailAchievement)
    {
        $achievementPassed = 0;
        $achievement = null;
        foreach ($detailAchievement as $keyAch => $achievement) {
            $getTrxUser = Transaction::with('outlet.city.province', 'productTransaction')->where(['transactions.id_user' => $idUser, 'transactions.transaction_payment_status' => 'Completed'])->get()->toArray();
            
            if ($achievementPassed == $keyAch) {
                $totalTrx       = 0;
                $totalOutlet    = [];
                $totalProvince  = [];
                foreach ($getTrxUser as $user) {
                    $trxNominalStatus = false;
                    if (!is_null($achievement['trx_nominal'])) {
                        if ((int) $achievement['trx_nominal'] <= $user['transaction_grandtotal']) {
                            $trxNominalStatus = true;
                        } else {
                            $trxNominalStatus = false;
                        }
                    } else {
                        $trxNominalStatus = true;
                    }

                    $trxProductStatus = false;
                    $trxTotalProductStatus = false;
                    if (!is_null($achievement['id_product']) || !is_null($achievement['product_total'])) {
                        foreach ($user['product_transaction'] as $product) {
                            if (!is_null($achievement['id_product'])) {
                                if ((int) $achievement['id_product'] == $product['id_product']) {
                                    $trxProductStatus = true;
                                    if (!is_null($achievement['product_total'])) {
                                        if ((int) $achievement['product_total'] <= $product['transaction_product_qty']) {
                                            $trxTotalProductStatus = true;
                                            break;
                                        } else {
                                            $trxTotalProductStatus = false;
                                            break;
                                        }
                                    } else {
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
                
                if (!is_null($achievement['different_outlet'])) {
                    if (count(array_unique($totalOutlet)) >= (int) $achievement['different_outlet']) {
                        AchievementUserLog::create([
                            'id_achievement_detail'     => $achievement['id_achievement_detail'],
                            'id_user'                   => $idUser,
                            'json_rule'                 => json_encode([
                                'id_product'            => $achievement['id_product'],
                                'product_total'         => $achievement['product_total'],
                                'trx_nominal'           => $achievement['trx_nominal'],
                                'trx_total'             => $achievement['trx_total'],
                                'id_outlet'             => $achievement['id_outlet'],
                                'different_outlet'      => $achievement['different_outlet'],
                                'id_province'           => $achievement['id_province'],
                                'different_province'    => $achievement['different_province']
                            ]),
                            'json_rule_enc'             => MyHelper::encrypt2019(json_encode([
                                'id_product'            => $achievement['id_product'],
                                'product_total'         => $achievement['product_total'],
                                'trx_nominal'           => $achievement['trx_nominal'],
                                'trx_total'             => $achievement['trx_total'],
                                'id_outlet'             => $achievement['id_outlet'],
                                'different_outlet'      => $achievement['different_outlet'],
                                'id_province'           => $achievement['id_province'],
                                'different_province'    => $achievement['different_province']
                            ])),
                            'date'                      => date('Y-m-d H:i:s')
                        ]);
                        $achievementPassed = $achievementPassed + 1;
                        continue;
                    } else {
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
                        AchievementUserLog::create([
                            'id_achievement_detail'     => $achievement['id_achievement_detail'],
                            'id_user'                   => $idUser,
                            'json_rule'                 => json_encode([
                                'id_product'            => $achievement['id_product'],
                                'product_total'         => $achievement['product_total'],
                                'trx_nominal'           => $achievement['trx_nominal'],
                                'trx_total'             => $achievement['trx_total'],
                                'id_outlet'             => $achievement['id_outlet'],
                                'different_outlet'      => $achievement['different_outlet'],
                                'id_province'           => $achievement['id_province'],
                                'different_province'    => $achievement['different_province']
                            ]),
                            'json_rule_enc'             => MyHelper::encrypt2019(json_encode([
                                'id_product'            => $achievement['id_product'],
                                'product_total'         => $achievement['product_total'],
                                'trx_nominal'           => $achievement['trx_nominal'],
                                'trx_total'             => $achievement['trx_total'],
                                'id_outlet'             => $achievement['id_outlet'],
                                'different_outlet'      => $achievement['different_outlet'],
                                'id_province'           => $achievement['id_province'],
                                'different_province'    => $achievement['different_province']
                            ])),
                            'date'                      => date('Y-m-d H:i:s')
                        ]);
                        $achievementPassed = $achievementPassed + 1;
                        continue;
                    } else {
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
                        AchievementUserLog::create([
                            'id_achievement_detail'     => $achievement['id_achievement_detail'],
                            'id_user'                   => $idUser,
                            'json_rule'                 => json_encode([
                                'id_product'            => $achievement['id_product'],
                                'product_total'         => $achievement['product_total'],
                                'trx_nominal'           => $achievement['trx_nominal'],
                                'trx_total'             => $achievement['trx_total'],
                                'id_outlet'             => $achievement['id_outlet'],
                                'different_outlet'      => $achievement['different_outlet'],
                                'id_province'           => $achievement['id_province'],
                                'different_province'    => $achievement['different_province']
                            ]),
                            'json_rule_enc'             => MyHelper::encrypt2019(json_encode([
                                'id_product'            => $achievement['id_product'],
                                'product_total'         => $achievement['product_total'],
                                'trx_nominal'           => $achievement['trx_nominal'],
                                'trx_total'             => $achievement['trx_total'],
                                'id_outlet'             => $achievement['id_outlet'],
                                'different_outlet'      => $achievement['different_outlet'],
                                'id_province'           => $achievement['id_province'],
                                'different_province'    => $achievement['different_province']
                            ])),
                            'date'                      => date('Y-m-d H:i:s')
                        ]);
                        $achievementPassed = $achievementPassed + 1;
                        continue;
                    } else {
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
            AchievementUser::create([
                'id_achievement_detail'     => $achievement['id_achievement_detail'],
                'id_user'                   => $idUser,
                'json_rule'                 => json_encode([
                    'id_product'            => $achievement['id_product'],
                    'product_total'         => $achievement['product_total'],
                    'trx_nominal'           => $achievement['trx_nominal'],
                    'trx_total'             => $achievement['trx_total'],
                    'id_outlet'             => $achievement['id_outlet'],
                    'different_outlet'      => $achievement['different_outlet'],
                    'id_province'           => $achievement['id_province'],
                    'different_province'    => $achievement['different_province']
                ]),
                'json_rule_enc'             => MyHelper::encrypt2019(json_encode([
                    'id_product'            => $achievement['id_product'],
                    'product_total'         => $achievement['product_total'],
                    'trx_nominal'           => $achievement['trx_nominal'],
                    'trx_total'             => $achievement['trx_total'],
                    'id_outlet'             => $achievement['id_outlet'],
                    'different_outlet'      => $achievement['different_outlet'],
                    'id_province'           => $achievement['id_province'],
                    'different_province'    => $achievement['different_province']
                ])),
                'date'                      => date('Y-m-d H:i:s')
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
            $data['group']      = AchievementGroup::where('id_achievement_group', MyHelper::decSlug($request['id_achievement_group']))->first();
            $data['category']   = AchievementCategory::select('name')->where('id_achievement_category', $data['group']->id_achievement_category)->first();
            $data['detail']     = AchievementDetail::with('product', 'outlet', 'province')->where('id_achievement_group', MyHelper::decSlug($request['id_achievement_group']))->get()->toArray();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'    => 'fail',
                'message'   => 'Get Achievement Detail Failed',
                'error'     => $e->getMessage()
            ]);
        }

        $data['group']['logo_badge_default']    = env('S3_URL_API') . $data['group']['logo_badge_default'];
        foreach ($data['detail'] as $key => $value) {
            $data['detail'][$key]['logo_badge'] = env('S3_URL_API') . $value['logo_badge'];
        }

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
                    'status'   => 'fail',
                    'messages' => ['Failed to upload image']
                ]);
            }
        }

        DB::beginTransaction();
        try {
            AchievementDetail::where('id_achievement_detail', $post['id_achievement_detail'])->update($post);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'    => 'fail',
                'message'   => 'Update Achievement Detail Failed',
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
                'status'    => 'fail',
                'message'   => 'Get Achievement Detail Failed',
                'error'     => $e->getMessage()
            ]);
        }

        DB::commit();

        return response()->json([
            'status'    => 'success'
        ]);
    }
}
