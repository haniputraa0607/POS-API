<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductWebHookUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id_item' => 'required',
            'item_name' => 'required',
            'item_code' => 'required',
            'item_type' => 'required',
            'description' => 'required',
            'id_item_category' => [
                'required',
                Rule::exists('product_categories', 'equal_id')->where(function ($query) {
                    return $query->where('equal_id', $this->input('id_item_category'));
                }),
            ],
            'id_item_category' => [
                'required',
                Rule::exists('product_categories', 'equal_id')->where(function ($query) {
                    return $query->where('equal_id', $this->input('id_item_category'));
                }),
            ],
            'item_groups' => [
                Rule::exists('products', 'equal_id')->where(function ($query) {
                    return $query->whereIn('equal_id', $this->input('item_groups.*.id_item_child'));
                }),
            ],
        ];
    }

    public function messages()
    {
        return [
            'id_item.unique' => 'ID Item already exists',
        ];
    }

    public function authorize()
    {
        return true;
    }
}
