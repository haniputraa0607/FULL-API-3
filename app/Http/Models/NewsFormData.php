<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class NewsFormData extends Model
{
	protected $connection = 'mysql';
    protected $table = 'news_form_datas';
    protected $primaryKey = 'id_news_form_data';

    protected $fillable = ['id_news', 'id_user'];
}
