<?php

namespace App\Http\Requests\Cagnottes;

use Illuminate\Foundation\Http\FormRequest;

class UnlockCagnotteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identity_document' => 'required|file|mimes:jpeg,png,pdf|max:5120', // Max 5MB, PDF or images
        ];
    }

    public function messages(): array
    {
        return [
            'identity_document.required' => 'Le document d\'identité est obligatoire pour débloquer les fonds.',
            'identity_document.mimes' => 'Le document doit être au format PDF, JPEG ou PNG.',
            'identity_document.max' => 'La taille du document ne doit pas dépasser 5 Mo.',
        ];
    }
}
