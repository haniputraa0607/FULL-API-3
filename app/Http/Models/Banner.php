<?php

namespace App\Http\Models;

use App\Lib\MyHelper;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;

class Banner extends Model
{
    protected $primaryKey = 'id_banner';
	
	protected $fillable   = [
		'image',
		'id_news',
		'url',
		'position',
		'type',
		'banner_start',
		'banner_end',
		"time_start",
		"time_end"
	];

	public function news()
	{
		return $this->belongsTo(News::class, 'id_news', 'id_news');
	}


	public function storeImage($image)
	{
        // img 4:3
	    $upload = MyHelper::uploadPhotoStrict($image, "img/banner/", 750, 375);

        if ( @$upload['status'] == "success" ) {
            $this->attributes['image'] = $upload['path'];
            return $upload;
        }

        throw new UploadException("Failed to upload image");
	}

	public function setPosition($position = 0)
	{
		if(empty($position)) {
			$last_position = self::max('position');
	        if ($last_position == null) $last_position = 0;

	        $position = $last_position + 1;
		}

        $this->attributes['position'] = $position;
	}

	public function setTypeAttribute($type)
	{
        if ($type == 'gofood') {
            $this->attributes['url'] = config('url.app_url').'outlet/webview/gofood/list';;
        }
        $this->attributes['type'] = $type;
	}
}
