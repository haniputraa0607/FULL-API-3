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
class Subdistricts extends Model
{
    protected $table = 'subdistricts';
    protected $primaryKey = 'id_subdistrict';
    public $timestamps = false;

    protected $fillable = [
        'id_subdistrict_external',
        'id_district',
        'subdistrict_name',
        'subdistrict_postal_code',
        'subdistrict_latitude',
        'subdistrict_longitude'
    ];

    public function district()
    {
        return $this->belongsTo(\App\Http\Models\Districts::class, 'id_district');
    }
}
