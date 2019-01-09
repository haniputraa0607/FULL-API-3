<?php

namespace Modules\Users\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\User;
use App\Http\Models\Setting;
use App\Http\Models\LogPoint;
use App\Http\Models\LogBalance;

use Modules\Balance\Http\Controllers\BalanceController;

use App\Lib\MyHelper;
use DB;
use Hash;
use Auth;

class ApiWebviewUser extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }
    
    // update profile, point, balance
    public function completeProfile(Request $request)
    {
        $post = $request->json()->all();
        $phone = $post['phone'];
        unset($post['phone']);

        DB::beginTransaction();
            $update = User::where('phone', $phone)->update($post);
            if ( !$update ) {
                DB::rollback();
                return [
                    'status' => 'fail',
                    'messages' => 'Failed to save data'
                ];
            }

            $user = User::with('memberships')->where('phone', $phone)->first();

            // get point and cashback from setting
            $complete_profile_point = 0;
            $complete_profile_cashback = 0;
            $setting_profile_point = Setting::where('key', 'complete_profile_point')->first();
            $setting_profile_cashback = Setting::where('key', 'complete_profile_cashback')->first();
            if (isset($setting_profile_point->value)) {
                $complete_profile_point = $setting_profile_point->value;
            }
            if (isset($setting_profile_cashback->value)) {
                $complete_profile_cashback = $setting_profile_cashback->value;
            }

            // membership level
            $level = null;
            $point_percentage = 0;
            // $cashback_percentage = 0;
            $user_member = $user->toArray();
            if (isset($user_member['memberships'][0]['membership_name'])) {
                $level = $user_member['memberships'][0]['membership_name'];
            }
            if (isset($user_member['memberships'][0]['benefit_point_multiplier'])) {
                $point_percentage = $user_member['memberships'][0]['benefit_point_multiplier'];
            }

            // add point
            $setting_point = Setting::where('key', 'point_conversion_value')->first();
            $log_point = [
                'id_user'                     => $user->id,
                'point'                       => $complete_profile_point,
                'id_reference'                => null,
                'source'                      => 'Completing User Profile',
                'grand_total'                 => 0,
                'point_conversion'            => $setting_point->value,
                'membership_level'            => $level,
                'membership_point_percentage' => $point_percentage
            ];
            $insert_log_point = LogPoint::create($log_point);

            // update user point
            $new_user_point = LogPoint::where('id_user', $user->id)->sum('point');
            $user_update = $user->update(['points' => $new_user_point]);

            /* add cashback */
            $balance_nominal = $complete_profile_cashback;
            // add log balance & update user balance
            $balanceController = new BalanceController();
            $addLogBalance = $balanceController->addLogBalance($user->id, $balance_nominal, null, "Completing User Profile", 0);

            if ( !($user_update && $insert_log_point && $addLogBalance) ) {
                DB::rollback();
                return [
                    'status' => 'fail',
                    'messages' => 'Failed to save data'
                ];
            }
        DB::commit();

        return MyHelper::checkUpdate($update);
    }

    public function completeProfileLater()
    {
        $user = Auth::user();

        $data = [
            'count_complete_profile' => $user->count_complete_profile + 1,
            'last_complete_profile'  => date('Y-m-d H:i:s')
        ];

        $update = User::where('phone', $user->phone)->update($data);

        return MyHelper::checkUpdate($update);
    }
}
