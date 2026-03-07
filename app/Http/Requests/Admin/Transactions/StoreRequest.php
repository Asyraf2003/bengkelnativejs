<?php

namespace App\Http\Requests\Admin\Transactions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:150'],
            'transacted_at' => ['required', 'date'],
            'note' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.kind' => ['required', 'in:product_sale,service_fee,service_product,outside_cost'],
            'lines.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'lines.*.qty' => ['nullable', 'integer', 'min:1'],
            'lines.*.amount' => ['required', 'integer', 'min:0'],
            'lines.*.note' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $lines = $this->input('lines', []);

            foreach ($lines as $index => $line) {
                $kind = (string) ($line['kind'] ?? '');
                $productId = $line['product_id'] ?? null;
                $qty = $line['qty'] ?? null;

                $usesStock = in_array($kind, ['product_sale', 'service_product'], true);

                if ($usesStock) {
                    if ($productId === null || $productId === '') {
                        $validator->errors()->add("lines.{$index}.product_id", 'product_id wajib untuk line stok.');
                    }

                    if ($qty === null || $qty === '' || (int) $qty <= 0) {
                        $validator->errors()->add("lines.{$index}.qty", 'qty wajib > 0 untuk line stok.');
                    }
                }
            }
        });
    }
}
