<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Seed the six services and starting prices from the approved design.
     */
    public function run(): void
    {
        $services = [
            [
                'title' => 'Business & portfolio websites',
                'description' => 'For shops, hotels, clinics, schools, churches and professionals who need to be found on Google.',
                'features' => ['Up to 6 pages, mobile-first', 'WhatsApp chat button and contact form', 'Google Maps and Business Profile setup'],
                'price' => 150000,
                'price_note' => 'from · 7 days',
                'badge' => null,
            ],
            [
                'title' => 'Blog & news websites',
                'description' => 'A full newsroom: breaking news, categories, polls, live coverage and an admin panel for your writers.',
                'features' => ['Fast homepage, ready for AdSense', 'Auto-share to your Facebook page', 'Newsletter and push alerts'],
                'price' => 250000,
                'price_note' => 'from · 14 days',
                'badge' => 'Most requested',
            ],
            [
                'title' => 'E-commerce websites',
                'description' => 'Sell online with a product catalogue, cart and checkout, paid in naira.',
                'features' => ['Paystack or Flutterwave payments', 'Orders, stock and delivery tracking', 'Order alerts by email and WhatsApp'],
                'price' => 350000,
                'price_note' => 'from · 21 days',
                'badge' => null,
            ],
            [
                'title' => 'Landing pages',
                'description' => 'One focused page for a product launch, event, campaign or ad, built to turn visitors into calls.',
                'features' => ['Ready for Facebook and Google ads', 'Lead form straight to your WhatsApp', 'Loads fast on mobile data'],
                'price' => 80000,
                'price_note' => 'from · 3 days',
                'badge' => null,
            ],
            [
                'title' => 'Custom web development',
                'description' => 'Portals, booking systems, school results and anything your business needs built in Laravel.',
                'features' => ['Planned with you before we build', 'Admin panel you can run yourself', 'Tested before every launch'],
                'price' => null,
                'price_note' => 'after a short chat',
                'badge' => null,
            ],
            [
                'title' => 'Ongoing support',
                'description' => 'We keep your site fast, backed up and updated so you can focus on your business.',
                'features' => ['Hosting, backups and security updates', 'Up to 2 hours of changes a month', 'Monthly speed and traffic report'],
                'price' => 15000,
                'price_note' => 'per month',
                'badge' => null,
            ],
        ];

        foreach ($services as $index => $service) {
            Service::query()->updateOrCreate(
                ['title' => $service['title']],
                [...$service, 'sort_order' => $index + 1, 'is_visible' => true],
            );
        }
    }
}
