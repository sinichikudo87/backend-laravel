<?php

namespace App\Http\Requests\Accounting;

use App\Models\Accounting\AccountCategory;
use App\Models\Accounting\JournalAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJournalAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $account = $this->route('journalAccount');
        $accountId = $account instanceof JournalAccount ? $account->id : $this->route('journalAccount');
        $companyId = $this->integer('company_id') ?: ($account instanceof JournalAccount ? $account->company_id : null);

        return [
            'company_id' => ['sometimes', 'integer', 'min:1'],
            'account_category_id' => ['sometimes', 'integer', 'exists:account_categories,id'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:journal_accounts,id'],
            'account_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:64',
                Rule::unique('journal_accounts', 'account_code')
                    ->ignore($accountId)
                    ->where('company_id', $companyId),
            ],
            'account_name' => ['sometimes', 'required', 'string', 'max:255'],
            'is_group' => ['sometimes', 'boolean'],
            'currency_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'bank_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'tax_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'auto_number' => ['sometimes', 'boolean'],
            'children' => ['sometimes', 'array'],
            'children.*' => ['integer', 'exists:journal_accounts,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $account = $this->route('journalAccount');

            if (!$account instanceof JournalAccount) {
                return;
            }

            $companyId = $this->integer('company_id') ?: $account->company_id;
            $categoryId = $this->integer('account_category_id') ?: $account->account_category_id;
            $accountCode = (string) ($this->input('account_code') ?? $account->account_code);
            $parentId = $this->has('parent_id') ? $this->input('parent_id') : $account->parent_id;
            $isGroup = $this->has('is_group') ? $this->boolean('is_group') : $account->is_group;

            $category = AccountCategory::query()->find($categoryId);

            if (!$category) {
                return;
            }

            if ((int) $category->company_id !== (int) $companyId) {
                $validator->errors()->add('account_category_id', 'Account category must belong to the same company.');
            }

            if (!$this->boolean('auto_number') && $accountCode !== '' && !str_starts_with($accountCode, $category->code_prefix)) {
                $validator->errors()->add('account_code', 'Account code must start with the selected category prefix.');
            }

            if ($parentId === null || $parentId === '') {
                return;
            }

            if ((int) $parentId === (int) $account->id) {
                $validator->errors()->add('parent_id', 'Parent account cannot be itself.');
                return;
            }

            $parent = JournalAccount::query()->find($parentId);

            if ($parent && (int) $parent->company_id !== (int) $companyId) {
                $validator->errors()->add('parent_id', 'Parent account must belong to the same company.');
            }

            if ($parent && !$parent->is_group) {
                $validator->errors()->add('parent_id', 'Parent account must be a group account.');
            }

            if (!$isGroup && filled($this->input('children'))) {
                $validator->errors()->add('children', 'Only group accounts can adopt child accounts.');
            }
        });
    }
}
