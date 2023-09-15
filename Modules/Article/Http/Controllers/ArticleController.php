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
            if (strpos($article->image, 'https://') !== 0) {
                $article->image = asset($article->image);
            }
        }
        return $this->ok("success get data all article", $articles);
    }


    public function show($id): JsonResponse
    {
        $article = Article::find($id)->first();
        $other_article = Article::whereNotIn('id', [$id])->inRandomOrder()->limit(3)->get();
        $payload = [
            'detail' => $article,
            'read_too' => $other_article
        ];
        return $this->ok("success", $payload);
    }

    public function otherArticle()
    {
        $articles = Article::orderBy('created_at', 'DESC')->limit(15)->get();
        foreach ($articles as $article) {
            if (strpos($article->image, 'https://') !== 0) {
                $article->image = asset($article->image);
            }
        }
        return $this->ok("success", $articles);
    }


}
