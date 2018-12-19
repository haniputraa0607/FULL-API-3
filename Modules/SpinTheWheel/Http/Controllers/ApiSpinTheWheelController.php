<?php

namespace Modules\SpinTheWheel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Deals\Http\Controllers\ApiDealsVoucher;

use App\Http\Models\Setting;
use App\Http\Models\SpinTheWheel;
use App\Http\Models\User;
use App\Http\Models\LogPoint;
use App\Http\Models\Deal;
use App\Http\Models\DealsUser;
use App\Http\Models\Membership;
use App\Http\Models\UsersMembership;
use App\Http\Models\SpinPrizeTemporary;

use App\Lib\MyHelper;
use DB;

class ApiSpinTheWheelController extends Controller
{
    /*
     * Spin The Wheel is Deals with type Spin
    */

    public function getSetting()
    {
        $setting = Setting::where('key', 'spin_the_wheel_point')->first();
        if ($setting == null) {
            $data['spin_the_wheel_point'] = 0;
        }
        else{
            $data['spin_the_wheel_point'] = $setting->value;
        }

        $spin_weighted_items = SpinTheWheel::get()->toArray();
        $data['spin_weighted_items'] = $spin_weighted_items;

        return response()->json(MyHelper::checkGet($data));
    }

    public function setting(Request $request)
    {
        $post = $request->json()->all();
        
        $response = DB::transaction(function () use($post) {
            // insert spin point to setting
            $data_setting = [ 'key' => 'spin_the_wheel_point', 'value' => $post['spin_the_wheel_point'] ];
            $setting = Setting::updateOrCreate(['key' => 'spin_the_wheel_point'], $data_setting);

            // array for updateOrCreate id
            $id_spin_the_wheel = [];

            // update
            $items = $post['spin_weighted_items'];
            foreach ($items as $key => $item) {
                $id = "";
                if (isset($item['id_spin_the_wheel'])) {
                    $id = $item['id_spin_the_wheel'];
                    unset($item['id_spin_the_wheel']);
                }
                
                $check_expiry = Deal::where('id_deals', $item['id_deals'])->first();
                if ( isset($check_expiry->deals_voucher_expired)){
                    $expiry_date = date('Y-m-d', strtotime($check_expiry->deals_voucher_expired));
                    $today = date('Y-m-d');

                    if ($today >= $expiry_date) {
                        $response = [
                                'status'   => 'fail',
                                'messages' => 'Expired item can\'t be set for Spin. Please check item expiry date.'
                            ];
                        return $response;
                    }
                }

                $update_spin = SpinTheWheel::updateOrCreate(['id_spin_the_wheel'=>$id], $item);

                // get created or updated id
                array_push($id_spin_the_wheel, $update_spin->id_spin_the_wheel);
            }

            // delete
            $delete = SpinTheWheel::whereNotIn('id_spin_the_wheel', $id_spin_the_wheel)->delete();

            // check if fail
            if ( !($setting && $update_spin) ) {
                $response = [
                    'status'   => 'fail',
                    'messages' => 'Failed to save data.'
                ];
                return $response;
            }
            else{
                $response = ['status' => 'success'];
                return $response;
            }

        });

        return $response;
    }

    /**
     * Get items and temporary spin prize
     * 
     * Spin algorithm taken from https://stackoverflow.com/questions/28155800/organize-array-in-certain-way-roulette-wheel-items
     * @return selected item
     */
    public function getItems(Request $request)
    {
        $post = $request->json()->all();
        $user = User::where('phone', $post['phone'])->first();

        $spin_items = SpinTheWheel::with('deals')->orderBy('created_at')->get()->toArray();
        $setting_point_spin = Setting::where('key', 'spin_the_wheel_point')->first();

        $items = [];
        foreach ($spin_items as $key => $spin) {
            $items[$spin['id_deals']] = $spin['value'];
        }

        // total, needed to work out the weight
        $total = array_sum($items);
        
        // find out the weight somehow
        $items_w = [];
        foreach ($items as $key => $item) {
            $items_w[$key] = $item / $total * 100;
        }

        // get random prize (id_deals)
        $prize = $this->getRandomWeightedElement($items_w);

        // save temp prize
        $temp_prize = ["id_deals" => $prize, "id_user" => $user->id];
        $create = SpinPrizeTemporary::create($temp_prize);
        
        $deals = Deal::findOrFail( ['id_deals' => $prize] )->first()->toArray();

        $data['spin_point'] = $setting_point_spin->value;
        $data['spin_items'] = $spin_items;
        $data['spin_prize'] = $deals;

        return response()->json(MyHelper::checkGet($data));
    }

