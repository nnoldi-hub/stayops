<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitiesSeeder extends Seeder
{
    public function run(): void
    {
        $amenities = [
            // basic
            ['slug' => 'wifi', 'name_ro' => 'Wi-Fi', 'name_en' => 'Wi-Fi', 'icon' => 'heroicon-o-wifi', 'category' => 'basic'],
            ['slug' => 'tv', 'name_ro' => 'TV', 'name_en' => 'TV', 'icon' => 'heroicon-o-tv', 'category' => 'basic'],
            ['slug' => 'air_conditioning', 'name_ro' => 'Aer condiționat', 'name_en' => 'Air conditioning', 'icon' => 'heroicon-o-cloud', 'category' => 'basic'],
            ['slug' => 'heating', 'name_ro' => 'Încălzire', 'name_en' => 'Heating', 'icon' => 'heroicon-o-fire', 'category' => 'basic'],
            ['slug' => 'parking', 'name_ro' => 'Parcare', 'name_en' => 'Parking', 'icon' => 'heroicon-o-truck', 'category' => 'basic'],
            ['slug' => 'elevator', 'name_ro' => 'Lift', 'name_en' => 'Elevator', 'icon' => 'heroicon-o-arrows-up-down', 'category' => 'basic'],
            // kitchen
            ['slug' => 'kitchen', 'name_ro' => 'Bucătărie', 'name_en' => 'Kitchen', 'icon' => 'heroicon-o-cake', 'category' => 'kitchen'],
            ['slug' => 'refrigerator', 'name_ro' => 'Frigider', 'name_en' => 'Refrigerator', 'icon' => 'heroicon-o-cube', 'category' => 'kitchen'],
            ['slug' => 'microwave', 'name_ro' => 'Cuptor cu microunde', 'name_en' => 'Microwave', 'icon' => 'heroicon-o-square-3-stack-3d', 'category' => 'kitchen'],
            ['slug' => 'coffee_maker', 'name_ro' => 'Espressor', 'name_en' => 'Coffee maker', 'icon' => 'heroicon-o-beaker', 'category' => 'kitchen'],
            ['slug' => 'dishwasher', 'name_ro' => 'Mașină de spălat vase', 'name_en' => 'Dishwasher', 'icon' => 'heroicon-o-squares-2x2', 'category' => 'kitchen'],
            // bathroom
            ['slug' => 'hot_water', 'name_ro' => 'Apă caldă', 'name_en' => 'Hot water', 'icon' => 'heroicon-o-cloud', 'category' => 'bathroom'],
            ['slug' => 'hair_dryer', 'name_ro' => 'Uscător de păr', 'name_en' => 'Hair dryer', 'icon' => 'heroicon-o-bolt', 'category' => 'bathroom'],
            ['slug' => 'shampoo', 'name_ro' => 'Șampon', 'name_en' => 'Shampoo', 'icon' => 'heroicon-o-sparkles', 'category' => 'bathroom'],
            ['slug' => 'towels', 'name_ro' => 'Prosoape', 'name_en' => 'Towels', 'icon' => 'heroicon-o-rectangle-stack', 'category' => 'bathroom'],
            // outdoor
            ['slug' => 'balcony', 'name_ro' => 'Balcon', 'name_en' => 'Balcony', 'icon' => 'heroicon-o-sun', 'category' => 'outdoor'],
            ['slug' => 'terrace', 'name_ro' => 'Terasă', 'name_en' => 'Terrace', 'icon' => 'heroicon-o-rectangle-group', 'category' => 'outdoor'],
            ['slug' => 'pool', 'name_ro' => 'Piscină', 'name_en' => 'Pool', 'icon' => 'heroicon-o-circle-stack', 'category' => 'outdoor'],
            ['slug' => 'garden', 'name_ro' => 'Grădină', 'name_en' => 'Garden', 'icon' => 'heroicon-o-globe-europe-africa', 'category' => 'outdoor'],
            ['slug' => 'bbq', 'name_ro' => 'Grătar', 'name_en' => 'BBQ', 'icon' => 'heroicon-o-fire', 'category' => 'outdoor'],
            // safety
            ['slug' => 'smoke_detector', 'name_ro' => 'Detector de fum', 'name_en' => 'Smoke detector', 'icon' => 'heroicon-o-shield-exclamation', 'category' => 'safety'],
            ['slug' => 'co_detector', 'name_ro' => 'Detector CO', 'name_en' => 'CO detector', 'icon' => 'heroicon-o-shield-check', 'category' => 'safety'],
            ['slug' => 'first_aid_kit', 'name_ro' => 'Trusă de prim ajutor', 'name_en' => 'First aid kit', 'icon' => 'heroicon-o-plus-circle', 'category' => 'safety'],
            ['slug' => 'fire_extinguisher', 'name_ro' => 'Stingător', 'name_en' => 'Fire extinguisher', 'icon' => 'heroicon-o-shield-exclamation', 'category' => 'safety'],
            // family
            ['slug' => 'crib', 'name_ro' => 'Pătuț bebe', 'name_en' => 'Crib', 'icon' => 'heroicon-o-heart', 'category' => 'family'],
            ['slug' => 'high_chair', 'name_ro' => 'Scaun înalt', 'name_en' => 'High chair', 'icon' => 'heroicon-o-user', 'category' => 'family'],
            ['slug' => 'pets_allowed', 'name_ro' => 'Animale permise', 'name_en' => 'Pets allowed', 'icon' => 'heroicon-o-heart', 'category' => 'family'],
        ];

        foreach ($amenities as $i => $data) {
            $data['sort_order'] = $i;
            Amenity::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
