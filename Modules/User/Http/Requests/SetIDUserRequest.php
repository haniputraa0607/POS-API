<?php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\User\Entities\User;

class SetIDUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "id" => "required",
            "equal_id" => "required"
        ];
    }
    
    public function authorize()
    {
        return true;
    }

}
