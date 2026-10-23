<?php

namespace Database\Seeders;

use App\Models\Work;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * The owner's login is created with `php artisan app:create-admin`, not here.
     */
    public function run(): void
    {
        $this->call([ServiceSeeder::class, FaqSeeder::class, PostSeeder::class]);

        Work::query()->firstOrCreate(['slug' => 'regional-news-platform'], [
            'title' => 'A regional news platform',
            'client' => 'News publisher, South East',
            'category' => 'Blog & news website',
            'summary' => 'Built from scratch in Laravel for a news publisher in the South East. Readers mostly come from phones on mobile data.',
            'headline' => 'From slow and hard to find, to fast and fully indexed',
            'description' => 'The site loaded in 3 to 16 seconds for readers in Nigeria and Google could see only 2 of its pages. We rebuilt it for speed, fixed the sitemap and moved hosting closer to the readers.',
            'highlights' => [
                'Live election results by local government, updated without reloading',
                'Reader polls with one vote per person, and staff kept out',
                'Admin panel: scheduling, live coverage, ads, comments and analytics',
                'Stories shared to Facebook automatically with the right photo',
            ],
            'results' => [
                ['value' => '59 → 92', 'label' => 'mobile speed score'],
                ['value' => '2 → 366', 'label' => 'pages Google can find'],
                ['value' => '16 s → 0.7 s', 'label' => 'time to open a story'],
            ],
            'technologies' => ['PHP / Laravel', 'MySQL', 'Livewire', 'Tailwind CSS'],
            'seo_description' => 'How we rebuilt a regional Nigerian news website in Laravel: mobile speed score 59 to 92 and 366 pages indexed by Google.',
            'is_published' => true,
            'is_featured' => true,
            'sort_order' => 1,
        ]);
    }
}
