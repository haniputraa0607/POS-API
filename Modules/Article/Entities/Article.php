<?php

namespace Modules\Article\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Article\Database\factories\ArticleFactory;

class Article extends Model
{
    use HasFactory;

    protected $table = 'articles';
    protected $fillable = [
        'title',
        'image',
        'writer',
        'release_date',
        'description',
    ];

    protected static function newFactory()
    {
        return ArticleFactory::new();
    }
}
