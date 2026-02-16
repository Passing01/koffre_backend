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
            'payout_method' => ['required', 'string', 'max:50'],
            'payout_account' => ['required', 'string', 'max:255'],
            'ends_at' => ['required', 'date', 'after:now'],
            'participants' => ['nullable', 'array'],
            'participants.*' => ['required', 'string'],
        ];
    }
}
