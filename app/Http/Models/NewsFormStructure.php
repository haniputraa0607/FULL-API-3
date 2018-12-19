<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class NewsFormStructure extends Model
{
	protected $connection = 'mysql';
	public $incrementing = false;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'news_form_structures';

    /**
     * @var array
     */
    protected $fillable = ['id_news_form_structure','id_news','form_input_types','form_input_options','form_input_label','form_input_autofill','is_unique','is_required','position'];

    public function news()
    {
        return $this->belongsTo(News::class, 'id_news', 'id_news');
    }
}
