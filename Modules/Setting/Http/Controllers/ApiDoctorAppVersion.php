<?php

namespace Modules\Setting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Setting\Http\Requests\Version\VersionList;
use App\Http\Models\Setting;
use Modules\Setting\Entities\Version;
use App\Lib\MyHelper;
use DB;

class ApiDoctorAppVersion extends Controller
{
    public function index(VersionList $request)
    {
        /*Start check status maintenance mode for apps*/
        $getMaintenance = Setting::where('key', 'maintenance_mode')->first();
        if ($getMaintenance && $getMaintenance['value'] == 1) {
            $dt = (array)json_decode($getMaintenance['value_text']);
            $message = $dt['message'];
            if ($dt['image'] != "") {
                $url_image = config('url.storage_url_api') . $dt['image'];
            } else {
                $url_image = config('url.storage_url_api') . 'img/maintenance/default.png';
            }
            return response()->json([
                'status' => 'fail',
                'messages' => [$message],
                'maintenance' => config('url.api_url') . "api/maintenance-mode",
                'data_maintenance' => [
                    'url_image' => $url_image,
                    'text' => $message
                ]
            ], 200);
        }
        /*=======================End====================*/
        $post = $request->json()->all();
        $dbSetting = Setting::where('key', 'like', 'version_%')->get()->toArray();
        $dbDevice = Version::select('app_type', 'app_version')->orderBy('app_version', 'desc')->where('rules', '1')->get()->toArray();

        if (empty($dbDevice)) {
            return response()->json(['status' => 'success', 'message' => 'Belum ada pengaturan versi untuk aplikasi']);
        }

        $setting = array(
            "version_image_doctor_mobile" => "error_default_doctor_app_version_image_mobile.png",
            "version_text_alert_mobile" => "Update aplikasi ke version $post[version]",
            "version_text_button_mobile" => "Update",
            "version_playstore" => "",
            "version_appstore" => "",
            "version_image_outlet" => "error_default_doctor_app_version_image_mobile.png",
            "version_text_alert_outlet" => "Update aplikasi ke version $post[version]",
            "version_text_button_outlet" => "Update",
            "version_outletstore" => ""
        );
        foreach ($dbSetting as $val) {
            $setting[$val['key']] = $val['value'];
        }
        $setting['Device'] = $dbDevice;
        $device = null;
        if (isset($post['device'])) {
            $device = $post['device'];
        } else {
            $agent = $_SERVER['HTTP_USER_AGENT'];
            if (stristr($agent, 'okhttp')) {
                $device = 'android';
            }
            if (stristr($agent, 'android')) {
                $device = 'android';
            }
            if (stristr($agent, 'ios')) {
                $device = 'ios';
            }
        }
        if ($device != null) {
            if ($device == 'android') {
                $compare_version = [];
                foreach ($setting['Device'] as $value) {
                    if (in_array('DoctorAndroid', $value)) {
                        $value['app_type'] = strtolower($value['app_type']);
                        $compare_version[] = $value;
                    }
                }
                for ($i = 0; $i < count($compare_version); $i++) {
                    if ($post['version'] == $compare_version[$i]['app_version']) {
                        return response()->json(['status' => 'success']);
                    }
                }
                $versionRec = array_shift($compare_version);
                $setting['version_text_alert_doctor_mobile'] = str_replace('%version_app%', $versionRec['app_version'], $setting['version_text_alert_doctor_mobile']);
                return response()->json([
                    'status' => 'fail',
                    'result' => [
                        'image' => config('url.storage_url_api') . $setting['version_image_doctor_mobile'],
                        'text' => $setting['version_text_alert_doctor_mobile'],
                        'button_text' => $setting['version_text_button_doctor_mobile'],
                        'button_url' => $setting['version_playstore']
                    ],
                ]);
            }
            if ($device == 'ios') {
                $compare_version = [];
                foreach ($setting['Device'] as $value) {
                    if (in_array('DoctorIOS', $value)) {
                        $value['app_type'] = strtolower($value['app_type']);
                        $compare_version[] = $value;
                    }
                }
                for ($i = 0; $i < count($compare_version); $i++) {
                    if ($post['version'] == $compare_version[$i]['app_version']) {
                        return response()->json(['status' => 'success']);
                    }
                }
                $versionRec = array_shift($compare_version);
                $setting['version_text_alert_doctor_mobile'] = str_replace('%version_app%', $versionRec['app_version'], $setting['version_text_alert_doctor_mobile']);
                return response()->json([
                    'status' => 'fail',
                    'result' => [
                        'image' => config('url.storage_url_api') . $setting['version_image_doctor_mobile'],
                        'text' => $setting['version_text_alert_doctor_mobile'],
                        'button_text' => $setting['version_text_button_doctor_mobile'],
                        'button_url' => $setting['version_appstore']
                    ],
                ]);
            }
        } else {
            return response()->json(['status' => 'fail', 'message' => 'Device tidak teridentifikasi']);
        }
    }
}
