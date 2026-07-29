<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\House;
use App\Models\Invoice;
use App\Models\Issue;
use App\Models\Landlord;
use App\Models\Location;
use App\Models\NoticeToVacate;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

/**
 * Seeds a demo multi-landlord Nairobi dataset for sales demos. Not wired into
 * DatabaseSeeder by default - run explicitly:
 *   php artisan db:seed --class=Database\\Seeders\\DemoNairobiSeeder
 * Intended to run against a freshly migrated database (php artisan migrate:fresh).
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
        $issuePool = collect();

        foreach ($manifest as $landlordConfig) {
            [$landlord, $tenants] = $this->seedLandlord($landlordConfig, $packages);
            $allTenants = $allTenants->merge($tenants);
        }

        $this->seedIssues($allTenants);
        $this->seedNoticeToVacate($allTenants);

        $this->command?->info('Done. Superadmin login: superadmin@rentydemo.co.ke / ' . self::DEMO_PASSWORD);
    }

    private function packages(): array
    {
        $starter = Package::firstOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter', 'price' => 1500, 'billing_interval' => 'monthly',
                'max_locations' => 2, 'max_houses' => 15, 'max_tenants' => 15,
                'trial_days' => 14, 'is_active' => true, 'sort_order' => 1,
                'features' => ['sms_notifications' => true],
            ]
        );

        $growth = Package::firstOrCreate(
            ['slug' => 'growth'],
            [
                'name' => 'Growth', 'price' => 3500, 'billing_interval' => 'monthly',
                'max_locations' => 6, 'max_houses' => 60, 'max_tenants' => 60,
                'trial_days' => 14, 'is_active' => true, 'sort_order' => 2,
                'features' => ['sms_notifications' => true, 'mpesa_payments' => true],
            ]
        );

        $pro = Package::firstOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro', 'price' => 7000, 'billing_interval' => 'monthly',
                'max_locations' => null, 'max_houses' => null, 'max_tenants' => null,
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

        return [
            [
                'business_name' => 'Mwangi Properties Ltd',
                'owner_email' => 'james.mwangi@rentydemo.co.ke',
                'owner_name' => 'James Mwangi',
                'package' => 'growth',
                'subscription_status' => 'active',
                // Automatic payment mode, so this landlord's tenant portal shows the
                // M-Pesa/Pesapal "Pay Now" button - the other two demo landlords are
                // left on the (manual) default to show both modes exist.
                'payment_mode' => 'automatic',
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
                // trial, for visible contrast against Mwangi's and Kariuki's larger
                // portfolios. Well within the Starter package's 15 house/tenant cap,
                // which also leaves headroom to demo "add another property" live.
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
                // The biggest portfolio - Pro has no location/house/tenant caps, so this
                // is the one landlord it makes sense to show with 4 buildings.
                'locations' => [
                    ['name' => 'Ruaka Riverside Apartments', 'area' => 'Ruaka', 'rates' => $mid, 'mix' => ['Bedsitter' => 4, '1 Bedroom' => 7, '2 Bedroom' => 4, '3 Bedroom' => 1]],
                    ['name' => 'Syokimau Palm Court', 'area' => 'Syokimau', 'rates' => $mid, 'mix' => ['Bedsitter' => 3, '1 Bedroom' => 4, '2 Bedroom' => 2, '3 Bedroom' => 1]],
                    ['name' => 'Westlands Vista Annex', 'area' => 'Westlands', 'rates' => $upperMid, 'mix' => ['Bedsitter' => 1, '1 Bedroom' => 2, '2 Bedroom' => 2, '3 Bedroom' => 1]],
                    ['name' => 'Karen Manor Apartments', 'area' => 'Karen', 'rates' => $upperMid, 'mix' => ['1 Bedroom' => 2, '2 Bedroom' => 4, '3 Bedroom' => 2]],
                ],
            ],
        ];
    }

    private function seedLandlord(array $config, array $packages): array
    {
        $landlord = Landlord::firstOrCreate(
            ['contact_email' => $config['owner_email']],
            ['name' => $config['business_name'], 'status' => 'active']
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
                'status' => $config['subscription_status'], 'starts_at' => now()->subDays(30),
                'trial_ends_at' => $config['subscription_status'] === 'trialing' ? now()->addDays(7) : null,
                'expires_at' => now()->addDays($config['subscription_status'] === 'trialing' ? 7 : 335),
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

        foreach ($config['locations'] as $locConfig) {
            $location = Location::firstOrCreate(
                ['location_name' => $locConfig['name'], 'landlord_id' => $landlord->id],
                ['geo_id' => $locConfig['area']]
            );

            $caretakerEmail = 'caretaker.' . str($locConfig['name'])->slug('') . '@rentydemo.co.ke';
            User::firstOrCreate(
                ['email' => $caretakerEmail],
                ['name' => 'Caretaker - ' . $locConfig['name'], 'password' => Hash::make(self::DEMO_PASSWORD), 'role' => 'caretaker', 'landlord_id' => $landlord->id, 'location_id' => $location->id]
            );

            $houseTenants = $this->seedLocationHouses($location, $locConfig);
            $tenants = $tenants->merge($houseTenants);
        }

        Auth::logout();

        return [$landlord, $tenants];
    }

    private function seedLocationHouses(Location $location, array $locConfig): \Illuminate\Support\Collection
    {
        if ($location->houses()->count() > 0) {
            // Already seeded (idempotent re-run) - reuse existing tenants for this location.
            return Tenant::whereHas('house', fn ($q) => $q->where('location_id', $location->id))->get();
        }

        $tenants = collect();
        $unitNumber = 1;

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
                ]);
                $unitNumber++;

                // ~85% occupancy
                if (random_int(1, 100) <= 85) {
                    $tenants->push($this->seedTenantWithHistory($house));
                }
            }
        }

        return $tenants;
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

    private function seedIssues(\Illuminate\Support\Collection $tenants): void
    {
        if ($tenants->isEmpty()) {
            return;
        }

        $statuses = array_merge(array_fill(0, 4, 'open'), array_fill(0, 3, 'in_progress'), array_fill(0, 5, 'resolved'));

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
        if ($tenants->count() < 2) {
            return;
        }

        $pendingTenant = $tenants->first();
        $approvedTenant = $tenants->last();

        Auth::login($pendingTenant->user);
        NoticeToVacate::firstOrCreate(
            ['tenant_id' => $pendingTenant->id, 'status' => 'pending'],
            ['vacate_date' => now()->addDays(30), 'reason_type' => 'Job Transfer']
        );
        Auth::logout();

        $approver = User::where('role', 'admin')->where('landlord_id', $approvedTenant->landlord_id)->first();

        Auth::login($approvedTenant->user);
        $notice = NoticeToVacate::firstOrCreate(
            ['tenant_id' => $approvedTenant->id, 'status' => 'approved'],
            [
                'vacate_date' => now()->addDays(14), 'reason_type' => 'Relocation',
                'approved_by' => $approver?->id, 'approved_at' => now()->subDays(2),
            ]
        );
        Auth::logout();
    }
}
