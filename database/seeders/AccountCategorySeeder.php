<?php

namespace Database\Seeders;

use App\Models\Accounting\AccountCategory;
use Illuminate\Database\Seeder;

class AccountCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code_prefix' => '1', 'name' => 'Aktiva'],
            ['code_prefix' => '2', 'name' => 'Kewajiban/Liabilitas'],
            ['code_prefix' => '3', 'name' => 'Ekuitas / Modal'],
            ['code_prefix' => '4', 'name' => 'Pendapatan / Penjualan'],
            ['code_prefix' => '5', 'name' => 'Harga Pokok Penjualan (HPP)'],
            ['code_prefix' => '6', 'name' => 'Beban Operasional'],
            ['code_prefix' => '7', 'name' => 'Pendapatan Lain'],
            ['code_prefix' => '8', 'name' => 'Pengeluaran Lain'],
            ['code_prefix' => '9', 'name' => 'Pajak'],
        ];

        foreach ($categories as $category) {
            AccountCategory::query()->updateOrCreate(
                [
                    'company_id' => 1,
                    'code_prefix' => $category['code_prefix'],
                ],
                [
                    'parent_id' => null,
                    'name' => $category['name'],
                    'description' => null,
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}
