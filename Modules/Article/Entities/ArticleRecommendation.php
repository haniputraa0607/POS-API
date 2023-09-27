<?php

namespace Modules\Article\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Article\Database\factories\ArticleFactory;

class ArticleRecommendation extends Model
{
    use HasFactory;

    protected $table = 'article_recommendations';
    protected $fillable = [
        'article_top',
        'article_recommendation'
    ];

    public function topArticle()
    {
        return $this->belongsTo(Article::class, 'article_top', 'id');
    }

    protected static function newFactory()
    {
        return ArticleFactory::new();
    }
}
