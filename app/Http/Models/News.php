<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
	protected $connection = 'mysql';
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'news';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'id_news';

    protected $appends  = ['url_news_image_luar', 'url_news_image_dalam', 'url_form','news_form_status'];

    /**
     * @var array
     */
    protected $fillable = [
    	'news_slug', 
		'news_title', 
		'news_second_title', 
		'news_content_short', 
		'news_content_long', 
		'news_video', 
		'news_image_luar', 
		'news_image_dalam', 
		'news_post_date', 
		'news_publish_date', 
		'news_expired_date',
		'news_date_start',
		'news_date_end',
		'news_time_start',
		'news_time_end', 
		'news_location_name', 
		'news_location_phone', 
		'news_location_address', 
		'news_location_map', 
		'news_latitude', 
		'news_longitude', 
		'news_outlet_text', 
		'news_product_text', 
		'news_treatment_text', 
		'news_button_form_text', 
		'news_button_form_expired',
		'news_form_success_message',
		'created_at', 
		'updated_at'
	];
	
	public function getUrlFormAttribute() {
		if (empty($this->news_button_form_text)) {
            return null;
        }
        else {
            return env('APP_URL').'/news_form/'.$this->id_news.'/form';
        }
	}
	
	public function getNewsFormStatusAttribute() {
		$today = date("Y-m-d H:i:s");
		if (strtotime($this->news_button_form_expired) <= strtotime($today)) {
            return false;
        }
        else {
            return true;
        }
	}
	
	public function getUrlNewsImageLuarAttribute() {
		if (empty($this->news_image_luar)) {
            return env('AWS_URL').'img/default.jpg';
        }
        else {
            return env('AWS_URL').$this->news_image_luar;
        }
	}

	public function getUrlNewsImageDalamAttribute() {
		if (empty($this->news_image_dalam)) {
            return env('AWS_URL').'img/default.jpg';
        }
        else {
            return env('AWS_URL').$this->news_image_dalam;
        }
	}

	public function scopeId($query, $id) {
		return $query->where('id_news', $id);
	}

	public function scopeSlug($query, $id) {
		return $query->where('news_slug', $id);
	}

	public function newsOutlet()
	{
	    return $this->hasMany(NewsOutlet::class, 'id_news', 'id_news');
	}

	public function news_form_structures(){
		return $this->hasMany(NewsFormStructure::class, 'id_news', 'id_news');
	}
	public function newsProduct()
	{
	    return $this->hasMany(NewsProduct::class, 'id_news', 'id_news');
	}

	public function newsTreatment()
	{
	    return $this->hasMany(NewsTreatment::class, 'id_news', 'id_news');
	}
}
