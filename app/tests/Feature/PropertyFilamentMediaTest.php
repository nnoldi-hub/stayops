<?php

namespace Tests\Feature;

use App\Filament\Resources\Properties\Pages\CreateProperty;
use App\Filament\Resources\Properties\Pages\EditProperty;
use App\Models\Host;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PropertyFilamentMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function actAsAdmin(): User
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        $admin->assignRole('SuperAdmin');
        $this->actingAs($admin);

        return $admin;
    }

    public function test_admin_can_render_property_create_page(): void
    {
        $this->actAsAdmin();

        Livewire::test(CreateProperty::class)
            ->assertOk()
            ->assertFormFieldExists('photos')
            ->assertFormFieldExists('floor_plans');
    }

    public function test_admin_can_upload_photos_via_filament_form(): void
    {
        Storage::fake('public');
        $this->actAsAdmin();

        $property = Property::factory()->for(Host::factory())->create();
        $file = UploadedFile::fake()->image('cover.jpg', 1200, 800);

        Livewire::test(EditProperty::class, ['record' => $property->getRouteKey()])
            ->assertOk()
            ->set('data.photos', [$file])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertCount(1, $property->fresh()->getMedia('photos'));
        $this->assertSame('photos', $property->fresh()->getFirstMedia('photos')->collection_name);
    }

    public function test_admin_can_upload_floor_plan(): void
    {
        Storage::fake('public');
        $this->actAsAdmin();

        $property = Property::factory()->for(Host::factory())->create();
        $file = UploadedFile::fake()->image('plan.png');

        Livewire::test(EditProperty::class, ['record' => $property->getRouteKey()])
            ->set('data.floor_plans', [$file])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertCount(1, $property->fresh()->getMedia('floor_plans'));
    }

    public function test_admin_can_list_properties_in_filament(): void
    {
        $this->actAsAdmin();

        $property = Property::factory()->for(Host::factory())->create([
            'title' => 'Villa Test Filament',
        ]);

        $this->get('/admin/properties')
            ->assertOk()
            ->assertSee('Villa Test Filament');
    }
}
