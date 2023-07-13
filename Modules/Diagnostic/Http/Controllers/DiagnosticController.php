<?php

namespace Modules\Diagnostic\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Diagnostic\Entities\Diagnostic;
use Modules\Diagnostic\Http\Requests\DiagnosticRequest;

class DiagnosticController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        $data = Diagnostic::isActive()->paginate($request->length ?? 10);
        return $this->ok("success", $data);
    }

    public function store(DiagnosticRequest $request): JsonResponse
    {
        $diagnostic = Diagnostic::create($request->all());
        return $this->ok("success", $diagnostic);
    }

    public function show(Diagnostic $diagnostic): JsonResponse
    {
        return $this->ok("success", $diagnostic);
    }

    public function update(DiagnosticRequest $request, Diagnostic $diagnostic): JsonResponse
    {
        $diagnostic->update($request->all());
        return $this->ok("success", $diagnostic);
    }


    public function destroy(Diagnostic $diagnostic): JsonResponse
    {
        $diagnostic->delete();
        return $this->ok("success", $diagnostic);
    }
}
