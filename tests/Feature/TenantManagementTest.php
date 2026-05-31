<?php

use App\Domain\Billing\Contracts\BookingPaymentGateway;
use App\Domain\Billing\DTO\BookingPaymentRedirectData;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\File as FileRecord;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\SuperAdminRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    cleanupTenantDatabases();
});

afterEach(function (): void {
    cleanupTenantDatabases();
});

function actingAsSuperAdmin(): User
{
    seed(SuperAdminRoleSeeder::class);

    $user = User::query()
        ->where('email', 'super-admin@gmail.com')
        ->firstOrFail();

    Sanctum::actingAs($user);

    return $user;
}

function cleanupTenantDatabases(): void
{
    if (tenancy()->initialized) {
        tenancy()->end();
    }

    DB::disconnect('tenant');
    DB::purge('tenant');

    collect(File::glob(database_path('tenant*')))
        ->each(fn (string $database): bool => File::delete($database));
}

test('super admin can create a tenant', function () {
    actingAsSuperAdmin();

    $response = postJson('/api/tenants', [
        'id' => 'keranipara',
        'name' => 'keranipara',
        'domain' => 'keranipara.com',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', 'keranipara')
        ->assertJsonPath('data.name', 'keranipara')
        ->assertJsonPath('data.domain', 'keranipara.com')
        ->assertJsonPath('data.domains.0', 'keranipara.com');

    assertDatabaseHas('tenants', [
        'id' => 'keranipara',
    ]);

    assertDatabaseHas('domains', [
        'tenant_id' => 'keranipara',
        'domain' => 'keranipara.com',
    ]);
});

test('super admin can get tenants', function () {
    actingAsSuperAdmin();

    $hotelAlpha = Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]);
    $hotelAlpha->createDomain('alpha.example.com');

    $keranipara = Tenant::query()->create([
        'id' => 'keranipara',
        'name' => 'keranipara',
    ]);
    $keranipara->createDomain('keranipara.com');

    getJson('/api/tenants')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.id', 'keranipara')
        ->assertJsonPath('data.0.domain', 'keranipara.com')
        ->assertJsonPath('data.0.domains.0', 'keranipara.com')
        ->assertJsonPath('data.1.id', 'hotel-alpha')
        ->assertJsonPath('data.1.domain', 'alpha.example.com');
});

test('super admin can add tenant admin or staff users', function (string $role) {
    actingAsSuperAdmin();

    $tenantId = "hotel-{$role}";

    Tenant::query()->create([
        'id' => $tenantId,
        'name' => 'Hotel Alpha',
    ]);

    $response = postJson("/api/tenants/{$tenantId}/users", [
        'name' => 'Tenant User',
        'email' => "{$role}@example.com",
        'password' => 'password',
        'role' => $role,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.tenant_id', $tenantId)
        ->assertJsonPath('data.email', "{$role}@example.com")
        ->assertJsonPath('data.roles.0.name', $role);

    $user = User::query()
        ->where('email', "{$role}@example.com")
        ->firstOrFail();

    expect($user->tenant_id)->toBe($tenantId)
        ->and($user->hasRole($role))->toBeTrue();
})->with(['admin', 'staff']);

test('super admin can get tenant users', function () {
    actingAsSuperAdmin();

    $tenant = Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]);

    $otherTenant = Tenant::query()->create([
        'id' => 'hotel-beta',
        'name' => 'Hotel Beta',
    ]);

    $admin = User::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'name' => 'Tenant Admin',
        'email' => 'admin@example.com',
    ]);
    $admin->assignRole(Role::findOrCreate('admin', 'sanctum'));

    $staff = User::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'name' => 'Tenant Staff',
        'email' => 'staff@example.com',
    ]);
    $staff->assignRole(Role::findOrCreate('staff', 'sanctum'));

    User::factory()->create([
        'tenant_id' => $otherTenant->getKey(),
        'email' => 'other@example.com',
    ]);

    getJson('/api/tenants/hotel-alpha/users')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.tenant_id', 'hotel-alpha')
        ->assertJsonPath('data.0.email', 'staff@example.com')
        ->assertJsonPath('data.0.roles.0.name', 'staff')
        ->assertJsonPath('data.1.tenant_id', 'hotel-alpha')
        ->assertJsonPath('data.1.email', 'admin@example.com')
        ->assertJsonPath('data.1.roles.0.name', 'admin');
});

