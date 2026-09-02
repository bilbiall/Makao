<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo mode
    |--------------------------------------------------------------------------
    |
    | Gates the homepage's one-click "try the demo" buttons (DemoLoginController)
    | that log a visitor straight into a seeded demo account with no password.
    | Flip to false (or unset DEMO_MODE) before pointing this app at a real
    | customer's data - it's the one switch that turns the feature off without
    | touching any route/controller/view code.
    |
    */
    'enabled' => env('DEMO_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Flagship demo landlord
    |--------------------------------------------------------------------------
    |
    | Every "try the demo" role button logs in as an account under this one
    | landlord (see DemoNairobiSeeder's manifest) - the only one seeded with
    | long-term units, BnB units, a manager, caretakers and an agent all at
    | once, so the owner/admin/manager/caretaker/agent/tenant buttons tell one
    | coherent story instead of five unrelated portfolios.
    |
    */
    'landlord_email' => 'linda.achieng@rentydemo.co.ke',

];
