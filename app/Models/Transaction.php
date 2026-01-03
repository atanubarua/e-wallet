<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $guarded = [];

    const TYPE_SEND_MONEY = 1;

    const STATUS_PENDING = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_FAILED = 3;
    const STATUS_REVERSED = 4;

    public function ledger_entries() {
        return $this->hasMany(LedgerEntry::class);
    }
}
