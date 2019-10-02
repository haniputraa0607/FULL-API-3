<?php

namespace Modules\Setting\Entities;

use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    protected $table = 'app_versions';
    protected $primaryKey = 'id_app_version';
    protected $fillable = [
        'app_type',
        'app_version',
        'rules',
    ];
}
