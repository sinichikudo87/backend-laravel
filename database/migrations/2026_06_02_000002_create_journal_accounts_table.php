<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreignId('account_category_id')
                ->constrained('account_categories')
                ->cascadeOnDelete();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('journal_accounts')
                ->nullOnDelete();
            $table->string('account_code', 64);
            $table->string('account_name');
            $table->boolean('is_group')->default(false);
            $table->string('currency_code', 10)->nullable();
            $table->string('bank_code', 20)->nullable();
            $table->string('tax_type', 50)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'account_code']);
            $table->index(['company_id', 'account_category_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_accounts');
    }
};
