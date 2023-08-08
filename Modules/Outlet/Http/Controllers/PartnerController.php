<?php

namespace Modules\Outlet\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Outlet\Entities\Partner;

class PartnerController extends Controller
{
    public function index(Request $request):JsonResponse
    {
        $post = $request->json()->all();
        $paginate = empty($post['pagination_total_row']) ? 8 : $post['pagination_total_row'];
        $partner = Partner::paginate($paginate, ['*'], 'page', $post['page']);
        return $this->ok('', $partner);
    }

    public function show(Partner $partner):JsonResponse
    {
        return $this->ok('', $partner);
    }

}
