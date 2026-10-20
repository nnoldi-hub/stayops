<?php

namespace Tests\Feature;

use App\Filament\Provider\Pages\Verification as ProviderVerificationPage;
use App\Models\Host;
use App\Models\Property;
use App\Models\Provider;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class KycVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeHost(bool $approved = true): Host
    {
        /** @var User $user */
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        $user->assignRole('Host');

        return Host::factory()->create([
            'user_id' => $user->id,
            'kyc_status' => $approved ? 'approved' : 'pending',
        ]);
    }

    private function makeProvider(): Provider
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('Provider');

        return Provider::factory()->create(['user_id' => $user->id, 'kyc_status' => 'pending']);
    }

    public function test_host_can_submit_kyc_documents(): void
    {
        Storage::fake('public');
        $host = $this->makeHost(approved: false);
        $this->actingAs($host->user);

        Volt::test('host.verification')
            ->set('documents', [UploadedFile::fake()->create('identitate.pdf', 100, 'application/pdf')])
            ->call('submit')
            ->assertHasNoErrors();

        $host->refresh();

        $this->assertSame('submitted', $host->kyc_status);
        $this->assertCount(1, $host->kyc_documents);
        Storage::disk('public')->assertExists($host->kyc_documents[0]);
    }

    public function test_host_cannot_submit_kyc_without_documents(): void
    {
        Storage::fake('public');
        $host = $this->makeHost(approved: false);
        $this->actingAs($host->user);

        Volt::test('host.verification')
            ->call('submit')
            ->assertHasErrors('documents');

        $this->assertSame('pending', $host->fresh()->kyc_status);
    }

    public function test_host_dashboard_links_to_verification_page(): void
    {
        $host = $this->makeHost(approved: false);
        $this->actingAs($host->user);

        $this->get('/host')
            ->assertOk()
            ->assertSee(route('host.verification'))
            ->assertSee('kyc-verification-link', false);
    }

    public function test_non_host_cannot_access_host_verification_page(): void
    {
        $this->get('/host/verification')->assertRedirect(route('login'));

        /** @var User $guest */
        $guest = User::factory()->create();
        $guest->assignRole('Guest');

        $this->actingAs($guest)->get('/host/verification')->assertForbidden();
    }

    public function test_provider_verification_page_renders(): void
    {
        Filament::setCurrentPanel('provider');
        $provider = $this->makeProvider();
        $this->actingAs($provider->user);

        $this->get('/provider/verification')
            ->assertOk()
            ->assertSee('Verificare KYC');
    }

    public function test_provider_can_submit_kyc_documents(): void
    {
        Storage::fake('public');
        Filament::setCurrentPanel('provider');
        $provider = $this->makeProvider();
        $this->actingAs($provider->user);

        Livewire::test(ProviderVerificationPage::class)
            ->callAction('submitKyc', data: [
                'documents' => [UploadedFile::fake()->create('cui.pdf', 100, 'application/pdf')],
            ])
            ->assertHasNoActionErrors();

        $provider->refresh();

        $this->assertSame('submitted', $provider->kyc_status);
        $this->assertNotEmpty($provider->kyc_documents);
    }

    public function test_property_of_pending_host_is_hidden_from_public_listing(): void
    {
        $host = $this->makeHost(approved: false);
        Property::factory()->for($host)->create(['title' => 'Apartament Secret', 'status' => 'published']);

        $this->get('/properties')
            ->assertOk()
            ->assertDontSee('Apartament Secret');
    }

    public function test_property_of_pending_host_returns_404_on_show_and_book(): void
    {
        $host = $this->makeHost(approved: false);
        $property = Property::factory()->for($host)->create(['status' => 'published']);

        $this->get(route('properties.show', $property->slug))->assertNotFound();

        /** @var User $guest */
        $guest = User::factory()->create();
        $guest->assignRole('Guest');

        $this->actingAs($guest)
            ->get(route('properties.book', $property->slug))
            ->assertNotFound();
    }

    public function test_property_of_approved_host_is_publicly_visible(): void
    {
        $host = $this->makeHost();
        Property::factory()->for($host)->create(['title' => 'Apartament Public', 'status' => 'published']);

        $this->get('/properties')
            ->assertOk()
            ->assertSee('Apartament Public');
    }
}
