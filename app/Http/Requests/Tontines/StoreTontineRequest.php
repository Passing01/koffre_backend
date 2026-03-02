<?php

namespace App\Http\Requests\Tontines;

use Illuminate\Foundation\Http\FormRequest;

class StoreTontineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount_per_installment' => 'required|numeric|min:0',
            'frequency' => 'required|in:days,weeks,months',
            'frequency_number' => 'required|integer|min:1',
            'starts_at' => 'required|date|after:now',
            'payout_mode' => 'required|in:direct,automatic',
            'creator_percentage' => 'nullable|numeric|min:0|max:100',
            'identity_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'notification_settings' => 'nullable|array',
            'late_fee_amount' => 'nullable|numeric|min:0',
            'max_participants' => 'nullable|integer|min:2',
            'requires_member_registration' => 'nullable|boolean',
            'is_random_payout' => 'nullable|boolean',
            'members' => 'required|array|min:1',
            'members.*.phone' => 'required|string',
            'members.*.payout_rank' => 'nullable|integer|min:1',
            'members.*.permissions' => 'nullable|array',
        ];
    }
}