test('tenant user can login for their tenant', function () {
    $tenant = Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);
    $user->assignRole(Role::findOrCreate('admin', 'sanctum'));

    postJson('/api/tenants/hotel-alpha/login', [
        'identifier' => 'admin@example.com',
        'password' => 'password',
        'device_name' => 'tenant-dashboard',
    ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.tenant_id', 'hotel-alpha')
        ->assertJsonPath('data.user.email', 'admin@example.com')
        ->assertJsonPath('data.user.roles.0.name', 'admin')
        ->assertJsonPath('data.user.roles.0.guard_name', 'sanctum');
});

test('tenant user cannot login for another tenant', function () {
    Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]);

    $otherTenant = Tenant::query()->create([
        'id' => 'hotel-beta',
        'name' => 'Hotel Beta',
    ]);

    User::factory()->create([
        'tenant_id' => $otherTenant->getKey(),
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    postJson('/api/tenants/hotel-alpha/login', [
        'identifier' => 'admin@example.com',
        'password' => 'password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('identifier');
});

test('tenant user can get their profile', function () {
    $tenant = Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'name' => 'Tenant Admin',
        'email' => 'admin@example.com',
        'phone_number' => '01700000000',
    ]);
    $user->assignRole(Role::findOrCreate('admin', 'sanctum'));

    Sanctum::actingAs($user);

    getJson('/api/tenants/hotel-alpha/me')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.tenant_id', 'hotel-alpha')
        ->assertJsonPath('data.name', 'Tenant Admin')
        ->assertJsonPath('data.email', 'admin@example.com')
        ->assertJsonPath('data.phone_number', '01700000000')
        ->assertJsonPath('data.roles.0.name', 'admin');
});

test('tenant user can upload files using api files upload path', function () {
    Storage::fake('public');

    $tenant = Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'email' => 'admin@example.com',
    ]);
    $user->assignRole(Role::findOrCreate('admin', 'sanctum'));

    Sanctum::actingAs($user);

    post('/api/files/upload', [
        'directory' => 'uploads',
        'file' => UploadedFile::fake()->image('room.jpg'),
    ], [
        'Accept' => 'application/json',
    ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.path', fn (string $path): bool => str_starts_with($path, 'uploads/'));
});

test('tenant user can create a room with multiple images', function () {
    Storage::fake('public');

    $tenant = Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'email' => 'admin@example.com',
    ]);
    $user->assignRole(Role::findOrCreate('admin', 'sanctum'));

    Sanctum::actingAs($user);

    $uploadResponse = post('/api/tenants/hotel-alpha/files/upload', [
        'directory' => 'rooms',
        'files' => [
            UploadedFile::fake()->image('front.jpg'),
            UploadedFile::fake()->image('inside.jpg'),
        ],
    ], [
        'Accept' => 'application/json',
    ]);

    $uploadResponse
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data');

    $response = postJson('/api/tenants/hotel-alpha/rooms', [
        'room_name' => 'Deluxe Family Room',
        'room_type' => 'ac',
        'capacity' => 4,
        'rate' => '4500.50',
        'available_rooms' => 3,
        'status' => 'available',
        'amenities' => ['wifi', 'ac', 'breakfast'],
        'description' => 'Large family room with city view.',
        'images' => [
            $uploadResponse->json('data.0.url'),
            $uploadResponse->json('data.1.url'),
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.tenant_id', 'hotel-alpha')
        ->assertJsonPath('data.room_name', 'Deluxe Family Room')
        ->assertJsonPath('data.room_type', 'ac')
        ->assertJsonPath('data.capacity', 4)
        ->assertJsonPath('data.rate', '4500.50')
        ->assertJsonPath('data.available_rooms', 3)
        ->assertJsonPath('data.status', 'available')
        ->assertJsonPath('data.amenities.0', 'wifi')
        ->assertJsonPath('data.description', 'Large family room with city view.')
        ->assertJsonCount(2, 'data.images')
        ->assertJsonCount(2, 'data.image_urls');

    tenancy()->initialize($tenant);

    try {
        expect(Storage::disk('public')->exists($response->json('data.images.0')))->toBeTrue()
            ->and(Storage::disk('public')->exists($response->json('data.images.1')))->toBeTrue();

        expect(Room::query()
            ->where('tenant_id', 'hotel-alpha')
            ->where('room_name', 'Deluxe Family Room')
            ->where('room_type', 'ac')
            ->where('capacity', 4)
            ->where('available_rooms', 3)
            ->where('status', 'available')
            ->exists())->toBeTrue();
    } finally {
        tenancy()->end();
    }

    assertDatabaseHas('files', [
        'tenant_id' => 'hotel-alpha',
        'category' => Room::IMAGE_CATEGORY,
        'fileable_type' => Room::class,
        'path' => $response->json('data.images.0'),
    ]);
});

test('public user can get rooms', function () {
    $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]));

    tenancy()->initialize($tenant);

    try {
        $room = Room::factory()->create([
            'room_name' => 'Suite Room',
            'room_type' => 'non_ac',
            'capacity' => 2,
            'rate' => '6500.00',
            'available_rooms' => 1,
            'status' => 'maintenance',
            'amenities' => ['wifi', 'tv'],
            'description' => 'Suite with balcony.',
        ]);

        FileRecord::query()->create([
            'fileable_id' => $room->id,
            'fileable_type' => Room::class,
            'category' => Room::IMAGE_CATEGORY,
            'tenant_id' => 'hotel-alpha',
            'disk' => 'public',
            'path' => 'rooms/suite.jpg',
        ]);
    } finally {
        tenancy()->end();
    }

    getJson('/api/tenants/hotel-alpha/rooms')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.tenant_id', 'hotel-alpha')
        ->assertJsonPath('data.0.room_name', 'Suite Room')
        ->assertJsonPath('data.0.room_type', 'non_ac')
        ->assertJsonPath('data.0.capacity', 2)
        ->assertJsonPath('data.0.rate', '6500.00')
        ->assertJsonPath('data.0.available_rooms', 1)
        ->assertJsonPath('data.0.status', 'maintenance')
        ->assertJsonPath('data.0.amenities.0', 'wifi')
        ->assertJsonPath('data.0.images.0', 'rooms/suite.jpg')
        ->assertJsonPath('data.0.description', 'Suite with balcony.');
});

