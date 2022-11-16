<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Modules\UserRating\Entities\UserRating;

/**
 * Class TransactionProduct
 *
 * @property int $id_transaction_product
 * @property int $id_transaction
 * @property int $id_product
 * @property int $transaction_product_qty
 * @property int $transaction_product_price
 * @property int $transaction_product_subtotal
 * @property string $transaction_product_note
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \App\Http\Models\Product $product
 * @property \App\Http\Models\Transaction $transaction
 *
 * @package App\Models
 */
class TransactionConsultation extends \App\Http\Models\Template\TransactionService
{
    protected $primaryKey = 'id_transaction_consultation';

    protected $casts = [
        'id_transaction' => 'int',
        'id_doctor' => 'int',
        'id_customer' => 'int'
    ];

    protected $fillable = [
        'id_transaction',
        'id_doctor',
        'id_user',
        'consultation_type',
        'schedule_date',
        'schedule_start_time',
        'schedule_end_time',
        'consultation_start_time',
        'consultation_end_time',
        'consultation_session_price',
        'consultation_status',
        'completed_at',
        'recipe_code',
        'recipe_redemption_limit',
        'recipe_redemption_counter',
        'referral_code',
        'id_conversation',
        'id_doctor_infobip',
        'id_user_infobip',
        'reason_status_change',
        'id_user_modifier'
    ];

    protected $appends = [
        'schedule_date_formatted',
        'schedule_date_human_formatted',
        'schedule_date_human_short_formatted',
        'schedule_start_time_formatted',
        'schedule_end_time_formatted',
        'schedule_day_formatted'
    ];

    public function scopeOnlySoon($query)
    {
        return $query->whereIn('consultation_status', ["soon", 'ongoing']);
    }

    public function scopeOnlyOngoing($query)
    {
        return $query->orWhere('consultation_status', "ongoing");
    }

    public function scopeOnlyDone($query)
    {
        return $query->where('consultation_status', "done");
    }

    public function doctor()
    {
        return $this->belongsTo(\Modules\Doctor\Entities\Doctor::class, 'id_doctor');
    }

    public function user()
    {
        return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
    }

    public function recomendation()
    {
        return $this->hasMany(\App\Http\Models\TransactionConsultationRecomendation::class, 'id_transaction_consultation', 'id_transaction_consultation');
    }

    public function triggerPaymentCancelled($data = [])
    {
        $this->update(['consultation_status' => 'canceled']);

        return true;
    }

    public function getScheduleDateFormattedAttribute()
    {
        return date('d-m-Y', strtotime($this->attributes['schedule_date']));
    }

    public function getScheduleDateHumanFormattedAttribute()
    {
        $dateId = Carbon::parse($this->attributes['schedule_date'])->locale('id');
        $dateId->settings(['formatFunction' => 'translatedFormat']);
        $date = $dateId->format('d F Y');

        return $date;
    }

    public function getScheduleDateHumanShortFormattedAttribute()
    {
        return date('d M Y', strtotime($this->attributes['schedule_date']));
    }

    public function getScheduleStartTimeFormattedAttribute()
    {
        return date('H:i', strtotime($this->attributes['schedule_start_time']));
    }

    public function getScheduleEndTimeFormattedAttribute()
    {
        return date('H:i', strtotime($this->attributes['schedule_end_time']));
    }

    public function getScheduleDayFormattedAttribute()
    {
        $dateId = Carbon::parse($this->attributes['schedule_date'])->locale('id');
        $dateId->settings(['formatFunction' => 'translatedFormat']);
        $dayId = $dateId->format('l');

        return $dayId;
    }

    public function getConsultationChatUrlAttribute()
    {
        return url('api/consultation/detail/chat.html') . '?id_transaction=' . $this->id_transaction;
    }

    public function getIsRatedAttribute()
    {
        //get rating from consultation
        $rating = UserRating::where('id_transaction', $this->id_transaction)->first();

        $is_rated = false;
        if (!empty($rating)) {
            $is_rated = true;
        }

        return $is_rated;
    }

    public function getRatingValueAttribute()
    {
        //get rating value from consultation
        $rating = UserRating::where('id_transaction', $this->id_transaction)->first();

        $rating_value = 0;
        if (!empty($rating)) {
            $rating_value = $rating->rating_value;
        }

        return $rating_value;
    }
}
