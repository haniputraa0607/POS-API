<?php

namespace Modules\Customer\Http\Requests;

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
            'name' => 'required|string',
            'gender' => 'required|in:Male,Female',
            'birth_date' => 'required|date_format:Y-m-d',
            'phone' => 'required|unique:customers,phone,'.$this->customer->id,
            'email' => 'required|email|unique:customers,email,'.$this->customer->id,
        ];
    }
    protected function prepareForValidation(): void
    {
        $this->merge([
            'gender' => ucfirst($this->gender),
            'phone' => preg_replace( '/[^0-9]/', '', $this->phone),
        ]);
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
