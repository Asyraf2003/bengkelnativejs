<?php

namespace App\Http\Requests\Admin\OperationalExpenses;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'     => ['required','string','max:150'],
            'spent_at' => ['required','date'],
            'amount'   => ['required','integer','min:0'],
            'note'     => ['required','string','min:3'],
        ];
    }
}
