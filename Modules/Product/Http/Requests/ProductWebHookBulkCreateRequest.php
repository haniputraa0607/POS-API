<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductWebHookBulkCreateRequest extends FormRequest
{
    public function rules()
    {
        return [
            '*.id_item' => [
                'required',
            ],
            '*.item_name' => 'required',
            '*.item_code' => 'required',
            '*.item_type' => 'required',
            '*.description' => 'required',
            '*.id_item_category' => [
                'required',
                Rule::exists('product_categories', 'equal_id'),
            ],
            '*.item_groups.*.id_item_child' => [
                Rule::exists('products', 'equal_id'),
            ],
        ];
    }

    public function messages()
    {
        return [
            '*.id_item.required' => 'ID Item is required.',
            '*.item_name.required' => 'Item Name is required.',
            '*.item_code.required' => 'Item Code is required.',
            '*.item_type.required' => 'Item Type is required.',
            '*.description.required' => 'Description is required.',
            '*.id_item_category.required' => 'ID Item Category is required.',
            '*.id_item.unique' => 'ID Item already exists.',
        ];
    }

    public function authorize()
    {
        return true;
    }
}
