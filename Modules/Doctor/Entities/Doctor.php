<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Doctor\Entities\DoctorClinic;
use Modules\Doctor\Entities\DoctorSpecialist;

class Doctor extends Model
{
    protected $table = 'doctors';

    protected $primaryKey = 'id_doctor';

    protected $fillable   = [
        'doctor_name',
        'doctor_phone',
        'password',
        'id_doctor_clinic',
        'id_doctor_specialist',
        'doctor_status',
        'doctor_session_price',
        'is_active',
        'doctor_sevice',
        'doctor_photo'
    ];

    public function clinic()
    {
        return $this->belongsTo(DoctorClinic::class, 'id_doctor_clinic', 'id_doctor_clinic');
    }

    // public function specialists()
    // {
    //     return $this->belongsToMany(DoctorSpecialist::class);
    // }

    // public function specialistPivot(Model $parent, array $attributes, $table, $exists)
    // {
    //     if ($parent instanceof DoctorSpecialist)
    //     {
    //         return new SpecialistPivot($parent, $attributes, $table, $exists);
    //     }

    //     return parent::specialistPivot($parent, $attributes, $table, $exists);
    // }

    public function specialists()
    {
        return $this->belongsToMany(DoctorSpecialist::class, 'doctors_specialists_pivots', 'id_doctor', 'id_doctor_specialist');
    }

    // public function specialists()
    // {
    //     return $this->belongsToMany(DoctorSpecialist::class)
    //     ->using(SpecialistPivot::class)
    //     ->withPivot('id_doctor', 'id_doctor_specialist');
    // }

    // public function specialists()
    // {
    //     return $this->hasMany(SpecialistPivot::class, 'id_doctor', 'id_doctor');
    // }

    /*public function getLogoBrandAttribute($value)
    {
        if(empty($value)){
            return '';
        }
        return config('url.storage_url_api') . $value;
    }

    public function getImageBrandAttribute($value)
    {
        if(empty($value)){
            return '';
        }
        return config('url.storage_url_api') . $value;
    }

    public function brand_product(){
        return $this->hasMany(BrandProduct::class, 'id_brand', 'id_brand');
    }

    public function brand_outlet(){
        return $this->hasMany(BrandOutlet::class, 'id_brand', 'id_brand');
    }

    public function daily_report_trx_menus()
    {
        return $this->hasMany(\App\Http\Models\DailyReportTrxMenu::class, 'id_brand', 'id_brand');
    }

    public function monthly_report_trx_menus()
    {
        return $this->hasMany(\App\Http\Models\MonthlyReportTrxMenu::class, 'id_brand', 'id_brand');
    }

    public function daily_report_trx_modifiers()
    {
        return $this->hasMany(\Modules\Report\Entities\DailyReportTrxModifier::class, 'id_brand', 'id_brand');
    }

    public function monthly_report_trx_modifiers()
    {
        return $this->hasMany(\Modules\Report\Entities\MonthlyReportTrxModifier::class, 'id_brand', 'id_brand');
    }

    public function transaction_products()
    {
		return $this->hasMany(\App\Http\Models\TransactionProduct::class, 'id_brand');
    }*/
}
