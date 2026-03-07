<?php

namespace App\Http\Requests\Admin\Transactions;

use Illuminate\Foundation\Http\FormRequest;

class RefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->isMethod('get')) {
            return [];
        }

        return [
            'refunded_at' => ['required', 'date'],
            'refund_amount' => ['required', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.line_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }
}
