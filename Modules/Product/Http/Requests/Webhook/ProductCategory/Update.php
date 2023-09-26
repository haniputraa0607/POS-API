<?php

namespace Modules\Product\Http\Requests\Webhook\ProductCategory;

use Illuminate\Foundation\Http\FormRequest;

class Update extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'equal_id' =>'required|exists:product_categories,equal_id',
            'equal_name' =>'required',
            'equal_code' =>'required',
            'equal_parent_id' =>'required',
            'product_category_name' =>'required',
        ];
    }

    public function prepareForValidation() : void {
        $payload = [
            'equal_id' => $this->id_item_category,
            'equal_name' => $this->item_category_name,
            'equal_code' => $this->item_category_code,
            'equal_parent_id' => $this->id_item_category_parent,
            'product_category_name' => $this->item_category_name,
            'product_category_photo' => $this->photo_path,
        ];
        
        $this->replace($payload);
    }

    public function messages()
    {
        return [
            'equal_id.required' => 'id item category is required',
            'equal_id.exists' => 'id item category does not exist',
            'equal_code.required' => 'item category code is required',
            'equal_parent_id.required' => 'id item category parent is required',
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
