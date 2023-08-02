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
        $article = $request->length ?  Article::paginate($request->length ?? 10) : Article::get();
        return $this->ok("success get data all users", $article);
        return response()->json($article);
    }
    
    public function show(Article $article): JsonResponse
    {
        return $this->ok("success", $article);
    }

}
