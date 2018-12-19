<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class NewsFormDataDetail extends Model
{
	protected $connection = 'mysql';
    protected $table = 'news_form_data_details';
    protected $primaryKey = 'id_news_form_data_detail';

    protected $fillable = ['id_news_form_data', 'id_news', 'form_input_label', 'form_input_value'];

    public function news_form_data()
    {
        return $this->belongsTo(NewsFormData::class, 'id_news_form_data', 'id_news_form_data');
    }
}
