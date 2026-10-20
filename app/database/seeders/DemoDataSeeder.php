<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Dispute;
use App\Models\Host;
use App\Models\Plan;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Review;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;

/**
 * Date demo pentru testarea vizuală/funcțională locală.
 *
 * Idempotent: rulat de mai multe ori nu duplică conturi, proprietăți,
 * rezervări sau fotografii.
 *
 *   php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    private const PASSWORD = 'password';

    public function run(): void
    {
        $this->call([RolesAndPermissionsSeeder::class, AmenitiesSeeder::class]);

        [$admin, $hostUser, $hostTwoUser, $providerUser, $guest] = $this->seedAccounts();
        [$workerOne, $workerTwo] = $this->seedTeamMembers();

        $hostA = $this->host($hostUser, [
            'company_name' => 'Piatra Craiului Cazări SRL',
            'cui' => 'RO18452367',
            'stripe_account_id' => 'acct_demo_host_a',
            'stripe_account_status' => 'active',
            'kyc_status' => 'approved',
        ]);
        $hostB = $this->host($hostTwoUser, [
            'company_name' => 'Delta Danube Retreat SRL',
            'cui' => 'RO27114589',
            'stripe_account_id' => null,
            'stripe_account_status' => 'none',
            'kyc_status' => 'approved',
        ]);
        $hostC = $this->host($this->user('Neconfirmată Gazdă', 'host-pending@stayops.local', ['Host']), [
            'company_name' => 'Gazdă În Verificare SRL',
            'cui' => 'RO00000001',
            'kyc_status' => 'pending',
            'onboarded_at' => null,
        ]);

        $this->seedPlans();

        $provider = $this->provider($providerUser);
        $team = Team::firstOrCreate(
            ['provider_id' => $provider->id, 'name' => 'Echipa Centrală'],
            ['description' => 'Echipa principală de curățenie și mentenanță.']
        );
        TeamMember::firstOrCreate(['team_id' => $team->id, 'user_id' => $workerOne->id], ['role' => 'lead']);
        TeamMember::firstOrCreate(['team_id' => $team->id, 'user_id' => $workerTwo->id], ['role' => 'member']);

        $properties = $this->seedProperties([$hostA, $hostB, $hostC]);
        $this->attachAmenities($properties);

        [$checkedOut, $confirmed] = $this->seedBookings($properties, $guest);
        $this->seedTasks($properties, $provider, $workerOne, $checkedOut);
        $this->seedReviews($properties, $guest, $checkedOut);
        $this->seedCommandCenterData([$hostA, $hostB, $hostC], $properties, $provider, $workerOne, $admin, $guest);

        $this->command?->info('');
        $this->command?->info('Conturi demo (parolă: '.self::PASSWORD.'):');
        $this->command?->info('  SuperAdmin  admin@stayops.local    → /admin');
        $this->command?->info('  Host        host@stayops.local     → /host   (abonament activ, Stripe conectat)');
        $this->command?->info('  Host        host2@stayops.local    → /host   (fără Stripe Connect)');
        $this->command?->info('  Provider    provider@stayops.local → /provider');
        $this->command?->info('  Oaspete     guest@stayops.local    → /dashboard');
        $this->command?->info('  Gazdă KYC pending: host-pending@stayops.local (blocație de gating)');
        $this->command?->info('Conturi echipă (fără rol): worker1@stayops.local, worker2@stayops.local');
        $this->command?->info('Proprietăți publicate: '.Property::query()->publiclyVisible()->count());
        $this->command?->info('Rezervări: '.Booking::query()->count().' · Taskuri: '.Task::query()->count());
    }

    /**
     * @return array{0: User, 1: User, 2: User, 3: User, 4: User}
     */
    private function seedAccounts(): array
    {
        $admin = $this->user('Super Admin', 'admin@stayops.local', ['SuperAdmin'], twoFactor: true);
        $host = $this->user('Andrei Popescu', 'host@stayops.local', ['Host'], twoFactor: true);
        $hostTwo = $this->user('Maria Ionescu', 'host2@stayops.local', ['Host'], twoFactor: true);
        $provider = $this->user('Firmă CleanPro SRL', 'provider@stayops.local', ['Provider']);
        $guest = $this->user('Ion Popescu', 'guest@stayops.local');

        return [$admin, $host, $hostTwo, $provider, $guest];
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function seedTeamMembers(): array
    {
        return [
            $this->user('Echipier Unu', 'worker1@stayops.local'),
            $this->user('Echipier Doi', 'worker2@stayops.local'),
        ];
    }

    /**
     * @param  array<int, string>  $roles
     */
    private function user(string $name, string $email, array $roles = [], bool $twoFactor = false): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(self::PASSWORD),
                'locale' => 'ro',
                'email_verified_at' => now(),
            ]
        );

        if ($roles !== []) {
            $user->assignRole($roles);
        }

        if ($twoFactor) {
            // 2FA confirmat din start: middleware-ul `require_2fa` trece fără setup,
            // iar Breeze nu cere cod la login (Fortify routes sunt ignorate).
            $user->forceFill([
                'two_factor_secret' => encrypt('stayops-demo-two-factor-secret'),
                'two_factor_confirmed_at' => now(),
            ])->save();
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function host(User $user, array $attributes = []): Host
    {
        $host = Host::query()->firstWhere('user_id', $user->id);

        if ($host === null) {
            return Host::factory()->create(['user_id' => $user->id] + $attributes);
        }

        $host->update($attributes);

        return $host;
    }

    private function provider(User $user): Provider
    {
        $provider = Provider::query()->firstWhere('user_id', $user->id);

        $attributes = [
            'company_name' => 'CleanPro Mentenanță SRL',
            'cui' => 'RO31884402',
            'service_types' => ['cleaning', 'repair', 'inspection'],
            'coverage_cities' => ['Brașov', 'București', 'Cluj-Napoca', 'Constanța', 'Sibiu', 'Timișoara'],
            'kyc_status' => 'approved',
            'kyc_documents' => [],
            'rating_avg' => 4.8,
            'jobs_completed' => 126,
            'is_active' => true,
        ];

        if ($provider === null) {
            return Provider::factory()->create(['user_id' => $user->id] + $attributes);
        }

        $provider->update($attributes);

        return $provider;
    }

    private function seedPlans(): void
    {
        Plan::updateOrCreate(
            ['slug' => 'basic'],
            [
                'name_ro' => 'Basic',
                'name_en' => 'Basic',
                'description_ro' => 'Pentru gazde la început de drum, cu 1–3 proprietăți.',
                'description_en' => 'For hosts getting started, 1–3 properties.',
                'price' => 49,
                'currency' => 'RON',
                'interval' => 'month',
                'features' => ['1–3 proprietăți', 'Rezervări nelimitate', 'Suport pe email'],
                'max_properties' => 3,
                'platform_fee_pct' => 12,
                'stripe_price_id' => 'price_host_basic',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'standard'],
            [
                'name_ro' => 'Standard',
                'name_en' => 'Standard',
                'description_ro' => 'Planul standard pentru gazde.',
                'description_en' => 'Standard plan for hosts.',
                'price' => 99,
                'currency' => 'RON',
                'interval' => 'month',
                'features' => ['Proprietăți nelimitate', 'iCal sync', 'Suport prioritar'],
                'max_properties' => null,
                'platform_fee_pct' => 10,
                'stripe_price_id' => 'price_host_standard',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'premium'],
            [
                'name_ro' => 'Premium',
                'name_en' => 'Premium',
                'description_ro' => 'Comision de platformă redus și funcții avansate.',
                'description_en' => 'Lower platform fee and advanced features.',
                'price' => 199,
                'currency' => 'RON',
                'interval' => 'month',
                'features' => ['Comision redus', 'Raporte avansate', 'Suport dedicat'],
                'max_properties' => null,
                'platform_fee_pct' => 6,
                'stripe_price_id' => 'price_host_premium',
                'is_active' => true,
                'sort_order' => 2,
            ]
        );
    }

    /**
     * @param  array<int, Host>  $hosts
     * @return array<string, Property>
     */
    private function seedProperties(array $hosts): array
    {
        [$hostA, $hostB, $hostC] = $hosts;

        $definitions = [
            ['host' => $hostA, 'title' => 'Apartament cochet lângă Piața Sfatului', 'city' => 'Brașov', 'county' => 'Brașov', 'lat' => 45.6427, 'lng' => 25.5887, 'type' => 'apartment', 'price' => 320, 'guests' => 4, 'bedrooms' => 2, 'beds' => 2, 'bathrooms' => 1, 'rating' => 4.8, 'reviews' => 24],
            ['host' => $hostA, 'title' => 'Vilă cu piscină în Poiana Brașov', 'city' => 'Brașov', 'county' => 'Brașov', 'lat' => 45.5950, 'lng' => 25.5300, 'type' => 'villa', 'price' => 780, 'guests' => 8, 'bedrooms' => 4, 'beds' => 5, 'bathrooms' => 3, 'rating' => 4.9, 'reviews' => 41],
            ['host' => $hostA, 'title' => 'Studio modern în Centrul Vechi', 'city' => 'București', 'county' => 'București', 'lat' => 44.4268, 'lng' => 26.1025, 'type' => 'studio', 'price' => 240, 'guests' => 2, 'bedrooms' => 1, 'beds' => 1, 'bathrooms' => 1, 'rating' => 4.6, 'reviews' => 17],
            ['host' => $hostA, 'title' => 'Casă de oaspeți cu grădină', 'city' => 'Sibiu', 'county' => 'Sibiu', 'lat' => 45.7983, 'lng' => 24.1500, 'type' => 'house', 'price' => 450, 'guests' => 6, 'bedrooms' => 3, 'beds' => 4, 'bathrooms' => 2, 'rating' => 4.7, 'reviews' => 12],
            ['host' => $hostB, 'title' => 'Apartament cu vedere la mare', 'city' => 'Constanța', 'county' => 'Constanța', 'lat' => 44.1598, 'lng' => 28.6348, 'type' => 'apartment', 'price' => 380, 'guests' => 4, 'bedrooms' => 2, 'beds' => 3, 'bathrooms' => 1, 'rating' => 4.5, 'reviews' => 19],
            ['host' => $hostB, 'title' => 'Loft industrial lângă Parcul Central', 'city' => 'Cluj-Napoca', 'county' => 'Cluj', 'lat' => 46.7712, 'lng' => 23.6236, 'type' => 'apartment', 'price' => 340, 'guests' => 3, 'bedrooms' => 1, 'beds' => 2, 'bathrooms' => 1, 'rating' => 4.4, 'reviews' => 9],
            ['host' => $hostB, 'title' => 'Casa tradițională banățeană', 'city' => 'Timișoara', 'county' => 'Timiș', 'lat' => 45.7489, 'lng' => 21.2087, 'type' => 'house', 'price' => 300, 'guests' => 5, 'bedrooms' => 3, 'beds' => 3, 'bathrooms' => 2, 'rating' => 4.6, 'reviews' => 14],
            ['host' => $hostC, 'title' => 'Garsonieră în apropiere de Gara de Nord', 'city' => 'București', 'county' => 'București', 'lat' => 44.4489, 'lng' => 26.0680, 'type' => 'studio', 'price' => 180, 'guests' => 2, 'bedrooms' => 1, 'beds' => 1, 'bathrooms' => 1, 'rating' => 0, 'reviews' => 0],
        ];

        $properties = [];

        foreach ($definitions as $index => $definition) {
            $host = $definition['host'];
            $slug = Str::slug($definition['title']).'-demo'.$index;

            $property = Property::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'host_id' => $host->id,
                    'title' => $definition['title'],
                    'description' => $this->descriptionFor($definition),
                    'property_type' => $definition['type'],
                    'address' => 'Str. Republicii '.($index + 12),
                    'city' => $definition['city'],
                    'county' => $definition['county'],
                    'country' => 'RO',
                    'postal_code' => '0'.(1000 + $index),
                    'latitude' => $definition['lat'],
                    'longitude' => $definition['lng'],
                    'base_price' => $definition['price'],
                    'currency' => 'RON',
                    'cleaning_fee' => 90,
                    'max_guests' => $definition['guests'],
                    'bedrooms' => $definition['bedrooms'],
                    'beds' => $definition['beds'],
                    'bathrooms' => $definition['bathrooms'],
                    'check_in_time' => '15:00',
                    'check_out_time' => '11:00',
                    'min_nights' => 1,
                    'max_nights' => 30,
                    'house_rules' => 'Fumatul interzis în interior. Liniște între 22:00 și 08:00.',
                    'status' => $host->kyc_status === 'approved' ? 'published' : 'draft',
                    'rating_avg' => $definition['rating'],
                    'reviews_count' => $definition['reviews'],
                ]
            );

            $this->attachPhotos($property, $definition['title'], $definition['city']);

            $properties[$slug] = $property;
        }

        return $properties;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function descriptionFor(array $definition): string
    {
        return sprintf(
            'Cazare de %s în %s, pentru până la %d oaspeți (%d dormitoare). '
            .'Poziție centrală, acces rapid la transport public și restaurante. '
            .'Mobilat modern, Wi-Fi, aer condiționat și bucătărie complet utilată.',
            $definition['type'],
            $definition['city'],
            $definition['guests'],
            $definition['bedrooms']
        );
    }

    /**
     * @param  array<string, Property>  $properties
     */
    private function attachAmenities(array $properties): void
    {
        $bySlug = [
            'wifi' => ['wifi', 'tv', 'air_conditioning', 'heating'],
            'villa' => ['wifi', 'pool', 'parking', 'garden', 'bbq', 'air_conditioning'],
            'house' => ['wifi', 'parking', 'garden', 'kitchen', 'heating', 'pets_allowed'],
            'studio' => ['wifi', 'tv', 'air_conditioning', 'kitchen'],
        ];

        foreach ($properties as $property) {
            $slugs = $bySlug[$property->property_type] ?? $bySlug['wifi'];
            $ids = Amenity::query()->whereIn('slug', $slugs)->pluck('id')->all();

            if ($ids !== []) {
                $property->amenities()->syncWithoutDetaching($ids);
            }
        }
    }

    /**
     * Generează fotografii placeholder cu GD (fără dependențe externe).
     */
    private function attachPhotos(Property $property, string $title, string $city): void
    {
        if ($property->getMedia('photos')->isNotEmpty()) {
            return;
        }

        foreach (['Living și zonă de luat masa', 'Dormitor principal'] as $label) {
            $path = $this->renderImage($label, $city, $property->title);

            try {
                $property->addMedia($path)
                    ->preservingOriginal()
                    ->withCustomProperties(['caption' => $label])
                    ->toMediaCollection('photos');
            } finally {
                @unlink($path);
            }
        }
    }

    private function renderImage(string $label, string $city, string $title): string
    {
        $width = 1200;
        $height = 800;

        $palettes = [
            [[30, 64, 175], [147, 197, 253]],
            [[6, 78, 59], [167, 243, 208]],
            [[120, 53, 15], [254, 215, 170]],
            [[55, 48, 163], [196, 181, 253]],
            [[13, 148, 136], [153, 246, 228]],
        ];

        $palette = $palettes[crc32($label.$city) % count($palettes)];
        [$from, $to] = $palette;

        $image = imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $color = imagecolorallocate(
                $image,
                (int) round($from[0] + ($to[0] - $from[0]) * $ratio),
                (int) round($from[1] + ($to[1] - $from[1]) * $ratio),
                (int) round($from[2] + ($to[2] - $from[2]) * $ratio)
            );
            imageline($image, 0, $y, $width, $y, $color);
        }

        // Siluetă abstractă „clădire".
        $building = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 760, 340, 1080, 720, $building);
        imagefilledrectangle($image, 640, 470, 760, 720, $building);
        $window = imagecolorallocate($image, 17, 24, 39);
        foreach ([800, 880, 960] as $x) {
            imagefilledrectangle($image, $x, 390, $x + 40, 450, $window);
            imagefilledrectangle($image, $x, 520, $x + 40, 580, $window);
            imagefilledrectangle($image, $x, 650, $x + 40, 710, $window);
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        imagestring($image, 5, 48, 48, 'StayOps', $white);
        imagestring($image, 3, 48, 84, strtoupper($city), $white);
        imagestring($image, 5, 48, $height - 96, $this->ascii($label), $white);
        imagestring($image, 3, 48, $height - 64, $this->ascii($title), $white);

        $path = tempnam(sys_get_temp_dir(), 'stayops_demo').'.jpg';
        imagejpeg($image, $path, 86);
        imagedestroy($image);

        return $path;
    }

    /**
     * imagestring() nu suportă diacritice → transliterare simplă la ASCII.
     */
    private function ascii(string $value): string
    {
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');

        return function_exists('iconv')
            ? (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value)
            : preg_replace('/[^\x20-\x7E]/', '', $value);
    }

    /**
     * @param  array<string, Property>  $properties
     * @return array{0: Booking, 1: Booking}
     */
    private function seedBookings(array $properties, User $guest): array
    {
        $byIndex = array_values($properties);

        $checkedOut = $this->makeBooking(
            'BK-DEMO0001',
            $byIndex[0],
            $guest,
            now()->subDays(20)->toDateString(),
            now()->subDays(17)->toDateString(),
            'checked_out',
            'paid'
        );

        $confirmed = $this->makeBooking(
            'BK-DEMO0002',
            $byIndex[1],
            $guest,
            now()->addDays(10)->toDateString(),
            now()->addDays(14)->toDateString(),
            'confirmed',
            'paid'
        );

        $this->makeBooking(
            'BK-DEMO0003',
            $byIndex[4],
            null,
            now()->addDays(5)->toDateString(),
            now()->addDays(8)->toDateString(),
            'confirmed',
            'paid'
        );

        $this->makeBooking(
            'BK-DEMO0004',
            $byIndex[2],
            null,
            now()->addDays(3)->toDateString(),
            now()->addDays(6)->toDateString(),
            'pending',
            'pending'
        );

        return [$checkedOut, $confirmed];
    }

    private function makeBooking(
        string $reference,
        Property $property,
        ?User $guest,
        string $checkIn,
        string $checkOut,
        string $status,
        string $paymentStatus,
    ): Booking {
        $in = Carbon::parse($checkIn);
        $out = Carbon::parse($checkOut);
        $nights = max(1, (int) $in->diffInDays($out));

        $rate = (float) $property->base_price;
        $subtotal = round($rate * $nights, 2);
        $cleaning = (float) $property->cleaning_fee;
        $platform = round($subtotal * 0.1, 2);

        $guestName = $guest?->name;
        $guestEmail = $guest?->email;

        return Booking::query()->updateOrCreate(
            ['reference' => $reference],
            [
                'property_id' => $property->id,
                'guest_id' => $guest?->id,
                'guest_name' => $guestName ?? 'Vizitator Demo',
                'guest_email' => $guestEmail ?? 'vizitator@example.test',
                'guest_phone' => '+40 700 000 111',
                'check_in' => $in->toDateString(),
                'check_out' => $out->toDateString(),
                'nights' => $nights,
                'guests_count' => 2,
                'adults' => 2,
                'children' => 0,
                'nightly_rate' => $rate,
                'subtotal' => $subtotal,
                'cleaning_fee' => $cleaning,
                'platform_fee' => $platform,
                'host_payout' => round($subtotal - $platform, 2),
                'total_price' => round($subtotal + $cleaning + $platform, 2),
                'currency' => $property->currency ?: 'RON',
                'status' => $status,
                'source' => 'direct',
                'payment_status' => $paymentStatus,
            ]
        );
    }

    /**
     * @param  array<string, Property>  $properties
     */
    private function seedTasks(array $properties, Provider $provider, User $assignee, Booking $checkedOut): void
    {
        $byIndex = array_values($properties);

        Task::query()->updateOrCreate(
            ['title' => 'Curățenie check-out', 'property_id' => $byIndex[0]->id],
            [
                'booking_id' => $checkedOut->id,
                'type' => 'cleaning',
                'description' => 'Curățenie completă după check-out: pardoseli, băi, bucătărie, schimb lenjerie.',
                'provider_id' => $provider->id,
                'assigned_to_user_id' => $assignee->id,
                'status' => 'completed',
                'priority' => 'high',
                'scheduled_at' => now()->subDays(17)->setTime(12, 0),
                'started_at' => now()->subDays(17)->setTime(12, 10),
                'completed_at' => now()->subDays(17)->setTime(14, 30),
                'estimated_cost' => 150,
                'actual_cost' => 140,
                'currency' => 'RON',
                'notes' => 'Lenjerie schimbată, provizii reînnoite.',
            ]
        );

        Task::query()->updateOrCreate(
            ['title' => 'Verificare tehnică periodica', 'property_id' => $byIndex[1]->id],
            [
                'booking_id' => null,
                'type' => 'inspection',
                'description' => 'Verificare instalații, detector de fum și centrală înainte de sezon.',
                'provider_id' => null,
                'assigned_to_user_id' => null,
                'status' => 'open',
                'priority' => 'normal',
                'scheduled_at' => now()->addDays(4)->setTime(10, 0),
                'estimated_cost' => 220,
                'currency' => 'RON',
                'notes' => null,
            ]
        );

        Task::query()->updateOrCreate(
            ['title' => 'Reparație aer condiționat', 'property_id' => $byIndex[4]->id],
            [
                'booking_id' => null,
                'type' => 'repair',
                'description' => 'Unitatea interioară nu pornește — intervenție în curs.',
                'provider_id' => $provider->id,
                'assigned_to_user_id' => $assignee->id,
                'status' => 'in_progress',
                'priority' => 'urgent',
                'scheduled_at' => now()->setTime(9, 0),
                'started_at' => now()->setTime(9, 30),
                'estimated_cost' => 350,
                'currency' => 'RON',
                'notes' => 'Piese comandate.',
            ]
        );
    }

    /**
     * @param  array<string, Property>  $properties
     */
    private function seedReviews(array $properties, User $guest, Booking $checkedOut): void
    {
        $byIndex = array_values($properties);
        $property = $byIndex[0];

        Review::query()->firstOrCreate(
            ['booking_id' => $checkedOut->id, 'author_id' => $guest->id],
            [
                'target_type' => Property::class,
                'target_id' => $property->id,
                'rating' => 5,
                'cleanliness' => 5,
                'communication' => 5,
                'accuracy' => 5,
                'value' => 4,
                'comment' => 'Apartament curat, gazdă promptă, zonă excelentă. Recomand!',
                'is_published' => true,
            ]
        );

        Review::query()->firstOrCreate(
            ['booking_id' => null, 'author_id' => $guest->id, 'target_id' => $byIndex[1]->id],
            [
                'target_type' => Property::class,
                'rating' => 5,
                'cleanliness' => 5,
                'communication' => 5,
                'accuracy' => 5,
                'value' => 5,
                'comment' => 'Vila este impresionantă, piscina perfect întreținută.',
                'is_published' => true,
            ]
        );
    }

    /**
     * Date istorice pentru graficele și KPI-urile din Command Center.
     *
     * @param  array<int, Host>  $hosts
     * @param  array<string, Property>  $properties
     */
    private function seedCommandCenterData(array $hosts, array $properties, Provider $provider, User $assignee, User $admin, User $guest): void
    {
        $subscriptionMonths = [9, 5, 2];

        foreach ($hosts as $index => $host) {
            Subscription::query()
                ->where('user_id', $host->user_id)
                ->where('type', 'host')
                ->update(['created_at' => now()->subMonths($subscriptionMonths[$index] ?? 1)]);
        }

        $byIndex = array_values($properties);

        $drafts = [
            ['host' => $hosts[0], 'title' => 'Pensiune cu vedere spre Bucegi', 'city' => 'Bușteni', 'county' => 'Prahova', 'lat' => 45.5050, 'lng' => 25.6000],
            ['host' => $hosts[1], 'title' => 'Apartament în Mamaia Nord', 'city' => 'Constanța', 'county' => 'Constanța', 'lat' => 44.2550, 'lng' => 28.6300],
        ];

        foreach ($drafts as $index => $draft) {
            Property::query()->updateOrCreate(
                ['slug' => Str::slug($draft['title']).'-draft'],
                [
                    'host_id' => $draft['host']->id,
                    'title' => $draft['title'],
                    'description' => 'Proprietate în lucru: fotografii și detalii complete în curs de pregătire.',
                    'property_type' => 'apartment',
                    'address' => 'Str. Moldovei '.($index + 3),
                    'city' => $draft['city'],
                    'county' => $draft['county'],
                    'country' => 'RO',
                    'postal_code' => '10'.(51 + $index),
                    'latitude' => $draft['lat'],
                    'longitude' => $draft['lng'],
                    'base_price' => 290,
                    'currency' => 'RON',
                    'cleaning_fee' => 100,
                    'max_guests' => 4,
                    'bedrooms' => 2,
                    'beds' => 2,
                    'bathrooms' => 1,
                    'check_in_time' => '15:00',
                    'check_out_time' => '11:00',
                    'min_nights' => 1,
                    'max_nights' => 30,
                    'house_rules' => 'Regulament în curs de definitivare.',
                    'status' => 'draft',
                    'rating_avg' => 0,
                    'reviews_count' => 0,
                ]
            );
        }

        $historical = [
            ['months' => 5, 'property' => $byIndex[0], 'offset' => 1],
            ['months' => 4, 'property' => $byIndex[4], 'offset' => 2],
            ['months' => 3, 'property' => $byIndex[1], 'offset' => 1],
            ['months' => 2, 'property' => $byIndex[5], 'offset' => 3],
            ['months' => 2, 'property' => $byIndex[2], 'offset' => 1],
            ['months' => 1, 'property' => $byIndex[0], 'offset' => 4],
            ['months' => 1, 'property' => $byIndex[3], 'offset' => 2],
            ['months' => 0, 'property' => $byIndex[4], 'offset' => 5],
        ];

        foreach ($historical as $index => $entry) {
            $booking = $this->makeBooking(
                'BK-DEMO'.str_pad((string) (10 + $index), 4, '0', STR_PAD_LEFT),
                $entry['property'],
                $guest,
                now()->subMonths($entry['months'])->subDays($entry['offset'])->toDateString(),
                now()->subMonths($entry['months'])->addDays(3 - $entry['offset'])->toDateString(),
                'checked_out',
                'paid'
            );

            $booking->forceFill([
                'created_at' => now()->subMonths($entry['months'])->subDays($entry['offset'] + 2),
            ])->save();
        }

        $this->makeBooking(
            'BK-DEMO0005',
            $byIndex[1],
            $guest,
            now()->subDay()->toDateString(),
            now()->toDateString(),
            'checked_in',
            'paid'
        );

        $overdue = [
            ['title' => 'Curățenie profundă întârziată', 'property' => $byIndex[0], 'type' => 'cleaning', 'status' => 'assigned', 'days' => 6, 'priority' => 'high', 'cost' => 180],
            ['title' => 'Schimbare garnitură baie', 'property' => $byIndex[4], 'type' => 'repair', 'status' => 'open', 'days' => 4, 'priority' => 'high', 'cost' => 120],
            ['title' => 'Inspecție detector de fum', 'property' => $byIndex[1], 'type' => 'inspection', 'status' => 'in_progress', 'days' => 3, 'priority' => 'urgent', 'cost' => 90],
            ['title' => 'Montare storuri exterioare', 'property' => $byIndex[5], 'type' => 'repair', 'status' => 'open', 'days' => 2, 'priority' => 'normal', 'cost' => 250],
        ];

        foreach ($overdue as $task) {
            Task::query()->updateOrCreate(
                ['title' => $task['title'], 'property_id' => $task['property']->id],
                [
                    'booking_id' => null,
                    'type' => $task['type'],
                    'description' => 'Intervenție programată care a depășit termenul.',
                    'provider_id' => $provider->id,
                    'assigned_to_user_id' => $task['status'] === 'open' ? null : $assignee->id,
                    'status' => $task['status'],
                    'priority' => $task['priority'],
                    'scheduled_at' => now()->subDays($task['days'])->setTime(10, 0),
                    'estimated_cost' => $task['cost'],
                    'currency' => 'RON',
                ]
            );
        }

        $completed = [
            ['title' => 'Curățenie mid-stay', 'property' => $byIndex[0], 'type' => 'cleaning', 'hours' => 3, 'cost' => 180],
            ['title' => 'Reparație mașină de spălat', 'property' => $byIndex[2], 'type' => 'repair', 'days' => 3, 'cost' => 320],
            ['title' => 'Verificare centrală termică', 'property' => $byIndex[4], 'type' => 'inspection', 'days' => 8, 'cost' => 140],
        ];

        foreach ($completed as $task) {
            $at = isset($task['hours']) ? now()->subHours($task['hours']) : now()->subDays($task['days']);

            Task::query()->updateOrCreate(
                ['title' => $task['title'], 'property_id' => $task['property']->id],
                [
                    'booking_id' => null,
                    'type' => $task['type'],
                    'description' => 'Lucrare finalizată și verificată de lead-ul echipei.',
                    'provider_id' => $provider->id,
                    'assigned_to_user_id' => $assignee->id,
                    'status' => 'completed',
                    'priority' => 'normal',
                    'scheduled_at' => $at->copy()->subHour(),
                    'started_at' => $at->copy()->subMinutes(45),
                    'completed_at' => $at,
                    'estimated_cost' => $task['cost'],
                    'actual_cost' => $task['cost'],
                    'currency' => 'RON',
                ]
            );
        }

        $disputes = [
            ['reference' => 'DSP-DEMO0001', 'category' => 'payment', 'subject' => 'Plată dublată pentru rezervare', 'status' => 'open'],
            ['reference' => 'DSP-DEMO0002', 'category' => 'damage', 'subject' => 'Daune raportate după check-out', 'status' => 'investigating'],
            [
                'reference' => 'DSP-DEMO0003',
                'category' => 'service_quality',
                'subject' => 'Curățenie nesatisfăcătoare la check-in',
                'status' => 'resolved',
                'resolution' => 'Rambursare parțială aprobată.',
                'refund_amount' => 120,
                'resolved_at' => now()->subDays(5),
            ],
        ];

        foreach ($disputes as $dispute) {
            Dispute::query()->updateOrCreate(
                ['reference' => $dispute['reference']],
                [
                    'booking_id' => null,
                    'task_id' => null,
                    'opener_id' => $admin->id,
                    'category' => $dispute['category'],
                    'subject' => $dispute['subject'],
                    'description' => $dispute['subject'].' (caz demo).',
                    'status' => $dispute['status'],
                    'assigned_admin_id' => $dispute['status'] === 'open' ? null : $admin->id,
                    'resolution' => $dispute['resolution'] ?? null,
                    'refund_amount' => $dispute['refund_amount'] ?? null,
                    'resolved_at' => $dispute['resolved_at'] ?? null,
                ]
            );
        }
    }
}
