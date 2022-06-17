<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Lib\MyHelper;
use App\Http\Models\TransactionProduct;
use DB;
class HairstylistIncome extends Model
{
    public $primaryKey = 'id_hairstylist_income';
    protected $fillable = [
        'id_user_hair_stylist',
        'type',
        'periode',
        'start_date',
        'end_date',
        'completed_at',
        'status',
        'amount',
        'notes',
    ];

    public function hairstylist_income_details()
    {
        return $this->hasMany(HairstylistIncomeDetail::class, 'id_hairstylist_income');
    }

    public static function calculateIncome(UserHairStylist $hs, $type = 'end')
    {
        $total = 0;
        if ($type == 'middle') {
            $date = (int) MyHelper::setting('hs_income_cut_off_mid_date', 'value');
            $calculations = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
        } else {
            $type = 'end';
            $date = (int) MyHelper::setting('hs_income_cut_off_end_date', 'value');
            $calculations = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
        }
        if (!$calculations) {
            throw new \Exception('No calculation for current periode. Check setting!');
        }

        $year = date('Y');
        if ($date >= date('d')) {
            $month = (int) date('m') - 1;
            if (!$month) {
                $month = 12;
                $year -= 1;
            }
        } else {
            $month = (int) date('m');
        }
        $exists = static::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->whereDate('periode', "$year-$month-$date")->where('type', $type)->where('status', '<>', 'Draft')->exists();
        if ($exists) {
            throw new \Exception("Hairstylist income for periode $type $month/$year already exists for $hs->id_user_hair_stylist");
        }

        $lastDate = static::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->orderBy('end_date', 'desc')->whereDate('end_date', '<', date('Y-m-d'))->where('status', '<>', 'Cancelled')->first();
        if ($lastDate) {
            $startDate = date('Y-m-d', strtotime($lastDate->end_date . '+1 days'));
        } else {
            $startDate = date('Y-m-d', strtotime("$year-" . ($month - 1) . "-$date +1 days"));
            if (date('m', strtotime($startDate)) != ($month - 1)) {
                $startDate = date('Y-m-d', strtotime("$year-$month-01 -1 days"));
            }
        }
        $endDate = date('Y-m-d', strtotime("$year-" . $month . "-$date"));
        if (date('m', strtotime($endDate)) != $month) {
            $endDate = date('Y-m-d', ("$year-" . ($month + 1) . "-01 -1 days"));
        }
        $hsIncome = static::updateOrCreate([
            'id_user_hair_stylist' => $hs->id_user_hair_stylist,
            'type' => $type,
            'periode' => date('Y-m-d', strtotime("$year-$month-$date")),
        ],[
            'start_date' => $startDate,
            'end_date' => $endDate,
            'completed_at' => null,
            'status' => 'Draft',
            'amount' => 0,
        ]);

        if (!$hsIncome) {
            throw new \Exception('Failed create hs income data');
        }
        $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');
        foreach ($id_outlets as $id_outlet) {
                    $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_late = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->where('is_on_time', 0)
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_absen = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->select('date')
                        ->get();
                    foreach ($total_overtimes as $value) {
                        array_push($overtime,$value);
                    }
                  
                }
                $over = 0;
                $ove = array();
                foreach (array_unique($overtime) as $value) {
                    $overtimess = HairstylistOverTime::where('id_user_hair_stylist',$hs->id_user_hair_stylist)
                            ->wherenotnull('approve_at')
                            ->wherenull('reject_at')
                            ->wheredate('date',$value['date'])
                            ->get();
                    foreach ($overtimess as $va) {
                        array_push($ove,$va['duration']);
                    }
                }
                $h = 0;
                $m = 0;
                $d = 0;
                foreach ($ove as $value) {
                    $va = explode(":", $value);
                    $h += $va[0];
                    $m += $va[1];
                    $d += $va[2];
                }
                if($d>60){
                  $s = floor($d / 60);
                  $m = $s + $m;
                }
                if($m>60){
                  $s = floor($m / 60);
                  $h = $s + $h;
                }
             $total_overtime = $h;
        foreach  ($calculations as $calculation) {
            if ($calculation == 'product_commission') {
                $trxs = TransactionProduct::select('transaction_products.id_transaction', 'transaction_products.id_transaction_product', 'transaction_breakdowns.*')
                    ->join('transaction_breakdowns', function($join) use ($startDate, $endDate) {
                        $join->on('transaction_breakdowns.id_transaction_product', 'transaction_products.id_transaction_product')
                            ->whereNotNull('transaction_products.transaction_product_completed_at')
                            ->whereBetween('transaction_product_completed_at',[$startDate,$endDate]);
                    })
                    ->where('transaction_breakdowns.type', 'fee_hs')
                    ->with('transaction')
                    ->get();
                $trxs->each(function ($item) use ($hsIncome, $calculation) {
                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source' => $calculation,
                        'reference' => $item->id_transaction_product,
                    ],
                    [
                        'id_outlet' => $item->transaction->id_outlet,
                        'amount' => $item->value,
                    ]);
                     $total = $total+$item->value;
                });
            } elseif (strpos($calculation, 'incentive_') === 0) { // start_with_calculation
                $code = str_replace('incentive_', '', $calculation);
                $incentive = HairstylistGroupInsentifDefault::leftJoin('hairstylist_group_insentifs', function($join) use ($hs) {
                                $join->on('hairstylist_group_insentifs.id_hairstylist_group_default_insentifs', 'hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs')
                                ->where('id_hairstylist_group', $hs->id_hairstylist_groups);
                            })->where('hairstylist_group_default_insentifs.code', $code)
                            ->select('hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs','hairstylist_group_default_insentifs.code',
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_insentifs.value IS NOT NULL THEN hairstylist_group_insentifs.value ELSE hairstylist_group_default_insentifs.value
                                       END as value
                                    '),
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_insentifs.formula IS NOT NULL THEN hairstylist_group_insentifs.formula ELSE hairstylist_group_default_insentifs.formula
                                       END as formula
                                    ')
                                )->first();
                if (!$incentive) {
                    continue;
                }
                $formula = str_replace('value', $incentive->value, $incentive->formula);

                $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->get()->pluck('id_outlet');
                $amount = 0;
                foreach ($id_outlets as $id_outlet) {
                    try {
                        $amount = MyHelper::calculator($formula, [
                            'total_attend' => $total_attend,
                            'total_late' => $total_late,
                            'total_absen' => $total_absen,
                            'total_overtime' => $total_overtime,
                        ]);
                    } catch (\Exception $e) {
                        $amount = 0;
                        $hsIncome->update(['notes' => $e->getMessage()]);
                    }

                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source' => $calculation,
                        'reference' => $incentive->id_hairstylist_group_default_insentifs,
                    ],
                    [
                        'id_outlet' => $id_outlet,
                        'amount' => $amount,
                    ]);
                }
                $total = $total+$amount;
            } elseif (strpos($calculation, 'salary_cut_') === 0) { // start_with_calculation
                $code = str_replace('salary_cut_', '', $calculation);
                $salary_cut = HairstylistGroupPotonganDefault::leftJoin('hairstylist_group_potongans', function($join) use ($hs) {
                    $join->on('hairstylist_group_potongans.id_hairstylist_group_default_potongans', 'hairstylist_group_default_potongans.id_hairstylist_group_default_potongans')
                        ->where('id_hairstylist_group', $hs->id_hairstylist_groups);
                })->where('hairstylist_group_default_potongans.code', $code)
                         ->select('hairstylist_group_default_potongans.id_hairstylist_group_default_potongans','hairstylist_group_default_potongans.code',
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_potongans.value IS NOT NULL THEN hairstylist_group_potongans.value ELSE hairstylist_group_default_potongans.value
                                       END as value
                                    '),
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_potongans.formula IS NOT NULL THEN hairstylist_group_potongans.formula ELSE hairstylist_group_default_potongans.formula
                                       END as formula
                                    '))
                        ->first();
                if (!$salary_cut) {
                    continue;
                }
                
                $formula = str_replace('value', $salary_cut->value, $salary_cut->formula);
                $amount = 0;
                $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->get()->pluck('id_outlet');
                foreach ($id_outlets as $id_outlet) {
                    try {
                        $amount = MyHelper::calculator($formula, [
                            'total_attend' => $total_attend,
                            'total_late' => $total_late,
                            'total_absen' => $total_absen,
                            'total_overtime' => $total_overtime,
                        ]);
                    } catch (\Exception $e) {
                        $amount = 0;
                        $hsIncome->update(['notes' => $e->getMessage()]);
                    }

                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source' => $calculation,
                        'reference' => $salary_cut->id_hairstylist_group_default_potongans,
                    ],
                    [
                        'id_outlet' => $id_outlet,
                        'amount' => $amount,
                    ]);
                  
                }
                  $total = $total-$amount;
            }
        }

        $hsIncome->update([
            'status' => 'Pending',
            'amount' => $total,
        ]);

        return $hsIncome;
    }
    public static function calculateIncomeExport(UserHairStylist $hs, $startDate,$endDate)
    {
        $total = 0;
        $calculation_mid = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
        $calculation_end = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
        $calculations    = array_unique(array_merge($calculation_mid,$calculation_end));
        if (!$calculations) {
            throw new \Exception('No calculation for income. Check setting!');
        }
        $total_attend = 0;
        $total_late = 0;
        $total_absen = 0;
        $total_overtime = 0;
        $overtime = array();
        $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');
        foreach ($id_outlets as $id_outlet) {
                    $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_late = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->where('is_on_time', 0)
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_absen = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->select('date')
                        ->get();
                    foreach ($total_overtimes as $value) {
                        array_push($overtime,$value);
                    }
                  
                }
                $over = 0;
                $ove = array();
                foreach (array_unique($overtime) as $value) {
                    $overtimess = HairstylistOverTime::where('id_user_hair_stylist',$hs->id_user_hair_stylist)
                            ->wherenotnull('approve_at')
                            ->wherenull('reject_at')
                            ->wheredate('date',$value['date'])
                            ->get();
                    foreach ($overtimess as $va) {
                        array_push($ove,$va['duration']);
                    }
                }
                $h = 0;
                $m = 0;
                $d = 0;
                foreach ($ove as $value) {
                    $va = explode(":", $value);
                    $h += $va[0];
                    $m += $va[1];
                    $d += $va[2];
                }
                if($d>60){
                  $s = floor($d / 60);
                  $m = $s + $m;
                }
                if($m>60){
                  $s = floor($m / 60);
                  $h = $s + $h;
                }
             $total_overtime = $h;
            
        foreach  ($calculations as $calculation) {
            if (strpos($calculation, 'incentive_') === 0) { // start_with_calculation
                $code = str_replace('incentive_', '', $calculation);
                $incentive = HairstylistGroupInsentifDefault::leftJoin('hairstylist_group_insentifs', function($join) use ($hs) {
                                $join->on('hairstylist_group_insentifs.id_hairstylist_group_default_insentifs', 'hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs')
                                ->where('id_hairstylist_group', $hs->id_hairstylist_groups);
                            })->where('hairstylist_group_default_insentifs.code', $code)
                            ->select('hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs','hairstylist_group_default_insentifs.name','hairstylist_group_default_insentifs.code',
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_insentifs.value IS NOT NULL THEN hairstylist_group_insentifs.value ELSE hairstylist_group_default_insentifs.value
                                       END as value
                                    '),
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_insentifs.formula IS NOT NULL THEN hairstylist_group_insentifs.formula ELSE hairstylist_group_default_insentifs.formula
                                       END as formula
                                    ')
                                )->first();
                if (!$incentive) {
                    continue;
                }
                $formula = str_replace('value', $incentive->value, $incentive->formula);
                $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->get()->pluck('id_outlet');
                $amount = 0;
                foreach ($id_outlets as $id_outlet) {
                    try {
                        $amount = MyHelper::calculator($formula, [
                            'total_attend' => $total_attend,
                            'total_late' => $total_late,
                            'total_absen' => $total_absen,
                            'total_overtime' => $total_overtime,
                        ]);
                    } catch (\Exception $e) {
                        $amount = 0;
                    }
                }
                $total = $total+$amount;
               $array[] = array(
                    "name"=> $incentive->name,
                    "value"=> $amount,
                );
            } elseif (strpos($calculation, 'salary_cut_') === 0) { // start_with_calculation
                $code = str_replace('salary_cut_', '', $calculation);
                $salary_cut = HairstylistGroupPotonganDefault::leftJoin('hairstylist_group_potongans', function($join) use ($hs) {
                    $join->on('hairstylist_group_potongans.id_hairstylist_group_default_potongans', 'hairstylist_group_default_potongans.id_hairstylist_group_default_potongans')
                        ->where('id_hairstylist_group', $hs->id_hairstylist_groups);
                })->where('hairstylist_group_default_potongans.code', $code)
                         ->select('hairstylist_group_default_potongans.id_hairstylist_group_default_potongans','hairstylist_group_default_potongans.name','hairstylist_group_default_potongans.code',
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_potongans.value IS NOT NULL THEN hairstylist_group_potongans.value ELSE hairstylist_group_default_potongans.value
                                       END as value
                                    '),
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_potongans.formula IS NOT NULL THEN hairstylist_group_potongans.formula ELSE hairstylist_group_default_potongans.formula
                                       END as formula
                                    '))
                        ->first();
                if (!$salary_cut) {
                    continue;
                }
                
                $formula = str_replace('value', $salary_cut->value, $salary_cut->formula);
                $amount = 0;
                $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->get()->pluck('id_outlet');
                foreach ($id_outlets as $id_outlet) {
                   
                    try {
                        $amount = MyHelper::calculator($formula, [
                            'total_attend' => $total_attend,
                            'total_late' => $total_late,
                            'total_absen' => $total_absen,
                            '$total_overtime' => $total_overtime,
                        ]);
                    } catch (\Exception $e) {
                        $amount = 0;
                    }
                  
                }
                $total = $total-$amount;
                  $array[] = array(
                    "name"=> $salary_cut->name,
                    "value"=> $amount,
                );
            }
        }
        return $array;
    }
    public static function calculateIncomeProductCode(UserHairStylist $hs, $startDate,$endDate)
    {
        $total = 0;
        $calculation_mid = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
        $calculation_end = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
        $calculations    = array_unique(array_merge($calculation_mid,$calculation_end));
        if (!$calculations) {
            throw new \Exception('No calculation for income. Check setting!');
        }
        $total_attend = 0;
        $total_late = 0;
        $total_absen = 0;
        $total_overtime = 0;
        $overtime = array();
        $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');
        foreach ($id_outlets as $id_outlet) {
                    $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_late = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->where('is_on_time', 0)
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_absen = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->select('date')
                        ->get();
                    foreach ($total_overtimes as $value) {
                        array_push($overtime,$value);
                    }
                  
                }
                $over = 0;
                $ove = array();
                foreach (array_unique($overtime) as $value) {
                    $overtimess = HairstylistOverTime::where('id_user_hair_stylist',$hs->id_user_hair_stylist)
                            ->wherenotnull('approve_at')
                            ->wherenull('reject_at')
                            ->wheredate('date',$value['date'])
                            ->get();
                    foreach ($overtimess as $va) {
                        array_push($ove,$va['duration']);
                    }
                }
                $h = 0;
                $m = 0;
                $d = 0;
                foreach ($ove as $value) {
                    $va = explode(":", $value);
                    $h += $va[0];
                    $m += $va[1];
                    $d += $va[2];
                }
                if($d>60){
                  $s = floor($d / 60);
                  $m = $s + $m;
                }
                if($m>60){
                  $s = floor($m / 60);
                  $h = $s + $h;
                }
             $total_overtime = $h;
            
        foreach  ($calculations as $calculation) {
            if ($calculation == 'product_commission') {
                $trxs = TransactionProduct::select('transaction_products.id_transaction', 'transaction_products.id_transaction_product', 'transaction_breakdowns.*')
                    ->join('transaction_breakdowns', function($join) use ($startDate, $endDate) {
                        $join->on('transaction_breakdowns.id_transaction_product', 'transaction_products.id_transaction_product')
                            ->whereNotNull('transaction_products.transaction_product_completed_at')
                            ->whereBetween('transaction_product_completed_at',[$startDate,$endDate]);
                    })
                    ->where('transaction_breakdowns.type', 'fee_hs')
                    ->with('transaction')
                    ->get();
                $total_product_commission = 0;
                $trxs->each(function ($item) use ($total_product_commission,$total) {
                     $total_product_commission += $item->value;
                     $total = $total+$item->value;
                });
                $array[] = array(
                    "name"=> "Total imbal jasa",
                    "value"=> $total_product_commission
                );
            } 
        }
        return $array;
    }
    public static function calculateIncomeTotal(UserHairStylist $hs, $startDate,$endDate)
    {
        $total = 0;
        $calculation_mid = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
        $calculation_end = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
        $calculations    = array_unique(array_merge($calculation_mid,$calculation_end));
        if (!$calculations) {
            throw new \Exception('No calculation for income. Check setting!');
        }
        $total_attend = 0;
        $total_late = 0;
        $total_absen = 0;
        $total_overtime = 0;
        $overtime = array();
        $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');
        foreach ($id_outlets as $id_outlet) {
                    $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_late = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->where('is_on_time', 0)
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_absen = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->select('date')
                        ->get();
                    foreach ($total_overtimes as $value) {
                        array_push($overtime,$value);
                    }
                  
                }
                $over = 0;
                $ove = array();
                foreach (array_unique($overtime) as $value) {
                    $overtimess = HairstylistOverTime::where('id_user_hair_stylist',$hs->id_user_hair_stylist)
                            ->wherenotnull('approve_at')
                            ->wherenull('reject_at')
                            ->wheredate('date',$value['date'])
                            ->get();
                    foreach ($overtimess as $va) {
                        array_push($ove,$va['duration']);
                    }
                }
                $h = 0;
                $m = 0;
                $d = 0;
                foreach ($ove as $value) {
                    $va = explode(":", $value);
                    $h += $va[0];
                    $m += $va[1];
                    $d += $va[2];
                }
                if($d>60){
                  $s = floor($d / 60);
                  $m = $s + $m;
                }
                if($m>60){
                  $s = floor($m / 60);
                  $h = $s + $h;
                }
             $total_overtime = $h;
            
        foreach  ($calculations as $calculation) {
            if ($calculation == 'product_commission') {
                $trxs = TransactionProduct::select('transaction_products.id_transaction', 'transaction_products.id_transaction_product', 'transaction_breakdowns.*')
                    ->join('transaction_breakdowns', function($join) use ($startDate, $endDate) {
                        $join->on('transaction_breakdowns.id_transaction_product', 'transaction_products.id_transaction_product')
                            ->whereNotNull('transaction_products.transaction_product_completed_at')
                            ->whereBetween('transaction_product_completed_at',[$startDate,$endDate]);
                    })
                    ->where('transaction_breakdowns.type', 'fee_hs')
                    ->with('transaction')
                    ->get();
                $total_product_commission = 0;
                $trxs->each(function ($item) use ($total_product_commission,$total) {
                     $total = $total+$item->value;
                });
              
            } elseif (strpos($calculation, 'incentive_') === 0) { // start_with_calculation
                $code = str_replace('incentive_', '', $calculation);
                $incentive = HairstylistGroupInsentifDefault::leftJoin('hairstylist_group_insentifs', function($join) use ($hs) {
                                $join->on('hairstylist_group_insentifs.id_hairstylist_group_default_insentifs', 'hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs')
                                ->where('id_hairstylist_group', $hs->id_hairstylist_groups);
                            })->where('hairstylist_group_default_insentifs.code', $code)
                            ->select('hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs','hairstylist_group_default_insentifs.code',
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_insentifs.value IS NOT NULL THEN hairstylist_group_insentifs.value ELSE hairstylist_group_default_insentifs.value
                                       END as value
                                    '),
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_insentifs.formula IS NOT NULL THEN hairstylist_group_insentifs.formula ELSE hairstylist_group_default_insentifs.formula
                                       END as formula
                                    ')
                                )->first();
                if (!$incentive) {
                    continue;
                }
                $formula = str_replace('value', $incentive->value, $incentive->formula);
                $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->get()->pluck('id_outlet');
                $amount = 0;
                foreach ($id_outlets as $id_outlet) {
                    try {
                        $amount = MyHelper::calculator($formula, [
                            'total_attend' => $total_attend,
                            'total_late' => $total_late,
                            'total_absen' => $total_absen,
                            'total_overtime' => $total_overtime,
                        ]);
                    } catch (\Exception $e) {
                        $amount = 0;
                    }
                }
                $total = $total+$amount;
               
            } elseif (strpos($calculation, 'salary_cut_') === 0) { // start_with_calculation
                $code = str_replace('salary_cut_', '', $calculation);
                $salary_cut = HairstylistGroupPotonganDefault::leftJoin('hairstylist_group_potongans', function($join) use ($hs) {
                    $join->on('hairstylist_group_potongans.id_hairstylist_group_default_potongans', 'hairstylist_group_default_potongans.id_hairstylist_group_default_potongans')
                        ->where('id_hairstylist_group', $hs->id_hairstylist_groups);
                })->where('hairstylist_group_default_potongans.code', $code)
                         ->select('hairstylist_group_default_potongans.id_hairstylist_group_default_potongans','hairstylist_group_default_potongans.code',
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_potongans.value IS NOT NULL THEN hairstylist_group_potongans.value ELSE hairstylist_group_default_potongans.value
                                       END as value
                                    '),
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_potongans.formula IS NOT NULL THEN hairstylist_group_potongans.formula ELSE hairstylist_group_default_potongans.formula
                                       END as formula
                                    '))
                        ->first();
                if (!$salary_cut) {
                    continue;
                }
                
                $formula = str_replace('value', $salary_cut->value, $salary_cut->formula);
                $amount = 0;
                $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->get()->pluck('id_outlet');
                foreach ($id_outlets as $id_outlet) {
                   
                    try {
                        $amount = MyHelper::calculator($formula, [
                            'total_attend' => $total_attend,
                            'total_late' => $total_late,
                            'total_absen' => $total_absen,
                            '$total_overtime' => $total_overtime,
                        ]);
                    } catch (\Exception $e) {
                        $amount = 0;
                    }
                  
                }
                $total = $total-$amount;
                
            }
        }
          $array = array(
              array(
                    "name"=> "total commission",
                    "value"=> $total
                    
                ),
                 array(
                    "name"=>"tambahan jam",
                    "value"=>$total_overtime
                 ),
              
                 array(
                    "name"=>"potongan telat",
                    "value"=>$total_late
                 ),
             );
        return $array;
    }
    public static function calculateIncomeGross(UserHairStylist $hs, $startDate,$endDate)
    {
        $total = 0;
        $calculation_mid = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
        $calculation_end = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
        $calculations    = array_unique(array_merge($calculation_mid,$calculation_end));
        if (!$calculations) {
            throw new \Exception('No calculation for income. Check setting!');
        }
        $total_attend = 0;
        $total_late = 0;
        $total_absen = 0;
        $total_overtime = 0;
        $overtime = array();
        $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');
        foreach ($id_outlets as $id_outlet) {
                    $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_late = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->where('is_on_time', 0)
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_absen = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->select('date')
                        ->get();
                    foreach ($total_overtimes as $value) {
                        array_push($overtime,$value);
                    }
                  
                }
                $over = 0;
                $ove = array();
                foreach (array_unique($overtime) as $value) {
                    $overtimess = HairstylistOverTime::where('id_user_hair_stylist',$hs->id_user_hair_stylist)
                            ->wherenotnull('approve_at')
                            ->wherenull('reject_at')
                            ->wheredate('date',$value['date'])
                            ->get();
                    foreach ($overtimess as $va) {
                        array_push($ove,$va['duration']);
                    }
                }
                $h = 0;
                $m = 0;
                $d = 0;
                foreach ($ove as $value) {
                    $va = explode(":", $value);
                    $h += $va[0];
                    $m += $va[1];
                    $d += $va[2];
                }
                if($d>60){
                  $s = floor($d / 60);
                  $m = $s + $m;
                }
                if($m>60){
                  $s = floor($m / 60);
                  $h = $s + $h;
                }
             $total_overtime = $h;
             $array = array(
                 array(
                    "name"=>"hari masuk",
                    "value"=>$total_attend
                 )
             );
        foreach  ($calculations as $calculation) {
            if ($calculation == 'product_commission') {
                $trxs = TransactionProduct::select('transaction_products.id_transaction', 'transaction_products.id_transaction_product', 'transaction_breakdowns.*')
                    ->join('transaction_breakdowns', function($join) use ($startDate, $endDate) {
                        $join->on('transaction_breakdowns.id_transaction_product', 'transaction_products.id_transaction_product')
                            ->whereNotNull('transaction_products.transaction_product_completed_at')
                            ->whereBetween('transaction_product_completed_at',[$startDate,$endDate]);
                    })
                    ->where('transaction_breakdowns.type', 'fee_hs')
                    ->with('transaction')
                    ->get();
                $total_product_commission = 0;
                $trxs->each(function ($item) use ($total_product_commission,$total) {
                     $total = $total+$item->value;
                });
              
            } elseif (strpos($calculation, 'incentive_') === 0) { // start_with_calculation
                $code = str_replace('incentive_', '', $calculation);
                $incentive = HairstylistGroupInsentifDefault::leftJoin('hairstylist_group_insentifs', function($join) use ($hs) {
                                $join->on('hairstylist_group_insentifs.id_hairstylist_group_default_insentifs', 'hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs')
                                ->where('id_hairstylist_group', $hs->id_hairstylist_groups);
                            })->where('hairstylist_group_default_insentifs.code', $code)
                            ->select('hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs','hairstylist_group_default_insentifs.code',
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_insentifs.value IS NOT NULL THEN hairstylist_group_insentifs.value ELSE hairstylist_group_default_insentifs.value
                                       END as value
                                    '),
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_insentifs.formula IS NOT NULL THEN hairstylist_group_insentifs.formula ELSE hairstylist_group_default_insentifs.formula
                                       END as formula
                                    ')
                                )->first();
                if (!$incentive) {
                    continue;
                }
                $formula = str_replace('value', $incentive->value, $incentive->formula);
                $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->get()->pluck('id_outlet');
                $amount = 0;
                foreach ($id_outlets as $id_outlet) {
                    try {
                        $amount = MyHelper::calculator($formula, [
                            'total_attend' => $total_attend,
                            'total_late' => $total_late,
                            'total_absen' => $total_absen,
                            'total_overtime' => $total_overtime,
                        ]);
                    } catch (\Exception $e) {
                        $amount = 0;
                    }
                }
                $total = $total+$amount;
               
            }
        }
        $array[] = array(
                    "name"=> "total gross income",
                    "value"=> $total
                    
                );
        return $array;
    }
}