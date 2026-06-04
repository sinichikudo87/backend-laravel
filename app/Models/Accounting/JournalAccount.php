<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalAccount extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'account_category_id',
        'parent_id',
        'account_code',
        'account_name',
        'is_group',
        'currency_code',
        'bank_code',
        'tax_type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'account_category_id' => 'integer',
        'parent_id' => 'integer',
        'is_group' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function accountCategory(): BelongsTo
    {
        return $this->belongsTo(AccountCategory::class, 'account_category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
