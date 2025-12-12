<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Wallet extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'currency',
        'balance',
        'status',
        'is_default',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'status' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the wallet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transactions for the wallet.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Add credit to wallet.
     */
    public function addCredit(float $amount, string $description, ?string $referenceType = null, ?int $referenceId = null, ?array $metadata = null): WalletTransaction
    {
        return DB::transaction(function () use ($amount, $description, $referenceType, $referenceId, $metadata) {
            $balanceBefore = $this->balance;
            $this->balance += $amount;
            $this->save();

            return $this->transactions()->create([
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $this->balance,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'metadata' => $metadata,
            ]);
        });
    }

    /**
     * Deduct from wallet.
     */
    public function deductBalance(float $amount, string $description, ?string $referenceType = null, ?int $referenceId = null, ?array $metadata = null): WalletTransaction
    {
        if ($this->balance < $amount) {
            throw new \Exception('رصيد المحفظة غير كافٍ');
        }

        return DB::transaction(function () use ($amount, $description, $referenceType, $referenceId, $metadata) {
            $balanceBefore = $this->balance;
            $this->balance -= $amount;
            $this->save();

            return $this->transactions()->create([
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $this->balance,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'metadata' => $metadata,
            ]);
        });
    }

    /**
     * Check if wallet has sufficient balance.
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Activate wallet.
     */
    public function activate(): void
    {
        // إيقاف جميع محافظ المستخدم الأخرى
        self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['status' => 0]);

        // تفعيل هذه المحفظة
        $this->status = 1;
        $this->save();
    }

    /**
     * Deactivate wallet.
     */
    public function deactivate(): void
    {
        // لا يمكن إيقاف المحفظة إذا كانت الوحيدة النشطة
        $activeWalletsCount = self::where('user_id', $this->user_id)
            ->where('status', 1)
            ->count();

        if ($activeWalletsCount <= 1) {
            throw new \Exception('يجب أن يكون لديك محفظة نشطة واحدة على الأقل');
        }

        $this->status = 0;
        $this->save();
    }

    /**
     * Check if wallet is active.
     */
    public function isActive(): bool
    {
        return $this->status === 1;
    }

    /**
     * Scope for active wallets.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope for inactive wallets.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }
}
