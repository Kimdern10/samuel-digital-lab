<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Seed a first article as a draft for the owner to check and publish.
     */
    public function run(): void
    {
        Post::query()->firstOrCreate(['slug' => 'how-much-does-a-website-cost-in-nigeria'], [
            'title' => 'How much does a website cost in Nigeria?',
            'excerpt' => 'Clear prices for business, blog, e-commerce and landing page websites in Nigeria, what affects the cost, and the yearly fees to plan for.',
            'category' => 'Website prices',
            'seo_description' => 'Website prices in Nigeria explained: business websites from ₦150,000, blogs from ₦250,000, online shops from ₦350,000 and landing pages from ₦80,000.',
            'is_published' => false,
            'published_at' => null,
            'body' => <<<'MARKDOWN'
"How much is a website?" is the first question almost every business owner asks us. The honest answer is: it depends on what the website must do. This guide explains the real costs so you can plan before you talk to any developer.

## Our starting prices

| Type of website | Starting price | Ready in |
| --- | --- | --- |
| Landing page | ₦80,000 | about 3 days |
| Business or portfolio website | ₦150,000 | about 7 days |
| Blog or news website | ₦250,000 | about 14 days |
| E-commerce website | ₦350,000 | about 21 days |
| Custom web application | On quote | after a short chat |

These are starting prices. You get a fixed price in writing before any work begins, so there are no surprises.

## What makes a website cost more?

- **Number of pages.** A five-page business site costs less than a forty-page school website.
- **Features.** Online payments with Paystack or Flutterwave, bookings, member logins and admin panels all add work.
- **Content.** If we write the text or take photos for you, that is extra. If you already have them, it is quicker.
- **Speed and quality.** A cheap template that loads in 10 seconds on mobile data will lose you customers. We build sites that open in about a second.

## Yearly costs to plan for

A website also needs a few things every year:

1. **Domain name** (for example yourbusiness.com.ng): usually ₦5,000 to ₦20,000 a year.
2. **Hosting** (where the website lives): usually ₦20,000 to ₦60,000 a year for a small business.
3. **Support and updates** (optional): our plan is ₦15,000 a month and covers backups, security updates and small changes.

## Is a cheap website worth it?

A website is there to bring you customers. A slow site, a site that looks wrong on phones, or one that Google cannot find costs you money every day. Paying a little more for a fast, well-built site usually pays for itself quickly.

## Get a fixed price for your website

Tell us what your business does and what the website should do. We will reply on WhatsApp within one working day with a fixed price and a timeline.
MARKDOWN,
        ]);
    }
}
