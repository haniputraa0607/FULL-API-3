<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class NewsProduct extends Model
{
	protected $connection = 'mysql';
	public $incrementing = false;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'news_products';

    /**
     * @var array
     */
    protected $fillable = ['id_product', 'id_news'];
	
	/**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function news()
    {
        return $this->hasMany(News::class, 'id_news', 'id_news');
    }
}
