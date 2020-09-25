<?php

namespace Modules\Setting\Http\Controllers;

use App\Http\Models\Configs;
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

use App\Exports\DefaultExport;
use Maatwebsite\Excel\Facades\Excel;

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use App\Lib\MailQueue as Mail;
use Image;

class ApiSetting extends Controller
{

    public $saveImage = "img/";
    public $endPoint;

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->endPoint = config('url.storage_url_api');
		$this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
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
        $log = MyHelper::logCron('Point Reset');
        try {
            $user = User::select('id','name','phone')->orderBy('name');

            //point reset
            $setting = Setting::where('key', 'point_reset')->get();
            $attachments = [];
            DB::beginTransaction();
            if($setting){
                $userData = [];
                foreach($setting as $date){
                    if($date['value'] == date('d F')){
                        foreach($user->cursor() as $datauser){
                            $totalPoint = LogPoint::where('id_user', $datauser['id'])->sum('point');
                            if($totalPoint){
                                $dataLog = [
                                    'id_user'                     => $datauser['id'],
                                    'point'                       => -$totalPoint,
                                    'source'                      => 'Point Reset',
                                ];
                                $userData[] = [
                                    'User' => "{$datauser['name']} ({$datauser['phone']})",
                                    'Previous Point' => MyHelper::requestNumber($totalBalance,'_CURRENCY')
                                ];

                                $insertDataLog = LogPoint::create($dataLog);
                                if (!$insertDataLog) {
                                    DB::rollback();
                                    $log->fail('Insert point failed');
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
                                    $log->fail('Update user point failed');
                                    return response()->json([
                                        'status'    => 'fail',
                                        'messages'  => ['Update User Point Failed']
                                    ]);
                                }
                            }
                        }
                    }
                }
                if($userData){
                    $attachments[] = Excel::download(new DefaultExport($userData), 'point.xlsx')->getFile();
                }

                DB::commit();
            }

            //point reset
            $setting = Setting::where('key', 'balance_reset')->get();

            DB::beginTransaction();
            if($setting){
                $userData1 = [];
                foreach($setting as $date){
                    if($date['value'] == date('d F')){
                        foreach($user->cursor() as $datauser){
                            $totalBalance = LogBalance::where('id_user', $datauser['id'])->sum('balance');
                            if($totalBalance){
                                $dataLog = [
                                    'id_user'                     => $datauser['id'],
                                    'balance'                       => -$totalBalance,
                                    'source'                      => 'Balance Reset',
                                ];
                                $userData1[] = [
                                    'User' => "{$datauser['name']} ({$datauser['phone']})",
                                    'Previous Point' => MyHelper::requestNumber($totalBalance,'_CURRENCY')
                                ];
                                $insertDataLog = LogBalance::create($dataLog);
                                if (!$insertDataLog) {
                                    DB::rollback();
                                    $log->fail('Insert Balance Failed');
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
                                    $log->fail('Update User Balance Failed');
                                    return response()->json([
                                        'status'    => 'fail',
                                        'messages'  => ['Update User Balance Failed']
                                    ]);
                                }
                            }
                        }
                    }
                }

                if($userData){
                    $attachments[] = Excel::download(new DefaultExport($userData), 'point.xlsx')->getFile();
                }
                
                DB::commit();

            }

            $send = app($this->autocrm)->SendAutoCRM('Report Point Reset', $user->first()->phone, ['datetime_reset' => date('d F Y H:i'), 'attachment' => $attachments],null,true);

            $log->success();
            return response()->json([
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
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
        $faqList = Faq::orderBy('faq_number_list', 'ASC')->get()->toArray();

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

    public function faqSortUpdate(Request $request) {
        $id_faq = $request->json('id_faq');
        $number_list = 0;

        foreach ($id_faq as $dt){
            $status = Faq::where('id_faq', $dt)->update(['faq_number_list' => $number_list + 1]);
            if(!$status){
                $result = [
                    'status' => 'fail'
                ];
                return response()->json($result);
            }
            $number_list++;
        }

        if($status){
            $result = [
                'status' => 'success'
            ];
        }

        return response()->json($result);
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
            // base64 image,path,h,w,name,ext
            $upload = MyHelper::uploadPhotoStrict($post['default_home_splash_screen'], $this->saveImage, 1080, 1920,'splash');

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

        $update = Setting::updateOrCreate(['key' => 'complete_profile_cashback'], ['key' => 'complete_profile_cashback', 'value' => $post['complete_profile_cashback']]);
        return MyHelper::checkUpdate($update);
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
            'url' => config('url.api_url').'api/setting/faq/webview'
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
            'url' => config('url.api_url').'api/setting/webview/'.$post['key']
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

    public function viewTOS(){
        $setting = Setting::where('key', 'tos')->first();
        if($setting && $setting['value_text']){
            $data['value'] =preg_replace('/font face="[^;"]*(")?/', 'div class="ProductSans"' , $setting['value_text']);
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

    /* ============== Start Text Menu Setting ============== */
    public function configsMenu(){

        try{
            $mainMenu = Configs::where('config_name', 'icon main menu')->first();
            $otherMenu = Configs::where('config_name', 'icon other menu')->first();

            $result = [
                'status' => 'success',
                'result' => [
                    'config_main_menu' => $mainMenu,
                    'config_other_menu' => $otherMenu
                ]
            ];

            return response()->json($result);

        }catch(Exception $e){

            return response()->json(['status' => 'fail', 'messages' => []]);
        }
    }

    public function textMenuList(Request $request){
        $post = $request->json()->all();

        try{
            $textMenuMain = Setting::where('key', 'text_menu_main')->first()->value_text;
            $textMenuOther = Setting::where('key', 'text_menu_other')->first()->value_text;
            $menuOther = (array)json_decode($textMenuOther);
            $menuMain = (array)json_decode($textMenuMain);

            foreach ($menuOther as $key=>$value){
                $val = (array)$value;
                if($val['icon'] != ''){
                    $menuOther[$key]->icon = config('url.storage_url_api').$val['icon'];
                }
            }

            foreach ($menuMain as $key=>$value){
                $val = (array)$value;
                if($val['icon1'] != ''){
                    $menuMain[$key]->icon1 = config('url.storage_url_api').$val['icon1'];
                }
                if($val['icon2'] != ''){
                    $menuMain[$key]->icon2 = config('url.storage_url_api').$val['icon2'];
                }
            }

            if(!isset($post['webview'])){
                $count = count($menuOther);
                $row = $count / 2;
                $arr1 = array_slice($menuOther,0,$row+1);
                $arr2 = array_slice($menuOther,$row+1,$count);

                $arr = [array_values($arr1), array_values($arr2)];
                $result = [
                    'status' => 'success',
                    'result' => [
                        'main_menu' => array_values($menuMain),
                        'other_menu' => $arr
                    ]
                ];
            }else{
                $result = [
                    'status' => 'success',
                    'result' => [
                        'main_menu' => $menuMain,
                        'other_menu' => $menuOther
                    ]
                ];
            }

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
                $arrFailedUploadImage = [];

                if($category == 'main-menu'){
                    $getmainMenu = Setting::where('key', 'text_menu_main')->first()->value_text;
                    $mainMenu = (array)json_decode($getmainMenu);

                    foreach ($mainMenu as $key=>$value){
                        $nameIcon1 = 'icon1_'.$key;
                        $nameIcon2 = 'icon2_'.$key;
                        $val = (array)$value;

                        $mainMenu[$key]->text_menu = $menu[$key.'_text_menu'];
                        $mainMenu[$key]->text_header = $menu[$key.'_text_header'];
                        if(isset($menu['images'][$nameIcon1])){
                            if($val['icon1'] != ''){
                                //Delete old icon
                                MyHelper::deletePhoto($val['icon1']);
                            }
                            $imgEncode = $menu['images'][$nameIcon1];

                            $decoded = base64_decode($imgEncode);
                            $img    = Image::make($decoded);
                            $width  = $img->width();
                            $height = $img->height();

                            if($width == $height){
                                $upload = MyHelper::uploadPhoto($imgEncode, $path = 'img/icon/');

                                if ($upload['status'] == "success") {
                                    $mainMenu[$key]->icon1 = $upload['path'];
                                } else {
                                    array_push($arrFailedUploadImage, $key);
                                }
                            }else{
                                array_push($arrFailedUploadImage, $key.'[dimensions not allowed]');
                            }
                        }

                        if(isset($menu['images'][$nameIcon2])){
                            if($val['icon2'] != ''){
                                //Delete old icon
                                MyHelper::deletePhoto($val['icon2']);
                            }
                            $imgEncode = $menu['images'][$nameIcon2];

                            $decoded = base64_decode($imgEncode);
                            $img    = Image::make($decoded);
                            $width  = $img->width();
                            $height = $img->height();

                            if($width == $height){
                                $upload = MyHelper::uploadPhoto($imgEncode, $path = 'img/icon/');

                                if ($upload['status'] == "success") {
                                    $mainMenu[$key]->icon2 = $upload['path'];
                                } else {
                                    array_push($arrFailedUploadImage, $key);
                                }
                            }else{
                                array_push($arrFailedUploadImage, $key.'[dimensions not allowed]');
                            }
                        }
                    }

                    $update = Setting::where('key','text_menu_main')->update(['value_text' => json_encode($mainMenu), 'updated_at' => date('Y-m-d H:i:s')]);

                    if(!$update){
                        return response()->json(['status' => 'fail', 'messages' => ['There is an error']]);
                    }

                }elseif($category == 'other-menu'){
                    $textOtherMenu = Setting::where('key', 'text_menu_other')->first()->value_text;
                    $otherMenu = (array)json_decode($textOtherMenu);

                    foreach ($otherMenu as $key=>$value){
                        $nameIcon = 'icon_'.$key;
                        $val = (array)$value;

                        $otherMenu[$key]->text_menu = $menu[$key.'_text_menu'];
                        $otherMenu[$key]->text_header = $menu[$key.'_text_header'];
                        if(isset($menu['images'][$nameIcon])){
                            if($val['icon'] != ''){
                                //Delete old icon
                                MyHelper::deletePhoto($val['icon']);
                            }
                            $imgEncode = $menu['images'][$nameIcon];

                            $decoded = base64_decode($imgEncode);
                            $img    = Image::make($decoded);
                            $width  = $img->width();
                            $height = $img->height();

                            if($width == $height){
                                $upload = MyHelper::uploadPhoto($imgEncode, $path = 'img/icon/');

                                if ($upload['status'] == "success") {
                                    $otherMenu[$key]->icon= $upload['path'];
                                } else {
                                    array_push($arrFailedUploadImage, $key);
                                }
                            }else{
                                array_push($arrFailedUploadImage, $key.'[dimensions not allowed]');
                            }
                        }
                    }

                    $update = Setting::where('key','text_menu_other')->update(['value_text' => json_encode($otherMenu), 'updated_at' => date('Y-m-d H:i:s')]);

                    if(!$update){
                        return response()->json(['status' => 'fail', 'messages' => ['There is an error']]);
                    }
                }else{
                    return response()->json(['status' => 'fail', 'messages' => ['No data for update']]);
                }

                $result = [
                    'status' => 'success',
                    'result' => [],
                    'upload_image_failed' => $arrFailedUploadImage
                ];

                return response()->json($result);

            }catch(Exception $e){
                return response()->json(['status' => 'fail', 'messages' => ['There is an error']]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incomplated Input']]);
        }
    }

    /* ============== End Text Menu Setting ============== */

    public function update(Request $request){
        if(($updates = $request->json('update'))&&is_array($updates)){
            DB::beginTransaction();
            foreach ($updates as $key => $value) {
                $up=Setting::updateOrCreate(['key'=>$key],[$value[0]=>$value[1]]);
                if(!$up){
                    DB::rollback();
                    return [
                        'status'=>'fail',
                        'messages'=>['Something went wrong']
                    ];
                }
            }
            DB::commit();
            return [
                'status'=>'success'
            ];
        }
        return [
            'status'=>'fail',
            'messages'=>['No setting updated']
        ];
    }
    public function get($key){
        $allowed=['inactive_logo_brand','inactive_image_brand'];
        if(in_array($key, $allowed)){
            $val=Setting::where('key',$key)->first();
            return MyHelper::checkGet($val);
        }
        return [
            'status'=>'fail',
            'messages'=>['No setting updated']
        ];        
    }

    /* ============== Start Phone Setting ============== */
    public function phoneSetting(Request $request){
        $phoneSetting = Setting::where('key', 'phone_setting')->first()->value_text;

        if($phoneSetting){
            $result = [
                'status' => 'success',
                'result' => [
                    'data' => json_decode($phoneSetting),
                    'phone_code' => $codePhone = config('countrycode.country_code.'.env('COUNTRY_CODE').'.code'),
                    'example_phone' => env('EXAMPLE_PHONE')
                ]
            ];

            return response()->json($result);
        }else{
            return response()->json([
                'status'=>'fail',
                'messages'=>['Failed get phone setting']
            ]);
        }
    }

    public function updatePhoneSetting(Request $request){
        $data = $request->json()->all();
        if($data['max_length_number'] < $data['min_length_number']){
            return response()->json(['status'=>'fail','message' => "Please input maximum above the minimum"]);
        }

        if($data['min_length_number'] > $data['max_length_number']){
            return response()->json(['status'=>'fail','message' => "Please input minimum below the maximum"]);
        }
        $updatePhoneSetting = Setting::where('key', 'phone_setting')->update(['value_text' => json_encode($data)]);

        if($updatePhoneSetting){
            return response()->json(['status'=>'success']);
        }else{
            return response()->json(['status'=>'fail','message' => "Failed update"]);
        }
    }
    /* ============== End Phone Setting ============== */

    /* ============== Start Maintenance Mode Setting ============== */
    function maintenanceMode(){
        $data = Setting::where('key', 'maintenance_mode')->first();
        if($data){
            $dt = (array)json_decode($data['value_text']);
            $newDt['status'] = $data['value'];
            $newDt['message'] = $dt['message'];
            if($dt['image'] != ""){
                $newDt['image'] = config('url.storage_url_api').$dt['image'];
            }else{
                $newDt['image'] = "";
            }
            $data = $newDt;
        }
        return response()->json(MyHelper::checkGet($data));
    }
    function updateMaintenanceMode(Request $request){
        $post = $request->json()->all();
        if(isset($post['status']) && $post['status'] == 'on'){
            $status = 1;
        }else{
            $status = 0;
        }
        $getData = Setting::where('key', 'maintenance_mode')->first();
        $decode = (array)json_decode($getData['value_text']);
        $valueText = ['message' => $post['message'], 'image' => $decode['image']];
        $imageToUpload = "";
        if(isset($post['image']) && !empty($post['image'])){
            if($decode['image'] != ''){
                //Delete old icon
                MyHelper::deletePhoto($decode['image']);
            }
            $decoded = base64_decode($post['image']);
            $img    = Image::make($decoded);
            $width  = $img->width();
            $height = $img->height();
            if($width == $height){
                $upload = MyHelper::uploadPhoto($post['image'], $path = 'img/maintenance/');
                if ($upload['status'] == "success") {
                    $valueText['image'] = $upload['path'];
                }
            }else{
                return response()->json(['status' => 'fail', 'messages' => ['Dimensions not allowed']]);
            }
        }
        $dataToUpdate = [
            'value' => $status,
            'value_text' => json_encode($valueText)
        ];
        $update = Setting::where('key', 'maintenance_mode')->update($dataToUpdate);
        return response()->json(MyHelper::checkUpdate($update));
    }
    /* ============== End Maintenance Mode Setting ============== */

    public function settingPhoneNumber() {
        $phoneSetting = Setting::where('key', 'phone_setting')->first();

        if($phoneSetting){
            $result = [
                'status' => 'success',
                'result' => json_decode($phoneSetting['value_text'])
            ];

            return response()->json($result);
        }else{
            return response()->json([
                'status'=>'fail',
                'messages'=>['Failed get phone setting']
            ]);
        }
    }

    /* ============== Start Time Expired Setting ============== */
    function timeExpired(){
        $timeOtp = Setting::where('key', 'setting_expired_otp')->first();
        $timeEmail = Setting::where('key', 'setting_expired_time_email_verify')->first();

        $data = [];
        if($timeOtp){
            $data['expired_otp'] = $timeOtp['value'];
        }

        if($timeEmail){
            $data['expired_time_email'] = $timeEmail['value'];
        }

        return response()->json(MyHelper::checkGet($data));
    }

    function updateTimeExpired(Request $request){
        $post = $request->json()->all();

        if(isset($post['expired_otp'])){
            $update = Setting::where('key', 'setting_expired_otp')->update(['value' => $post['expired_otp']]);
        }

        if(isset($post['expired_time_email'])){
            $update = Setting::where('key', 'setting_expired_time_email_verify')->update(['value' => $post['expired_time_email']]);
        }

        return response()->json(MyHelper::checkUpdate($update));
    }
    /* ============== End Time Expired Setting ============== */

    function splashScreenOutletApps(Request $request){
        $post = $request->json()->all();

        if(empty($post)){
            $image = Setting::where('key', 'default_splash_screen_outlet_apps')->first();
            $duration = Setting::where('key', 'default_splash_screen_outlet_apps_duration')->first();

            $data = [
                'default_splash_screen_outlet_apps' => NULL,
                'default_splash_screen_outlet_apps_duration' => NULL
            ];
            if(isset($image['value'])){
                $data['default_splash_screen_outlet_apps'] = $this->endPoint.$image['value'];
            }

            if(isset($duration['value'])){
                $data['default_splash_screen_outlet_apps_duration'] = $duration['value'];
            }

            return response()->json(MyHelper::checkGet($data));
        }else{
            if (isset($post['default_splash_screen_outlet_apps'])) {
                $image = Setting::where('key', 'default_splash_screen_outlet_apps')->first();

                if(isset($image['value']) && file_exists($image['value'])){
                    unlink($image['value']);
                }
                // base64 image,path,h,w,name,ext
                $upload = MyHelper::uploadPhotoStrict($post['default_splash_screen_outlet_apps'], $this->saveImage, 1080, 1920,'splash_outletapp');

                if (isset($upload['status']) && $upload['status'] == "success") {
                    $save = Setting::where('key', 'default_splash_screen_outlet_apps')->update(['value'=>$upload['path']]);
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

            if(isset($post['default_splash_screen_outlet_apps_duration'])){
                $save = Setting::where('key', 'default_splash_screen_outlet_apps_duration')->update(['value'=>$post['default_splash_screen_outlet_apps_duration']]);
            }

            return response()->json(MyHelper::checkUpdate($save));
        }
    }
}
