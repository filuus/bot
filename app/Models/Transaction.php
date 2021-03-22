<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Transaction
 * @package App\Models
 * @property integer $id
 * @property string $transaction_id
 * @property boolean $type
 * @property float $amount
 * @property float $rate
 * @property float $balance
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 */
class Transaction extends Model
{
    use HasFactory;

    const SELL = 0;
    const BUY = 1;

    const TRANSACTION_TYPES = [
        self::SELL => 'SELL',
        self::BUY => 'BUY'
    ];

    public static function pln($value): string
    {
        return number_format($value, 2, ',', ' ') . ' ' . 'PLN';
    }

    public static function eth($value): string
    {
        return number_format($value, 8, ',', ' ') . ' ' . 'ETH';
    }

    /**
     * @param $value
     * @return string
     */
    public function getTypeAttribute($value): string
    {
        return self::TRANSACTION_TYPES[$value];
    }

    public function getFormattedAmountAttribute(): string
    {
        return self::eth($this->amount);
    }

    public function getFormattedRateAttribute(): string
    {
        return self::pln($this->rate);
    }

    public function getFormattedBalanceAttribute(): string
    {
        return self::pln($this->balance);
    }

    /**
     * @return float|int|string
     */
    public function getProfitAttribute() {
        $lastId = Transaction::latest()->first()->id;
        $prevTransaction = Transaction::find($this->id - 1);
        if ($this->type === self::TRANSACTION_TYPES[self::BUY] || $this->id === 1) {
            return 0;
        } else {
            while($prevTransaction->type === self::TRANSACTION_TYPES[self::BUY]) {
                $prevTransaction = Transaction::find($prevBalance->id - 1);
            }
        }
        return $this->balance - $prevTransaction->balance;
    }
}
