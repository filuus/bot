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

    public function getProfitAttribute() {
        $lastId = Transaction::latest()->first()->id;
        if ($this->id === 1) {
            return 0;
        } elseif ($this->id === $lastId) {
            return 0;
        }
        $prevBalance = Transaction::find($this->id - 1)->balance;
        return $prevBalance - $this->balance;
    }
}
