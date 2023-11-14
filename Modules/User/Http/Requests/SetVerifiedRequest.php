<?php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\User\Entities\User;

class SetVerifiedRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "equal_id" => "required",
            "verified_at" => "required"
        ];
    }
    
    public function authorize()
    {
        return true;
    }

}
