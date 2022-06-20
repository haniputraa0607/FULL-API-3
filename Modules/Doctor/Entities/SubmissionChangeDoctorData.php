<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;

class SubmissionChangeDoctorData extends Model
{
    protected $table = 'submission_change_doctor_data';

    protected $primaryKey = 'id_submission';

    protected $fillable = [
        'id_doctor',
        'modified_column',
        'modified_value',
        'modified_reason',
        'approved_by',
        'is_approved',
        'is_rejected',
        'approved_at',
        'rejected_at'
    ];
}
