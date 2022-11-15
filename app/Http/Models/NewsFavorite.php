<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\ProductBundling\Entities\BundlingCategory;

class NewsFavorite extends Model
{
    protected $connection = 'mysql';
    protected $table = 'news_favorites';
    protected $primaryKey = 'id_news_favorite';

    protected $fillable = [
        'id_user',
        'id_news'
    ];
}
