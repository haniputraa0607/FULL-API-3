<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Http\Models\OauthAccessToken;
use App\Http\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Outlet;
use App\Http\Models\OutletSchedule;

use Modules\Franchise\Entities\TransactionProduct;
use Modules\Outlet\Entities\OutletTimeShift;

use Modules\Recruitment\Entities\HairstylistLogBalance;
use Modules\Recruitment\Entities\OutletCash;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\HairstylistAnnouncement;
use Modules\Recruitment\Entities\HairstylistInbox;
use Modules\Recruitment\Entities\HairstylistAttendance;

use Modules\Transaction\Entities\TransactionPaymentCash;
use Modules\UserRating\Entities\UserRating;
use Modules\UserRating\Entities\RatingOption;
use Modules\UserRating\Entities\UserRatingLog;
use Modules\UserRating\Entities\UserRatingSummary;
use App\Http\Models\Transaction;

use Modules\Recruitment\Http\Requests\ScheduleCreateRequest;
use Modules\Recruitment\Entities\OutletCashAttachment;

use App\Lib\MyHelper;
use DB;
use DateTime;
use DateTimeZone;
use Modules\Users\Http\Requests\users_forgot;
use Modules\Users\Http\Requests\users_phone_pin_new_v2;
use PharIo\Manifest\EmailTest;
use Auth;
use Modules\Recruitment\Http\Requests\Income;
use Modules\Recruitment\Entities\HairstylistIncome;
use Config;
use Modules\Recruitment\Http\Requests\Export_Outlet;

class ApiIncome extends Controller
{

