<?php

namespace Modules\Grievance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Grievance\Entities\Grievance;
use Modules\Grievance\Http\Requests\GrievanceRequest;

class GrievanceController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        $data = Grievance::isActive()->paginate($request->length ?? 10);
        return $this->ok("success", $data);
    }

    public function store(GrievanceRequest $request): JsonResponse
    {
        $grievance = Grievance::create($request->all());
        return $this->ok("success", $grievance);
    }

    public function show(Grievance $grievance): JsonResponse
    {
        return $this->ok("success", $grievance);
    }

    public function update(GrievanceRequest $request, Grievance $grievance): JsonResponse
    {
        $grievance->update($request->all());
        return $this->ok("success", $grievance);
    }
    public function destroy(Grievance $grievance): JsonResponse
    {
        $grievance->delete();
        return $this->ok("success", $grievance);
    }
}
