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
            'id' => 'required|integer|exists:customers,id',
            'name' => 'required|string',
            'gender' => 'required|in:Male,Female',
            'birth_date' => 'required|date_format:Y-m-d',
            'phone' => 'required|string|unique:customers,phone,' . $this->id,
            'email' => 'required|string|unique:customers,email,' . $this->id,
        ];
    }
    protected function prepareForValidation(): void
    {
        $this->merge([
            'gender' => ucfirst($this->gender),
            'phone' => preg_replace('/[^0-9]/', '', $this->phone),
        ]);
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id.exists' => 'Customer :attribute not found',
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
