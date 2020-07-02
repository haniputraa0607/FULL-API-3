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

    protected $appends  = ['url_news_image_luar', 'url_news_image_dalam', 'url_form','news_form_status', 'url_webview'];

    /**
     * @var array
     */
    protected $fillable = [
    	'id_news_category',
    	'news_slug',
		'news_title',
		'id_news_category',
		'news_second_title',
		'news_content_short',
		'news_content_long',
		'news_video',
		'news_video_text',
		'news_image_luar',
		'news_image_dalam',
		'news_post_date',
		'news_publish_date',
		'news_expired_date',
		'news_event_date_start',
		'news_event_date_end',
		'news_event_time_start',
		'news_event_time_end',
		'news_event_location_name',
		'news_event_location_phone',
		'news_event_location_address',
		'news_event_location_map',
		'news_event_latitude',
		'news_event_longitude',
		'news_outlet_text',
		'news_product_text',
		'news_treatment_text',
		'news_button_form_text',
		'news_button_form_expired',
		'news_form_success_message',
		'created_at',
		'updated_at'
	];

	public function getUrlWebviewAttribute() {
		return config('url.api_url') ."news/webview/". $this->id_news;
	}

	public function getUrlFormAttribute() {
		if (empty($this->news_button_form_text)) {
            return null;
        }
        else {
            return config('url.app_url').'/news_form/'.$this->id_news.'/form';
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
            return config('url.storage_url_api').'img/default.jpg';
        }
        else {
            return config('url.storage_url_api').$this->news_image_luar;
        }
	}

	public function getUrlNewsImageDalamAttribute() {
		if (empty($this->news_image_dalam)) {
            return config('url.storage_url_api').'img/default.jpg';
        }
        else {
            return config('url.storage_url_api').$this->news_image_dalam;
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
	public function newsCategory(){
		return $this->belongsTo(NewsCategory::class,'id_news_category','id_news_category');
	}
}
