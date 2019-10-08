<?php

namespace Modules\POS\Entities;

use Illuminate\Database\Eloquent\Model;

class SyncMenuResult extends Model
{
    protected $table = 'sync_menu_result';

    protected $fillable   = [
        'result'
    ];
}
