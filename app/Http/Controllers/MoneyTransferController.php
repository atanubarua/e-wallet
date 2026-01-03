<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MoneyTransferController extends Controller
{
    public function send(Request $request) {
        $request->validate([
            'recipient_phone' => 'required|digits:10',
            'amount' => 'required|numeric|min:20|max:50000'
        ]);

        $amount = $request->amount;
        $sender = $request->user();
        $senderWallet = $sender->wallet;

        if (!$senderWallet || ($senderWallet && $senderWallet->status != Wallet::STATUS_ACTIVE)) {
            return response()->json([
                'success' => false,
                'message' => 'Sender wallet not found'
            ], 404);
        }

        $receiver = User::where('phone', $request->recipient_phone)->first();
        $receiverWallet = $receiver->wallet;

        if (!$receiverWallet || ($receiverWallet && $receiverWallet->status != Wallet::STATUS_ACTIVE)) {
            return response()->json([
                'success' => false,
                'message' => 'Receiver wallet not found'
            ], 404);
        }

        if ($sender->id == $receiver->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot send money to yourself'
            ], 422);
        }

        try {
            [$code, $message, $success] = DB::transaction(function () use ($sender, $receiver, $amount) {
                $code = 201;
                $message = 'Send money successful';
                $success = true;

                $senderWallet = Wallet::where('user_id', $sender->id)->lockForUpdate()->first();
                $receiverWallet = Wallet::where('user_id', $receiver->id)->lockForUpdate()->first();

                if ($senderWallet->balance < $amount) {
                    $success = false;
                    $code = 422;
                    $message = 'Insufficient balance';
                    return [$code, $message, $success];
                }

                $transaction = Transaction::create([
                    'reference' => 'TXN-' . strtoupper(bin2hex(random_bytes(6))),
                    'initiated_by_id' => $sender->id,
                    'type' => Transaction::TYPE_SEND_MONEY,
                    'from_wallet_id' => $senderWallet->id,
                    'to_wallet_id' => $receiverWallet->id,
                    'amount' => $amount,
                    'status' => Transaction::STATUS_PROCESSING
                ]);

                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'wallet_id' => $senderWallet->id,
                    'user_id' => $sender->id,
                    'direction' => LedgerEntry::DIRECTION_DEBIT,
                    'amount' => $amount,
                    'after_balance' => $senderWallet->balance - $amount
                ]);

                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'wallet_id' => $receiverWallet->id,
                    'user_id' => $receiver->id,
                    'direction' => LedgerEntry::DIRECTION_CREDIT,
                    'amount' => $amount,
                    'after_balance' => $receiverWallet->balance + $amount
                ]);

                $senderWallet->update(['balance' => $senderWallet->balance - $amount]);
                $receiverWallet->update(['balance' => $receiverWallet->balance + $amount]);
                $transaction->update(['status' => Transaction::STATUS_COMPLETED]);

                return [$code, $message, $success];
            }, 5);
        } catch (\Throwable $th) {
            $code = 500;
            $message = 'Send money failed. Please contact support';
            $success = false;
            logger('SEND_MONEY_FAILED', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'payload' => $request->all()
            ]);
        }

        return response()->json([
            'success' => $success,
            'message' => $message
        ], $code);
    }
}
