<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function register(Request $request) {
        $request->validate([
            'name' => 'required|string|max:200',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|digits:10|unique:users,phone',
            'password' => 'required|min:6',
            'pin' => 'required|digits:5'
        ]);

        $result = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'pin' => Hash::make($request->pin)
            ]);

            $wallet = Wallet::create([
                'user_id' => $user->id,
            ]);

            return [
                'user' => $user,
                'wallet' => $wallet->refresh()
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $result
        ]);
    }

    public function login(Request $request) {
        $credentials = $request->validate([
            'phone' => 'required|digits:10',
            'password' => 'required'
        ]);

        try {
            $response = Http::asForm()->post(config('app.url') . '/oauth/token', [
                'grant_type' => 'password',
                'client_id' => config('passport.customer_client_id'),
                'client_secret' => config('passport.customer_client_secret'),
                'username' => $credentials['phone'],
                'password' => $credentials['password']
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Logged in successfully',
                    'data' => $response->json()
                ]);
            }

            if ($response->status() == 400 || $response->status() == 401) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            logger('CUSTOMER_LOGIN_ERROR', ['phone' => $request->phone, 'status' => $response->status()]);
            return response()->json([
                'success' => false,
                'message' => 'Authentication service unavailable'
            ], 503);
        } catch (\Throwable $th) {
            logger('CUSTOMER_LOGIN_FAILED', ['message' => $th->getMessage(), 'trace' => $th->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while attempting to login'
            ], 500);
        }
    }
}
