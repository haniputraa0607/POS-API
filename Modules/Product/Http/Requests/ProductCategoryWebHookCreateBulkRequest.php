<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductCategoryWebHookCreateBulkRequest extends FormRequest
{
    public function rules()
    {
        return [
            '*.equal_id' => 'required|unique:product_categories,equal_id',
            '*.equal_code' => 'required',
            '*.product_category_name' => 'required',
            '*.equal_parent_id' => 'required', // Sesuaikan dengan nama kolom yang sesuai dalam database Anda
            '*.product_category_photo' => 'required', // Sesuaikan dengan nama kolom yang sesuai dalam database Anda
        ];
    }

    public function prepareForValidation() : void {
        $items = $this->all();

        $preparedData = [];
        foreach ($items as $item) {
            $preparedData[] = [
                'equal_id' => $item['id_item_category'],
                'equal_code' => $item['item_category_code'],
                'product_category_name' => $item['item_category_name'],
                'equal_parent_id' => $item['id_item_category_parent'],
                'product_category_photo' => $item['photo_path'],
            ];
        }

        $this->replace($preparedData);
    }

    public function messages()
    {
        return [
            '*.equal_id.required' => 'id item category is required',
            '*.equal_id.unique' => 'id item category has been taken',
            '*.equal_code.required' => 'item category code is required',
            '*.product_category_name.required' => 'item category name is required',
            '*.product_category_photo.required' => 'photo path is required',
            '*.equal_parent_id.required' => 'parent id is required',
        ];
    }

    public function authorize()
    {
        return true;
    }
}
