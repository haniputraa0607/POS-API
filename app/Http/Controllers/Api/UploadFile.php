<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Lib\MyHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadFile extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $uploads = [];
        foreach ($request->file('images') as $image) {
            $uploaded = Storage::put($request->folder, $image);
            array_push($uploads, $uploaded);
        }

        return $this->ok('success upload file', $uploads);
    }
}
