<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class NewsOutlet extends Model
{
	protected $connection = 'mysql';
	public $incrementing = false;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'news_outlets';

    /**
     * @var array
     */
    protected $fillable = ['id_outlet', 'id_news'];
	
	/**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'id_outlet', 'id_outlet');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function news()
    {
        return $this->hasMany(News::class, 'id_news', 'id_news');
    }
}
