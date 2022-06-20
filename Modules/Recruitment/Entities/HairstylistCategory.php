<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistCategory extends Model
{
    protected $table = 'hairstylist_categories';
	protected $primaryKey = 'id_hairstylist_category';

	protected $fillable = [
        'hairstylist_category_name'
	];
}
