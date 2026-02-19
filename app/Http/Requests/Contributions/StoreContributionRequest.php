<?php

namespace App\Http\Requests\Contributions;

use Illuminate\Foundation\Http\FormRequest;

class StoreContributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cagnotte_id' => ['required', 'integer', 'exists:cagnottes,id'],
            'amount' => ['required', 'numeric', 'min:200'], // GeniusPay minimum standard is 200
            'contributor_name' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string'],
        ];
    }
}
