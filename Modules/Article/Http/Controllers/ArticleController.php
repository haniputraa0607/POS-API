<?php

namespace Modules\Article\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Article\Entities\Article;
use Illuminate\Http\JsonResponse;
use App\Traits\ApiResponse;

class ArticleController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $post = $request->json()->all();
        $paginate = empty($post['pagination_total_row']) ? 8 : $post['pagination_total_row'];
        $articles = Article::paginate($paginate, ['*'], 'page', $post['page']);
        foreach ($articles as $article) {
            $article->image = 'https://be-daviena.belum.live/images/'.$article->image;
        }
        return $this->ok("success get data all article", $articles);
    }

    public function show(Article $article): JsonResponse
    {
        return $this->ok("success", $article);
    }

}