test('public user can create a pay later booking', function () {
    $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]));

    tenancy()->initialize($tenant);

    try {
        $room = Room::factory()->create([
            'tenant_id' => 'hotel-alpha',
            'room_name' => 'Business Twin Room',
            'rate' => '625.25',
        ]);
    } finally {
        tenancy()->end();
    }

    $response = postJson('/api/tenants/hotel-alpha/public-bookings', [
        'customer_name' => 'Louis Roman',
        'phone_number' => '+1 (322) 772-6369',
        'email' => 'judolyky@mailinator.com',
        'address' => 'Cum dicta ullamco po',
        'payment_option' => 'pay_later',
        'check_in' => '2026-05-31',
        'check_out' => '2026-06-01',
        'guests' => '4',
        'room_id' => (string) $room->id,
        'room_quantity' => 1,
        'stay_nights' => 1,
    ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.booking.tenant_id', 'hotel-alpha')
        ->assertJsonPath('data.booking.room_id', $room->id)
        ->assertJsonPath('data.booking.guest_name', 'Louis Roman')
        ->assertJsonPath('data.booking.guest_email', 'judolyky@mailinator.com')
        ->assertJsonPath('data.booking.guest_phone', '+1 (322) 772-6369')
        ->assertJsonPath('data.booking.assigned_room_number', 'To be assigned')
        ->assertJsonPath('data.booking.invoice.status', 'issued')
        ->assertJsonPath('data.payment', null);

    expect($response->json('data.booking.invoice.total_amount'))->toBe('625.25');

    getJson("/api/tenants/hotel-alpha/public/invoices/{$response->json('data.booking.invoice.id')}")
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.invoice_number', $response->json('data.booking.invoice.invoice_number'))
        ->assertJsonPath('data.total_amount', '625.25')
        ->assertJsonPath('data.amount_due', '625.25')
        ->assertJsonPath('data.status', 'issued');
});

