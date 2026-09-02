<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\House;
use App\Models\HousePhoto;
use App\Models\HousePricePackage;
use App\Models\Invoice;
use App\Models\Issue;
use App\Models\Landlord;
use App\Models\Location;
use App\Models\Message;
use App\Models\NoticeToVacate;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\StaffAssignment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\ViewingRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Seeds a demo multi-landlord Nairobi dataset for sales demos/pitches. Not wired into
 * DatabaseSeeder by default - run explicitly:
 *   php artisan db:seed --class=Database\\Seeders\\DemoNairobiSeeder
 * Intended to run against a freshly migrated database (php artisan migrate:fresh), but
 * safe to re-run on top of itself - every step here is guarded (firstOrCreate/existence
 * checks) so a second run tops up anything missing (e.g. photos, staff assignments)
 * without duplicating what's already there. Entirely additive: only ever touches
 * records under the @rentydemo.co.ke manifest below, never any other landlord account
 * that already exists in the database.
 */
class DemoNairobiSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'password';

    private array $tenantFirstNames = [
        'Grace', 'Brian', 'Faith', 'Kevin', 'Mary', 'Peter', 'Ann', 'David', 'Lucy', 'Samuel',
        'Esther', 'James', 'Joyce', 'Dennis', 'Rose', 'Michael', 'Caroline', 'Daniel', 'Agnes', 'John',
        'Winnie', 'Collins', 'Sarah', 'Felix', 'Nancy', 'Eric', 'Beatrice', 'Victor', 'Irene', 'Paul',
        'Diana', 'Moses', 'Catherine', 'Robert', 'Josephine', 'Simon', 'Emily', 'Anthony', 'Christine', 'George',
        'Purity', 'Stephen', 'Mercy', 'Charles', 'Ruth', 'Patrick', 'Susan', 'Vincent', 'Millicent', 'Edwin',
        'Alice', 'Isaac', 'Jane', 'Martin', 'Eunice', 'Francis', 'Gladys', 'Julius', 'Rhoda', 'Timothy',
        'Naomi', 'Benard', 'Consolata', 'Elijah', 'Sharon', 'Duncan', 'Priscilla', 'Hillary', 'Wanjiru', 'Kiprop',
        'Achieng', 'Wafula', 'Njeri', 'Otieno', 'Wambui', 'Kamau', 'Auma', 'Mutua', 'Nyambura', 'Kimani',
    ];

    private array $tenantLastNames = [
        'Wanjiru', 'Otieno', 'Njeri', 'Mutua', 'Achieng', 'Kamau', 'Wambui', 'Kiprop', 'Nyambura', 'Kimani',
        'Muthoni', 'Ochieng', 'Wafula', 'Auma', 'Kariuki', 'Adhiambo', 'Mwangi', 'Chebet', 'Onyango', 'Njoroge',
        'Waweru', 'Cheruiyot', 'Odhiambo', 'Wairimu', 'Korir', 'Atieno', 'Gitau', 'Rotich', 'Akinyi', 'Maina',
    ];

    private int $phoneCounter = 700000001;
    private int $tenantCounter = 1;
    private int $userCounter = 1;
    private int $photoCounter = 1;

    private array $issueTitles = [
        'Leaking kitchen tap', 'Power tripping in bedroom', 'Broken window latch', 'Blocked bathroom drain',
        'Gate intercom not working', 'Cracked bathroom tile', 'Faulty water heater', 'Squeaky front door hinge',
        'Damp patch on ceiling', 'Non-functional parking gate', 'Weak wifi signal in unit', 'Broken kitchen cabinet door',
    ];

    public function run(): void
    {
        $this->command?->info('Seeding demo Nairobi dataset...');

        $packages = $this->packages();

        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@rentydemo.co.ke'],
            ['name' => 'Renty Superadmin', 'password' => Hash::make(self::DEMO_PASSWORD), 'role' => 'superadmin']
        );

        $manifest = $this->manifest();
        $allTenants = collect();
        $allVacantHouses = collect();

        foreach ($manifest as $landlordConfig) {
            [$landlord, $tenants, $vacantHouses] = $this->seedLandlord($landlordConfig, $packages);
            $allTenants = $allTenants->merge($tenants);
            $allVacantHouses = $allVacantHouses->merge($vacantHouses);
        }

        $this->seedIssues($allTenants);
        $this->seedNoticeToVacate($allTenants);
        $this->seedMessages($allTenants);

        $prospects = $this->seedProspectiveUsers();
        $this->seedViewingRequests($prospects, $allVacantHouses);
        $this->seedWatchlist($prospects, $allVacantHouses);

        $this->command?->info('Done. Superadmin login: superadmin@rentydemo.co.ke / ' . self::DEMO_PASSWORD);
    }

    private function packages(): array
    {
        // updateOrCreate (not firstOrCreate) so re-running this seeder also backfills
        // new fields - like max_users, added after these packages already existed in
        // earlier demo databases - onto rows that were created before that field existed.
        $starter = Package::updateOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter', 'price' => 1500, 'billing_interval' => 'monthly',
                'max_locations' => 2, 'max_houses' => 15, 'max_tenants' => 15, 'max_users' => 3,
                'trial_days' => 14, 'is_active' => true, 'sort_order' => 1,
                'features' => ['sms_notifications' => true],
            ]
        );

        $growth = Package::updateOrCreate(
            ['slug' => 'growth'],
            [
                'name' => 'Growth', 'price' => 3500, 'billing_interval' => 'monthly',
                'max_locations' => 6, 'max_houses' => 60, 'max_tenants' => 60, 'max_users' => 8,
                'trial_days' => 14, 'is_active' => true, 'sort_order' => 2,
                'features' => ['sms_notifications' => true, 'mpesa_payments' => true],
            ]
        );

        $pro = Package::updateOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro', 'price' => 7000, 'billing_interval' => 'monthly',
                'max_locations' => null, 'max_houses' => null, 'max_tenants' => null, 'max_users' => null,
                'trial_days' => 14, 'is_active' => true, 'sort_order' => 3,
                'features' => ['sms_notifications' => true, 'mpesa_payments' => true, 'reports' => true],
            ]
        );

        return compact('starter', 'growth', 'pro');
    }

    private function manifest(): array
    {
        $upperMid = [
            'Bedsitter' => [18000, 26000], '1 Bedroom' => [28000, 42000],
            '2 Bedroom' => [45000, 68000], '3 Bedroom' => [70000, 115000],
        ];
        $mid = [
            'Bedsitter' => [10000, 15000], '1 Bedroom' => [16000, 22000],
            '2 Bedroom' => [24000, 35000], '3 Bedroom' => [38000, 55000],
        ];
        $budget = [
            'Single Room' => [4500, 6500], 'Bedsitter' => [7000, 9500], '1 Bedroom' => [11000, 15000],
        ];

        return [
            [
                'business_name' => 'Mwangi Properties Ltd',
                'owner_email' => 'james.mwangi@rentydemo.co.ke',
                'owner_name' => 'James Mwangi',
                'package' => 'growth',
                'subscription_status' => 'active',
                // Automatic payment mode, so this landlord's tenant portal shows the
                // M-Pesa/Pesapal "Pay Now" button - the other demo landlords are left
                // on the (manual) default to show both modes exist.
                'payment_mode' => 'automatic',
                'has_manager' => false,
                'locations' => [
                    ['name' => 'Kilimani Heights', 'area' => 'Kilimani', 'rates' => $upperMid, 'mix' => ['Bedsitter' => 2, '1 Bedroom' => 5, '2 Bedroom' => 4, '3 Bedroom' => 1]],
                    ['name' => 'Lavington Court', 'area' => 'Lavington', 'rates' => $upperMid, 'mix' => ['Bedsitter' => 1, '1 Bedroom' => 3, '2 Bedroom' => 3, '3 Bedroom' => 1]],
                    ['name' => 'Westlands Vista Apartments', 'area' => 'Westlands', 'rates' => $upperMid, 'mix' => ['Bedsitter' => 2, '1 Bedroom' => 4, '2 Bedroom' => 3, '3 Bedroom' => 1]],
                ],
            ],
            [
                'business_name' => 'Otieno Realty',
                'owner_email' => 'susan.otieno@rentydemo.co.ke',
                'owner_name' => 'Susan Otieno',
                'package' => 'starter',
                'subscription_status' => 'trialing',
                // Deliberately a single building - a landlord just starting out on a
                // trial, for visible contrast against the larger portfolios. Well
                // within the Starter package's 15 house/tenant cap, which also leaves
                // headroom to demo "add another property" live.
                'has_manager' => false,
                'locations' => [
                    ['name' => 'South B Pines', 'area' => 'South B', 'rates' => $mid, 'mix' => ['Bedsitter' => 2, '1 Bedroom' => 3, '2 Bedroom' => 3, '3 Bedroom' => 1]],
                ],
            ],
            [
                'business_name' => 'Kariuki Estates',
                'owner_email' => 'daniel.kariuki@rentydemo.co.ke',
                'owner_name' => 'Daniel Kariuki',
                'package' => 'pro',
                'subscription_status' => 'active',
                // The biggest portfolio - Pro has no location/house/tenant caps - and
                // the one landlord with a Manager (on top of per-property Caretakers),
                // large enough that "a manager overseeing several caretakers" reads
                // naturally in a demo.
                'has_manager' => true,
                'locations' => [
                    ['name' => 'Ruaka Riverside Apartments', 'area' => 'Ruaka', 'rates' => $mid, 'mix' => ['Bedsitter' => 4, '1 Bedroom' => 7, '2 Bedroom' => 4, '3 Bedroom' => 1]],
                    ['name' => 'Syokimau Palm Court', 'area' => 'Syokimau', 'rates' => $mid, 'mix' => ['Bedsitter' => 3, '1 Bedroom' => 4, '2 Bedroom' => 2, '3 Bedroom' => 1]],
                    ['name' => 'Westlands Vista Annex', 'area' => 'Westlands', 'rates' => $upperMid, 'mix' => ['Bedsitter' => 1, '1 Bedroom' => 2, '2 Bedroom' => 2, '3 Bedroom' => 1]],
                    ['name' => 'Karen Manor Apartments', 'area' => 'Karen', 'rates' => $upperMid, 'mix' => ['1 Bedroom' => 2, '2 Bedroom' => 4, '3 Bedroom' => 2]],
                ],
            ],
            [
                'business_name' => 'Njoroge Homes',
                'owner_email' => 'peter.njoroge@rentydemo.co.ke',
                'owner_name' => 'Peter Njoroge',
                'package' => 'starter',
                // Behind on their subscription - a landlord who needs to renew, for
                // contrast against the trialing/active ones on the superadmin side.
                'subscription_status' => 'past_due',
                'has_manager' => false,
                'locations' => [
                    ['name' => 'Rongai Greenview', 'area' => 'Rongai', 'rates' => $budget, 'mix' => ['Single Room' => 3, 'Bedsitter' => 3, '1 Bedroom' => 2]],
                ],
            ],
            [
                'business_name' => 'Coastal Vista BnB & Apartments',
                'owner_email' => 'linda.achieng@rentydemo.co.ke',
                'owner_name' => 'Linda Achieng',
                'package' => 'growth',
                'subscription_status' => 'active',
                'payment_mode' => 'automatic',
                'has_manager' => false,
                // The BnB-focused demo landlord - Kilimani Skyline mixes long-term
                // units with a handful of short-stay (BnB) units, priced nightly/
                // weekly/monthly, so the booking calendar/agent-staff features have
                // somewhere real to point at.
                'locations' => [
                    [
                        'name' => 'Kilimani Skyline Suites', 'area' => 'Kilimani', 'rates' => $upperMid,
                        'mix' => ['Bedsitter' => 2, '1 Bedroom' => 3, '2 Bedroom' => 2],
                        'bnb_units' => [
                            ['type' => 'Studio', 'nightly' => 4500, 'weekly' => 27000, 'monthly' => 85000],
                            ['type' => 'Studio', 'nightly' => 4800, 'weekly' => 29000, 'monthly' => 90000],
                            ['type' => '1 Bedroom', 'nightly' => 6500, 'weekly' => 39000, 'monthly' => 120000],
                            ['type' => '2 Bedroom', 'nightly' => 9500, 'weekly' => 57000, 'monthly' => 170000],
                        ],
                    ],
                    ['name' => 'Nyali Breeze Apartments', 'area' => 'Nyali', 'rates' => $mid, 'mix' => ['Bedsitter' => 2, '1 Bedroom' => 3, '2 Bedroom' => 1]],
                ],
            ],
            [
                'business_name' => 'Ngugi Rentals',
                'owner_email' => 'peter.ngugi@rentydemo.co.ke',
                'owner_name' => 'Peter Ngugi',
                'package' => 'starter',
                'subscription_status' => 'active',
                // Suspended at the *platform* level (Landlord.status), not a
                // subscription lapse - shows the superadmin's "suspend an account"
                // capability with a real record behind it. Deliberately tiny.
                'landlord_status' => 'suspended',
                'has_manager' => false,
                'locations' => [
                    ['name' => 'Dagoretti Court', 'area' => 'Dagoretti', 'rates' => $budget, 'mix' => ['Bedsitter' => 2, '1 Bedroom' => 2]],
                ],
            ],
        ];
    }

    private function seedLandlord(array $config, array $packages): array
    {
        $landlord = Landlord::firstOrCreate(
            ['contact_email' => $config['owner_email']],
            ['name' => $config['business_name'], 'status' => $config['landlord_status'] ?? 'active']
        );

        $owner = User::firstOrCreate(
            ['email' => $config['owner_email']],
            [
                'name' => $config['owner_name'], 'password' => Hash::make(self::DEMO_PASSWORD),
                'role' => 'landlord', 'landlord_id' => $landlord->id,
            ]
        );

        $package = $packages[$config['package']];
        if (!$landlord->currentSubscription) {
            Subscription::create([
                'landlord_id' => $landlord->id, 'package_id' => $package->id,
                'status' => $config['subscription_status'],
                'starts_at' => now()->subDays(30),
                'trial_ends_at' => $config['subscription_status'] === 'trialing' ? now()->addDays(7) : null,
                'expires_at' => now()->addDays(match ($config['subscription_status']) {
                    'trialing' => 7,
                    'past_due' => -5,
                    default => 335,
                }),
            ]);
        }

        if (!empty($config['payment_mode'])) {
            $settings = Setting::forLandlord($landlord->id);
            $settings->payload = array_merge($settings->payload ?? [], ['payment_mode' => $config['payment_mode']]);
            $settings->save();
        }

        $adminEmail = 'admin.' . str($config['business_name'])->slug('') . '@rentydemo.co.ke';
        $admin = User::firstOrCreate(
            ['email' => $adminEmail],
            ['name' => 'Property Manager (' . $config['business_name'] . ')', 'password' => Hash::make(self::DEMO_PASSWORD), 'role' => 'admin', 'landlord_id' => $landlord->id]
        );

        // Auth context needed so Location::creating()'s CurrentLandlord::id() fallback,
        // and every model's package-limit check, resolve against this landlord.
        Auth::login($admin);

        $tenants = collect();
        $vacantHouses = collect();
        $locations = collect();

        foreach ($config['locations'] as $locConfig) {
            $location = Location::firstOrCreate(
                ['location_name' => $locConfig['name'], 'landlord_id' => $landlord->id],
                ['geo_id' => $locConfig['area']]
            );
            $locations->push($location);

            $caretakerEmail = 'caretaker.' . str($locConfig['name'])->slug('') . '@rentydemo.co.ke';
            $caretaker = User::firstOrCreate(
                ['email' => $caretakerEmail],
                ['name' => 'Caretaker - ' . $locConfig['name'], 'password' => Hash::make(self::DEMO_PASSWORD), 'role' => 'caretaker', 'landlord_id' => $landlord->id]
            );

            // Grants access via the staff_assignments pivot - App\Support\StaffScope
            // reads this, not the legacy User.location_id column, so a caretaker
            // created without this row would log in to an empty dashboard.
            StaffAssignment::firstOrCreate(
                ['user_id' => $caretaker->id, 'location_id' => $location->id, 'role' => 'caretaker'],
                ['assigned_by' => $admin->id]
            );

            [$houseTenants, $locationVacant] = $this->seedLocationHouses($location, $locConfig);
            $tenants = $tenants->merge($houseTenants);
            $vacantHouses = $vacantHouses->merge($locationVacant);
        }

        if (!empty($config['has_manager']) && $locations->count() > 1) {
            $managerEmail = 'manager.' . str($config['business_name'])->slug('') . '@rentydemo.co.ke';
            $manager = User::firstOrCreate(
                ['email' => $managerEmail],
                ['name' => 'Portfolio Manager (' . $config['business_name'] . ')', 'password' => Hash::make(self::DEMO_PASSWORD), 'role' => 'manager', 'landlord_id' => $landlord->id]
            );

            foreach ($locations as $location) {
                StaffAssignment::firstOrCreate(
                    ['user_id' => $manager->id, 'location_id' => $location->id, 'role' => 'manager'],
                    ['assigned_by' => $admin->id]
                );
            }
        }

        // BnB agent - only for landlords whose manifest actually has short_term units.
        $bnbHouses = House::where('landlord_id', $landlord->id)->where('listing_mode', 'short_term')->get();
        if ($bnbHouses->isNotEmpty()) {
            $agentEmail = 'agent.' . str($config['business_name'])->slug('') . '@rentydemo.co.ke';
            $agent = User::firstOrCreate(
                ['email' => $agentEmail],
                ['name' => 'BnB Agent (' . $config['business_name'] . ')', 'password' => Hash::make(self::DEMO_PASSWORD), 'role' => 'agent', 'landlord_id' => $landlord->id]
            );

            foreach ($bnbHouses as $bnbHouse) {
                StaffAssignment::firstOrCreate(
                    ['user_id' => $agent->id, 'house_id' => $bnbHouse->id, 'role' => 'agent'],
                    ['assigned_by' => $admin->id]
                );
            }

            $this->seedBookings($bnbHouses, $landlord->id);
        }

        Auth::logout();

        return [$landlord, $tenants, $vacantHouses];
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection} [tenants, vacant houses]
     */
    private function seedLocationHouses(Location $location, array $locConfig): array
    {
        // Long-term and short-term units are guarded independently (not "does this
        // location have any houses at all") - a prior run that created the long-term
        // mix and then failed partway into the BnB units (as happened once here, on
        // the rent_amount-not-nullable bug) must still go back and finish the BnB
        // units on the next run, not see "houses already exist" and skip them forever.
        $hasLongTerm = $location->houses()->where('listing_mode', 'long_term')->exists();
        $hasBnb = $location->houses()->where('listing_mode', 'short_term')->exists();
        $unitNumber = $location->houses()->count() + 1;

        if (!$hasLongTerm) {
            foreach ($locConfig['mix'] as $type => $count) {
                [$min, $max] = $locConfig['rates'][$type];

                for ($i = 0; $i < $count; $i++) {
                    $rent = random_int((int) ($min / 500), (int) ($max / 500)) * 500;
                    $house = House::create([
                        'house_name' => $location->location_name . ' - Unit ' . $unitNumber,
                        'rent_amount' => $rent,
                        'location_id' => $location->id,
                        'house_type' => $type,
                        'house_status' => 'Vacant',
                        'listing_mode' => 'long_term',
                        'description' => "A well-kept {$type} in {$location->location_name}, {$location->geo_id}. Close to shops, matatu routes and schools.",
                    ]);
                    $unitNumber++;

                    // ~85% occupancy
                    if (random_int(1, 100) <= 85) {
                        $this->seedTenantWithHistory($house);
                    }
                }
            }
        }

        if (!$hasBnb) {
            foreach ($locConfig['bnb_units'] ?? [] as $bnb) {
                $house = House::create([
                    'house_name' => $location->location_name . ' - Unit ' . $unitNumber,
                    'rent_amount' => null,
                    'location_id' => $location->id,
                    'house_type' => $bnb['type'],
                    'house_status' => 'Vacant',
                    'listing_mode' => 'short_term',
                    'description' => "Furnished {$bnb['type']} short-stay unit in {$location->location_name}, {$location->geo_id}. Self check-in, wifi, backup water.",
                ]);
                $unitNumber++;

                foreach ([
                    ['name' => 'Nightly', 'price' => $bnb['nightly'], 'billing_unit' => 'night', 'sort_order' => 0],
                    ['name' => 'Weekly', 'price' => $bnb['weekly'], 'billing_unit' => 'week', 'sort_order' => 1],
                    ['name' => 'Monthly', 'price' => $bnb['monthly'], 'billing_unit' => 'month', 'sort_order' => 2],
                ] as $tier) {
                    HousePricePackage::create($tier + ['house_id' => $house->id]);
                }
            }
        }

        // Photo backfill - runs on every seeder invocation (not just first-time), for
        // any house of this location still missing photos. Only houses that actually
        // need photos to show up publicly: every short_term unit (BnB visibility
        // requires photos regardless of "occupancy"), and vacant long_term units
        // (occupied ones never appear in public search either way).
        foreach ($location->houses()->get() as $house) {
            if ($house->photos()->count() === 0 && ($house->listing_mode === 'short_term' || $house->house_status === 'Vacant')) {
                $this->seedHousePhotos($house);
            }
        }

        $this->ensureUnpublishedShowcaseUnit($location);

        $tenants = Tenant::whereHas('house', fn ($q) => $q->where('location_id', $location->id))->get();
        $vacant = House::where('location_id', $location->id)
            ->where('house_status', 'Vacant')
            ->where('listing_mode', 'long_term')
            ->where('is_published', true)
            ->get();

        return [$tenants, $vacant];
    }

    /**
     * Every demo property keeps exactly one otherwise-eligible vacant unit switched
     * off (is_published=false) so a demo can flip House::is_published live in the
     * app/Filament and watch the unit appear in search/"all units" - without this,
     * every seeded vacant unit would already be public and the toggle would have
     * nothing to show off. Idempotent per-location, and never touches a location
     * that already has one (so re-running doesn't shuffle which unit is hidden, or
     * flip one back on that a demo deliberately toggled during a pitch).
     */
    private function ensureUnpublishedShowcaseUnit(Location $location): void
    {
        if ($location->houses()->where('is_published', false)->exists()) {
            return;
        }

        $location->houses()
            ->where('house_status', 'Vacant')
            ->where('listing_mode', 'long_term')
            ->whereHas('photos')
            ->inRandomOrder()
            ->first()
            ?->update(['is_published' => false]);
    }

    /**
     * Two real photos per house via a deterministic (seeded) placeholder photo
     * service - same house always gets the same images on a re-run, so this stays
     * idempotent. Falls back to a locally generated branded placeholder if the
     * download fails for any reason (offline, rate-limited, etc.) - the seeder must
     * never fail, or half-fail, over a network hiccup.
     */
    private function seedHousePhotos(House $house, int $count = 2): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $seed = 'renty-demo-' . $this->photoCounter++;
            $filename = 'houses/' . $house->id . '-' . $i . '.jpg';
            $saved = false;

            try {
                $response = Http::timeout(8)->retry(1, 200)->get("https://picsum.photos/seed/{$seed}/900/650");
                if ($response->successful() && str_starts_with((string) $response->header('Content-Type'), 'image/')) {
                    Storage::disk('public')->put($filename, $response->body());
                    $saved = true;
                }
            } catch (\Throwable $e) {
                // fall through to the generated placeholder below
            }

            if (!$saved) {
                $this->generatePlaceholderPhoto($filename, $house);
            }

            HousePhoto::create(['house_id' => $house->id, 'path' => $filename, 'sort_order' => $i - 1]);
        }
    }

    /** Offline fallback: a clean branded gradient card with the unit type/name on it - never a broken image. */
    private function generatePlaceholderPhoto(string $filename, House $house): void
    {
        $width = 900;
        $height = 650;
        $image = imagecreatetruecolor($width, $height);

        $top = ['r' => 6, 'g' => 95, 'b' => 70];
        $bottom = ['r' => 16, 'g' => 150, 'b' => 105];
        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $color = imagecolorallocate(
                $image,
                (int) ($top['r'] + ($bottom['r'] - $top['r']) * $ratio),
                (int) ($top['g'] + ($bottom['g'] - $top['g']) * $ratio),
                (int) ($top['b'] + ($bottom['b'] - $top['b']) * $ratio)
            );
            imageline($image, 0, $y, $width, $y, $color);
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        imagestring($image, 5, 40, (int) ($height / 2) - 30, $house->house_type ?? 'Unit', $white);
        imagestring($image, 3, 40, (int) ($height / 2), $house->house_name, $white);

        ob_start();
        imagejpeg($image, null, 85);
        $data = ob_get_clean();
        imagedestroy($image);

        Storage::disk('public')->put($filename, $data);
    }

    private function seedTenantWithHistory(House $house): Tenant
    {
        $firstName = $this->tenantFirstNames[array_rand($this->tenantFirstNames)];
        $lastName = $this->tenantLastNames[array_rand($this->tenantLastNames)];
        $fullName = "{$firstName} {$lastName}";
        $phone = '254' . $this->phoneCounter++;
        // A small sequential number (not the 9-digit phone counter) keeps these emails
        // short and readable, e.g. grace.wanjiru3@example.test.
        $email = strtolower($firstName . '.' . $lastName . $this->tenantCounter++) . '@example.test';

        $tenantUser = User::create([
            'name' => $fullName, 'email' => $email, 'phone_number' => $phone,
            'password' => Hash::make(self::DEMO_PASSWORD), 'role' => 'tenant',
            'landlord_id' => $house->landlord_id,
        ]);

        $tenant = Tenant::create([
            'house_id' => $house->id, 'tenant_name' => $fullName, 'email' => $email,
            'phone_number' => $phone, 'date_admitted' => now()->subMonths(8),
        ]);

        $tenant->update(['user_id' => $tenantUser->id]);

        $this->seedFinancialHistory($tenant, $house);

        return $tenant->fresh();
    }

    private function seedFinancialHistory(Tenant $tenant, House $house): void
    {
        // 70% fully paid every month, 20% one partial month, 10% current month unpaid/overdue
        $roll = random_int(1, 100);
        $profile = $roll <= 70 ? 'full' : ($roll <= 90 ? 'partial' : 'unpaid');
        $partialMonthIndex = random_int(0, 4); // not the last month

        $months = collect(range(0, 5))->map(fn ($i) => now()->startOfMonth()->subMonths(5 - $i));

        foreach ($months as $index => $monthDate) {
            $water = random_int(500, 1500);
            $electricity = random_int(800, 3000);
            $internet = random_int(0, 1) ? 2500 : 0;
            $trash = random_int(200, 500);

            Bill::create([
                'tenant_id' => $tenant->id, 'water' => $water, 'electricity' => $electricity,
                'internet' => $internet, 'trash' => $trash, 'bill_month' => $monthDate->toDateString(),
            ]);

            $invoice = Invoice::create([
                'tenant_id' => $tenant->id,
                'invoice_date' => $monthDate->copy()->addDay(),
                'due_date' => $monthDate->copy()->addDays(10),
            ]);

            $isLastMonth = $index === $months->count() - 1;

            if ($profile === 'unpaid' && $isLastMonth) {
                continue; // overdue - no payment recorded for the current month
            }

            $amountToPay = $invoice->amount;
            if ($profile === 'partial' && $index === $partialMonthIndex) {
                $amountToPay = round($invoice->amount * (random_int(60, 80) / 100));
            }

            Payment::create([
                'tenant_id' => $tenant->id, 'invoice_id' => $invoice->id,
                'amount_paid' => $amountToPay, 'payment_reference' => 'DEMO-' . strtoupper(uniqid()),
                'payment_date' => $monthDate->copy()->addDays(random_int(1, 9)),
                'payment_method' => 'mpesa', 'payment_type' => 'mpesa',
            ]);
        }
    }

    /**
     * A spread of booking statuses across a landlord's BnB houses - confirmed and
     * checked-in stays (paid), completed past stays (paid), one still-live pending
     * hold (unpaid, matches a real STK-push-in-progress state), and one cancelled.
     */
    private function seedBookings(\Illuminate\Support\Collection $bnbHouses, int $landlordId): void
    {
        if (Booking::where('landlord_id', $landlordId)->exists()) {
            return; // idempotent - already seeded for this landlord
        }

        $guestPool = [
            ['name' => 'Tom Achieng', 'phone' => '254711000101', 'email' => 'tom.achieng@example.test'],
            ['name' => 'Wanjiku Kamau', 'phone' => '254711000102', 'email' => 'wanjiku.kamau@example.test'],
            ['name' => 'Brian Otieno', 'phone' => '254711000103', 'email' => null],
            ['name' => 'Faith Njoroge', 'phone' => '254711000104', 'email' => 'faith.njoroge@example.test'],
            ['name' => 'Kevin Mutiso', 'phone' => '254711000105', 'email' => null],
            ['name' => 'Ann Wafula', 'phone' => '254711000106', 'email' => 'ann.wafula@example.test'],
        ];

        $plan = [
            ['status' => 'confirmed', 'payment_status' => 'paid', 'checkIn' => 4, 'nights' => 3, 'paid' => true],
            ['status' => 'confirmed', 'payment_status' => 'deposit_paid', 'checkIn' => 9, 'nights' => 5, 'paid' => true],
            ['status' => 'checked_in', 'payment_status' => 'paid', 'checkIn' => -1, 'nights' => 4, 'paid' => true],
            ['status' => 'checked_out', 'payment_status' => 'paid', 'checkIn' => -12, 'nights' => 3, 'paid' => true],
            ['status' => 'checked_out', 'payment_status' => 'paid', 'checkIn' => -25, 'nights' => 6, 'paid' => true],
            ['status' => 'pending', 'payment_status' => 'unpaid', 'checkIn' => 16, 'nights' => 2, 'paid' => false],
            ['status' => 'cancelled', 'payment_status' => 'unpaid', 'checkIn' => -6, 'nights' => 2, 'paid' => false],
        ];

        foreach ($plan as $index => $row) {
            $house = $bnbHouses[$index % $bnbHouses->count()];
            $guest = $guestPool[$index % count($guestPool)];
            $nightlyPackage = $house->pricePackages()->where('billing_unit', 'night')->first();
            $nightlyRate = $nightlyPackage?->price ?? 5000;

            $checkIn = now()->addDays($row['checkIn'])->toDateString();
            $checkOut = now()->addDays($row['checkIn'] + $row['nights'])->toDateString();
            $total = $nightlyRate * $row['nights'];

            $booking = Booking::create([
                'house_id' => $house->id,
                'price_package_id' => $nightlyPackage?->id,
                'guest_name' => $guest['name'],
                'guest_phone' => $guest['phone'],
                'guest_email' => $guest['email'],
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'nights' => $row['nights'],
                'package_name' => 'Nightly',
                'nightly_rate' => $nightlyRate,
                'billing_unit' => 'night',
                'total_amount' => $total,
                'status' => $row['status'],
                'payment_status' => $row['payment_status'],
                'expires_at' => $row['status'] === 'pending' ? now()->addHours(2) : null,
            ]);

            if ($row['paid']) {
                BookingPayment::create([
                    'booking_id' => $booking->id,
                    'amount' => $row['payment_status'] === 'deposit_paid' ? round($total * 0.4) : $total,
                    'method' => 'mpesa',
                    'status' => 'completed',
                    'reference' => 'DEMO-BNB-' . strtoupper(uniqid()),
                    'landlord_id' => $landlordId,
                ]);
            }
        }
    }

    private function seedIssues(\Illuminate\Support\Collection $tenants): void
    {
        if ($tenants->isEmpty()) {
            return;
        }

        $statuses = array_merge(array_fill(0, 6, 'open'), array_fill(0, 4, 'in_progress'), array_fill(0, 7, 'resolved'));

        foreach ($statuses as $index => $status) {
            $tenant = $tenants->random();
            Auth::login($tenant->user);

            Issue::firstOrCreate(
                ['tenant_id' => $tenant->id, 'title' => $this->issueTitles[$index % count($this->issueTitles)]],
                ['description' => 'Reported via tenant portal during demo data seeding.', 'status' => $status]
            );

            Auth::logout();
        }
    }

    private function seedNoticeToVacate(\Illuminate\Support\Collection $tenants): void
    {
        if ($tenants->count() < 3) {
            return;
        }

        $shuffled = $tenants->shuffle();
        $pendingTenant = $shuffled[0];
        $approvedTenant = $shuffled[1];
        $deniedTenant = $shuffled[2];

        Auth::login($pendingTenant->user);
        NoticeToVacate::firstOrCreate(
            ['tenant_id' => $pendingTenant->id, 'status' => 'pending'],
            ['vacate_date' => now()->addDays(30), 'reason_type' => 'Job Transfer']
        );
        Auth::logout();

        $approver = User::where('role', 'admin')->where('landlord_id', $approvedTenant->landlord_id)->first();

        Auth::login($approvedTenant->user);
        NoticeToVacate::firstOrCreate(
            ['tenant_id' => $approvedTenant->id, 'status' => 'approved'],
            [
                'vacate_date' => now()->addDays(14), 'reason_type' => 'Relocation',
                'approved_by' => $approver?->id, 'approved_at' => now()->subDays(2),
            ]
        );
        Auth::logout();

        $deniedApprover = User::where('role', 'admin')->where('landlord_id', $deniedTenant->landlord_id)->first();

        Auth::login($deniedTenant->user);
        NoticeToVacate::firstOrCreate(
            ['tenant_id' => $deniedTenant->id, 'status' => 'denied'],
            [
                'vacate_date' => now()->addDays(21), 'reason_type' => 'Other',
                'reason_text' => 'Moving in with family.',
                'admin_notes' => 'Lease has 4 months remaining - please serve full notice period or settle the early-exit fee.',
                'approved_by' => $deniedApprover?->id, 'denied_at' => now()->subDay(),
            ]
        );
        Auth::logout();
    }

    /** A short chat thread per tenant with their landlord's admin - some read, one left unread for the notification bell. */
    private function seedMessages(\Illuminate\Support\Collection $tenants): void
    {
        if ($tenants->isEmpty()) {
            return;
        }

        $threads = [
            [
                ['from' => 'tenant', 'body' => 'Hi, is it possible to get a receipt for last month\'s rent payment?', 'read' => true],
                ['from' => 'admin', 'body' => 'Sure, I\'ll email it over today.', 'read' => true],
                ['from' => 'tenant', 'body' => 'Thank you!', 'read' => true],
            ],
            [
                ['from' => 'tenant', 'body' => 'The parking gate has been stuck open since yesterday evening.', 'read' => true],
                ['from' => 'admin', 'body' => 'Thanks for flagging - I\'ve logged it with the caretaker, should be fixed by tomorrow.', 'read' => false],
            ],
            [
                ['from' => 'tenant', 'body' => 'Good morning, will rent for next month still be KES the same amount?', 'read' => false],
            ],
            [
                ['from' => 'admin', 'body' => 'Reminder: your invoice for this month is due in 3 days.', 'read' => true],
                ['from' => 'tenant', 'body' => 'Noted, will pay by Friday.', 'read' => false],
            ],
        ];

        $sample = $tenants->shuffle()->take(min(4, $tenants->count()));

        foreach ($sample as $index => $tenant) {
            $admin = User::where('role', 'admin')->where('landlord_id', $tenant->landlord_id)->first();
            if (!$admin || !$tenant->user) {
                continue;
            }

            // Idempotent per-house (not a global "any message exists" check, which
            // would wrongly skip every tenant just because some other landlord's real,
            // non-demo chat history already has messages in it).
            if (Message::withoutGlobalScopes()->where('house_id', $tenant->house_id)->exists()) {
                continue;
            }

            $thread = $threads[$index % count($threads)];

            foreach ($thread as $i => $msg) {
                $sender = $msg['from'] === 'tenant' ? $tenant->user : $admin;
                $receiver = $msg['from'] === 'tenant' ? $admin : $tenant->user;

                Message::create([
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                    'house_id' => $tenant->house_id,
                    'body' => $msg['body'],
                    'read_at' => $msg['read'] ? now()->subMinutes(count($thread) - $i) : null,
                    'created_at' => now()->subHours(count($thread) - $i)->subDays($index),
                    'updated_at' => now()->subHours(count($thread) - $i)->subDays($index),
                ]);
            }
        }
    }

    /** Self-registered "looking for a house" accounts - no landlord_id, can browse/apply across every landlord. */
    private function seedProspectiveUsers(): \Illuminate\Support\Collection
    {
        $names = [
            'Cynthia Muthoni', 'Brian Kiplagat', 'Amina Hassan', 'Derrick Omondi', 'Sheila Chepkoech',
            'Nixon Odhiambo', 'Faith Wangari', 'Allan Kiptoo',
        ];

        return collect($names)->map(function ($name) {
            $email = strtolower(str_replace(' ', '.', $name)) . ($this->userCounter++) . '@example.test';

            return User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make(self::DEMO_PASSWORD),
                    'role' => 'user',
                    'phone_number' => '2547' . random_int(10000000, 99999999),
                ]
            );
        });
    }

    private function seedViewingRequests(\Illuminate\Support\Collection $prospects, \Illuminate\Support\Collection $vacantHouses): void
    {
        if ($prospects->isEmpty() || $vacantHouses->isEmpty() || ViewingRequest::query()->exists()) {
            return; // idempotent - only ever seed this once
        }

        $plan = [
            ['status' => 'pending'],
            ['status' => 'pending'],
            ['status' => 'admitted'],
            ['status' => 'revoked'],
        ];

        foreach ($plan as $index => $row) {
            $user = $prospects[$index % $prospects->count()];
            $house = $vacantHouses->random();
            $handler = User::where('role', 'admin')->where('landlord_id', $house->landlord_id)->first();

            Auth::login($user);

            $request = ViewingRequest::create([
                'user_id' => $user->id,
                'house_id' => $house->id,
                'status' => $row['status'],
                'requested_at' => now()->subDays(random_int(1, 6)),
            ]);

            Auth::logout();

            if ($row['status'] !== 'pending' && $handler) {
                $request->update([
                    'handled_by' => $handler->id,
                    'admin_notes' => $row['status'] === 'admitted'
                        ? 'Viewed the unit and moved in the same week.'
                        : 'Did not show up for the scheduled viewing twice.',
                ]);
            }
        }
    }

    private function seedWatchlist(\Illuminate\Support\Collection $prospects, \Illuminate\Support\Collection $vacantHouses): void
    {
        if ($prospects->isEmpty() || $vacantHouses->isEmpty()) {
            return;
        }

        foreach ($prospects as $user) {
            $picks = $vacantHouses->random(min(random_int(1, 3), $vacantHouses->count()));
            $user->watchlist()->syncWithoutDetaching($picks->pluck('id'));
        }
    }
}
