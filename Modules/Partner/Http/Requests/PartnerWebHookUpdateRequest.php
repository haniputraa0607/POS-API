<?php

namespace Modules\Partner\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PartnerWebhookUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id' => 'required|exists:partner_equals,equal_id',
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'id_member' => 'required',
            'is_suspended' => 'required|boolean',
            'mitra_store.id' => 'required',
            'mitra_store.store_name' => 'required',
            'mitra_store.store_address' => 'required',
            'mitra_store.store_city' => 'required',
            'mitra_store.social_media_stores' => 'required|array',
            'mitra_store.social_media_stores.*.id' => 'required',
            'mitra_store.social_media_stores.*.type' => 'required|in:1,2,3,4,5', // Sesuaikan dengan tipe yang diterima
            'mitra_store.social_media_stores.*.url' => 'required|url',
        ];
    }
}
