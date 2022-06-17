<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistGroupPotonganDefault extends Model
{
	protected $table = 'hairstylist_group_default_potongans';
	protected $primaryKey = 'id_hairstylist_group_default_potongans';


	protected $fillable = [
        'name',
        'code',
        'value',
        'formula',
        'created_at',   
        'updated_at',
        'code'
	];
}
