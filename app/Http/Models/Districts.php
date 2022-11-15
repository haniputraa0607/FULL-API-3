<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class City
 *
 * @property int $id_city
 * @property int $id_province
 * @property string $city_name
 * @property string $city_type
 * @property string $city_postal_code
 *
 * @property \App\Http\Models\Province $province
 * @property \Illuminate\Database\Eloquent\Collection $outlets
 * @property \Illuminate\Database\Eloquent\Collection $transaction_shipments
 * @property \Illuminate\Database\Eloquent\Collection $user_addresses
 * @property \Illuminate\Database\Eloquent\Collection $users
 *
 * @package App\Models
 */
class Districts extends Model
{
    protected $table = 'districts';
    protected $primaryKey = 'id_district';
    public $timestamps = false;

    protected $fillable = [
        'id_district_external',
        'id_city',
        'district_name'
    ];
}
