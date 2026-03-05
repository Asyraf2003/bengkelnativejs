<?php

namespace App\Http\Requests\Admin\Products;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $productId = (int) $this->route('product');

        return [
            'code'       => ['required','string','max:50', Rule::unique('products','code')->ignore($productId)],
            'name'       => ['required','string','max:150'],
            'brand'      => ['required','string','max:100'],
            'size'       => ['required','string','max:50'],
            'sale_price' => ['required','integer','min:0'],
            'is_active'  => ['nullable','boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
