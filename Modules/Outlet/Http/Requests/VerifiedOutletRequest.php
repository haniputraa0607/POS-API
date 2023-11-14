<?php

namespace Modules\Outlet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifiedOutletRequest extends FormRequest
{
    
    public function rules()
    {
        return [
            'equal_id' => 'required',
            'verified_at' => 'required',
        ];
    }

    public function authorize()
    {
        return true;
    }
}
