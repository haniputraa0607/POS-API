<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductCategoryWebHookCreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'equal_id' =>'required|unique:product_categories,equal_id',
            'equal_code' =>'required',
            'product_category_name' =>'required',
        ];
    }

    public function prepareForValidation() : void {
        $this->replace([
            'equal_id' => $this->id_item_category,
            'equal_name' => $this->item_category_name,
            'equal_code' => $this->item_category_code,
            'equal_parent_id' => $this->id_item_category_parent,
            'product_category_name' => $this->item_category_name,
            'product_category_photo' => $this->photo_path,
        ]);
    }

    public function messages()
    {
        return [
            'equal_id.required' => 'id item category is required',
            'equal_id.unique' => 'id item category has been taken',
            'equal_code.required' => 'item category code is required',
            'product_category_name.required' => 'item category name is required',
            'product_category_photo.required' => 'photo path is required',
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
