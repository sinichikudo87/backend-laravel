<?php

namespace App\Http\Controllers\accounting\master\journal_account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StoreJournalAccountRequest;
use App\Http\Requests\Accounting\UpdateJournalAccountRequest;
use App\Models\Accounting\AccountCategory;
use App\Models\Accounting\JournalAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => ['required', 'integer', 'min:1'],
            'account_category_id' => ['sometimes', 'nullable', 'integer'],
            'include_inactive' => ['sometimes', 'boolean'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $query = JournalAccount::query()
            ->with('accountCategory:id,name,code_prefix,is_currency,is_bank,seq_width,next_seq')
            ->with('parent:id,account_code,account_name,is_group')
            ->where('company_id', $request->integer('company_id'))
            ->orderBy('account_code');

        if (!$request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }

        if ($request->filled('account_category_id')) {
            $query->where('account_category_id', $request->integer('account_category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query->where('account_code', 'like', "%{$search}%")
                    ->orWhere('account_name', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'message' => 'Journal accounts fetched successfully',
            'data' => $query->get()->map(fn (JournalAccount $account) => $this->transform($account))->values(),
        ]);
    }

    public function show(JournalAccount $journalAccount): JsonResponse
    {
        $journalAccount->load('accountCategory:id,name,code_prefix,is_currency,is_bank,seq_width,next_seq', 'parent:id,account_code,account_name,is_group');

        return response()->json([
            'success' => true,
            'message' => 'Journal account fetched successfully',
            'data' => $this->transform($journalAccount),
        ]);
    }

    public function store(StoreJournalAccountRequest $request): JsonResponse
    {
        $account = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $children = $data['children'] ?? [];
            $autoNumber = (bool) ($data['auto_number'] ?? false);

            unset($data['children'], $data['auto_number']);

            if ($autoNumber || empty($data['account_code'])) {
                $data['account_code'] = $this->nextAccountCode((int) $data['account_category_id']);
            }

            $data['is_group'] = (bool) ($data['is_group'] ?? false);
            $data['parent_id'] = $data['is_group'] ? null : ($data['parent_id'] ?? null);
            $data['is_active'] = $request->boolean('is_active', true);

            $account = JournalAccount::query()->create($data);

            if ($data['is_group'] && !empty($children)) {
                JournalAccount::query()
                    ->where('company_id', $account->company_id)
                    ->whereIn('id', $children)
                    ->update(['parent_id' => $account->id]);
            }

            return $account;
        });

        $account->load('accountCategory:id,name,code_prefix,is_currency,is_bank,seq_width,next_seq', 'parent:id,account_code,account_name,is_group');

        return response()->json([
            'success' => true,
            'message' => 'Journal account created successfully',
            'data' => $this->transform($account),
        ], 201);
    }

    public function update(UpdateJournalAccountRequest $request, JournalAccount $journalAccount): JsonResponse
    {
        DB::transaction(function () use ($request, $journalAccount) {
            $data = $request->validated();
            $children = $data['children'] ?? [];
            $autoNumber = (bool) ($data['auto_number'] ?? false);

            unset($data['children'], $data['auto_number']);

            if ($autoNumber || (array_key_exists('account_code', $data) && empty($data['account_code']))) {
                $categoryId = (int) ($data['account_category_id'] ?? $journalAccount->account_category_id);
                $data['account_code'] = $this->nextAccountCode($categoryId);
            }

            $isGroup = array_key_exists('is_group', $data)
                ? (bool) $data['is_group']
                : $journalAccount->is_group;

            if ($isGroup) {
                $data['parent_id'] = null;
            }

            $journalAccount->update($data);

            if ($isGroup && !empty($children)) {
                JournalAccount::query()
                    ->where('company_id', $journalAccount->company_id)
                    ->whereIn('id', $children)
                    ->update(['parent_id' => $journalAccount->id]);
            }
        });

        $journalAccount->load('accountCategory:id,name,code_prefix,is_currency,is_bank,seq_width,next_seq', 'parent:id,account_code,account_name,is_group');

        return response()->json([
            'success' => true,
            'message' => 'Journal account updated successfully',
            'data' => $this->transform($journalAccount),
        ]);
    }

    public function updateStatus(Request $request, JournalAccount $journalAccount): JsonResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $journalAccount->update($validated);
        $journalAccount->load('accountCategory:id,name,code_prefix,is_currency,is_bank,seq_width,next_seq', 'parent:id,account_code,account_name,is_group');

        return response()->json([
            'success' => true,
            'message' => 'Journal account status updated successfully',
            'data' => $this->transform($journalAccount),
        ]);
    }

    public function destroy(JournalAccount $journalAccount): JsonResponse
    {
        $journalAccount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Journal account deleted successfully',
            'data' => null,
        ]);
    }

    private function transform(JournalAccount $account): array
    {
        return [
            'id' => $account->id,
            'company_id' => $account->company_id,
            'account_category_id' => $account->account_category_id,
            'account_category' => $account->accountCategory ? [
                'id' => $account->accountCategory->id,
                'name' => $account->accountCategory->name,
                'code_prefix' => $account->accountCategory->code_prefix,
                'is_currency' => $account->accountCategory->is_currency,
                'is_bank' => $account->accountCategory->is_bank,
            ] : null,
            'parent_id' => $account->parent_id,
            'parent' => $account->parent ? [
                'id' => $account->parent->id,
                'account_code' => $account->parent->account_code,
                'account_name' => $account->parent->account_name,
                'is_group' => $account->parent->is_group,
            ] : null,
            'account_code' => $account->account_code,
            'account_name' => $account->account_name,
            'is_group' => $account->is_group,
            'currency_code' => $account->currency_code,
            'bank_code' => $account->bank_code,
            'tax_type' => $account->tax_type,
            'description' => $account->description ?? '',
            'is_active' => $account->is_active,
            'created_at' => $account->created_at,
            'updated_at' => $account->updated_at,
        ];
    }

    private function nextAccountCode(int $accountCategoryId): string
    {
        $category = AccountCategory::query()
            ->lockForUpdate()
            ->findOrFail($accountCategoryId);
        $width = max(1, (int) ($category->seq_width ?? 2));
        $sequence = max(1, (int) ($category->next_seq ?? 1));
        $max = (10 ** $width) - 1;

        if ($sequence > $max) {
            abort(422, "Account category {$category->name} has reached the maximum account sequence.");
        }

        $accountCode = $category->code_prefix . str_pad((string) $sequence, $width, '0', STR_PAD_LEFT);
        $category->next_seq = $sequence + 1;
        $category->save();

        return $accountCode;
    }
}
