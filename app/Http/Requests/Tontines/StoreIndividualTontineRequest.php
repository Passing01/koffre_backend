<?php

namespace App\Http\Requests\Tontines;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class StoreIndividualTontineRequest extends FormRequest
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
            'amount_per_installment' => ['required', 'numeric', 'min:100'],
            'currency' => ['required', 'string', 'size:3'],
            'frequency' => ['required', 'in:daily,weekly,biweekly,monthly'],
            'frequency_number' => ['required', 'integer', 'min:1'],
            'starts_at' => ['required', 'date', 'after_or_equal:today'],
            'target_payout_date' => [
                'required', 
                'date', 
                'after:starts_at',
                function ($attribute, $value, $fail) {
                    $startStr = $this->input('starts_at');
                    if (!$startStr) return; // Si non renseigné, la règle 'required' s'en charge

                    $start = Carbon::parse($startStr)->startOfDay();
                    $target = Carbon::parse($value)->startOfDay();
                    
                    if ($start->copy()->addDays(7)->isAfter($target)) {
                        $fail('La date de retrait doit être d\'au moins une semaine après le début.');
                    }
                }
            ],
            'payout_mode' => ['required', 'in:manual,automatic'],
            'notification_settings' => ['nullable', 'array'],
        ];
    }
}
