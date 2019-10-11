<?php

namespace Modules\Setting\Http\Controllers;

use App\Http\Models\Setting;
use App\Http\Models\User;
use App\Http\Models\LogPoint;
use App\Http\Models\LogBalance;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Level;
use App\Http\Models\Outlet;
use App\Http\Models\Faq;
use App\Http\Models\OutletHoliday;
use App\Http\Models\Holiday;
use App\Http\Models\DateHoliday;

use Modules\Setting\Http\Requests\Level\LevelList;
use Modules\Setting\Http\Requests\Level\LevelCreate;
use Modules\Setting\Http\Requests\Level\LevelEdit;
use Modules\Setting\Http\Requests\Level\LevelUpdate;
use Modules\Setting\Http\Requests\Level\LevelDelete;

use Modules\Setting\Http\Requests\Holiday\HolidayList;
use Modules\Setting\Http\Requests\Holiday\HolidayCreate;
use Modules\Setting\Http\Requests\Holiday\HolidayStore;
use Modules\Setting\Http\Requests\Holiday\HolidayEdit;
use Modules\Setting\Http\Requests\Holiday\HolidayUpdate;
use Modules\Setting\Http\Requests\Holiday\HolidayDelete;

use Modules\Setting\Http\Requests\Faq\FaqCreate;
use Modules\Setting\Http\Requests\Faq\FaqList;
use Modules\Setting\Http\Requests\Faq\FaqEdit;
use Modules\Setting\Http\Requests\Faq\FaqUpdate;
use Modules\Setting\Http\Requests\Faq\FaqDelete;

use Modules\Setting\Http\Requests\SettingList;
use Modules\Setting\Http\Requests\SettingEdit;
use Modules\Setting\Http\Requests\SettingUpdate;
use Modules\Setting\Http\Requests\DatePost;

use Modules\Setting\Http\Requests\Version\VersionList;
use Modules\Setting\Entities\Version;

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;

class ApiSetting extends Controller
{

