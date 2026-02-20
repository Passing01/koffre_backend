<?php

namespace App\Http\Requests\Cagnottes;

use Illuminate\Foundation\Http\FormRequest;

class StoreCagnotteRequest extends FormRequest
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
            'visibility' => ['required', 'in:public,private'],
            'payout_mode' => ['required', 'in:direct,escrow'],

            // Payout methods
            'payout_method' => ['nullable', 'string', 'max:50'],
            'payout_account' => ['nullable', 'string', 'max:255'],
            'payout_accounts' => ['nullable', 'array'],
            'payout_accounts.*.method' => ['required_with:payout_accounts', 'string'],
            'payout_accounts.*.account' => ['required_with:payout_accounts', 'string'],

            'ends_at' => ['required', 'date', 'after:now'],

            // KYC
            'creator_type' => ['required', 'in:physique,morale'],

            // Common
            'profile_photo' => ['required_if:creator_type,physique', 'file', 'image', 'max:5120'],

            // Physique
            'identity_document' => ['required_if:creator_type,physique', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],

            // Morale
            'business_name' => ['required_if:creator_type,morale', 'string', 'max:255'],
            'company_logo' => ['required_if:creator_type,morale', 'file', 'image', 'max:5120'],
            'rccm_number' => ['required_if:creator_type,morale', 'string', 'max:100'],
            'ifu_number' => ['required_if:creator_type,morale', 'string', 'max:100'],
            'rccm_document' => ['required_if:creator_type,morale', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'ifu_document' => ['required_if:creator_type,morale', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],

            // Contract for high target (> 10,000,000)
            'signed_contract' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240',
                function ($attribute, $value, $fail) {
                    if ($this->target_amount >= 10000000 && !$value) {
                        $fail('Le contrat signé est obligatoire pour un objectif supérieur ou égal à 10.000.000.');
                    }
                }
            ],

            'participants' => ['nullable', 'array'],
            'participants.*' => ['required', 'string'],

            // Image de fond de la cagnotte
            'background_image' => ['nullable', 'file', 'image', 'max:5120'],
        ];
    }
}
