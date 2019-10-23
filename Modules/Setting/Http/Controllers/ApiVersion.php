<?php

namespace Modules\Setting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use Modules\Setting\Entities\Version;

use App\Lib\MyHelper;
use DB;

class ApiVersion extends Controller
{
    function getVersion()
    {
        $display = Setting::where('key', 'LIKE', 'version%')->get();
        $android = Version::select('app_type', 'app_version', 'rules')->orderBy('app_version', 'desc')->where('app_type', 'Android')->get()->toArray();
        $ios = Version::select('app_type', 'app_version', 'rules')->orderBy('app_version', 'desc')->where('app_type', 'IOS')->get()->toArray();
        $outlet = Version::select('app_type', 'app_version', 'rules')->orderBy('app_version', 'desc')->where('app_type', 'OutletApp')->get()->toArray();
        $result = [];
        foreach ($display as $data) {
            $result[$data['key']] = $data['value'];
        }
        $result['Android'] = $android;
        $result['IOS'] = $ios;
        $result['OutletApp'] = $outlet;
        return response()->json(MyHelper::checkGet($result));
    }

    function updateVersion(Request $request)
    {
        $post = $request->json()->all();
        DB::beginTransaction();
        foreach ($post as $key => $data) {
            if ($key == 'Display') {
                foreach ($data as $keyData => $value) {
                    if ($keyData == 'version_image_mobile' || $keyData == 'version_image_outlet') {
                        if (!file_exists('img/setting/version/')) {
                            mkdir('img/setting/version/', 0777, true);
                        }
                        $upload = MyHelper::uploadPhoto($value, 'img/setting/version/');
                        if (isset($upload['status']) && $upload['status'] == "success") {
                            $value = $upload['path'];
                        } else {
                            return false;
                        }
                    }
                    $setting = Setting::updateOrCreate(['key' => $keyData], ['value' => $value]);
                    if (!$setting) {
                        DB::rollBack();
                        return response()->json(['status' => 'fail', 'messages' => $setting]);
                    }
                }
                DB::commit();
                return response()->json(['status' => 'success']);
            } else {
                $store = array_slice($data, -2, 2);
                foreach ($store as $keyStore => $value) {
                    $setting = Setting::updateOrCreate(['key' => $keyStore], ['value' => $value]);
                }
                if (!$setting) {
                    DB::rollBack();
                    return response()->json(['status' => 'fail', 'messages' => $setting]);
                }
                $sumVersion = array_pop($data);
                array_pop($data);
                // dd($data);
                if ($data == null) {
                    Version::where('app_type', $key)->delete();
                } else {
                    foreach ($data as $value) {
                        $reindex[] = $value;
                    }
                    for ($i = 0; $i < count($reindex); $i++) {
                        $reindex[$i]['app_type'] = $key;
                    }
                    foreach ($reindex as $value) {
                        if ($value['rules'] == 1) {
                            $checkData[] = $value;
                        }
                    }
                    if (count($checkData) > $sumVersion) {
                        asort($checkData);
                        $lastVersion = array_slice($checkData, -$sumVersion, $sumVersion);
                        $versionLast = array_column($lastVersion, 'app_version');
                    }
                    Version::where('app_type', $key)->delete();
                    foreach ($reindex as $value) {
                        if (!isset($versionLast)) {
                            $version = new Version;
                            $version->app_version = $value['app_version'];
                            $version->app_type = $value['app_type'];
                            $version->rules = $value['rules'];
                            $version->save();
                        } else {
                            if (in_array($value['app_version'], $versionLast)) {
                                $version = new Version;
                                $version->app_version = $value['app_version'];
                                $version->app_type = $value['app_type'];
                                $version->rules = $value['rules'];
                                $version->save();
                            } else {
                                $version = new Version;
                                $version->app_version = $value['app_version'];
                                $version->app_type = $value['app_type'];
                                $version->rules = 0;
                                $version->save();
                            }
                        }
                    }
                }
                DB::commit();
                return response()->json(['status' => 'success']);
            }
        }
    }
}