test('public user gets sslcommerz redirect data for pay now booking', function () {
    $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]));

    tenancy()->initialize($tenant);

    try {
        $room = Room::factory()->create([
            'tenant_id' => 'hotel-alpha',
            'room_name' => 'Business Twin Room',
            'rate' => '625.25',
        ]);
    } finally {
        tenancy()->end();
    }

    $gateway = new class implements BookingPaymentGateway
    {
        public ?Booking $booking = null;

        public function redirectForBooking(Booking $booking): BookingPaymentRedirectData
        {
            $this->booking = $booking;

            return new BookingPaymentRedirectData(
                payment_url: 'https://sandbox.sslcommerz.com/pay/test-session',
                transaction_id: 'BKG-000001-TEST',
            );
        }
    };

    app()->instance(BookingPaymentGateway::class, $gateway);

    $response = postJson('/api/tenants/hotel-alpha/public-bookings', [
        'customer_name' => 'Louis Roman',
        'phone_number' => '+1 (322) 772-6369',
        'email' => 'judolyky@mailinator.com',
        'address' => 'Cum dicta ullamco po',
        'payment_option' => 'pay_now',
        'check_in' => '2026-05-31',
        'check_out' => '2026-06-01',
        'guests' => '4',
        'room_id' => (string) $room->id,
        'room_quantity' => 1,
        'stay_nights' => 1,
    ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.booking.guest_name', 'Louis Roman')
        ->assertJsonPath('data.payment.payment_url', 'https://sandbox.sslcommerz.com/pay/test-session')
        ->assertJsonPath('data.payment.transaction_id', 'BKG-000001-TEST');

    expect($gateway->booking)->toBeInstanceOf(Booking::class)
        ->and($gateway->booking?->invoice?->amount_due)->toBe('625.25')
        ->and($response->json('data.booking.invoice.amount_due'))->toBe('625.25');

    $invoiceNumber = $response->json('data.booking.invoice.invoice_number');
    config()->set('services.sslcommerz.success_url', 'http://localhost:3000/payment/{invoice_number}/success');
    config()->set('services.sslcommerz.fail_url', 'http://localhost:3000/payment/{invoice_number}/fail');
    config()->set('services.sslcommerz.cancel_url', 'http://localhost:3000/payment/{invoice_number}/cancel');

    getJson("/api/tenants/hotel-alpha/public/invoices/by-number/{$invoiceNumber}/sslcommerz/success?".http_build_query([
        'tran_id' => 'BKG-000001-TEST',
        'amount' => '625.25',
        'status' => 'VALID',
        'card_type' => 'VISA',
        'tran_date' => '2026-05-31 10:30:00',
    ]), [
        'Accept' => 'application/json',
    ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'paid')
        ->assertJsonPath('data.amount_paid', '625.25')
        ->assertJsonPath('data.amount_due', '0.00')
        ->assertJsonPath('data.payments.0.amount', '625.25')
        ->assertJsonPath('data.payments.0.method', 'VISA')
        ->assertJsonPath('data.payments.0.reference', 'BKG-000001-TEST')
        ->assertJsonPath('data.payments.0.receipt.receipt_number', 'RCP-000001');

    getJson("/api/tenants/hotel-alpha/public/invoices/by-number/{$invoiceNumber}/sslcommerz/success?".http_build_query([
        'tran_id' => 'BKG-000001-TEST',
        'amount' => '625.25',
        'status' => 'VALID',
        'card_type' => 'VISA',
    ]), [
        'Accept' => 'application/json',
    ])
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.payments')
        ->assertJsonPath('data.status', 'paid')
        ->assertJsonPath('data.amount_paid', '625.25');

    get("/api/tenants/hotel-alpha/public/invoices/by-number/{$invoiceNumber}/sslcommerz/success?".http_build_query([
        'tran_id' => 'BKG-000001-TEST',
        'amount' => '625.25',
        'status' => 'VALID',
        'card_type' => 'VISA',
    ]))
        ->assertRedirect("http://localhost:3000/payment/{$invoiceNumber}/success");

    get("/api/tenants/hotel-alpha/public/invoices/by-number/{$invoiceNumber}/sslcommerz/fail")
        ->assertRedirect("http://localhost:3000/payment/{$invoiceNumber}/fail");

    get("/api/tenants/hotel-alpha/public/invoices/by-number/{$invoiceNumber}/sslcommerz/cancel")
        ->assertRedirect("http://localhost:3000/payment/{$invoiceNumber}/cancel");

    getJson("/api/tenants/hotel-alpha/public/invoices/{$invoiceNumber}")
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'paid')
        ->assertJsonPath('data.payments.0.amount', '625.25')
        ->assertJsonPath('data.payments.0.method', 'VISA')
        ->assertJsonPath('data.payments.0.reference', 'BKG-000001-TEST')
        ->assertJsonPath('data.payments.0.receipt.receipt_number', 'RCP-000001');

    getJson("/api/tenants/hotel-alpha/public/invoices/by-number/{$invoiceNumber}")
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'paid');
});

