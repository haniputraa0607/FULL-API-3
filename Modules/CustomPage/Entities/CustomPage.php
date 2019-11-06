<?php

namespace Modules\CustomPage\Entities;

use Illuminate\Database\Eloquent\Model;

class CustomPage extends Model
{
    protected $table = 'custom_pages';

    protected $primaryKey = 'id_custom_page';

    protected $fillable = [
        'custom_page_title',
        'custom_page_menu',
        'custom_page_description',
        'custom_page_order',
        'custom_page_icon_image',
        'custom_page_video_text',
        'custom_page_video',
        'custom_page_event_date_start',
        'custom_page_event_date_end',
        'custom_page_event_time_start',
        'custom_page_event_time_end',
        'custom_page_event_location_name',
        'custom_page_event_location_phone',
        'custom_page_event_location_address',
        'custom_page_event_location_map',
        'custom_page_event_latitude',
        'custom_page_event_longitude',
        'custom_page_outlet_text',
        'custom_page_product_text',
        'custom_page_button_form',
        'custom_page_button_form_text',
    ];

    public function custom_page_image_header()
    {
        return $this->hasMany(CustomPageImage::class, 'id_custom_page', 'id_custom_page')->orderBy('image_order');
    }

    public function custom_page_outlet()
    {
        return $this->hasMany(CustomPageOutlet::class, 'id_custom_page', 'id_custom_page');
    }

    public function custom_page_product()
    {
        return $this->hasMany(CustomPageProduct::class, 'id_custom_page', 'id_custom_page');
    }
}
