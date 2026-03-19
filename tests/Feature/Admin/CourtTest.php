<?php

declare(strict_types=1);

use App\Enums\CourtStatus;
use App\Models\Country;
use App\Models\Court;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Permission::query()->firstOrCreate(['name' => 'edit-courts']);
});

test('guests are redirected from courts index', function (): void {
    $response = $this->get(route('admin.courts.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can view courts index', function (): void {
    $user = User::factory()->create()->givePermissionTo('edit-courts');
    $this->actingAs($user);

    $response = $this->get(route('admin.courts.index'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('admin/courts/index'));
});

test('authenticated users can create a court', function (): void {
    $user = User::factory()->create()->givePermissionTo('edit-courts');
    $country = Country::factory()->create(['name' => 'United Kingdom', 'iso_alpha2' => 'GB']);
    $this->actingAs($user);

    $response = $this->post(route('admin.courts.store'), [
        'name' => 'Test Court',
        'country_id' => $country->id,
        'city' => 'London',
        'host_name' => 'John Doe',
        'contact_email' => 'john@example.com',
        'contact_phone' => '+44 1234 567890',
        'latitude' => 51.5074,
        'longitude' => -0.1278,
        'status' => CourtStatus::ACTIVE->value,
    ]);

    $response->assertRedirect(route('admin.courts.index'));

    $court = Court::query()->where('name', 'Test Court')->firstOrFail();

    expect($court->court_code)->toMatch('/^GB-LON-\d{6}$/');

    $this->assertDatabaseHas('courts', [
        'name' => 'Test Court',
        'country_id' => $country->id,
        'city' => 'London',
        'host_name' => 'John Doe',
        'contact_email' => 'john@example.com',
        'contact_phone' => '+44 1234 567890',
        'status' => CourtStatus::ACTIVE->value,
        'created_by' => $user->id,
    ]);
});

test('create court validates required fields', function (): void {
    $user = User::factory()->create()->givePermissionTo('edit-courts');
    $this->actingAs($user);

    $response = $this->post(route('admin.courts.store'), []);

    $response->assertInvalid(['name', 'country_id', 'city', 'status']);
});

test('authenticated users can update a court', function (): void {
    $user = User::factory()->create()->givePermissionTo('edit-courts');
    $court = Court::factory()->create(['created_by' => $user->id]);
    $newCountry = Country::factory()->create(['name' => 'France', 'iso_alpha2' => 'FR']);
    $this->actingAs($user);

    $response = $this->patch(route('admin.courts.update', $court), [
        'name' => 'Updated Court',
        'country_id' => $newCountry->id,
        'city' => 'Paris',
        'host_name' => 'Jane Smith',
        'contact_email' => 'jane@example.com',
        'contact_phone' => '+33 1 23 45 67 89',
        'latitude' => 48.8566,
        'longitude' => 2.3522,
        'status' => CourtStatus::PILOT->value,
    ]);

    $response->assertRedirect(route('admin.courts.index'));

    $this->assertDatabaseHas('courts', [
        'id' => $court->id,
        'name' => 'Updated Court',
        'country_id' => $newCountry->id,
        'city' => 'Paris',
        'host_name' => 'Jane Smith',
        'contact_email' => 'jane@example.com',
        'contact_phone' => '+33 1 23 45 67 89',
        'status' => CourtStatus::PILOT->value,
    ]);
});

test('courts index can be filtered by search term', function (): void {
    $user = User::factory()->create()->givePermissionTo('edit-courts');
    $this->actingAs($user);

    $matching = Court::factory()->create(['name' => 'Wimbledon Centre Court', 'created_by' => $user->id]);
    $other = Court::factory()->create(['name' => 'Roland Garros', 'created_by' => $user->id]);

    $response = $this->get(route('admin.courts.index', ['search' => 'Wimbledon']));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('admin/courts/index')
            ->where('filters.search', 'Wimbledon')
            ->has('courts.data', 1)
            ->where('courts.data.0.id', $matching->id)
    );
});

test('authenticated users can delete a court', function (): void {
    $user = User::factory()->create()->givePermissionTo('edit-courts');
    $court = Court::factory()->create(['created_by' => $user->id]);
    $this->actingAs($user);

    $response = $this->delete(route('admin.courts.destroy', $court));

    $response->assertRedirect();

    $this->assertDatabaseMissing('courts', [
        'id' => $court->id,
    ]);
});

test('court_code is generated with correct format', function (): void {
    $country = Country::factory()->create(['iso_alpha2' => 'RW']);

    $code = Court::generateCourtCode($country, 'Kigali');

    expect($code)->toMatch('/^RW-KIG-\d{6}$/');
});

test('court_code sequence increments for the same location prefix', function (): void {
    $rwanda = Country::factory()->create(['iso_alpha2' => 'RW']);

    Court::factory()->create([
        'country_id' => $rwanda->id,
        'city' => 'Kigali',
        'court_code' => 'RW-KIG-000001',
    ]);

    $code = Court::generateCourtCode($rwanda, 'Kigali');

    expect($code)->toBe('RW-KIG-000002');
});

test('court_code is unique per court', function (): void {
    $user = User::factory()->create()->givePermissionTo('edit-courts');
    $country = Country::factory()->create(['name' => 'Rwanda', 'iso_alpha2' => 'RW']);
    $this->actingAs($user);

    $this->post(route('admin.courts.store'), [
        'name' => 'Court A',
        'country_id' => $country->id,
        'city' => 'Kigali',
        'status' => CourtStatus::ACTIVE->value,
    ]);

    $this->post(route('admin.courts.store'), [
        'name' => 'Court B',
        'country_id' => $country->id,
        'city' => 'Kigali',
        'status' => CourtStatus::ACTIVE->value,
    ]);

    $codes = Court::query()
        ->orderBy('court_code')
        ->pluck('court_code');

    expect($codes)->toHaveCount(2)
        ->and($codes[0])->toBe('RW-KIG-000001')
        ->and($codes[1])->toBe('RW-KIG-000002');
});

test('courts can be searched by court_code', function (): void {
    $user = User::factory()->create()->givePermissionTo('edit-courts');
    $this->actingAs($user);

    $matching = Court::factory()->create([
        'court_code' => 'RW-KIG-000001',
        'created_by' => $user->id,
    ]);
    Court::factory()->create([
        'court_code' => 'GB-LON-000001',
        'created_by' => $user->id,
    ]);

    $response = $this->get(route('admin.courts.index', ['search' => 'RW-KIG']));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('admin/courts/index')
            ->has('courts.data', 1)
            ->where('courts.data.0.id', $matching->id)
    );
});

test('court belongs to creator via createdBy relationship', function (): void {
    $user = User::factory()->create();
    $court = Court::factory()->create(['created_by' => $user->id]);

    expect($court->createdBy)->toBeInstanceOf(User::class)
        ->and($court->createdBy->id)->toBe($user->id);
});
