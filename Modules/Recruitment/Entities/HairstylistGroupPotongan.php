<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistGroupPotongan extends Model
{
	protected $table = 'hairstylist_group_potongans';
	protected $primaryKey = 'id_hairstylist_group_potongan';


	protected $fillable = [
		'id_hairstylist_group',
		'id_hairstylist_group_default_potongans',
		'value',
                'code',
		'formula',
                'created_at',   
                'updated_at'
	];
}
