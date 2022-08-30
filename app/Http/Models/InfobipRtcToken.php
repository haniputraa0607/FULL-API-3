<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class InfobipRtcToken extends Model
{
    protected $fillable = [
        'token',
        'expired_at'
    ];

    public function tokenable()
    {
        return $this->morphTo();
    }
}