test('tenant user can get a room for editing', function () {
    $tenant = Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'email' => 'admin@example.com',
    ]);
    $user->assignRole(Role::findOrCreate('admin', 'sanctum'));

    tenancy()->initialize($tenant);

    try {
        $room = Room::factory()->create([
            'room_name' => 'Garden Room',
            'room_type' => 'ac',
            'capacity' => 3,
            'rate' => '5500.00',
            'available_rooms' => 2,
            'status' => 'available',
        ]);
    } finally {
        tenancy()->end();
    }

    Sanctum::actingAs($user);

    getJson("/api/tenants/hotel-alpha/rooms/{$room->id}")
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $room->id)
        ->assertJsonPath('data.tenant_id', 'hotel-alpha')
        ->assertJsonPath('data.room_name', 'Garden Room')
        ->assertJsonPath('data.room_type', 'ac')
        ->assertJsonPath('data.capacity', 3)
        ->assertJsonPath('data.rate', '5500.00')
        ->assertJsonPath('data.available_rooms', 2);
});

test('tenant user can update a room', function () {
    $tenant = Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'email' => 'admin@example.com',
    ]);
    $user->assignRole(Role::findOrCreate('admin', 'sanctum'));

    tenancy()->initialize($tenant);

    try {
        $room = Room::factory()->create([
            'room_name' => 'Old Room',
            'room_type' => 'non_ac',
            'capacity' => 2,
            'rate' => '2500.00',
            'available_rooms' => 1,
            'status' => 'available',
            'amenities' => ['wifi'],
            'description' => 'Old description.',
        ]);
    } finally {
        tenancy()->end();
    }

    Sanctum::actingAs($user);

    patchJson("/api/tenants/hotel-alpha/rooms/{$room->id}", [
        'room_name' => 'Updated Deluxe Room',
        'room_type' => 'ac',
        'capacity' => 5,
        'rate' => '7200.75',
        'available_rooms' => 4,
        'status' => 'maintenance',
        'amenities' => ['wifi', 'breakfast'],
        'description' => 'Updated room description.',
    ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.tenant_id', 'hotel-alpha')
        ->assertJsonPath('data.room_name', 'Updated Deluxe Room')
        ->assertJsonPath('data.room_type', 'ac')
        ->assertJsonPath('data.capacity', 5)
        ->assertJsonPath('data.rate', '7200.75')
        ->assertJsonPath('data.available_rooms', 4)
        ->assertJsonPath('data.status', 'maintenance')
        ->assertJsonPath('data.amenities.1', 'breakfast')
        ->assertJsonPath('data.description', 'Updated room description.');

    tenancy()->initialize($tenant);

    try {
        expect(Room::query()
            ->whereKey($room->id)
            ->where('tenant_id', 'hotel-alpha')
            ->where('room_name', 'Updated Deluxe Room')
            ->where('room_type', 'ac')
            ->where('capacity', 5)
            ->where('rate', '7200.75')
            ->where('available_rooms', 4)
            ->where('status', 'maintenance')
            ->exists())->toBeTrue();
    } finally {
        tenancy()->end();
    }
});

