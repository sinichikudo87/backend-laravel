<?php

namespace App\Http\Requests\Accounting;

use App\Models\Accounting\AccountCategory;
use App\Models\Accounting\JournalAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJournalAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'min:1'],
            'account_category_id' => ['required', 'integer', 'exists:account_categories,id'],
            'parent_id' => ['nullable', 'integer', 'exists:journal_accounts,id'],
            'account_code' => [
                $this->boolean('auto_number') ? 'nullable' : 'required',
                'string',
                'max:64',
                Rule::unique('journal_accounts', 'account_code')
                    ->where('company_id', $this->integer('company_id')),
            ],
            'account_name' => ['required', 'string', 'max:255'],
            'is_group' => ['sometimes', 'boolean'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'bank_code' => ['nullable', 'string', 'max:20'],
            'tax_type' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'auto_number' => ['sometimes', 'boolean'],
            'children' => ['sometimes', 'array'],
            'children.*' => ['integer', 'exists:journal_accounts,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $category = AccountCategory::query()->find($this->input('account_category_id'));

            if (!$category) {
                return;
            }

            if ((int) $category->company_id !== $this->integer('company_id')) {
                $validator->errors()->add('account_category_id', 'Account category must belong to the same company.');
            }

            if (!$this->boolean('auto_number') && !str_starts_with((string) $this->input('account_code'), $category->code_prefix)) {
                $validator->errors()->add('account_code', 'Account code must start with the selected category prefix.');
            }

            $parentId = $this->input('parent_id');

            if ($parentId === null || $parentId === '') {
                return;
            }

            $parent = JournalAccount::query()->find($parentId);

            if ($parent && (int) $parent->company_id !== $this->integer('company_id')) {
                $validator->errors()->add('parent_id', 'Parent account must belong to the same company.');
            }

            if ($parent && !$parent->is_group) {
                $validator->errors()->add('parent_id', 'Parent account must be a group account.');
            }

            if (!$this->boolean('is_group') && filled($this->input('children'))) {
                $validator->errors()->add('children', 'Only group accounts can adopt child accounts.');
            }
        });
    }
}
