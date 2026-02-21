<?php

namespace App\Http\Requests\Cagnottes;

use Illuminate\Foundation\Http\FormRequest;

class StorePrivateCagnotteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_amount' => ['nullable', 'numeric', 'min:0'],

            // Payout methods
            'payout_method' => ['nullable', 'string', 'max:50'],
            'payout_account' => ['nullable', 'string', 'max:255'],
            'payout_accounts' => ['nullable', 'array'],
            'payout_accounts.*.method' => ['required_with:payout_accounts', 'string'],
            'payout_accounts.*.account' => ['required_with:payout_accounts', 'string'],

            'ends_at' => ['required', 'date', 'after:now'],

            // KYC (Always Physique for Private)
            'profile_photo' => ['required', 'file', 'image', 'max:5120'],
            'identity_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],

            'participants' => ['nullable', 'array'],
            'participants.*' => ['required', 'string'],

            'background_image' => ['nullable', 'file', 'image', 'max:5120'],

            // NEW FIELDS
            'accepted_policy' => ['required', 'accepted'],
        ];
    }
}
