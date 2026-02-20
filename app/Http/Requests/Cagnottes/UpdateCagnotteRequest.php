<?php

namespace App\Http\Requests\Cagnottes;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCagnotteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Champs modifiables par le créateur
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'target_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after:now'],
            'visibility' => ['sometimes', 'in:public,private'],

            // Image de fond
            'background_image' => ['sometimes', 'nullable', 'file', 'image', 'max:5120'],

            // Comptes de payout (le créateur peut modifier ses comptes)
            'payout_method' => ['sometimes', 'nullable', 'string', 'max:50'],
            'payout_account' => ['sometimes', 'nullable', 'string', 'max:255'],
            'payout_accounts' => ['sometimes', 'nullable', 'array'],
            'payout_accounts.*.method' => ['required_with:payout_accounts', 'string'],
            'payout_accounts.*.account' => ['required_with:payout_accounts', 'string'],
        ];
    }
}
