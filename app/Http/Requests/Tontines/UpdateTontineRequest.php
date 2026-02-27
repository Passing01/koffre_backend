<?php

namespace App\Http\Requests\Tontines;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTontineRequest extends FormRequest
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
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'payout_mode' => 'sometimes|in:direct,automatic',
            'creator_percentage' => 'nullable|numeric|min:0|max:100',
            'notification_settings' => 'nullable|array',
            'late_fee_amount' => 'nullable|numeric|min:0',
            'requires_member_registration' => 'nullable|boolean',
            'status' => 'sometimes|in:pending,active,completed,cancelled',
        ];
    }
}
