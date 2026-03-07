<?php

namespace App\Http\Requests\Admin\Transactions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'refund_amount' => ['required', 'integer', 'min:1'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.line_id' => ['required', 'integer'],
            'items.*.qty' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if ($this->isMethod('get')) {
            return;
        }

        $validator->after(function (Validator $validator) {
            $items = $this->input('items', []);

            $hasPositiveQty = collect($items)->contains(function ($item) {
                return (int) ($item['qty'] ?? 0) > 0;
            });

            if (!$hasPositiveQty) {
                $validator->errors()->add('items', 'Minimal 1 qty refund harus lebih dari 0.');
            }
        });
    }
}
