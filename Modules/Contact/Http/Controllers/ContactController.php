<?php

namespace Modules\Contact\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Contact\Entities\ContactMessage;
use Modules\Contact\Http\Requests\ContactMessageRequest;

class ContactController extends Controller
{

    public function send_message(ContactMessageRequest $request)
    {
        $contact_message = ContactMessage::create($request->all());
        return $this->ok("success", $contact_message);
    }

}
