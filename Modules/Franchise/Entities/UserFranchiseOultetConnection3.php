<?php

namespace Modules\Franchise\Entities;

use Illuminate\Database\Eloquent\Model;

class UserFranchiseOultetConnection3 extends Model
{
    protected $connection = 'mysql3';
    protected $table = 'user_franchise_outlet';
    protected $primaryKey = 'id_user_franchise_outlet';

    protected $fillable = [
        'id_user_franchise',
        'id_outlet',
        'status_franchise'
    ];
}
