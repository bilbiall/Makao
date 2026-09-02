<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

/**
 * Verification is a soft gate, not a hard one - every role lands straight in
 * their own dashboard at signup (see LandlordProvisioningService,
 * UserSignupController) and stays there whether verified or not. These three
 * routes exist for: the signed link in the verification email itself
 * (verify()), the "resend" button on the unverified banner every app-shell
 * layout shows (resend()), and notice() as the one place a *hard*-gated
 * action (see PropertyListingController::requestViewing(),
 * BookingController::store()) sends someone back to when they hit it.
 */
class EmailVerificationController extends Controller
{
    public function notice(Request $request)
    {
        return redirect()->route(GenericLoginController::dashboardRouteForRole($request->user()->role))
            ->with('status', 'Please verify your email first - check your inbox for the link, or request a new one below.');
    }

    public function verify(EmailVerificationRequest $request)
    {
        if (!$request->user()->hasVerifiedEmail()) {
            $request->fulfill();
        }

        return redirect()->route(GenericLoginController::dashboardRouteForRole($request->user()->role))
            ->with('status', 'Email verified - you\'re all set.');
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return back();
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            return back()->with('status', 'Couldn\'t send the verification email right now - please try again shortly.');
        }

        return back()->with('status', 'Verification link sent - check your inbox.');
    }
}
