<?php

namespace Modules\Deals\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\Deals\Http\Requests\Deals\Create;
use Modules\Deals\Http\Controllers\ApiDeals;

use App\Http\Models\Deal;
use App\Http\Models\DealsOutlet;
use App\Http\Models\DealsSubscription;

use App\Lib\MyHelper;
use DB;

class ApiDealsSubscription extends Controller
{
    
    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create(Create $request)
    {
        $post = $request->json()->all();
        if (isset($post['voucher_subscriptions'])) { 
            $voucher_subs = $post['voucher_subscriptions'];
            unset($post['voucher_subscriptions']);
        }

        $data = $this->checkInput($post);

        DB::beginTransaction();
        $save = Deal::create($data);

        if ($save) {
            if (isset($data['id_outlet'])) {
                $apiDeals = new ApiDeals();
                $saveOutlet = $apiDeals->saveOutlet($save->id_deals, $data['id_outlet']);

                if (!$saveOutlet) {
                    DB::rollback();
                    return [
                        'status'   => 'fail',
                        'messages' => 'Failed to save data.'
                    ];
                }
            }

            // save voucher_subscriptions
            $subscriptions = [];
            foreach ($voucher_subs as $key => $subscription) {
                switch ($subscription['deals_promo_id_type']) {
                    case 'promoid':
                        $promo_value = $subscription['deals_promo_id_promoid'];
                        break;
                    case 'nominal':
                        $promo_value = $subscription['deals_promo_id_nominal'];
                        break;
                    case 'free item':
                        $promo_value = $subscription['deals_promo_id_free_item'];
                        break;
                    default:
                        $promo_value = '';
                        break;
                }

                $item = [
                    'id_deals' => $save->id_deals,
                    'promo_type' => $subscription['deals_promo_id_type'],
                    'promo_value' => $promo_value,
                    'total_voucher' => $subscription['total_voucher'],
                    'voucher_start' => $subscription['voucher_start'],
                    'voucher_end' => $subscription['voucher_end'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                array_push($subscriptions, $item);
            }
            // insert bulk
            $save_subscription = DealsSubscription::insert($subscriptions);

            if ($save_subscription) {
                DB::commit();
            }
        }
        else {
            DB::rollback();
        }

        return response()->json(MyHelper::checkCreate($save));
    }

    private function checkInput($post)
    {
        $data = $post;
        if (isset($post['deals_image'])) { 
            $path = "img/deals/";
            $upload = MyHelper::uploadPhotoStrict($post['deals_image'], $path, 300, 300);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $data['deals_image'] = $upload['path'];
            }
            else {
                $result = [
                    'error'    => 1,
                    'status'   => 'fail',
                    'messages' => ['fail upload image']
                ];

                return $result;
            }
        }
        if (!isset($post['deals_total_voucher'])) { 
            $data['deals_total_voucher'] = 0;
        }
        if (isset($post['deals_start'])) { 
            $data['deals_start'] = date('Y-m-d H:i:s', strtotime($post['deals_start']));
        }
        if (isset($post['deals_end'])) { 
            $data['deals_end'] = date('Y-m-d H:i:s', strtotime($post['deals_end']));
        }
        if (isset($post['deals_publish_start'])) { 
            $data['deals_publish_start'] = date('Y-m-d H:i:s', strtotime($post['deals_publish_start']));
        }
        if (isset($post['deals_publish_end'])) { 
            $data['deals_publish_end'] = date('Y-m-d H:i:s', strtotime($post['deals_publish_end']));
        }

        // ---------------------------- DURATION
        if (empty($post['deals_voucher_duration']) || is_null($post['deals_voucher_duration'])) {
            $data['deals_voucher_duration'] = null;
        }
        // ---------------------------- EXPIRED
        if (empty($post['deals_voucher_expired']) || is_null($post['deals_voucher_expired'])) {
            $data['deals_voucher_expired'] = null;
        }
        // ---------------------------- POINT
        if (empty($post['deals_voucher_price_point']) || is_null($post['deals_voucher_price_point'])) {
            $data['deals_voucher_price_point'] = null;
        }
        // ---------------------------- CASH
        if (empty($post['deals_voucher_price_cash']) || is_null($post['deals_voucher_price_cash'])) {
            $data['deals_voucher_price_cash'] = null;
        }

        return $data;
    }


    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    {
        $post = $request->json()->all();
        $id_deals = $post['id_deals'];
        
        unset($post['prices_by']);

        if (isset($post['voucher_subscriptions'])) { 
            $voucher_subs = $post['voucher_subscriptions'];
            unset($post['voucher_subscriptions']);
        }
        if (isset($post['id_outlet'])) { 
            $id_outlets = $post['id_outlet'];
            unset($post['id_outlet']);
        }

        $data = $this->checkInput($post);

        DB::beginTransaction();
        $update = Deal::where('id_deals', $id_deals)->update($data);

        if ($update) {
            if (isset($data['id_outlet'])) {
                $apiDeals = new ApiDeals();

                $apiDeals->deleteOutlet($id_deals);
                $saveOutlet = $apiDeals->saveOutlet($update->id_deals, $id_outlets);

                if (!$saveOutlet) {
                    DB::rollback();
                    return [
                        'status'   => 'fail',
                        'messages' => 'Failed to update data.'
                    ];
                }
            }

            // save voucher_subscriptions
            $id_subscriptions = [];
            foreach ($voucher_subs as $key => $subscription) {
                $id = "";
                if (isset($subscription['id_deals_subscription'])) {
                    $id = $subscription['id_deals_subscription'];
                    unset($subscription['id_deals_subscription']);
                }

                switch ($subscription['deals_promo_id_type']) {
                    case 'promoid':
                        $promo_value = $subscription['deals_promo_id_promoid'];
                        break;
                    case 'nominal':
                        $promo_value = $subscription['deals_promo_id_nominal'];
                        break;
                    case 'free item':
                        $promo_value = $subscription['deals_promo_id_free_item'];
                        break;
                    default:
                        $promo_value = '';
                        break;
                }

                $item = [
                    'id_deals' => $id_deals,
                    'promo_type' => $subscription['deals_promo_id_type'],
                    'promo_value' => $promo_value,
                    'total_voucher' => $subscription['total_voucher'],
                    'voucher_start' => $subscription['voucher_start'],
                    'voucher_end' => $subscription['voucher_end']
                ];

                $update_subs = DealsSubscription::updateOrCreate(['id_deals_subscription'=>$id], $item);
                array_push($id_subscriptions, $update_subs->id_deals_subscription);

                if (!$update_subs) {
                    DB::rollback();
                    return [
                        'status'   => 'fail',
                        'messages' => 'Failed to update data.'
                    ];
                }
            }

            // check if deals subscription has voucher (already claimed)
            $check_subs = DealsSubscription::where('id_deals', $id_deals)->whereNotIn('id_deals_subscription', $id_subscriptions)->get();
            foreach ($check_subs as $key => $subscription) {
                if ($subscription->deals_vouchers->count() > 0) {
                    DB::rollback();
                    return [
                        'status'   => 'fail',
                        'messages' => 'Failed to delete Voucher because it already claimed by user.'
                    ];
                }
            }
            // delete
            $delete = DealsSubscription::where('id_deals', $id_deals)->whereNotIn('id_deals_subscription', $id_subscriptions)->delete();

            DB::commit();
        }
        else {
            DB::rollback();
        }

        return response()->json(MyHelper::checkUpdate($update));
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy($id_deals)
    {
        $deals = Deal::find($id_deals);
        if ($deals->deals_total_claimed > 0) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Deals already claimed']
            ]);
        }

        DB::beginTransaction();
            // delete outlet
            DealsOutlet::where('id_deals', $id_deals)->delete();
            // delete subscription
            DealsSubscription::where('id_deals', $id_deals)->delete();

            // delete image
            $delete_image = MyHelper::deletePhoto($deals->deals_image);

            $delete = Deal::where('id_deals', $id_deals)->delete();
            if (!$delete) {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Delete data failed']
                ]);
            }
        DB::commit();

        return ['status' => 'success', 'result' => $delete];
    }
}
