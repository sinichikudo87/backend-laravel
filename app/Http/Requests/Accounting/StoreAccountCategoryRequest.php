<?php

namespace App\Http\Requests\Accounting;

use App\Models\Accounting\AccountCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'min:1'],
            'parent_id' => ['required', 'integer', 'exists:account_categories,id'],
            'code_prefix' => [
                'required',
                'string',
                'max:32',
                'regex:/^[1-9](-[0-9]+)*$/',
                Rule::unique('account_categories', 'code_prefix')
                    ->where('company_id', $this->integer('company_id')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_currency' => ['sometimes', 'boolean'],
            'is_bank' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'seq_width' => ['sometimes', 'integer', 'min:1', 'max:9'],
            'next_seq' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $parent = AccountCategory::query()->find($this->input('parent_id'));

            if (!$parent) {
                return;
            }

            if ((int) $parent->company_id !== $this->integer('company_id')) {
                $validator->errors()->add('parent_id', 'Parent category must belong to the same company.');
            }

            if (!str_starts_with((string) $this->input('code_prefix'), $parent->code_prefix)) {
                $validator->errors()->add('code_prefix', 'Code prefix must start with the parent category prefix.');
            }
        });
    }
}
