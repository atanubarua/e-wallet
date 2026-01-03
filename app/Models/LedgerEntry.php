<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    protected $guarded = [];

    const DIRECTION_DEBIT = 1;
    const DIRECTION_CREDIT = 2;

    public function transaction() {
        return $this->belongsTo(Transaction::class);
    }
}
