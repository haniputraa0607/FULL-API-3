<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $table = 'otps';

    protected $primaryKey = 'id_otp';

    protected $fillable = [
        'otp',
        'phone_number',
        'purpose',
        'expired_at'
    ];

    public function scopeOnlyNotExpired($query)
    {
        return $query->where('is_expired', false);
    }
}