test('tenant user can delete a room', function () {
    Storage::fake('public');

    $tenant = Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'email' => 'admin@example.com',
    ]);
    $user->assignRole(Role::findOrCreate('admin', 'sanctum'));

    tenancy()->initialize($tenant);

    try {
        Storage::disk('public')->put('rooms/delete-me.jpg', 'image');

        $room = Room::factory()->create([
            'room_name' => 'Delete Room',
        ]);

        FileRecord::query()->create([
            'fileable_id' => $room->id,
            'fileable_type' => Room::class,
            'category' => Room::IMAGE_CATEGORY,
            'tenant_id' => 'hotel-alpha',
            'disk' => 'public',
            'path' => 'rooms/delete-me.jpg',
        ]);
    } finally {
        tenancy()->end();
    }

    Sanctum::actingAs($user);

    deleteJson("/api/tenants/hotel-alpha/rooms/{$room->id}")
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    tenancy()->initialize($tenant);

    try {
        expect(Room::query()->whereKey($room->id)->exists())->toBeFalse()
            ->and(Room::withTrashed()->whereKey($room->id)->exists())->toBeTrue()
            ->and(Storage::disk('public')->exists('rooms/delete-me.jpg'))->toBeTrue()
            ->and(FileRecord::query()->where('path', 'rooms/delete-me.jpg')->exists())->toBeFalse()
            ->and(FileRecord::withTrashed()->where('path', 'rooms/delete-me.jpg')->whereNotNull('deleted_at')->exists())->toBeTrue();
    } finally {
        tenancy()->end();
    }
});

test('tenant bookings are stored in the tenant database', function () {
    $tenant = Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'email' => 'admin@example.com',
    ]);
    $user->assignRole(Role::findOrCreate('admin', 'sanctum'));

    tenancy()->initialize($tenant);

    try {
        $room = Room::factory()->create([
            'tenant_id' => 'hotel-alpha',
            'room_name' => 'Business Twin Room',
            'rate' => '625.25',
        ]);
    } finally {
        tenancy()->end();
    }

    Sanctum::actingAs($user);

    $bookingResponse = postJson('/api/tenants/hotel-alpha/bookings', [
        'guest_name' => 'Guest One',
        'guest_email' => 'guest@example.com',
        'guest_phone' => '01700000000',
        'guest_address' => 'House 12, Road 3',
        'room_id' => $room->id,
        'assigned_room_number' => '301',
        'nid_number' => '1234567890',
        'nid_image_url' => '',
        'room_quantity' => 1,
        'discount' => 10,
        'promo_code' => 'SUMMER10',
        'check_in' => '2026-06-01',
        'check_out' => '2026-06-03',
    ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.tenant_id', 'hotel-alpha')
        ->assertJsonPath('data.room_id', $room->id)
        ->assertJsonPath('data.guest_name', 'Guest One')
        ->assertJsonPath('data.guest_email', 'guest@example.com')
        ->assertJsonPath('data.guest_phone', '01700000000')
        ->assertJsonPath('data.room', 'Business Twin Room')
        ->assertJsonPath('data.assigned_room_number', '301')
        ->assertJsonPath('data.discount', '10.00');

    $bookingId = $bookingResponse->json('data.id');

    expect($bookingResponse->json('data.booking_number'))->toBe(Booking::numberForId($bookingId))
        ->and($bookingResponse->json('data.status'))->toBe(BookingStatus::Pending->value);

    expect(DB::connection()->getSchemaBuilder()->hasTable('bookings'))->toBeTrue()
        ->and(DB::connection()->getSchemaBuilder()->getColumnListing('bookings'))->toContain(
            'tenant_id',
            'booking_number',
            'room_id',
            'guest_name',
            'guest_phone',
            'guest_email',
            'guest_address',
            'room',
            'assigned_room_number',
            'nid_image_url',
            'room_quantity',
            'discount',
            'promo_code',
            'check_in',
            'check_out',
            'status',
        )
        ->and(DB::table('bookings')->count())->toBe(0);

    $guest = User::query()
        ->where('email', 'guest@example.com')
        ->firstOrFail();

    expect($guest->tenant_id)->toBe('hotel-alpha')
        ->and($guest->name)->toBe('Guest One')
        ->and($guest->phone_number)->toBe('01700000000');

    tenancy()->initialize($tenant);

    try {
        expect(Booking::query()
            ->where('tenant_id', 'hotel-alpha')
            ->where('booking_number', Booking::numberForId($bookingId))
            ->where('user_id', $guest->id)
            ->where('room_id', $room->id)
            ->where('guest_email', 'guest@example.com')
            ->where('guest_phone', '01700000000')
            ->where('guest_address', 'House 12, Road 3')
            ->where('room', 'Business Twin Room')
            ->where('assigned_room_number', '301')
            ->where('nid_number', '1234567890')
            ->where('room_quantity', 1)
            ->where('discount', 10)
            ->where('check_in', '2026-06-01')
            ->where('check_out', '2026-06-03')
            ->where('status', BookingStatus::Pending->value)
            ->where('deleted_at', null)
            ->exists())->toBeTrue();
    } finally {
        tenancy()->end();
    }

    getJson('/api/tenants/hotel-alpha/bookings')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.data.0.tenant_id', 'hotel-alpha')
        ->assertJsonPath('data.data.0.booking_number', Booking::numberForId($bookingId))
        ->assertJsonPath('data.data.0.user_id', $guest->id)
        ->assertJsonPath('data.data.0.room_id', $room->id)
        ->assertJsonPath('data.data.0.guest_name', 'Guest One')
        ->assertJsonPath('data.data.0.room', 'Business Twin Room')
        ->assertJsonPath('data.data.0.assigned_room_number', '301')
        ->assertJsonPath('data.data.0.discount', '10.00')
        ->assertJsonPath('data.data.0.status', BookingStatus::Pending->value)
        ->assertJsonPath('data.current_page', 1)
        ->assertJsonPath('data.per_page', 15)
        ->assertJsonPath('data.total', 1);

    getJson("/api/tenants/hotel-alpha/bookings/{$bookingId}")
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $bookingId)
        ->assertJsonPath('data.booking_number', Booking::numberForId($bookingId))
        ->assertJsonPath('data.tenant_id', 'hotel-alpha')
        ->assertJsonPath('data.user_id', $guest->id)
        ->assertJsonPath('data.room_id', $room->id)
        ->assertJsonPath('data.guest_name', 'Guest One')
        ->assertJsonPath('data.room', 'Business Twin Room')
        ->assertJsonPath('data.assigned_room_number', '301')
        ->assertJsonPath('data.discount', '10.00')
        ->assertJsonPath('data.status', BookingStatus::Pending->value);

    patchJson("/api/tenants/hotel-alpha/bookings/{$bookingId}/status", [
        'status' => BookingStatus::Confirmed->value,
    ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $bookingId)
        ->assertJsonPath('data.status', BookingStatus::Confirmed->value);
});

