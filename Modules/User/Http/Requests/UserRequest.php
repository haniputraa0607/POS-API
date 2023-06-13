<?php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\User\Entities\User;

class UserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return $this->uniqueFields();
    }
    
    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $latestId = User::latest()->first()->id + 1;
        $this->merge([
            'username' => ucfirst($this->type == 'salesman' ? 'Dok' : 'Kas') . explode(' ', $this->name)[0] . $latestId,
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

    public function uniqueFields(): array
    {
        return $this->method() == 'POST' ?
            [
                'equal_id' => 'required|unique:users,equal_id',
                'name' => 'required',
                'email' => 'required|unique:users,email',
                'phone' => 'required|unique:users,phone',
                'idc' => 'required|unique:users,idc',
                'birthdate' => 'required',
                'type' => 'required|in:salesman,cashier',
                'outlet_id' => 'required|exists:outlets,id',
            ] :
            [
                'equal_id' => 'required|unique:users,equal_id,' . $this->user->id,
                'name' => 'required',
                'email' => 'required|unique:users,email,' . $this->user->id,
                'phone' => 'required|unique:users,phone,' . $this->user->id,
                'idc' => 'required|unique:users,idc,' . $this->user->id,
                'birthdate' => 'required',
                'type' => 'required|in:salesman,cashier',
                'outlet_id' => 'required|exists:outlets,id',
            ];
    }
}
