<?php

namespace Modules\Banner\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BannerRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required',
            'product_id' => 'required|exists:products,id',
        ];
    }

    public function messages():array{
        return [
            'product_id.exists' => 'Product not found',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
