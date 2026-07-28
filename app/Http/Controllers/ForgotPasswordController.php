<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function showForgotForm()
    {
        return view('forgot-password');
    }

    public function sendReset(Request $request)
    {
        $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $identifier = trim($request->input('identifier'));

        // Try email first
        $user = User::where('email', $identifier)->first();

        // Try phone (any user/tenant) if no user by email
        if (! $user) {
            $digits = preg_replace('/\D+/', '', $identifier);
            if ($digits) {
                $normalized = $digits;
                if (str_starts_with($normalized, '0')) {
                    $normalized = '254' . substr($normalized, 1);
                }
                if (str_starts_with($normalized, '254')) {
                    $user = User::where('phone_number', 'like', '%' . $normalized)->first();

                    if (! $user) {
                        $tenant = Tenant::where('phone_number', 'like', '%' . $normalized)->first();
                        if ($tenant && $tenant->user) {
                            $user = $tenant->user;
                        }
                    }
                }
            }
        }

        if (! $user) {
            return back()->withErrors(['identifier' => 'We could not find an account with that email or phone number.']);
        }

        // Generate token and send via user notifier (overridden to email + optional SMS)
        $token = Password::broker()->createToken($user);
        $user->sendPasswordResetNotification($token);

        return back()->with('status', 'We have sent password reset instructions. Check your email or SMS.');
    }
}
