<?php

namespace App\Http\Requests\Admin\Transactions;

use Illuminate\Foundation\Http\FormRequest;

class MarkPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paid_at' => ['required', 'date'],
        ];
    }
}