    public $saveImage = "img/";
    public $endPoint;

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->endPoint = env('S3_URL_API');
    }
    public function emailUpdate(Request $request) {
		$data = $request->json()->all();
		if (isset($data['email_logo'])) {
            $upload = MyHelper::uploadPhoto($data['email_logo'], $this->saveImage, 300);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $data['email_logo'] = $upload['path'];
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

		foreach($data as $key => $row){
            $setting = Setting::updateOrCreate(['key' => $key], ['value' => $row]);
		}
		return response()->json(MyHelper::checkUpdate($setting));
	}

	public function Navigation() {
		$setting_logo = Setting::where('key','like','app_logo%')->get()->toArray();
		$setting_navbar = Setting::where('key','like','app_navbar%')->get()->toArray();
		$setting_sidebar = Setting::where('key','like','app_sidebar%')->get()->toArray();

		$set = array();
		foreach($setting_logo as $setting){
			array_push($set, array($setting['key'] => $this->endPoint.$setting['value']));
		}

		foreach($setting_navbar as $setting){
			array_push($set, array($setting['key'] => $setting['value']));
		}

		foreach($setting_sidebar as $setting){
			array_push($set, array($setting['key'] => $setting['value']));
		}

		return response()->json(MyHelper::checkGet($set));
	}

    public function NavigationLogo() {
		$setting_logo = Setting::where('key','like','app_logo%')->get()->toArray();

		$set = array();
		foreach($setting_logo as $setting){
			array_push($set, array($setting['key'] => $this->endPoint.$setting['value']."?"));
		}

		return response()->json(MyHelper::checkGet($set));
	}

	public function NavigationNavbar() {
		$setting_navbar = Setting::where('key','like','app_navbar%')->get()->toArray();

		$set = array();
		foreach($setting_navbar as $setting){
			array_push($set, array($setting['key'] => $setting['value']));
		}

		return response()->json(MyHelper::checkGet($set));
	}

	public function NavigationSidebar() {
		$setting_sidebar = Setting::where('key','like','app_sidebar%')->get()->toArray();

		$set = array();

		foreach($setting_sidebar as $setting){
			array_push($set, array($setting['key'] => $setting['value']));
		}

		return response()->json(MyHelper::checkGet($set));
	}

    public function settingCourier() {
        $setting = Setting::get()->toArray();

        return response()->json(MyHelper::checkGet($setting));
    }

    public function settingList(SettingList $request){
        $data = $request->json()->all();

		if(isset($data['key']))
        $setting = Setting::where('key', $data['key'])->first();

		if(isset($data['key-like']))
        $setting = Setting::where('key', 'like', "%".$data['key-like']."%")->get()->toArray();

        return response()->json(MyHelper::checkGet($setting));
    }

    public function settingEdit(SettingEdit $request){
        $id = $request->json('id_setting');

        $setting = Setting::where('id_setting', $id)->get()->toArray();

        return response()->json(MyHelper::checkGet($setting));

    }

    public function settingUpdate(SettingUpdate $request){
        $post = $request->json()->all();
        $id = $request->json('id_setting');

        $update = Setting::where('id_setting', $id)->update($post);

        return response()->json(MyHelper::checkUpdate($update));
    }

    public function pointResetUpdate(Request $request, $type){
        $post = $request->json()->all();

        if(isset($post['setting'])){
            DB::beginTransaction();

            $idSetting = [];
            foreach($post['setting'] as $key => $value){
                if($value['value']){
                    if($value['id_setting']){
                        $save = Setting::where('id_setting', $value['id_setting'])->update(['value' => $value['value']]);
                        if(!$save){
                            DB::rollback();
                            return response()->json(MyHelper::checkUpdate($save));
                        }

                        $idSetting[] = $value['id_setting'];
                    }else{
                        $save = Setting::create([
                            'key' => $type,
                            'value' => $value['value']
                        ]);

                        if(!$save){
                            DB::rollback();
                            return response()->json(MyHelper::checkCreate($save));
                        }

                        $idSetting[] = $save['id_setting'];
                    }
                }
            }

            $delete = Setting::where('key', $type)->whereNotIn('id_setting', $idSetting)->delete();

            DB::commit();
            return response()->json(['status' => 'success']);
        }else{
            $delete = Setting::where('key', $type)->delete();
        }

        return response()->json(['status' => 'success']);

    }

    public function cronPointReset(){
        $user = User::get();

        //point reset
        $setting = Setting::where('key', 'point_reset')->get();

        DB::beginTransaction();
        if($setting){
            foreach($setting as $date){
                if($date['value'] == date('d F')){
                    foreach($user as $datauser){
                        $totalPoint = LogPoint::where('id_user', $datauser['id'])->sum('point');
                        if($totalPoint){
                            $dataLog = [
                                'id_user'                     => $datauser['id'],
                                'point'                       => -$totalPoint,
                                'source'                      => 'Point Reset',
                            ];

                            $insertDataLog = LogPoint::create($dataLog);
                            if (!$insertDataLog) {
                                DB::rollback();
                                return response()->json([
                                    'status'    => 'fail',
                                    'messages'  => ['Insert Point Failed']
                                ]);
                            }

                            //update point user
                            $totalPoint = LogPoint::where('id_user',$datauser['id'])->sum('point');
                            $updateUserPoint = User::where('id', $datauser['id'])->update(['points' => $totalPoint]);
                            if (!$updateUserPoint) {
                                DB::rollback();
                                return response()->json([
                                    'status'    => 'fail',
                                    'messages'  => ['Update User Point Failed']
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();
        }

        //point reset
        $setting = Setting::where('key', 'balance_reset')->get();

        DB::beginTransaction();
        if($setting){
            foreach($setting as $date){
                if($date['value'] == date('d F')){
                    foreach($user as $datauser){
                        $totalBalance = LogBalance::where('id_user', $datauser['id'])->sum('balance');
                        if($totalBalance){
                            $dataLog = [
                                'id_user'                     => $datauser['id'],
                                'balance'                       => -$totalBalance,
                                'source'                      => 'Balance Reset',
                            ];

                            $insertDataLog = LogBalance::create($dataLog);
                            if (!$insertDataLog) {
                                DB::rollback();
                                return response()->json([
                                    'status'    => 'fail',
                                    'messages'  => ['Insert Balance Failed']
                                ]);
                            }

                            //update balance user
                            $totalBalance = LogBalance::where('id_user',$datauser['id'])->sum('balance');
                            $updateUserBalance = User::where('id', $datauser['id'])->update(['balance' => $totalBalance]);
                            if (!$updateUserBalance) {
                                DB::rollback();
                                return response()->json([
                                    'status'    => 'fail',
                                    'messages'  => ['Update User Balance Failed']
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success'
            ]);
        }

    }

    public function levelList(LevelList $request) {
        $post = $request->json()->all();

        $levelList = Level::orderBy('id_level', 'ASC')->get()->toArray();

        return response()->json(MyHelper::checkGet($levelList));
    }

    public function levelCreate(LevelCreate $request) {
        $post = $request->json()->all();

        $createLevel = Level::create($post);

        return response()->json(MyHelper::checkCreate($createLevel));
    }

    public function levelEdit(LevelEdit $request) {
        $id_level = $request->json('id_level');

        $level = Level::where('id_level', $id_level)->first();

        return response()->json(MyHelper::checkGet($level));
    }

    public function levelUpdate(LevelUpdate $request) {
        $post = $request->json()->all();

        $id_level = $request->json('id_level');

        $update = Level::where('id_level', $id_level)->update($post);

        return response()->json(MyHelper::checkUpdate($update));
    }

    public function levelDelete(LevelDelete $request) {
        $id_level = $request->json('id_level');

        $delete = Level::where('id_level', $id_level)->delete();

        return response()->json(MyHelper::checkDelete($delete));
    }

    public function holidayList(HolidayList $request) {
        $post = $request->json()->all();

        $holidayList = Holiday::select('holidays.id_holiday', 'holidays.holiday_name', 'date_holidays.day','holidays.created_at')
								->join('date_holidays','date_holidays.id_holiday','=','holidays.id_holiday')
								->orderBy('id_holiday', 'ASC')
								->get()
								->toArray();

        return response()->json(MyHelper::checkGet($holidayList));
    }

    public function holidayCreate(HolidayCreate $request) {
        $post = $request->json()->all();

        $outlet = Outlet::orderBy('id_outlet', 'ASC')->get()->toArray();

        return response()->json(MyHelper::checkGet($outlet));
    }

    public function holidayStore(HolidayStore $request) {
        $post = $request->json()->all();

        $holiday = [
            'holiday_name'  => $post['holiday_name']
        ];

        DB::beginTransaction();
        $insertHoliday = Holiday::create($holiday);

        if ($insertHoliday) {
            $dateHoliday = [];
            $day = $post['day'];

            foreach ($day as $value) {
                $dataDay = [
                    'id_holiday'    => $insertHoliday['id'],
                    'day'           => $value['day'],
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s')
                ];

                array_push($dateHoliday, $dataDay);
            }

            $insertDateHoliday = DateHoliday::insert($dateHoliday);

            if ($insertDateHoliday) {
                $outletHoliday = [];
                $outlet = $post['id_outlet'];

                foreach ($outlet as $ou) {
                    $dataOutlet = [
                        'id_holiday'    => $insertHoliday['id'],
                        'id_outlet'     => $ou,
                        'created_at'    => date('Y-m-d H:i:s'),
                        'updated_at'    => date('Y-m-d H:i:s')
                    ];

                    array_push($outletHoliday, $dataOutlet);
                }

                $insertOutletHoliday = OutletHoliday::insert($outletHoliday);

                if ($insertOutletHoliday) {
                    DB::commit();
                    return response()->json([
                        'status'    => 'success'
                    ]);

                } else {
                    DB::rollBack();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'      => [
                            'Data is invalid !!!'
                        ]
                    ]);
                }
            } else {
                DB::rollBack();
                return response()->json([
                    'status'    => 'fail',
                    'messages'      => [
                        'Data is invalid !!!'
                    ]
                ]);
            }

        } else {
            DB::rollBack();
            return response()->json([
                'status'    => 'fail',
                'messages'      => [
                    'Data is invalid !!!'
                ]
            ]);
        }
    }

    public function holidayEdit(HolidayEdit $request) {
        $id_holiday = $request->json('id_holiday');

        $data = Holiday::where('id_holiday', $id_holiday)->with('dateHoliday')->first();
        $data['outlet'] = Outlet::orderBy('id_outlet', 'ASC')->get()->toArray();

        if (count($data) > 0) {
            $data['outletHoliday'] = OutletHoliday::where('id_holiday', $data['id_holiday'])->get();

            if ($data['outletHoliday']) {
                $outlet = [];

                foreach ($data['outletHoliday'] as $key => $ou) {
                    $data['outletHoliday'][$key]['outlet'] = Outlet::where('id_outlet', $ou['id_outlet'])->first();
                }
            } else {
                return response()->json([
                    'status'    => 'fail',
                    'messages'      => [
                        'Data is invalid !!!'
                    ]
                ]);
            }

        } else {
            return response()->json([
                    'status'    => 'fail',
                    'messages'      => [
                        'Data is invalid !!!'
                    ]
                ]);
        }

        return response()->json(MyHelper::checkGet($data));
    }

    public function holidayUpdate(HolidayUpdate $request) {
        $post = $request->json()->all();
        $holiday = [
            'holiday_name'  => $post['holiday_name']
        ];

        DB::beginTransaction();
        $updateHoliday = Holiday::where('id_holiday', $post['id_holiday'])->update($holiday);

        if ($updateHoliday) {
            $delete = DateHoliday::where('id_holiday', $post['id_holiday'])->delete();

            if ($delete) {
                $dateHoliday = [];
                $day = $post['day'];

                foreach ($day as $value) {
                    $dataDay = [
                        'id_holiday'    => $post['id_holiday'],
                        'day'           => $value['day'],
                        'created_at'    => date('Y-m-d H:i:s'),
                        'updated_at'    => date('Y-m-d H:i:s')
                    ];

                    array_push($dateHoliday, $dataDay);
                }

                $updateDateHoliday = DateHoliday::insert($dateHoliday);

                if ($updateDateHoliday) {
                    $deleteOutletHoliday = OutletHoliday::where('id_holiday', $post['id_holiday'])->delete();

                    if ($deleteOutletHoliday) {
                        $outletHoliday = [];
                        $outlet = $post['id_outlet'];

                        foreach ($outlet as $ou) {
                            $dataOutlet = [
                                'id_holiday'    => $post['id_holiday'],
                                'id_outlet'     => $ou,
                                'created_at'    => date('Y-m-d H:i:s'),
                                'updated_at'    => date('Y-m-d H:i:s')
                            ];

                            array_push($outletHoliday, $dataOutlet);
                        }

                        $insertOutletHoliday = OutletHoliday::insert($outletHoliday);

                        if ($insertOutletHoliday) {
                            DB::commit();
                            return response()->json([
                                'status'    => 'success'
                            ]);

                        } else {
                            DB::rollBack();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'      => [
                                    'Data is invalid !!!'
                                ]
                            ]);
                        }
                    } else {
                        DB::rollBack();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'      => [
                                'Data is invalid !!!'
                            ]
                        ]);
                    }

                } else {
                    DB::rollBack();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'      => [
                            'Data is invalid !!!'
                        ]
                    ]);
                }
            }
        }

        return response()->json(MyHelper::checkUpdate($update));
    }

    public function holidayDelete(HolidayDelete $request) {
        $id_holiday = $request->json('id_holiday');

        $delete = Holiday::where('id_holiday', $id_holiday)->delete();

        return response()->json(MyHelper::checkDelete($delete));
    }

    public function holidayDetail(HolidayDelete $request) {
        $id_holiday = $request->json('id_holiday');

        $detail = Holiday::where('id_holiday', $id_holiday)->with('dateHoliday')->first();

        if (count($detail) > 0) {
            $detail['outletHoliday'] = OutletHoliday::where('id_holiday', $detail['id_holiday'])->get();

            if ($detail['outletHoliday']) {
                $outlet = [];

                foreach ($detail['outletHoliday'] as $key => $ou) {
                    $detail['outletHoliday'][$key]['outlet'] = Outlet::where('id_outlet', $ou['id_outlet'])->first();
                }
            } else {
                return response()->json([
                    'status'    => 'fail',
                    'messages'      => [
                        'Data is invalid !!!'
                    ]
                ]);
            }

        } else {
            return response()->json([
                'status'    => 'fail',
                'messages'      => [
                    'Data is invalid !!!'
                ]
            ]);
        }

        return response()->json(MyHelper::checkGet($detail));
    }

    public function faqCreate(FaqCreate $request) {
        $post = $request->json()->all();

        $faq = Faq::create($post);

        return response()->json(MyHelper::checkCreate($faq));
    }

    public function faqList(FaqList $request) {
        $faqList = Faq::orderBy('id_faq', 'ASC')->get()->toArray();

        return response()->json(MyHelper::checkGet($faqList));
    }

    public function faqEdit(FaqEdit $request) {
        $id = $request->json('id_faq');

        $faq = Faq::where('id_faq', $id)->first();

        return response()->json(MyHelper::checkGet($faq));
    }

    public function faqUpdate(FaqUpdate $request) {
        $post = $request->json()->all();

        $update = Faq::where('id_faq', $post['id_faq'])->update($post);

        return response()->json(MyHelper::checkUpdate($update));
    }

    public function faqDelete(FaqDelete $request) {
        $id = $request->json('id_faq');

        $delete = Faq::where('id_faq', $id)->delete();

        return response()->json(MyHelper::checkDelete($delete));
    }

    public function date(DatePost $request) {
        $post = $request->json()->all();

        $setting = Setting::where('key', 'date_limit_reservation')->first();

        if (empty($setting)) {
            return response()->json([
                'status'    => 'fail',
                'messages'      => [
                    'Data is invalid !!!'
                ]
            ]);
        }

        $setting->value = $post['limit'];
        $setting->save();

        return response()->json(MyHelper::checkUpdate($setting));
    }

    public function settingEmail(Request $request){
        $post = $request->json()->all();

        DB::beginTransaction();
        if(isset($post['email_logo'])){
            $upload = MyHelper::uploadPhoto($post['email_logo'],'img',1000,'email_logo');
            if (isset($upload['status']) && $upload['status'] == "success") {
                $post['email_logo'] = $upload['path'];
            }
        }

        foreach ($post as $key => $value) {
            $save = Setting::updateOrCreate(['key' => $key], ['key' => $key, 'value' => $value]);
            if(!$save){
                break;
                DB::rollback();
            }
        }
        DB::commit();
        return response()->json(MyHelper::checkUpdate($save));
    }

    public function getSettingEmail(){
        $setting = array();

        $set = Setting::where('key', 'email_from')->first();
        if(!empty($set)){
            $setting['email_from'] = $set['value'];
        }else{
            $setting['email_from'] = null;
        }

        $set = Setting::where('key', 'email_sender')->first();
        if(!empty($set)){
            $setting['email_sender'] = $set['value'];
        }else{
            $setting['email_sender'] = null;
        }

        $set = Setting::where('key', 'email_reply_to')->first();
        if(!empty($set)){
            $setting['email_reply_to'] = $set['value'];
        }else{
            $setting['email_reply_to'] = null;
        }

        $set = Setting::where('key', 'email_reply_to_name')->first();
        if(!empty($set)){
            $setting['email_reply_to_name'] = $set['value'];
        }else{
            $setting['email_reply_to_name'] = null;
        }

        $set = Setting::where('key', 'email_cc')->first();
        if(!empty($set)){
            $setting['email_cc'] = $set['value'];
        }else{
            $setting['email_cc'] = null;
        }

        $set = Setting::where('key', 'email_cc_name')->first();
        if(!empty($set)){
            $setting['email_cc_name'] = $set['value'];
        }else{
            $setting['email_cc_name'] = null;
        }

        $set = Setting::where('key', 'email_bcc')->first();
        if(!empty($set)){
            $setting['email_bcc'] = $set['value'];
        }else{
            $setting['email_bcc'] = null;
        }

        $set = Setting::where('key', 'email_bcc_name')->first();
        if(!empty($set)){
            $setting['email_bcc_name'] = $set['value'];
        }else{
            $setting['email_bcc_name'] = null;
        }

        $set = Setting::where('key', 'email_logo')->first();
        if(!empty($set)){
            $setting['email_logo'] = $set['value'];
        }else{
            $setting['email_logo'] = null;
        }

        $set = Setting::where('key', 'email_logo_position')->first();
        if(!empty($set)){
            $setting['email_logo_position'] = $set['value'];
        }else{
            $setting['email_logo_position'] = null;
        }

        $set = Setting::where('key', 'email_copyright')->first();
        if(!empty($set)){
            $setting['email_copyright'] = $set['value'];
        }else{
            $setting['email_copyright'] = null;
        }

        $set = Setting::where('key', 'email_contact')->first();
        if(!empty($set)){
            $setting['email_contact'] = $set['value'];
        }else{
            $setting['email_contact'] = null;
        }
        return response()->json(MyHelper::checkGet($setting));
    }

    public function appLogo(Request $request) {
		$post = $request->json()->all();

        if(empty($post)){
            $key = array_pluck(Setting::where('key', 'LIKE', '%app_logo%')->get()->toArray(), 'key');
            $value = array_pluck(Setting::where('key', 'LIKE', '%app_logo%')->get()->toArray(), 'value');
            $defaultHome = array_combine($key, $value);
            if(isset($defaultHome['app_logo'])){
                $defaultHome['app_logo'] = $this->endPoint.$defaultHome['app_logo'];
            }
            return response()->json(MyHelper::checkGet($defaultHome));
        } else {
			if (isset($post['app_logo'])) {
				$image = Setting::where('key', 'app_logo')->first();

				if(isset($image['value']) && file_exists($image['value'])){
					unlink($image['value']);
				}
				$upload = MyHelper::uploadPhotoStrict($post['app_logo'], $this->saveImage."app/", 433, 318,'logo3x','.png');
				$upload = MyHelper::uploadPhotoStrict($post['app_logo'], $this->saveImage."app/", 304, 223,'logo2x','.png');
				$upload = MyHelper::uploadPhotoStrict($post['app_logo'], $this->saveImage."app/", 130, 96,'logo','.png');
				if (isset($upload['status']) && $upload['status'] == "success") {
					$post['app_logo'] = $upload['path'];
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

			return response()->json(['status'   => 'success']);
		}
	}

	public function appNavbar(Request $request) {
		$post = $request->json()->all();

        if(empty($post)){
            $key = array_pluck(Setting::where('key', 'LIKE', '%app_navbar%')->get()->toArray(), 'key');
            $value = array_pluck(Setting::where('key', 'LIKE', '%app_navbar%')->get()->toArray(), 'value');
            $defaultHome = array_combine($key, $value);
            return response()->json(MyHelper::checkGet($defaultHome));
        } else {
			foreach($post as $key => $value){
				$setting = Setting::where('key','=',$key)->update(['value' => $value]);
			}
			return response()->json(MyHelper::checkUpdate($setting));
		}
	}

	public function appSidebar(Request $request) {
		$post = $request->json()->all();

        if(empty($post)){
            $key = array_pluck(Setting::where('key', 'LIKE', '%app_sidebar%')->get()->toArray(), 'key');
            $value = array_pluck(Setting::where('key', 'LIKE', '%app_sidebar%')->get()->toArray(), 'value');
            $defaultHome = array_combine($key, $value);
            return response()->json(MyHelper::checkGet($defaultHome));
       } else {
			foreach($post as $key => $value){
				$setting = Setting::where('key','=',$key)->update(['value' => $value]);
			}
			return response()->json(MyHelper::checkUpdate($setting));
	   }
	}

    public function homeNotLogin(Request $request) {
        $post = $request->json()->all();

        if(empty($post)){
            $key = array_pluck(Setting::where('key', 'LIKE', '%default_home%')->get()->toArray(), 'key');
            $value = array_pluck(Setting::where('key', 'LIKE', '%default_home%')->get()->toArray(), 'value');
            $defaultHome = array_combine($key, $value);
            if(isset($defaultHome['default_home_image'])){
                $defaultHome['default_home_image_url'] = $this->endPoint.$defaultHome['default_home_image'];
            }
			if(isset($defaultHome['default_home_splash_screen'])){
                $defaultHome['default_home_splash_screen_url'] = $this->endPoint.$defaultHome['default_home_splash_screen'];
            }
            return response()->json(MyHelper::checkGet($defaultHome));
        }

        if (isset($post['default_home_image'])) {
            $image = Setting::where('key', 'default_home_image')->first();

            if(isset($image['value']) && file_exists($image['value'])){
                unlink($image['value']);
            }
            $upload = MyHelper::uploadPhotoStrict($post['default_home_image'], $this->saveImage, 1080, 270);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $post['default_home_image'] = $upload['path'];
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

		if (isset($post['default_home_splash_screen'])) {
            $image = Setting::where('key', 'default_home_splash_screen')->first();

            if(isset($image['value']) && file_exists($image['value'])){
                unlink($image['value']);
            }
            $upload = MyHelper::uploadPhotoStrict($post['default_home_splash_screen'], $this->saveImage, 1080, 1920,'splash','.jpg');

            if (isset($upload['status']) && $upload['status'] == "success") {
                $post['default_home_splash_screen'] = $upload['path'];
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

        DB::beginTransaction();
        foreach ($post as $key => $value) {
            $insert = [
                'key' => $key,
                'value' => $value
            ];
            $save = Setting::updateOrCreate(['key' => $key], $insert);
            if(!$save){
                return $insert;
                DB::rollBack();
                return response()->json([
                    'status'    => 'fail',
                    'messages'      => [
                        'Data is invalid !!!'
                    ]
                ]);
            }
        }
        DB::commit();
        return response()->json(MyHelper::checkUpdate($save));
    }

    public function settingWhatsApp(Request $request){
        $post = $request->json()->all();

        if(isset($post['api_key_whatsapp'])){
            $save = Setting::updateOrCreate(['key' => 'api_key_whatsapp'], ['value' => $post['api_key_whatsapp']]);
            if(!$save){
                return response()->json([
                    'status'    => 'fail',
                    'messages'      => [
                        'Update api key whatsApp failed.'
                    ]
                ]);
            }
            return response()->json(MyHelper::checkUpdate($save));
        }else{
            $setting = Setting::where('key', 'api_key_whatsapp')->first();
            return response()->json(MyHelper::checkGet($setting));
        }

    }

    /* complete profile */
    public function getCompleteProfile()
    {
        $key = array_pluck(Setting::where('key', 'LIKE', '%complete_profile%')->get()->toArray(), 'key');
        $value = array_pluck(Setting::where('key', 'LIKE', '%complete_profile%')->get()->toArray(), 'value');
        $complete_profiles = array_combine($key, $value);

        // get user profile success page content
        $value_text = Setting::where('key', 'complete_profile_success_page')->get()->pluck('value_text');
        if(isset($value_text[0]))
        $complete_profiles['complete_profile_success_page'] = $value_text[0];

        if (!isset($complete_profiles['complete_profile_popup'])) {
            $complete_profiles['complete_profile_popup'] = '';
        }
        if (!isset($complete_profiles['complete_profile_point'])) {
            $complete_profiles['complete_profile_point'] = '';
        }
        if (!isset($complete_profiles['complete_profile_cashback'])) {
            $complete_profiles['complete_profile_cashback'] = '';
        }
        if (!isset($complete_profiles['complete_profile_count'])) {
            $complete_profiles['complete_profile_count'] = '';
        }
        if (!isset($complete_profiles['complete_profile_interval'])) {
            $complete_profiles['complete_profile_interval'] = '';
        }
        // success page
        if (!isset($complete_profiles['complete_profile_success_page'])) {
            $complete_profiles['complete_profile_success_page'] = '';
        }

        return response()->json(MyHelper::checkGet($complete_profiles));
    }

    // update complete profile
    public function completeProfile(Request $request)
    {
        $post = $request->json()->all();

        $update[] = Setting::updateOrCreate(['key' => 'complete_profile_popup'], ['key' => 'complete_profile_popup', 'value' => $post['complete_profile_popup']]);
        $update[] = Setting::updateOrCreate(['key' => 'complete_profile_point'], ['key' => 'complete_profile_point', 'value' => $post['complete_profile_point']]);
        $update[] = Setting::updateOrCreate(['key' => 'complete_profile_cashback'], ['key' => 'complete_profile_cashback', 'value' => $post['complete_profile_cashback']]);
        $update[] = Setting::updateOrCreate(['key' => 'complete_profile_count'], ['key' => 'complete_profile_count', 'value' => $post['complete_profile_count']]);
        $update[] = Setting::updateOrCreate(['key' => 'complete_profile_interval'], ['key' => 'complete_profile_interval', 'value' => $post['complete_profile_interval']]);

        if (count($update) == 5) {
            return [
                'status' => 'success',
                'result' => $update
            ];
        }
        else {
            return [
                'status' => 'fail',
                'messages' => ['Some data may not saved.']
            ];
        }
    }

    public function completeProfileSuccessPage(Request $request)
    {
        $post = $request->json()->all();

        $update = Setting::updateOrCreate(['key' => 'complete_profile_success_page'], ['value_text' => $post['complete_profile_success_page']]);
        if ($update) {
            return [
                'status' => 'success',
                'result' => $update
            ];
        }
        else {
            return [
                'status' => 'fail',
                'messages' => ['Failed to save data.']
            ];
        }
    }

    public function faqWebview(Request $request)
    {
        $faq = Faq::get()->toArray();
        if (empty($faq)) {
            return response()->json([
                'status' => 'fail',
                'messages' => ['Faq is empty']
            ]);
        }

        return response()->json([
            'status' => 'success',
            'url' => env('VIEW_URL').'/setting/faq/webview'
        ]);
    }

    public function settingWebview(SettingList $request){
        $post = $request->json()->all();
        if(isset($post['data'])){
            $setting = Setting::where('key', $post['key'])->first();
            return response()->json(MyHelper::checkGet($setting));
        }

        return response()->json([
            'status' => 'success',
            'url' => env('VIEW_URL').'/setting/webview/'.$post['key']
        ]);

    }

    public function updateFreeDelivery(Request $request){
        $post = $request->json()->all();

        DB::beginTransaction();

        foreach($post as $key => $value){
            $data['key'] = $key;
            $data['value'] = $value;

            $update = Setting::updateOrCreate(['key' => $data['key']], $data);
            if (!$update) {
                DB::rollback();
                return response()->json(MyHelper::checkUpdate($update));
            }
        }

        DB::commit();
        return response()->json(MyHelper::checkUpdate($update));
    }

    public function updateGoSendPackage(Request $request){
        $post = $request->json()->all();

        $update = Setting::updateOrCreate(['key' => 'go_send_package_detail'], ['value' => $post['value']]);
        if (!$update) {
            DB::rollback();
            return response()->json(MyHelper::checkUpdate($update));
        }

        return response()->json(MyHelper::checkUpdate($update));
    }

    public function Version(VersionList $request)
    {
        $post = $request->json()->all();
        $dbSetting = Setting::where('key', 'like', 'version_%')->get()->toArray();
        $dbDevice = Version::select('app_type', 'app_version')->orderBy('app_version', 'desc')->where('rules', '1')->get()->toArray();
        $setting = array();
        foreach ($dbSetting as $val) {
            $setting[$val['key']] = $val['value'];
        }
        $setting['Device'] = $dbDevice;
        $device = null;
        if (isset($post['device'])) {
            $device = $post['device'];
        } else {
            $agent = $_SERVER['HTTP_USER_AGENT'];
            if (stristr($agent, 'okhttp')) $device = 'android';
            if (stristr($agent, 'android')) $device = 'android';
            if (stristr($agent, 'ios')) $device = 'ios';
        }
        if ($device != null) {
            if ($device == 'android') {
                foreach ($setting['Device'] as $value) {
                    if (in_array('Android', $value)) {
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
                $setting['version_text_alert_mobile'] = str_replace('%version_app%', $versionRec['app_version'], $setting['version_text_alert_mobile']);
                return response()->json([
                    'status' => 'fail',
                    'image' => env('AWS_URL') . $setting['version_image_mobile'],
                    'text' => $setting['version_text_alert_mobile'],
                    'button_text' => $setting['version_text_button_mobile'],
                    'button_url' => $setting['version_playstore']
                ]);
            }
            if ($device == 'ios') {
                foreach ($setting['Device'] as $value) {
                    if (in_array('IOS', $value)) {
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
                $setting['version_text_alert_mobile'] = str_replace('%version_app%', $versionRec['app_version'], $setting['version_text_alert_mobile']);
                return response()->json([
                    'status' => 'fail',
                    'image' => env('AWS_URL') . $setting['version_image_mobile'],
                    'text' => $setting['version_text_alert_mobile'],
                    'button_text' => $setting['version_text_button_mobile'],
                    'button_url' => $setting['version_appstore']
                ]);
            }
            if ($device == 'outlet') {
                foreach ($setting['Device'] as $value) {
                    if (in_array('OutletApp', $value)) {
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
                $setting['version_text_alert_outlet'] = str_replace('%version_app%', $versionRec['app_version'], $setting['version_text_alert_outlet']);
                return response()->json([
                    'status' => 'fail',
                    'image' => env('AWS_URL') . $setting['version_image_outlet'],
                    'text' => $setting['version_text_alert_outlet'],
                    'button_text' => $setting['version_text_button_outlet'],
                    'button_url' => $setting['version_outletstore']
                ]);
            }
        } else {
            return response()->json(['status' => 'fail', 'message' => 'Device tidak teridentifikasi']);
        }
    }

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

    public function viewTOS(){
        $setting = Setting::where('key', 'tos')->first();
        if($setting && $setting['value_text']){
            $data['value'] =preg_replace('/font face="[^;"]*(")?/', 'div class="seravek-light-font"' , $setting['value_text']);
            $data['value'] =preg_replace('/face="[^;"]*(")?/', '' , $data['value']);
        }else{
             $data['value'] = "";
        }

        return view('setting::tos', $data);

    }

    public function jobsList(Request $request){
        $post=$request->json()->all();
        $setting=Setting::where('key','jobs_list')->first();
        $data=[];
        if($setting&&$setting->value_text){
            try{
                $data=json_decode($setting->value_text);
            }catch(\Exception $e){
                $data=[];
            }
        }
        if($post['jobs_list']??false){
            $postedJobs=json_encode($post['jobs_list']);
            if($setting){
                $save=Setting::where('key','jobs_list')->update(['value_text'=>$postedJobs]);
            }else{
                $save=Setting::create(['key'=>'jobs_list','value_text'=>$postedJobs]);
            }
            if($save){
                return ['status'=>'success','result'=>json_decode($postedJobs)];
            }else{
                return ['status'=>'fail','messages'=>'Something went wrong'];
            }
        }else{
            return MyHelper::checkGet($data);
        }
    }

    public function celebrateList(Request $request){
        $post=$request->json()->all();
        $setting=Setting::where('key','celebrate_list')->first();
        $data=[];
        if($setting&&$setting->value_text){
            try{
                $data=json_decode($setting->value_text);
            }catch(\Exception $e){
                $data=[];
            }
        }
        if($post['celebrate_list']??false){
            $postedCelebrate=json_encode($post['celebrate_list']);
            if($setting){
                $save=Setting::where('key','celebrate_list')->update(['value_text'=>$postedCelebrate]);
            }else{
                $save=Setting::create(['key'=>'celebrate_list','value_text'=>$postedCelebrate]);
            }
            if($save){
                return ['status'=>'success','result'=>json_decode($postedCelebrate)];
            }else{
                return ['status'=>'fail','messages'=>'Something went wrong'];
            }
        }else{
            return MyHelper::checkGet($data);
        }
    }

    public function textMenuList(){

        try{
            $textMenuHome = Setting::where('key', 'text_menu_home')->first()->value_text;
            $textMenuAccount = Setting::where('key', 'text_menu_account')->first()->value_text;

            $result = [
                'status' => 'success',
                'result' => [
                    'text_menu_home' => json_decode($textMenuHome),
                    'text_menu_account' => json_decode($textMenuAccount)
                ]
            ];

            return response()->json($result);

        }catch(Exception $e){

            return response()->json(['status' => 'fail', 'messages' => []]);
        }
    }

    public function updateTextMenu(Request $request){
        $post = $request->json()->all();

        if(isset($post['category']) && !empty($post['category']) &&
            isset($post['data_menu']) && !empty($post['data_menu'])){

            try{
                $category = $post['category'];
                $menu = $post['data_menu'];

                if($category == 'menu-home'){

                    $dataMenuForUpdate = [
                        "home" => [
                            "text_menu" => $menu['home_text_menu'],
                            "text_header" => $menu['home_text_header']
                        ],
                        "deals" => [
                            "text_menu" => $menu['deals_text_menu'],
                            "text_header" => $menu['deals_text_header']
                        ],
                        "voucher" => [
                            "text_menu" => $menu['voucher_text_menu'],
                            "text_header" => $menu['voucher_text_header']
                        ],
                        "history" => [
                            "text_menu" => $menu['history_text_menu'],
                            "text_header" => $menu['history_text_header']
                        ],
                        "account" => [
                            "text_menu" => $menu['account_text_menu'],
                            "text_header" => $menu['account_text_header']
                        ]
                    ];

                    $update = Setting::where('key','text_menu_home')->update(['value_text' => json_encode($dataMenuForUpdate), 'updated_at' => date('Y-m-d H:i:s')]);

                    if(!$update){
                        return response()->json(['status' => 'fail', 'messages' => ['There is an error']]);
                    }
                }elseif($category == 'menu-account'){
                    $dataMenuForUpdate = [
                        "my_profile" => [
                                "text_menu" => $menu['my_profile_text_menu'],
                                "text_header" => $menu['my_profile_text_header']
                        ],
                        "outlet" => [
                                "text_menu" => $menu['outlet_text_menu'],
                                "text_header" => $menu['outlet_text_header']
                        ],
                        "benefit" => [
                                "text_menu" => $menu['benefit_text_menu'],
                                "text_header" => $menu['benefit_text_menu']
                        ],
                        "news" => [
                                "text_menu" => $menu['news_text_menu'],
                                "text_header" => $menu['news_text_header']
                        ],
                        "delivery_service" => [
                                "text_menu" => $menu['delivery_service_text_menu'],
                                "text_header" => $menu['delivery_service_text_header']
                        ],
                        "faq" => [
                                "text_menu" => $menu['delivery_service_text_menu'],
                                "text_header" => $menu['delivery_service_text_header']
                        ],
                        "terms_service" => [
                                "text_menu" =>$menu['terms_service_text_menu'],
                                "text_header" => $menu['terms_service_text_header']
                        ],
                        "contact" => [
                                "text_menu" => $menu['contact_text_menu'],
                                "text_header" => $menu['contact_text_header']
                        ]
                    ];

                    $update = Setting::where('key','text_menu_account')->update(['value_text' => json_encode($dataMenuForUpdate), 'updated_at' => date('Y-m-d H:i:s')]);

                    if(!$update){
                        return response()->json(['status' => 'fail', 'messages' => ['There is an error']]);
                    }
                }else{
                    return response()->json(['status' => 'fail', 'messages' => ['No data for update']]);
                }

                $result = [
                    'status' => 'success',
                    'result' => []
                ];

                return response()->json($result);

            }catch(Exception $e){
                return response()->json(['status' => 'fail', 'messages' => ['There is an error']]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incomplated Input']]);
        }
    }
}
