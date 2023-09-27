<?php

namespace Modules\Contact\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Contact\Entities\ContactMessage;
use Modules\Contact\Entities\ContactOfficial;
use Modules\Contact\Entities\ContactSosialMedia;
use Modules\Contact\Http\Requests\ContactMessageRequest;

class ContactController extends Controller
{

    public function send_message(ContactMessageRequest $request)
    {
        $contact_message = ContactMessage::create($request->all());
        return $this->ok("success", $contact_message);
    }

    public function official()
    {
        $official = ContactOfficial::all();
        $sosial_media = ContactSosialMedia::all();
        $official->transform(function ($item, $key) {
            $item->official_value = json_decode($item->official_value);
            return $item;
        });
        return $this->ok("success", [
            'official' => $official,
            'sosial_media' => $sosial_media
        ]);
    }

}
