<?php

namespace Modules\POS\Entities;

use Illuminate\Database\Eloquent\Model;

class SyncMenuRequest extends Model
{
    protected $table = 'sync_menu_request';

    protected $fillable   = [
        'request',
        'store_code',
        'is_end'
    ];
}
