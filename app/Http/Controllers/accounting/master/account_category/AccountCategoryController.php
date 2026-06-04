<?php

namespace App\Http\Controllers\accounting\master\account_category;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StoreAccountCategoryRequest;
use App\Http\Requests\Accounting\UpdateAccountCategoryRequest;
use App\Models\Accounting\AccountCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => ['required', 'integer', 'min:1'],
            'include_inactive' => ['sometimes', 'boolean'],
            'include_deleted' => ['sometimes', 'boolean'],
            'parent_id' => ['sometimes', 'nullable', 'integer'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $query = AccountCategory::query()
            ->with('parent:id,name,code_prefix')
            ->withCount('journalAccounts')
            ->where('company_id', $request->integer('company_id'))
            ->orderBy('code_prefix');

        if ($request->boolean('include_deleted')) {
            $query->withTrashed();
        }

        if (!$request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->input('parent_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code_prefix', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'message' => 'Account categories fetched successfully',
            'data' => $query->get()->map(fn (AccountCategory $category) => $this->transform($category))->values(),
        ]);
    }

    public function show(AccountCategory $accountCategory): JsonResponse
    {
        $accountCategory->load('parent:id,name,code_prefix')->loadCount('journalAccounts');

        return response()->json([
            'success' => true,
            'message' => 'Account category fetched successfully',
            'data' => $this->transform($accountCategory),
        ]);
    }

    public function store(StoreAccountCategoryRequest $request): JsonResponse
    {
        $category = AccountCategory::query()->create([
            ...$request->validated(),
            'is_system' => false,
            'is_currency' => $request->boolean('is_currency', false),
            'is_bank' => $request->boolean('is_bank', false),
            'is_active' => $request->boolean('is_active', true),
            'seq_width' => $request->integer('seq_width') ?: 2,
            'next_seq' => $request->integer('next_seq') ?: 1,
        ]);

        $category->load('parent:id,name,code_prefix')->loadCount('journalAccounts');

        return response()->json([
            'success' => true,
            'message' => 'Account category created successfully',
            'data' => $this->transform($category),
        ], 201);
    }

    public function update(UpdateAccountCategoryRequest $request, AccountCategory $accountCategory): JsonResponse
    {
        $accountCategory->update($request->validated());
        $accountCategory->load('parent:id,name,code_prefix')->loadCount('journalAccounts');

        return response()->json([
            'success' => true,
            'message' => 'Account category updated successfully',
            'data' => $this->transform($accountCategory),
        ]);
    }

    public function updateStatus(Request $request, AccountCategory $accountCategory): JsonResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $accountCategory->update($validated);
        $accountCategory->load('parent:id,name,code_prefix')->loadCount('journalAccounts');

        return response()->json([
            'success' => true,
            'message' => 'Account category status updated successfully',
            'data' => $this->transform($accountCategory),
        ]);
    }

    public function destroy(AccountCategory $accountCategory): JsonResponse
    {
        if ($accountCategory->is_system || $accountCategory->parent_id === null) {
            return response()->json([
                'success' => false,
                'message' => 'System root account categories cannot be deleted',
            ], 422);
        }

        $accountCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account category deleted successfully',
            'data' => null,
        ]);
    }

    private function transform(AccountCategory $category): array
    {
        return [
            'id' => $category->id,
            'company_id' => $category->company_id,
            'parent_id' => $category->parent_id,
            'parent' => $category->parent ? [
                'id' => $category->parent->id,
                'name' => $category->parent->name,
                'code_prefix' => $category->parent->code_prefix,
            ] : null,
            'code_prefix' => $category->code_prefix,
            'name' => $category->name,
            'description' => $category->description ?? '',
            'is_system' => $category->is_system,
            'is_currency' => $category->is_currency,
            'is_bank' => $category->is_bank,
            'is_active' => $category->is_active,
            'seq_width' => $category->seq_width,
            'next_seq' => $category->next_seq,
            'journal_accounts_count' => $category->journal_accounts_count ?? 0,
            'created_at' => $category->created_at,
            'updated_at' => $category->updated_at,
            'deleted_at' => $category->deleted_at,
        ];
    }
}
