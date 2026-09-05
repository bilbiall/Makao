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

        // Same message whether or not an account exists - a different message here is an
        // account-enumeration oracle (confirms which emails/phone numbers are registered).
        $status = 'If an account exists for that email or phone number, we\'ve sent password reset instructions.';

        if (! $user) {
            return back()->with('status', $status);
        }

        // Generate token and send via user notifier (overridden to email + optional SMS)
        $token = Password::broker()->createToken($user);
        $user->sendPasswordResetNotification($token);

        return back()->with('status', $status);
    }
}
