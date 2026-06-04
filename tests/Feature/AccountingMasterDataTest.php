<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountCategory;
use App\Models\Accounting\JournalAccount;
use Database\Seeders\AccountCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingMasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        $this->seed(AccountCategorySeeder::class);
    }

    public function test_seeded_root_categories_are_listed(): void
    {
        $response = $this->getJson('/api/public/v1/account-categories?company_id=1&include_inactive=1');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(9, 'data')
            ->assertJsonPath('data.0.code_prefix', '1')
            ->assertJsonPath('data.0.name', 'Aktiva');
    }

    public function test_valid_child_category_can_be_created(): void
    {
        $parent = AccountCategory::query()
            ->where('company_id', 1)
            ->where('code_prefix', '1')
            ->firstOrFail();

        $response = $this->postJson('/api/public/v1/account-categories', [
            'company_id' => 1,
            'parent_id' => $parent->id,
            'code_prefix' => '1-100',
            'name' => 'Kas dan Bank',
            'description' => 'Cash and bank accounts',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code_prefix', '1-100');

        $this->assertDatabaseHas('account_categories', [
            'company_id' => 1,
            'parent_id' => $parent->id,
            'code_prefix' => '1-100',
            'name' => 'Kas dan Bank',
            'is_system' => false,
        ]);
    }

    public function test_invalid_child_prefix_is_rejected(): void
    {
        $parent = AccountCategory::query()
            ->where('company_id', 1)
            ->where('code_prefix', '1')
            ->firstOrFail();

        $response = $this->postJson('/api/public/v1/account-categories', [
            'company_id' => 1,
            'parent_id' => $parent->id,
            'code_prefix' => '2-100',
            'name' => 'Invalid Aktiva Child',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('code_prefix');
    }

    public function test_system_root_category_cannot_be_deleted(): void
    {
        $root = AccountCategory::query()
            ->where('company_id', 1)
            ->where('code_prefix', '1')
            ->firstOrFail();

        $response = $this->deleteJson("/api/public/v1/account-categories/{$root->id}");

        $response->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('account_categories', [
            'id' => $root->id,
            'deleted_at' => null,
        ]);
    }

    public function test_soft_deleted_category_prefix_remains_reserved_and_can_be_loaded(): void
    {
        $parent = AccountCategory::query()
            ->where('company_id', 1)
            ->where('code_prefix', '2')
            ->firstOrFail();

        $createResponse = $this->postJson('/api/public/v1/account-categories', [
            'company_id' => 1,
            'parent_id' => $parent->id,
            'code_prefix' => '2-10',
            'name' => 'Hutang Bank',
        ])->assertCreated()
            ->assertJsonPath('success', true);

        $categoryId = AccountCategory::query()
            ->where('company_id', 1)
            ->where('code_prefix', '2-10')
            ->value('id');

        AccountCategory::query()->findOrFail($categoryId)->delete();

        $this->assertSoftDeleted('account_categories', [
            'id' => $categoryId,
            'code_prefix' => '2-10',
        ]);

        $this->getJson('/api/public/v1/account-categories?company_id=1&include_inactive=1')
            ->assertOk()
            ->assertJsonMissing(['code_prefix' => '2-10']);

        $this->getJson('/api/public/v1/account-categories?company_id=1&include_inactive=1&include_deleted=1')
            ->assertOk()
            ->assertJsonFragment(['code_prefix' => '2-10']);

        $this->postJson('/api/public/v1/account-categories', [
            'company_id' => 1,
            'parent_id' => $parent->id,
            'code_prefix' => '2-10',
            'name' => 'Duplicate Hutang Bank',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('code_prefix');
    }

    public function test_journal_account_must_match_category_prefix(): void
    {
        $category = AccountCategory::query()
            ->where('company_id', 1)
            ->where('code_prefix', '1')
            ->firstOrFail();

        $response = $this->postJson('/api/public/v1/journal-accounts', [
            'company_id' => 1,
            'account_category_id' => $category->id,
            'account_code' => '4-10001',
            'account_name' => 'Sales Revenue',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('account_code');
    }

    public function test_duplicate_prefix_and_account_code_are_rejected_per_company(): void
    {
        $category = AccountCategory::query()
            ->where('company_id', 1)
            ->where('code_prefix', '1')
            ->firstOrFail();

        $this->postJson('/api/public/v1/account-categories', [
            'company_id' => 1,
            'parent_id' => $category->id,
            'code_prefix' => '1-100',
            'name' => 'Kas dan Bank',
        ])->assertCreated();

        $this->postJson('/api/public/v1/account-categories', [
            'company_id' => 1,
            'parent_id' => $category->id,
            'code_prefix' => '1-100',
            'name' => 'Duplicate Kas dan Bank',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('code_prefix');

        $account = JournalAccount::query()->create([
            'company_id' => 1,
            'account_category_id' => $category->id,
            'account_code' => '1-10101',
            'account_name' => 'Kas Kecil',
            'is_active' => true,
        ]);

        $this->postJson('/api/public/v1/journal-accounts', [
            'company_id' => 1,
            'account_category_id' => $category->id,
            'account_code' => $account->account_code,
            'account_name' => 'Duplicate Kas Kecil',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('account_code');
    }
}
