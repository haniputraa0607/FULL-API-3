<?php

namespace Modules\Users\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\User;
use App\Http\Models\Setting;
use App\Http\Models\LogPoint;
use App\Http\Models\LogBalance;

use Modules\Balance\Http\Controllers\NewTopupController;

use App\Lib\MyHelper;
use DB;
use Hash;

class ApiWebviewUser extends Controller
{
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

            $user = User::where('phone', $phone)->first();

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
            $cashback_percentage = 0;
            if ($user->memberships->count() > 0) {
                if (isset($user->memberships->membership_name)) {
                    $level = $user->memberships->membership_name;
                }
                if (isset($user->memberships->benefit_point_multiplier)) {
                    $point_percentage = $user->memberships->benefit_point_multiplier;
                }
                if (isset($user->memberships->benefit_cashback_multiplier)) {
                    $cashback_percentage = $user->memberships->benefit_cashback_multiplier;
                }
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

            // add cashback
            $setting_cashback = Setting::where('key', 'cashback_conversion_value')->first();
            $balance_before = LogBalance::where('id_user', $user->id)->sum('balance');
            $balance_after = $balance_before + $complete_profile_cashback;

            // check balance data from hashed text
            $newTopupController = new NewTopupController();
            $checkHashBefore = $newTopupController->checkHash('log_balances', $user->id);
            if (!$checkHashBefore) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Invalid transaction data']]);
            }

            $log_cashback = [
                'id_user'                        => $user->id,
                'balance'                        => $complete_profile_cashback,
                'balance_before'                 => $balance_before,
                'balance_after'                  => $balance_after,
                'id_reference'                   => null,
                'source'                         => 'Completing User Profile',
                'grand_total'                    => 0,
                'ccashback_conversion'           => $setting_cashback->value,
                'membership_level'               => $level,
                'membership_cashback_percentage' => $cashback_percentage
            ];
            $insert_log_cashback = LogBalance::create($log_cashback);

            // hash the inserted data
            $dataHashBalance = [
                'id_log_balance'                 => $insert_log_cashback->id_log_balance,
                'id_user'                        => $insert_log_cashback->id_user,
                'balance'                        => $insert_log_cashback->balance,
                'balance_before'                 => $insert_log_cashback->balance_before,
                'balance_after'                  => $insert_log_cashback->balance_after,
                'id_reference'                   => $insert_log_cashback->id_reference,
                'source'                         => $insert_log_cashback->source,
                'grand_total'                    => $insert_log_cashback->grand_total,
                'ccashback_conversion'           => $insert_log_cashback->ccashback_conversion,
                'membership_level'               => $insert_log_cashback->membership_level,
                'membership_cashback_percentage' => $insert_log_cashback->membership_cashback_percentage
            ];
            $encodeCheck = json_encode($dataHashBalance);
            $enc = Hash::make($encodeCheck);

            // update enc column
            $insert_log_cashback->enc = $enc;
            $insert_log_cashback->update();

            if ( !($update && $insert_log_point && $insert_log_cashback) ) {
                DB::rollback();
                return [
                    'status' => 'fail',
                    'messages' => 'Failed to save data'
                ];
            }
        DB::commit();

        return MyHelper::checkUpdate($update);
    }

    public function completeProfileLater(Request $request)
    {
        $post = $request->json()->all();

        $user = User::where('phone', $post['phone'])->first();
        $data = [
            'count_complete_profile' => $user->count_complete_profile + 1,
            'last_complete_profile'  => date('Y-m-d H:i:s')
        ];

        $update = User::where('phone', $post['phone'])->update($data);

        return [
            'status' => 'success'
        ];
    }
}
