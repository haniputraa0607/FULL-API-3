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
    public function index()
    {
        $quests = Quest::paginate();
        return MyHelper::checkGet($quests);
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
        if (!is_null($post['quest']['publish_end'])) {
            $post['quest']['publish_end']   = date('Y-m-d H:i', strtotime($post['quest']['publish_end']));
        }
        if (!is_null($post['quest']['date_end'])) {
            $post['quest']['date_end']      = date('Y-m-d H:i', strtotime($post['quest']['date_end']));
        }

        try {
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
                'title' => 'Terms and Condition',
                'content' => '',
                'order' => 1,
                'is_active' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id_quest' => $quest->id_quest,
                'title' => 'Benefit',
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
            $create = QuestDetail::create($detail);
        }
        return MyHelper::checkCreate($create);
    }

    public function checkQuest($quest, $idUser, $detailQuest)
    {
        $questPassed = 0;
        foreach ($detailQuest as $keyQuest => $quest) {
            $getTrxUser = Transaction::with('outlet.city.province', 'productTransaction')->where(['transactions.id_user' => $idUser, 'transactions.transaction_payment_status' => 'Completed'])->get()->toArray();

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
                        foreach ($user['product_transaction'] as $product) {
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
        $transaction = Transaction::with(['productTransaction' => function($q) {
                    $q->select('transaction_products.*', 'products.*', 'brand_product.id_product_category')
                        ->join('products', 'products.id_product', 'transaction_products.id_product')
                        ->leftJoin('brand_product', 'products.id_product', 'brand_product.id_product');
                }, 'outlet', 'outlet.city'])->find($id_transaction);
        if (!$transaction) {
            return false;
        }
        // get all user quests
        $quests = Quest::join('quest_details', 'quest_details.id_quest', 'quests.id_quest')
            ->join('quest_users', 'quest_details.id_quest', 'quest_users.id_quest')
            ->where('id_user', $transaction->id_user)
            ->where('is_done', 0)
            ->where('date_start', '<=', date('Y-m-d H:i:s'))
            ->where('date_end', '>=', date('Y-m-d H:i:s'))
            ->get();

        foreach ($quests as $quest) {
            if (
                ($quest->id_outlet && $quest->id_outlet != $transaction->id_outlet) ||
                ($quest->id_province && $quest->id_province != $transaction->outlet->city->id_province) ||
                ($quest->id_product && !$transaction->productTransaction->pluck('id_product')->contains($quest->id_product)) ||
                ($quest->id_product_category && !$transaction->productTransaction->pluck('id_product_category')->contains($quest->id_product_category))
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
                        if ($transaction->created_at <= $questLog->date) {
                            \DB::rollBack();
                            continue;
                        }
                        $questLog->update([
                            'count' => $questLog->count+1,
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
                    $transaction->load(['productTransaction' => function($q) {
                        $q->join('products', 'products.id_product', 'transaction_products.id_product');
                    }]);
                    $has_product = 0;
                    foreach ($transaction->productTransaction as $transaction_product) {
                        if (($quest->id_product == $transaction_product->id_product && (!$quest->id_product_variant_group || $quest->id_product_variant_group == $transaction_product->id_product_variant_group)) || $quest->id_product_category == $transaction_product->id_product_category || $quest->different_product_category || $quest->product_total) {
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
                                if ($transaction->created_at <= $questLog->date) {
                                    continue;
                                }
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
                    }
                }

                // province
                if ($quest->id_province || $quest->different_province) {
                    $transaction->load('outlet');
                    if ($quest->id_province == $transaction->outlet->id_province || $quest->different_province) {
                        $questLog = QuestProvinceLog::updateOrCreate([
                            'id_quest' => $quest->id_quest,
                            'id_quest_detail' => $quest->id_quest_detail,
                            'id_user' => $transaction->id_user,
                            'id_transaction' => $transaction->id_transaction,
                            'id_province' => $transaction->outlet->id_province,
                        ],[
                            'date' => $transaction->created_at,
                        ]);
                    }
                }

                // transaction
                if ($quest->id_trx_nominal || $quest->trx_total) {
                    $questLog = QuestTransactionLog::where([
                        'id_quest' => $quest->id_quest,
                        'id_quest_detail' => $quest->id_quest_detail,
                        'id_user' => $transaction->id_user,
                        'id_transaction' => $transaction->id_transaction,
                        'id_outlet' => $transaction->id_outlet,
                    ])->first();
                    if ($questLog) {
                        if ($transaction->created_at <= $questLog->date) {
                            \DB::rollBack();
                            continue;
                        }
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
            ->each(function($quest) use ($transaction) {
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
            if (QuestProductLog::where('id_quest_detail', $questDetail->id_quest_detail)->distinct('id_product_category')->count() < $questDetail->different_product_category) {
                return false;
            }
        }

        if ($questDetail->product_total) {
            if (QuestProductLog::where('id_quest_detail', $questDetail->id_quest_detail)->select('product_total')->sum('product_total') < $questDetail->product_total) {
                return false;
            }
        }

        if ($questDetail->trx_total) {
            if (QuestTransactionLog::where('id_quest_detail', $questDetail->id_quest_detail)->select('transaction_total')->sum('transaction_total') < $questDetail->trx_total) {
                return false;
            }
        }

        if ($questDetail->trx_nominal) {
            if (QuestTransactionLog::where('id_quest_detail', $questDetail->id_quest_detail)->select('transaction_nominal')->sum('transaction_nominal') < $questDetail->trx_nominal) {
                return false;
            }
        }

        if ($questDetail->different_outlet) {
            if (optional(QuestOutletLog::where('id_quest_detail', $questDetail->id_quest_detail)->first())->count < $questDetail->different_outlet) {
                return false;
            }
        }

        if ($questDetail->different_province) {
            if (QuestProvinceLog::where('id_quest_detail', $questDetail->id_quest_detail)->distinct('id_province')->count() < $questDetail->different_province) {
                return false;
            }
        }

        QuestUser::where(['id_quest_user' => $questDetail->id_quest_user])->update(['is_done' => 1, 'date' => date('Y-m-d H:i:s')]);
        return true;
    }

    public function checkQuestCompleted($quest, $id_user, $auto = false, &$errors = [])
    {
        if (is_numeric($quest)) {
            $quest = Quest::where('id_quest', $quest)->first();
        }

        if (!$quest) {
            $errors[] = 'Quest tidak';
            return false;
        }

        $questIncomplete = QuestUser::where(['is_done' => 0, 'id_quest' => $quest->id_quest, 'id_user' => $id_user])->exists();
        if ($questIncomplete) {
            $errors[] = 'Quest belum selesai';
            return false;
        }

        $redemption = QuestUserRedemption::where(['id_quest' => $quest->id_quest, 'id_user' => $id_user, 'redemption_status' => 1])->first();
        if ($redemption) {
            $errors[] = 'Hadiah sudah di klaim';
            return false;
        }

        $benefit =  QuestBenefit::where(['id_quest' => $quest->id_quest])->first();
        if (!$benefit) {
            goto flag;
        }

        if (!$benefit->autoclaim_benefit && $auto) {
            // not autoclaim
            return false;
        }

        $user = User::find($id_user);
        if (!$user) {
            goto flag;
        }

        if ($benefit->benefit_type == 'point') {
            app($this->balance)->addLogBalance( $id_user, $benefit->value, $quest->id_quest, 'Quest Benefit', 0);
            // addLogBalance
            $autocrm = app($this->autocrm)->SendAutoCRM('Receive Quest Point', $user->phone,
                [
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

            for($i=0;$i<$total_benefit;$i++){
                if ($total_voucher > $total_claimed || $total_voucher === 0) {
                    $generateVoucher = app($this->hidden_deals)->autoClaimedAssign($deals, [$id_user]);
                    $count++;
                    app($this->deals_claim)->updateDeals($deals);
                    $deals = Deal::where('id_deals', $deals->id_user)->first();
                    $total_claimed = $deals['deals_total_claimed'];
                } else {
                    break;
                }
            }

            if ($count) {
                $autocrm = app($this->autocrm)->SendAutoCRM('Receive Quest Voucher', $user->phone,
                    [
                        'count_voucher'      => (string) $count,
                        'deals_title'        => $deals->deals_title,
                        'quest_name'         => $quest->name,
                        'voucher_qty'        => (string) $count,
                    ]
                );
            } else {
                $autocrm = app($this->autocrm)->SendAutoCRM('Quest Voucher Runs Out', $user->phone,
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
        QuestUserRedemption::updateOrCreate(['id_quest' => $quest->id_quest, 'id_user' => $id_user], ['redemption_status' => 1, 'redemption_date' => date('Y-m-d H:i:s')]);
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
            $data['quest']  = Quest::with('quest_detail', 'quest_detail.product', 'quest_detail.outlet', 'quest_detail.province', 'quest_contents', 'quest_benefit', 'quest_benefit.deals')->where('id_quest', $request['id_quest'])->first();
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
                'id_outlet' => $post['id_outlet'] ?? null,
                'id_province' => $post['id_province'] ?? null,
                'different_category_product' => $post['different_product_category'] ?? null,
                'different_outlet' => $post['different_outlet'] ?? null,
                'different_province' => $post['different_province'] ?? null
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
    public function destroy($id)
    {
        //
    }

    public function list(Request $request)
    {
        $id_user = $request->user()->id;
        $quests = Quest::select('quests.id_quest', 'name', 'image as image_url', 'date_start', 'date_end', 'short_description', 'description')
            ->leftJoin('quest_users', function($q) use ($id_user) {
                $q->on('quest_users.id_quest', 'quests.id_quest')
                    ->where('id_user', $id_user);
            })
            ->whereNull('quest_users.id_quest_user')
            ->where('publish_start', '<=', date('Y-m-d H:i:s'))
            ->where('publish_end', '>=', date('Y-m-d H:i:s'))
            ->where('is_complete', 1);

        if ($request->page) {
            $quests = $quests->paginate();
        } else {
            $quests = $quests->get();
        }

        $quests->each(function($item) {
            $item->append('contents', 'text_label');
            $item->makeHidden(['date_start', 'date_end', 'quest_contents']);
        });

        $result = $quests->toArray();
        return MyHelper::checkGet($result, "Belum ada misi saat ini.\nTunggu misi selanjutnya");
    }

    public function takeMission(Request $request)
    {
        $id_user = $request->user()->id;
        $quest = Quest::select('quests.*', 'id_quest_user')
            ->leftJoin('quest_users', function($q) use ($id_user) {
                $q->on('quest_users.id_quest', 'quests.id_quest')
                    ->where('id_user', $id_user);
            })
            ->where('quests.id_quest', $request['id_quest'] ?? $request->id_quest)
            ->where('is_complete', 1)
            ->first();
        if ($quest['id_quest_user']) {
            return [
                'status' => 'fail',
                'messages' => [
                    'Misi sudah diambil'
                ]
            ];
        }
        if ($quest->date_start > date('Y-m-d H:i:s')) {
            return [
                'status' => 'fail',
                'messages' => [
                    'Misi belum dimulai'
                ]
            ];
        }
        if ($quest->date_end < date('Y-m-d H:i:s')) {
            return [
                'status' => 'fail',
                'messages' => [
                    'Misi sudah selesai'
                ]
            ];
        }
        $questDetail = QuestDetail::where(['id_quest' => $quest->id_quest])->get();
        $questDetail->each(function($detail) use ($id_user) {
            QuestUser::updateOrCreate([
                'id_quest' => $detail->id_quest,
                'id_quest_detail' => $detail->id_quest_detail,
                'id_user' => $id_user,
            ]);
        });
        return [
            'status' => 'success',
            'result' => [
                'id_quest' => $quest->id_quest
            ],
        ];
    }

    public function claimBenefit(Request $request)
    {
        $claim = $this->checkQuestCompleted($request->id_quest, $request->user()->id, false, $errors);

        if ($claim) {
            return ['status' => 'success'];
        }
        return [
            'status' => 'fail',
            'messages' => $errors ?? [
                'Failed claim benefit'
            ],
        ];
    }

    public function me(Request $request)
    {
        $id_user = $request->user()->id;
        $quests = Quest::select('quests.id_quest', 'name', 'image as image_url', 'short_description', 'date_start', 'date_end', \DB::raw('COALESCE(redemption_status, 0) as claimed_status'))
            ->where('is_complete', 1)
            ->where('publish_start', '<=', date('Y-m-d H:i:s'))
            ->where('publish_end', '>=', date('Y-m-d H:i:s'))
            ->groupBy('quests.id_quest')
            ->join('quest_users', function($q) use ($id_user) {
                $q->on('quest_users.id_quest', 'quests.id_quest')
                    ->where('id_user', $id_user);
            })
            ->leftJoin('quest_user_redemptions', function($join) {
                $join->on('quest_user_redemptions.id_quest', 'quest_users.id_quest')
                    ->whereColumn('quest_user_redemptions.id_user', 'quest_users.id_user');
            });

        if ($request->page) {
            $quests = $quests->paginate();
        } else {
            $quests = $quests->get();
        }

        $time_server = date('Y-m-d H:i:s');
        $quests->each(function($item) use ($time_server) {
            $item->append(['progress']);
            $item->makeHidden(['date_start']);
            $item->date_end_format = MyHelper::indonesian_date_v2($item['date_end'], 'd F Y');
            $item->time_server = $time_server;
        });

        $result = $quests->toArray();
        return MyHelper::checkGet($result, "Belum ada misi saat ini.\nMulai sebuah misi baru");
    }

    public function detail(Request $request)
    {
        $id_user = $request->user()->id;
        $quest = Quest::select('quests.id_quest', 'name', 'image as image_url', 'description', 'short_description', 'date_start', 'date_end', \DB::raw('COALESCE(redemption_status, 0) as claimed_status'))
            ->join('quest_users', function($q) use ($id_user) {
                $q->on('quest_users.id_quest', 'quests.id_quest')
                    ->where('id_user', $id_user);
            })
            ->leftJoin('quest_user_redemptions', function($join) {
                $join->on('quest_user_redemptions.id_quest', 'quest_users.id_quest')
                    ->whereColumn('quest_user_redemptions.id_user', 'quest_users.id_user');
            })
            ->where('quests.id_quest', $request['id_quest'] ?? $request->id_quest)
            ->first();
        if (!$quest) {
            return MyHelper::checkGet([], "Quest tidak ditemukan");
        }
        $quest->append(['progress', 'contents']);
        $quest->makeHidden(['date_start', 'quest_contents', 'description']);
        $result = $quest->toArray();
        $result['date_end_format'] = MyHelper::indonesian_date_v2($result['date_end'], 'd F Y');
        $result['time_server'] = date('Y-m-d H:i:s');

        $details = QuestUser::select('name', 'short_description', 'is_done')->join('quest_details', 'quest_details.id_quest_detail', 'quest_users.id_quest_detail')->get();

        $result['details'] = $details;

        return MyHelper::checkGet($result, "Quest tidak ditemukan");
    }

    public function listDeals(Request $request)
    {
        $result = Deal::select('id_deals', 'deals_title')
            ->where('deals_end', '<=', date('Y-m-d H:i:s'))
            ->where('step_complete', '1')
            ->get()
            ->toArray();
        return MyHelper::checkGet($result);
    }

    public function listProduct(Request $request)
    {
        $result = Product::select('id_product', 'product_name')
            ->with(['product_variant_group' => function($relation) {
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

        $toUpdate['publish_start']     = date('Y-m-d H:i', strtotime($toUpdate['publish_start']));
        $toUpdate['date_start']        = date('Y-m-d H:i', strtotime($toUpdate['date_start']));
        if (!is_null($toUpdate['publish_end'])) {
            $toUpdate['publish_end']   = date('Y-m-d H:i', strtotime($toUpdate['publish_end']));
        }
        if (!is_null($toUpdate['date_end'])) {
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
        $update = $quest->update(['is_complete' => 1]);
        return MyHelper::checkUpdate($update);
    }

    public function status(Request $request)
    {
        $id_user = $request->user()->id;
        $myQuest = Quest::leftJoin('quest_user_redemptions', function($join) use ($id_user) {
                $join->on('quest_user_redemptions.id_quest', 'quests.id_quest')
                    ->where('quest_user_redemptions.id_user', $id_user);
            })
            ->where('is_complete', 1)
            ->where(function($query) {
                $query->where('redemption_status', '<>', 1)
                    ->orWhereNull('redemption_status');
            })
            ->where('publish_start', '<=', date('Y-m-d H:i:s'))
            ->where('publish_end', '>=', date('Y-m-d H:i:s'))
            ->count();
        if ($myQuest) {
            return [
                'status' => 'success',
                'result' => [
                    'total_quest' => $myQuest,
                ]
            ];
        } else {
            return [
                'status' => 'fail',
                'messages' => ['Belum ada misi yang berjalan']
            ];
        }
    }
}
