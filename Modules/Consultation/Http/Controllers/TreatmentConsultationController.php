<?php

namespace Modules\Consultation\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Consultation\Http\Requests\TreatmentConsultationRequest;
use App\Lib\MyHelper;
use Illuminate\Support\Facades\Storage;

class TreatmentConsultationController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function update(TreatmentConsultationRequest $request)//: JsonResponse
    {
        $upload_front = $request->file('image_front')->storeAs('public/treatment_consultation', 'image_front.png');
        $upload_behind = $request->file('image_behind')->storeAs('public/treatment_consultation', 'image_behind.png');
        $payload = [
            'title' => $request->title,
            'description' => $request->description,
            'image_front' => 'storage/treatment_consultation/image_front.png',
            'image_behind' => 'storage/treatment_consultation/image_behind.png',
        ];
        $payloadJson = json_encode($payload);
        Storage::put('public/treatment_consultation/payload.json', $payloadJson);
        return $this->ok('success', $payload);
    }

    public function index(){
        $fileContents = Storage::get('public/treatment_consultation/payload.json');
        if (empty($fileContents)) {
            return $this->error('Treatment and Consultation not set yet');
        } else {
            return $this->ok('success', json_decode($fileContents));
        }
    }
}
