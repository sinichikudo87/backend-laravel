<?php

namespace App\Http\Requests\Accounting;

use App\Models\Accounting\AccountCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->route('accountCategory');
        $categoryId = $category instanceof AccountCategory ? $category->id : $this->route('accountCategory');
        $companyId = $this->integer('company_id') ?: ($category instanceof AccountCategory ? $category->company_id : null);

        return [
            'company_id' => ['sometimes', 'integer', 'min:1'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:account_categories,id'],
            'code_prefix' => [
                'sometimes',
                'string',
                'max:32',
                'regex:/^[1-9](-[0-9]+)*$/',
                Rule::unique('account_categories', 'code_prefix')
                    ->ignore($categoryId)
                    ->where('company_id', $companyId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $category = $this->route('accountCategory');

            if (!$category instanceof AccountCategory) {
                return;
            }

            $companyId = $this->integer('company_id') ?: $category->company_id;
            $parentId = $this->has('parent_id') ? $this->input('parent_id') : $category->parent_id;
            $codePrefix = (string) ($this->input('code_prefix') ?? $category->code_prefix);

            if ($parentId === null || $parentId === '') {
                return;
            }

            if ((int) $parentId === (int) $category->id) {
                $validator->errors()->add('parent_id', 'Parent category cannot be itself.');
                return;
            }

            $parent = AccountCategory::query()->find($parentId);

            if (!$parent) {
                return;
            }

            if ((int) $parent->company_id !== (int) $companyId) {
                $validator->errors()->add('parent_id', 'Parent category must belong to the same company.');
            }

            if (!str_starts_with($codePrefix, $parent->code_prefix)) {
                $validator->errors()->add('code_prefix', 'Code prefix must start with the parent category prefix.');
            }
        });
    }
}