test('tenant user can filter and paginate bookings', function () {
    $tenant = Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'email' => 'admin@example.com',
    ]);
    $user->assignRole(Role::findOrCreate('admin', 'sanctum'));

    tenancy()->initialize($tenant);

    try {
        $room = Room::factory()->create([
            'tenant_id' => 'hotel-alpha',
            'room_name' => 'Business Twin Room',
            'rate' => '625.25',
        ]);
    } finally {
        tenancy()->end();
    }

    Sanctum::actingAs($user);

    $firstBookingResponse = postJson('/api/tenants/hotel-alpha/bookings', [
        'guest_name' => 'Guest One',
        'guest_email' => 'guest-one@example.com',
        'guest_phone' => '01700000000',
        'guest_address' => 'House 12, Road 3',
        'room_id' => $room->id,
        'assigned_room_number' => '301',
        'nid_number' => '1234567890',
        'nid_image_url' => '',
        'room_quantity' => 1,
        'discount' => 10,
        'promo_code' => 'SUMMER10',
        'check_in' => '2026-06-01',
        'check_out' => '2026-06-03',
    ])->assertCreated();

    $secondBookingResponse = postJson('/api/tenants/hotel-alpha/bookings', [
        'guest_name' => 'Guest Two',
        'guest_email' => 'guest-two@example.com',
        'guest_phone' => '01800000000',
        'guest_address' => 'House 20, Road 4',
        'room_id' => $room->id,
        'assigned_room_number' => '302',
        'nid_number' => '0987654321',
        'nid_image_url' => '',
        'room_quantity' => 1,
        'discount' => 0,
        'promo_code' => null,
        'check_in' => '2026-06-05',
        'check_out' => '2026-06-07',
        'status' => BookingStatus::Confirmed->value,
    ])->assertCreated();

    $firstBookingId = $firstBookingResponse->json('data.id');
    $secondBookingId = $secondBookingResponse->json('data.id');
    $firstBookingNumber = $firstBookingResponse->json('data.booking_number');

    getJson("/api/tenants/hotel-alpha/bookings?filter[booking_number]={$firstBookingNumber}")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.id', $firstBookingId)
        ->assertJsonPath('data.data.0.booking_number', $firstBookingNumber)
        ->assertJsonPath('data.total', 1);

    getJson('/api/tenants/hotel-alpha/bookings?filter[status]=confirmed')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.id', $secondBookingId)
        ->assertJsonPath('data.data.0.status', BookingStatus::Confirmed->value);

    getJson('/api/tenants/hotel-alpha/bookings?filter[customer_name]=Guest Two')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.id', $secondBookingId)
        ->assertJsonPath('data.data.0.guest_name', 'Guest Two');

    getJson('/api/tenants/hotel-alpha/bookings?filter[customer_email]=guest-one@example.com')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.id', $firstBookingId)
        ->assertJsonPath('data.data.0.guest_email', 'guest-one@example.com');

    getJson('/api/tenants/hotel-alpha/bookings?filter[customer_phone_number]=01800000000')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.id', $secondBookingId)
        ->assertJsonPath('data.data.0.guest_phone', '01800000000');

    getJson('/api/tenants/hotel-alpha/bookings?per_page=1')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.id', $secondBookingId)
        ->assertJsonPath('data.current_page', 1)
        ->assertJsonPath('data.per_page', 1)
        ->assertJsonPath('data.total', 2);
});

