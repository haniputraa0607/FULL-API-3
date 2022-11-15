<?php

namespace App\Http\Models;

use App\Lib\MyHelper;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Modules\Subscription\Entities\Subscription;

class Banner extends Model
{
    protected $primaryKey = 'id_banner';

    protected $fillable   = [
        'image',
        'id_reference',
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
        return $this->belongsTo(News::class, 'id_reference', 'id_news');
    }


    public function storeImage($image)
    {
        // img 4:3
        $upload = MyHelper::uploadPhotoStrict($image, "img/banner/", 750, 375);

        if (@$upload['status'] == "success") {
            $this->attributes['image'] = $upload['path'];
            return $upload;
        }

        throw new UploadException("Failed to upload image");
    }

    public function setPosition($position = 0)
    {
        if (empty($position)) {
            $last_position = self::max('position');
            if ($last_position == null) {
                $last_position = 0;
            }

            $position = $last_position + 1;
        }

        $this->attributes['position'] = $position;
    }

    public function setTypeAttribute($type)
    {
        if ($type == 'gofood') {
            $this->attributes['url'] = config('url.app_url') . 'outlet/webview/gofood/list';
            ;
        }
        $this->attributes['type'] = $type;
    }

    public function getReferenceTitleAttribute($value)
    {
        if ($this->id_reference && !in_array($this->type, ['elearning', 'merchant_detail', 'product_detail', 'promo_detail', 'doctor_detail'])) {
            switch ($this->type) {
                case 'promo_detail':
                    return Deal::where('id_deals', $this->id_reference)->value('deals_title');
                    break;
                case 'merchant_detail':
                    return Merchant::where('id_merchant', $this->id_reference)->value('merchant_pic_name');
                    break;
                case 'product_detail':
                    return Product::where('id_product', $this->id_reference)->value('product_name');
                    break;
                case 'doctor_detail':
                    return Product::where('id_doctor', $this->id_reference)->value('doctor_name');
                    break;
                case 'link':
                case 'url':
                    return $this->url;
                    break;
                default:
                    return News::where('id_news', $this->id_reference)->value('news_title');
            }
        }
        return ucwords(str_replace('_', ' ', $this->type));
    }

    public function getTypeAttribute($value)
    {
        if ($value == 'general') {
            if ($this->id_reference) {
                return 'news';
            } else {
                return 'none';
            }
        }
        return $value;
    }
}
