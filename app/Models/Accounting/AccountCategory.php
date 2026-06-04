<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'parent_id',
        'code_prefix',
        'name',
        'description',
        'is_system',
        'is_currency',
        'is_bank',
        'is_active',
        'seq_width',
        'next_seq',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'parent_id' => 'integer',
        'is_system' => 'boolean',
        'is_currency' => 'boolean',
        'is_bank' => 'boolean',
        'is_active' => 'boolean',
        'seq_width' => 'integer',
        'next_seq' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function journalAccounts(): HasMany
    {
        return $this->hasMany(JournalAccount::class, 'account_category_id');
    }
}
