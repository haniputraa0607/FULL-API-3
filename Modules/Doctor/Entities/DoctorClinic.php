<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;

class DoctorClinic extends Model
{
    protected $table = 'doctors_clinics';

    protected $primaryKey = 'id_doctor_clinic';

    protected $fillable   = [
        'doctor_clinic_name',
        'is_active',
    ];

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
