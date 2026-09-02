<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Services\LandlordProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class LandlordSignupController extends Controller
{
    public function create()
    {
        $packages = Package::where('is_active', true)->orderBy('sort_order')->get();

        return view('signup', ['packages' => $packages]);
    }

    public function store(Request $request, LandlordProvisioningService $provisioning)
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'package_id' => ['required', 'exists:packages,id'],
            'terms' => ['accepted'],
        ]);

        if (!Package::where('id', $data['package_id'])->where('is_active', true)->exists()) {
            return back()->withErrors(['package_id' => 'That plan is no longer available.'])->withInput();
        }

        $provisioning->provision($data);

        $request->session()->regenerate();

        return redirect()->route('app.admin.setup');
    }
}