    public function income(Income $request){
        $hs = $request->user()->id_user_hair_stylist;
        $date = $request->month;
        $schedule = $this->schedule($date,$hs);
        $schedule_akhir = $this->schedule_akhir($date,$hs);
        $attandance = array(
            'Tengah Bulan'=>$schedule,
            'Akhir Bulan'=>$schedule_akhir,
        );
        Return $attandance;
       
    }
    public function cron_middle() {
        return Config::get('app.income_date_middle');
       $log = MyHelper::logCron('Cron Income HS middle month');
        try {
        $hs = UserHairStylist::get();
        $type = 'middle';
        foreach ($hs as $value) {
            $income = $this->schedule_income($value['id_user_hair_stylist'], $type);
        }
        $log->success('success');
            return response()->json(['success']);
        } catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
        }
    }
    public function cron_end() {
       $log = MyHelper::logCron('Cron Income HS end month');
        try {
        $hs = UserHairStylist::get();
        $type = 'end';
        foreach ($hs as $value) {
            $income = $this->schedule_income($value['id_user_hair_stylist'], $type);
        }
        $log->success('success');
            return response()->json(['success']);
        } catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
        }
    }
    public function schedule_income($id,$type = 'end') {
       $b = new HairstylistIncome();
       $hs = UserHairStylist::where('id_user_hair_stylist',$id)->first();
       $bro = $b->calculateIncome($hs, $type);
       return $bro;
    }
    public function schedule($date,$hs) {
        $tanggal = Setting::where('key','attendances_date')->first();
        if($tanggal){
            $value = json_decode($tanggal->value_text);
            $bulan_awal = date('Y-m-d', strtotime(date($date.'-'.$value->start) . '- 1 month'));
              $bulan_tengah = date('Y-m-d', strtotime(date($date.'-'.$value->start) . '+'.$value->middle.'day'. '- 1 month'));
            $bulan_akhir = date('Y-m-d', strtotime(date($date.'-'.$value->end)));
        }else{
            $bulan_awal = date('Y-m-d', strtotime(date($date)));
            $bulan_akhir = date('Y-m-d', strtotime(date($date) . '+. 15 day'));
//            $bulan_akhir = date('Y-m-d', strtotime(date($date) . '+ 1 month'. '- 1 day'));
        }
        return array(
            $bulan_awal,$bulan_tengah,$bulan_akhir
        );
        $date = explode('-', $bulan_awal);
        $month = $date[1]??null;
        $year = $date[0]??null;
        $date1 = explode('-', $bulan_akhir);
        $month1 = $date1[1]??null;
        $year1 = $date1[0]??null;
        $shedule = array();
        $data = array();
        $data_schedule_outlet = array();
        $schedule_outlet_data = array();
        $schedule_outlet = HairstylistSchedule::join('outlets','outlets.id_outlet','hairstylist_schedules.id_outlet')
                ->select(['hairstylist_schedules.id_outlet','outlet_name'])
                ->where(array('id_user_hair_stylist'=>$hs,'schedule_month'=>$month,'schedule_year'=>$year))
                ->distinct()
                ->get();
        foreach ($schedule_outlet as $value) {
             array_push($schedule_outlet_data,$value);
        }
        
        $schedule_outlet = HairstylistSchedule::join('outlets','outlets.id_outlet','hairstylist_schedules.id_outlet')
                ->select(['hairstylist_schedules.id_outlet','outlet_name'])
                ->where(array('id_user_hair_stylist'=>$hs,'schedule_month'=>$month1,'schedule_year'=>$year1))
                ->distinct()
                ->get();
        foreach ($schedule_outlet as $value) {
             array_push($schedule_outlet_data,$value);
        }
        $schedule_outlet = array_unique($schedule_outlet_data);
       foreach ($schedule_outlet as $values) {
            $kehadiran = 0;
            $terlambat = 0;
            $tidak_hadir = 0;
            $jml_jadwal = 0;
           $data_schedules = array();
           $schedule = HairstylistSchedule::join('outlets','outlets.id_outlet','hairstylist_schedules.id_outlet')
                ->select(['hairstylist_schedules.*'])
                ->where(array('hairstylist_schedules.id_outlet'=>$values['id_outlet'],'id_user_hair_stylist'=>$hs,'schedule_month'=>$month,'schedule_year'=>$year))
                ->get();
        foreach ($schedule as $value) {
            $schedule_date = HairstylistScheduleDate::where(array('id_hairstylist_schedule'=>$value['id_hairstylist_schedule']))
                             ->wherebetween('date',[$bulan_awal,$bulan_akhir])
                             ->get();
                     foreach ($schedule_date as $va) {
                        $jml_jadwal++;
                        $attendance = HairstylistAttendance::where(array('id_hairstylist_schedule_date'=>$va['id_hairstylist_schedule_date']))->count();
                        $absensi = array();
                        if($attendance != 0){
                        $attendance = HairstylistAttendance::where(array('id_hairstylist_schedule_date'=>$va['id_hairstylist_schedule_date']))->get();
                        foreach ($attendance as $v) {
                            array_push($absensi,$v);
                            $kehadiran++;
                            if($v['is_on_time'] == 0){
                                $terlambat++;
                            }
                        }
                        }else{
                           $tidak_hadir++;
                        }
                        $data_value = array(
                            'data_schedules'=>$va,
                            'attendance'=>$absensi
                        );
                         array_push($data_schedules,$data_value);
                     }
                     
        }
        $schedule1 = HairstylistSchedule::join('outlets','outlets.id_outlet','hairstylist_schedules.id_outlet')
                    ->select(['hairstylist_schedules.*'])
                    ->where(array('hairstylist_schedules.id_outlet'=>$values['id_outlet'],'id_user_hair_stylist'=>$hs,'schedule_month'=>$month1,'schedule_year'=>$year1))
                    ->get();
        foreach ($schedule1 as $value) {
            $schedule_date = HairstylistScheduleDate::where(array('id_hairstylist_schedule'=>$value['id_hairstylist_schedule']))
                             ->wherebetween('date',[$bulan_awal,$bulan_akhir])
                             ->get();
            foreach ($schedule_date as $va) {
                        $jml_jadwal++;
                        $attendance = HairstylistAttendance::where(array('id_hairstylist_schedule_date'=>$va['id_hairstylist_schedule_date']))->count();
                        $absensi = array();
                        if($attendance != 0){
                        $attendance = HairstylistAttendance::where(array('id_hairstylist_schedule_date'=>$va['id_hairstylist_schedule_date']))->get();
                        foreach ($attendance as $v) {
                            array_push($absensi,$v);
                            $kehadiran++;
                            if($v['is_on_time'] == 0){
                                $terlambat++;
                            }
                        }
                        }else{
                           $tidak_hadir++;
                        }
                          $data_value = array(
                            'data_schedules'=>$va,
                            'attendance'=>$absensi
                        );
                         array_push($data_schedules,$data_value);
                     }
                     
        }
        $data_outlet = array(
            'id_outlet'=>$values['id_outlet'],
            'outlet_name'=>$values['outlet_name'],
//            'schedule_date'=> $data_schedules,
            'jadwal'=> $jml_jadwal,
            'kehadiran'=> $kehadiran,
            'terlambat'=> $terlambat,
            'tidak_hadir'=>$tidak_hadir
        );
        array_push($data_schedule_outlet,$data_outlet);
    }
    return $data_schedule_outlet;
    }
    public function schedule_akhir($date,$hs) {
        $tanggal = Setting::where('key','attendances_date')->first();
        if($tanggal){
//            $bulan_awal = date('Y-m-d', strtotime(date($date.'-'.$tanggal->value) . '- 1 month'));
              $bulan_awal = date('Y-m-d', strtotime(date($date.'-'.$tanggal->value) . '+ 16 day'. '- 1 month'));
            $bulan_akhir = date('Y-m-d', strtotime(date($date.'-'.$tanggal->value_text)));
        }else{
//            $bulan_awal = date('Y-m-d', strtotime(date($date)));
            $bulan_awal = date('Y-m-d', strtotime(date($date) . '+ 16 day'));
            $bulan_akhir = date('Y-m-d', strtotime(date($date) . '+ 1 month'. '- 1 day'));
        }
        $date = explode('-', $bulan_awal);
        $month = $date[1]??null;
        $year = $date[0]??null;
        $date1 = explode('-', $bulan_akhir);
        $month1 = $date1[1]??null;
        $year1 = $date1[0]??null;
        $shedule = array();
        $data = array();
        $data_schedule_outlet = array();
        $schedule_outlet_data = array();
        $schedule_outlet = HairstylistSchedule::join('outlets','outlets.id_outlet','hairstylist_schedules.id_outlet')
                ->select(['hairstylist_schedules.id_outlet','outlet_name'])
                ->where(array('id_user_hair_stylist'=>$hs,'schedule_month'=>$month,'schedule_year'=>$year))
                ->distinct()
                ->get();
        foreach ($schedule_outlet as $value) {
             array_push($schedule_outlet_data,$value);
        }
        
        $schedule_outlet = HairstylistSchedule::join('outlets','outlets.id_outlet','hairstylist_schedules.id_outlet')
                ->select(['hairstylist_schedules.id_outlet','outlet_name'])
                ->where(array('id_user_hair_stylist'=>$hs,'schedule_month'=>$month1,'schedule_year'=>$year1))
                ->distinct()
                ->get();
        foreach ($schedule_outlet as $value) {
             array_push($schedule_outlet_data,$value);
        }
        $schedule_outlet = array_unique($schedule_outlet_data);
       foreach ($schedule_outlet as $values) {
            $kehadiran = 0;
            $terlambat = 0;
            $tidak_hadir = 0;
            $jml_jadwal = 0;
           $data_schedules = array();
           $schedule = HairstylistSchedule::join('outlets','outlets.id_outlet','hairstylist_schedules.id_outlet')
                ->select(['hairstylist_schedules.*'])
                ->where(array('hairstylist_schedules.id_outlet'=>$values['id_outlet'],'id_user_hair_stylist'=>$hs,'schedule_month'=>$month,'schedule_year'=>$year))
                ->get();
        foreach ($schedule as $value) {
            $schedule_date = HairstylistScheduleDate::where(array('id_hairstylist_schedule'=>$value['id_hairstylist_schedule']))
                             ->wherebetween('date',[$bulan_awal,$bulan_akhir])
                             ->get();
            
                     foreach ($schedule_date as $va) {
                        $jml_jadwal++;
                        $attendance = HairstylistAttendance::where(array('id_hairstylist_schedule_date'=>$va['id_hairstylist_schedule_date']))->count();
                        $absensi = array();
                        if($attendance != 0){
                        $attendance = HairstylistAttendance::where(array('id_hairstylist_schedule_date'=>$va['id_hairstylist_schedule_date']))->get();
                        foreach ($attendance as $v) {
                            array_push($absensi,$v);
                            $kehadiran++;
                            if($v['is_on_time'] == 0){
                                $terlambat++;
                            }
                        }
                        }else{
                           $tidak_hadir++;
                        }
                        $data_value = array(
                            'data_schedules'=>$va,
                            'attendance'=>$absensi
                        );
                         array_push($data_schedules,$data_value);
                     }
                     
        }
        $schedule1 = HairstylistSchedule::join('outlets','outlets.id_outlet','hairstylist_schedules.id_outlet')
                    ->select(['hairstylist_schedules.*'])
                    ->where(array('hairstylist_schedules.id_outlet'=>$values['id_outlet'],'id_user_hair_stylist'=>$hs,'schedule_month'=>$month1,'schedule_year'=>$year1))
                    ->get();
        
        $data_outlet = array(
            'id_outlet'=>$values['id_outlet'],
            'outlet_name'=>$values['outlet_name'],
//            'schedule_date'=> $data_schedules,
            'jadwal'=> $jml_jadwal,
            'kehadiran'=> $kehadiran,
            'terlambat'=> $terlambat,
            'tidak_hadir'=>$tidak_hadir
        );
        array_push($data_schedule_outlet,$data_outlet);
    }
    return $data_schedule_outlet;
    }
    public function export_income(Export_Outlet $request) {
        $array = array();
        $b = new HairstylistIncome();
        $hairstyllist = UserHairStylist::join('outlets','outlets.id_outlet','user_hair_stylist.id_outlet')
                ->leftjoin('bank_accounts','bank_accounts.id_bank_account','user_hair_stylist.id_bank_account')
                ->leftjoin('bank_name','bank_name.id_bank_name','bank_accounts.id_bank_name')
                ->leftjoin('hairstylist_groups','hairstylist_groups.id_hairstylist_group','user_hair_stylist.id_hairstylist_group')
                ->wherein('user_hair_stylist.id_outlet',$request->id_outlet)
                ->get();
        foreach ($hairstyllist as $value) {
            $hs = UserHairStylist::where('id_user_hair_stylist',$value->id_user_hair_stylist)->first();
            $location = Outlet::where('id_outlet',$value->id_outlet)->join('locations','locations.id_location','outlets.id_location')->first();
            $diff = date_diff(date_create(date('Y-m-d')), date_create(date('Y-m-d',strtotime($location->start_date))));
           
            if($diff->m >= 3){
                $keterangan = "Non Proteksi";
            }else{
                $keterangan = "Proteksi";
            }
            $data = array(
                'NIK'=>$hs->user_hair_stylist_code??'',
                'NAMA LENGKAP'=>$hs->fullname??'',
                'Nama Panggilan'=>$hs->nickname??'',
                'Jabatan'=>$hs->level??'',
                'Join Date'=>date('d-M-Y',strtotime($hs->join_date))??'',
                'Outlet'=>$value->outlet_name??'',
            );
            $response = $b->calculateIncomeGross($hs, $request->start_date,$request->end_date);
            foreach ($response as $valu) {
                $data[ucfirst(str_replace('-', ' ', $valu['name']))]=(string)$valu['value'];
            }
            $response = $b->calculateIncomeTotal($hs, $request->start_date,$request->end_date);
            foreach ($response as $valu) {
                $data[ucfirst(str_replace('-', ' ', $valu['name']))]=(string)$valu['value'];
            }
            $response = $b->calculateIncomeExport($hs, $request->start_date,$request->end_date);
            foreach ($response as $values) {
                $data[ucfirst(str_replace('-', ' ', $values['name']))]=(string)$values['value'];
            }
            $response = $b->calculateIncomeProductCode($hs, $request->start_date,$request->end_date);
            foreach ($response as $values) {
                $data[ucfirst(str_replace('-', ' ', $values['name']))]=(string)$values['value'];
            }
            $data['Keterangan'] = $keterangan??'';
            $data['Bank'] = $value->bank_name??'';
            $data['Bank account'] = $value->beneficiary_name??'';
            $data['Email'] = $value->email??'';
            array_push($array,$data);
        }
        $b = array();
        foreach ($array as $key => $value) {
            $b = array_merge($b,array_keys($value));
        }
        $response = array(
            'start_date'=>$request->start_date,
            'end_date'=>$request->end_date,
            'head'=> array_unique($b),
            'body'=> $array,
        );
        return MyHelper::checkGet($response);
    }
}
