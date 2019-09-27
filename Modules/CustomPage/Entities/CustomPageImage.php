<?php

namespace Modules\CustomPage\Entities;

use Illuminate\Database\Eloquent\Model;

class CustomPageImage extends Model
{
    protected $table = 'custom_page_images';

    protected $primaryKey = 'id_custom_page_image';

    protected $fillable = [
        'id_custom_page',
        'custom_page_image',
        'image_order'
    ];
}