    // calc user point and get spin prize from temp prize
    public function spin(Request $request)
    {
        $post = $request->json()->all();

        $user = User::where('phone', $post['phone'])->first();

        $setting = Setting::where('key', 'spin_the_wheel_point')->first();
        $spin_the_wheel_point = $setting->value;

        // calc user point from log
        $user_point = LogPoint::where( ['id_user' => $user->id] )->sum('point');
        if (!$user_point) {
            $user_point = 0;
        }
        // calc point balance
        if ($user_point >= $spin_the_wheel_point) {
            $new_user_point = $user_point - $spin_the_wheel_point;
        }
        else{
            return ['status' => 'fail', 'messages' => 'Your point is not enough.'];
        }
        
        // get temp prize
        $temp_prize = SpinPrizeTemporary::with('deals')->where('id_user', $user->id)->latest()->first();
        $deals = $temp_prize->deals;

        // update point
        DB::beginTransaction();
            $setting_point_conversion = Setting::where('key', 'point_conversion_value')->first();
            $user_membership_level = null;
            $membership_point_percentage = 0;
            $user_membership = UsersMembership::where('id_user', $user->id)->first();

            if ($user_membership != null) {
                $user_membership_level = $user_membership->membership->membership_name;
                $membership_point_percentage = (int) $user_membership->membership->benefit_point_multiplier;
            }
            // log point
            $log_point = ['id_user' => $user->id,
                            'point' => -$spin_the_wheel_point,
                            'source' => 'spin the wheel',
                            'point_conversion' => $setting_point_conversion->value,
                            'membership_level' => $user_membership_level,
                            'membership_point_percentage' => $membership_point_percentage
                        ];
            $log = LogPoint::create($log_point);

            $user_data = ['id' => $user->id, 'points' => $new_user_point];
            $user_update = User::where('id', $user->id)->update($user_data);

            /* claim spin the wheel prize (deals voucher) */
            // create voucher
            $voucher = [
                'type' => 'generate',
                'id_deals' => $deals->id_deals,
                'total' => 1,
                'deals_type' => "Spin"
            ];
            $dealsVoucher = new ApiDealsVoucher();
            $dealsVoucher = $dealsVoucher->generateVoucher($deals->id_deals, 1, 1);

            // voucher user
            if ($dealsVoucher) {
                $claim = [
                    'id_deals_voucher' => $dealsVoucher->id_deals_voucher,
                    'id_user' => $user->id,
                    'claimed_at' => date('Y-m-d H:i:s'),
                    'voucher_price_point' => $spin_the_wheel_point,
                    'paid_status' => 'Completed'
                ];
                $dealsUser = DealsUser::create($claim);
            }

            // delete another temp prize by user id
            $delete_temp_prize = SpinPrizeTemporary::where('id_user', $user->id)->where('id_spin_prize_temporary', '!=', $temp_prize->id_spin_prize_temporary)->delete();

            // if not updated
            if ( !($log && $user_update && $dealsVoucher && $dealsVoucher) ) {
                DB::rollback();
                return ['status' => 'fail', 'messages' => 'Something went wrong. Please try again'];
            }
        DB::commit();

        $data = ['deals_user' => $dealsUser, 'user_point' => $new_user_point];
        return ['status' => 'success', 'result' => $data];
    }

    // This is the function that generates random numbers based on weigth.
    private function getRandomWeightedElement(array $weightedValues) {
        $rand = mt_rand(1, (int) array_sum($weightedValues));
        foreach ($weightedValues as $key => $value) {
            $rand -= $value;
            if ($rand <= 0) {
                return $key;
            }
        }
    }

}
