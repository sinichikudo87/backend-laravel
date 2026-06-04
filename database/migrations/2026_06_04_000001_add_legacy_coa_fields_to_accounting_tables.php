<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('account_categories', 'is_currency')) {
                $table->boolean('is_currency')->default(false)->after('is_system');
            }

            if (!Schema::hasColumn('account_categories', 'is_bank')) {
                $table->boolean('is_bank')->default(false)->after('is_currency');
            }

            if (!Schema::hasColumn('account_categories', 'seq_width')) {
                $table->unsignedTinyInteger('seq_width')->default(2)->after('is_active');
            }

            if (!Schema::hasColumn('account_categories', 'next_seq')) {
                $table->unsignedInteger('next_seq')->default(1)->after('seq_width');
            }
        });

        Schema::table('journal_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('journal_accounts', 'is_group')) {
                $table->boolean('is_group')->default(false)->after('account_name');
            }

            if (!Schema::hasColumn('journal_accounts', 'currency_code')) {
                $table->string('currency_code', 10)->nullable()->after('is_group');
            }

            if (!Schema::hasColumn('journal_accounts', 'bank_code')) {
                $table->string('bank_code', 20)->nullable()->after('currency_code');
            }

            if (!Schema::hasColumn('journal_accounts', 'tax_type')) {
                $table->string('tax_type', 50)->nullable()->after('bank_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('journal_accounts', function (Blueprint $table) {
            $columns = ['tax_type', 'bank_code', 'currency_code', 'is_group'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('journal_accounts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('account_categories', function (Blueprint $table) {
            $columns = ['next_seq', 'seq_width', 'is_bank', 'is_currency'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('account_categories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
