<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->unsignedBigInteger('initiated_by_id')->nullable();
            $table->tinyInteger('type')->comment('1=send money,2=mobile recharge,3=make payment,4=cashout,5=pay bill');
            $table->unsignedBigInteger('from_wallet_id')->nullable();
            $table->unsignedBigInteger('to_wallet_id')->nullable();
            $table->decimal('amount', 10);
            $table->tinyInteger('status')->comment('0=pending,1=processing,2=completed,3=failed,4-reversed')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
