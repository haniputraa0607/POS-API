<?php

namespace Modules\Outlet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EqualIdOutletRequest extends FormRequest
{
    
    public function rules()
    {
        return [
            'id' => 'required',
            'equal_id' => 'required',
        ];
    }

    public function authorize()
    {
        return true;
    }
}
