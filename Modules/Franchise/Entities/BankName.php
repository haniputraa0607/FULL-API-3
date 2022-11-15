<?php

namespace Modules\Franchise\Entities;

use Illuminate\Database\Eloquent\Model;

class BankName extends Model
{
    protected $connection = 'mysql3';
    protected $table = 'bank_name';
    protected $primaryKey = 'id_bank_name';

    protected $fillable = [
        'bank_code',
        'bank_name'
    ];
}
