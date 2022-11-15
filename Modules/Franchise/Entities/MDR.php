<?php

namespace Modules\Franchise\Entities;

use Illuminate\Database\Eloquent\Model;

class MDR extends Model
{
    protected $connection = 'mysql3';
    protected $table = 'mdr';
    protected $primaryKey = 'id_mdr';

    protected $fillable = [
        'payment_name',
        'mdr',
        'mdr_central',
        'percent_type',
        'charged',
        'days_to_sent'
    ];
}
