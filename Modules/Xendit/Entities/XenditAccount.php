<?php

namespace Modules\Xendit\Entities;

use Illuminate\Database\Eloquent\Model;

class XenditAccount extends Model
{
    protected $primaryKey = 'id_xendit_account';
    protected $fillable = [
        'xendit_id',
        'type',
        'email',
        'public_profile',
        'country',
    ];
    protected $casts = [
        'public_profile' => 'array',
    ];
}
