<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Seed common questions the owner can edit in the admin.
     */
    public function run(): void
    {
        $faqs = [
            ['How long does it take to build my website?', 'A landing page takes about 3 days. A business or portfolio website takes about 7 days, a blog or news website about 14 days and an online shop about 21 days. We agree the date before we start.'],
            ['How do I pay?', 'You pay 50% to start and the balance on launch day, when you are happy with the website. We accept bank transfer.'],
            ['Do you handle the domain name and hosting?', 'Yes. We register your domain, set up hosting with a free SSL certificate and connect your business email if you need one.'],
            ['Can I update the website myself?', 'Yes. Every website comes with an easy admin panel and a training call, so you can change text, photos, products and prices yourself.'],
            ['Will my website show on Google?', 'Yes. We set up Google Search Console, a sitemap and page titles and descriptions so Google can find and rank your pages.'],
            ['What happens after the website is launched?', 'Our ongoing support plan keeps your site fast, backed up and updated, with up to 2 hours of changes every month.'],
        ];

        foreach ($faqs as $index => [$question, $answer]) {
            Faq::query()->firstOrCreate(['question' => $question], ['answer' => $answer, 'sort_order' => $index + 1]);
        }
    }
}