test('tenant user can add a booking down payment and download invoice and receipt', function () {
    $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]));

    $user = User::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'email' => 'admin@example.com',
    ]);
    $user->assignRole(Role::findOrCreate('admin', 'sanctum'));

    tenancy()->initialize($tenant);

    try {
        $room = Room::factory()->create([
            'tenant_id' => 'hotel-alpha',
            'room_name' => 'Business Twin Room',
            'rate' => '625.25',
        ]);
    } finally {
        tenancy()->end();
    }

    Sanctum::actingAs($user);

    $bookingResponse = postJson('/api/tenants/hotel-alpha/bookings', [
        'guest_name' => 'Guest One',
        'guest_email' => 'guest@example.com',
        'guest_phone' => '01700000000',
        'room_id' => $room->id,
        'assigned_room_number' => '301',
        'room_quantity' => 1,
        'discount' => 0,
        'check_in' => '2026-06-01',
        'check_out' => '2026-06-03',
    ])->assertCreated();

    $bookingId = $bookingResponse->json('data.id');

    Carbon::setTestNow('2026-06-10 14:35:20');

    try {
        $downPaymentResponse = postJson("/api/tenants/hotel-alpha/bookings/{$bookingId}/down-payment", [
            'amount' => '500.00',
            'method' => 'cash',
            'reference' => 'REF-001',
            'paid_at' => '2026-06-10',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.invoice.status', 'partial')
            ->assertJsonPath('data.invoice.amount_paid', '500.00')
            ->assertJsonPath('data.invoice.amount_due', '750.50')
            ->assertJsonPath('data.invoice.payments.0.amount', '500.00')
            ->assertJsonPath('data.invoice.payments.0.method', 'cash')
            ->assertJsonPath('data.invoice.payments.0.paid_at', '2026-06-10 14:35:20')
            ->assertJsonPath('data.invoice.payments.0.receipt.receipt_number', 'RCP-000001');
    } finally {
        Carbon::setTestNow();
    }

    expect($downPaymentResponse->json('data.invoice.download_url'))
        ->toBe($downPaymentResponse->json('data.invoice.payments.0.receipt.download_url'));

    Pdf::fake();

    get($downPaymentResponse->json('data.invoice.download_url'))
        ->assertSuccessful();

    Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf): bool {
        return $pdf->downloadName === 'RCP-000001.pdf'
            && $pdf->isDownload()
            && $pdf->contains(['Receipt Number:', 'RCP-000001', 'Method:', 'cash']);
    });

    get($downPaymentResponse->json('data.invoice.payments.0.receipt.download_url'))
        ->assertSuccessful();

    Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf): bool {
        return $pdf->downloadName === 'RCP-000001.pdf'
            && $pdf->isDownload()
            && $pdf->contains(['Receipt Number:', 'RCP-000001', 'Method:', 'cash']);
    });
});

test('tenant management requires super admin role', function () {
    Sanctum::actingAs(User::factory()->create());

    postJson('/api/tenants', [
        'name' => 'Hotel Alpha',
    ])->assertForbidden();
});
