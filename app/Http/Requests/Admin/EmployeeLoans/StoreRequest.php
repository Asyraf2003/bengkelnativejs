<?php

namespace App\Http\Requests\Admin\EmployeeLoans;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'employee_id' => ['required','integer','exists:employees,id'],
            'loaned_at'   => ['required','date'],
            'amount'      => ['required','integer','min:0'],
            'note'        => ['required','string','min:3'], // UI wajib
        ];
    }
}
