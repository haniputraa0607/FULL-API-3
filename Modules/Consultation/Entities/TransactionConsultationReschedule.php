<?php

namespace Modules\Consultation\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionConsultationReschedule extends Model
{
    protected $table = 'transaction_consultation_reschedules';
    protected $primaryKey = 'id_transaction_consultation_reschedules';

    protected $fillable = [
        'id_transaction_consultation',
        'id_doctor',
        'id_user',
        'old_schedule_date',
        'old_schedule_start_time',
        'old_schedule_end_time',
        'new_schedule_date',
        'new_schedule_start_time',
        'new_schedule_end_time',
        'id_user_modifier',
        'user_modifier_type',
    ];
}
