<?php

namespace Tests\Feature;

use App\Livewire\AdminApp\Properties;
use App\Models\House;
use App\Models\Landlord;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Temporary verification-only test - not part of the permanent suite. Confirms the
 * new "Add property" / "Add unit" flow in the app-shell Properties page actually
 * creates real records through Livewire's real request/hydration cycle.
 */
class QaPropertiesCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_landlord_can_create_property_and_unit(): void
    {
        $landlord = Landlord::create(['name' => 'QA Landlord', 'contact_email' => 'qa@example.com']);
        $user = User::factory()->create(['role' => 'landlord', 'landlord_id' => $landlord->id]);
        $this->actingAs($user);

        Livewire::test(Properties::class)
            ->set('location_name', 'Test Court')
            ->set('geo_id', 'Kilimani')
            ->call('createProperty');

        $location = Location::where('location_name', 'Test Court')->first();
        $this->assertNotNull($location);
        $this->assertSame($landlord->id, $location->landlord_id);

        Livewire::test(Properties::class)
            ->call('startAddingHouse', $location->id)
            ->set('house_name', 'Unit A1')
            ->set('house_type', 'Bedsitter')
            ->set('rent_amount', 12000)
            ->set('listing_mode', 'long_term')
            ->call('createHouse');

        $house = House::where('house_name', 'Unit A1')->first();
        $this->assertNotNull($house);
        $this->assertSame($location->id, $house->location_id);
        $this->assertSame('Vacant', $house->house_status);
    }
}
