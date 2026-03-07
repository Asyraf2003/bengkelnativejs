<?php

namespace App\Http\Requests\Admin\Invoices;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_no' => ['required', 'string', 'max:100', 'unique:supplier_invoices,invoice_no'],
            'supplier_name' => ['required', 'string', 'max:150'],
            'delivered_at' => ['required', 'date'],
            'due_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.total_cost' => ['required', 'integer', 'min:1'],
        ];
    }
}
