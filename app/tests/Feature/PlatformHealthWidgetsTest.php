<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\MarketplaceReport;
use App\Filament\Pages\OperationalReport;
use App\Filament\Widgets\KpiOverviewWidget;
use App\Filament\Widgets\MarketplaceConversionWidget;
use App\Filament\Widgets\NeedsAttentionWidget;
use App\Filament\Widgets\PlatformHealthWidget;
use App\Filament\Widgets\PropertiesByCityWidget;
use App\Filament\Widgets\TopHostsWidget;
use App\Filament\Widgets\TopProvidersWidget;
use App\Models\Booking;
use App\Models\Dispute;
use App\Models\Task;
use App\Models\User;
use App\Services\PlatformHealthService;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformHealthWidgetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DemoDataSeeder::class);
    }

    private function actAsSuperAdmin(): User
    {
        /** @var User $admin */
        $admin = User::query()->where('email', 'admin@stayops.local')->firstOrFail();
        $this->actingAs($admin);

        return $admin;
    }

    public function test_health_service_returns_full_score_when_platform_is_clean(): void
    {
        Dispute::query()->delete();
        Task::query()->delete();
        Booking::query()->delete();

        $service = \Mockery::mock(PlatformHealthService::class)->makePartial();
        $service->shouldAllowMockingProtectedMethods()
            ->shouldReceive('countApplicationErrors')
            ->andReturn(0);

        $report = $service->report();

        $this->assertSame(100, $report['score']);
        $this->assertSame('healthy', $report['status']);
        $this->assertSame('success', $report['color']);
        $this->assertCount(4, $report['factors']);
    }

    public function test_health_score_drops_with_open_disputes(): void
    {
        Dispute::query()->delete();
        Task::query()->delete();
        Booking::query()->delete();

        Dispute::factory()->count(3)->create(['status' => 'open']);

        $service = \Mockery::mock(PlatformHealthService::class)->makePartial();
        $service->shouldAllowMockingProtectedMethods()
            ->shouldReceive('countApplicationErrors')
            ->andReturn(0);

        $report = $service->report();

        $this->assertSame(85, $report['score']);
        $this->assertSame('warning', $report['status']);
    }

    public function test_health_widget_renders_status_section(): void
    {
        $this->actAsSuperAdmin();

        Livewire::test(PlatformHealthWidget::class)
            ->assertSee('Status Platformă')
            ->assertSee('Dispute deschise')
            ->assertSee('Rezervări afectate');
    }

    public function test_kpi_widget_renders_new_metrics(): void
    {
        $this->actAsSuperAdmin();

        Livewire::test(KpiOverviewWidget::class)
            ->assertSee('Abonamente active')
            ->assertSee('ARPU')
            ->assertSee('Companii mentenanță')
            ->assertSee('Dispute deschise');
    }

    public function test_needs_attention_widget_renders_new_items(): void
    {
        $this->actAsSuperAdmin();

        Livewire::test(NeedsAttentionWidget::class)
            ->assertSee('Dispute noi')
            ->assertSee('Intervenții urgente')
            ->assertSee('Abonamente care expiră')
            ->assertSee('Proprietăți blocate');
    }

    public function test_properties_by_city_widget_renders(): void
    {
        $this->actAsSuperAdmin();

        Livewire::test(PropertiesByCityWidget::class)
            ->assertSee('Proprietăți după oraș');
    }

    public function test_top_hosts_widget_renders(): void
    {
        $this->actAsSuperAdmin();

        Livewire::test(TopHostsWidget::class)
            ->assertSee('Top Gazde');
    }

    public function test_top_providers_widget_renders(): void
    {
        $this->actAsSuperAdmin();

        Livewire::test(TopProvidersWidget::class)
            ->assertSee('Top Firme Mentenanță');
    }

    public function test_marketplace_conversion_widget_renders(): void
    {
        $this->actAsSuperAdmin();

        Livewire::test(MarketplaceConversionWidget::class)
            ->assertSee('Conversie Marketplace')
            ->assertSee('Gazde înscrise')
            ->assertSee('Proprietăți publicate');
    }

    public function test_new_widgets_are_registered_on_dashboard(): void
    {
        $this->actAsSuperAdmin();

        $widgets = (new Dashboard)->getWidgets();

        $this->assertContains(KpiOverviewWidget::class, $widgets);
        $this->assertContains(PlatformHealthWidget::class, $widgets);
        $this->assertContains(NeedsAttentionWidget::class, $widgets);
    }

    public function test_new_widgets_are_registered_on_report_pages(): void
    {
        $this->actAsSuperAdmin();

        $operational = (new OperationalReport)->getWidgets();
        $this->assertContains(PropertiesByCityWidget::class, $operational);
        $this->assertContains(TopProvidersWidget::class, $operational);

        $marketplace = (new MarketplaceReport)->getWidgets();
        $this->assertContains(MarketplaceConversionWidget::class, $marketplace);
        $this->assertContains(TopHostsWidget::class, $marketplace);
    }
}
