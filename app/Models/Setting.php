<?php

namespace App\Models;

use App\Support\ImageOptimizer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Once;

/**
 * Key/value store for business details and the editable text on the home page.
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /**
     * Text values used until the owner saves their own.
     *
     * @var array<string, string>
     */
    public const array DEFAULTS = [
        // Business details
        'business_name' => 'Samuel Digital Lab',
        'tagline' => 'We build fast, modern and responsive websites.',
        'whatsapp_primary' => '09060354675',
        'whatsapp_secondary' => '07025918539',
        'email' => 'udochukwus060@gmail.com',
        'city' => 'Port Harcourt, Rivers State',
        'facebook_url' => '',
        'instagram_url' => '',
        'x_url' => '',
        'linkedin_url' => '',
        'tiktok_url' => '',
        'logo_path' => '',
        'logo_show_name' => '1',
        'google_analytics_id' => '',
        'privacy_policy' => '',
        'calc_eyebrow' => 'Price estimate',
        'calc_title' => 'What will your website cost?',
        'calc_text' => 'Pick what you need and see an estimate straight away. Your final price is confirmed after a short chat.',
        'calc_extra_page_price' => '10000',
        'consult_enabled' => '1',
        'consult_start' => '09:00',
        'consult_end' => '17:00',
        'consult_minutes' => '15',
        'consult_interval' => '30',
        'consult_days_ahead' => '14',
        'consult_notice_hours' => '3',
        'bank_details' => '',
        'google_rating' => '',
        'google_review_count' => '',
        'google_reviews_url' => '',

        // About me
        'about_eyebrow' => 'About me',
        'about_title' => 'The person behind your website',
        'about_name' => 'Samuel',
        'about_role' => 'Founder & Lead Developer',
        'about_photo_path' => '',
        'about_cv_path' => '',
        'github_url' => '',
        'about_story' => "Hi, I'm Samuel, the founder and lead developer of Samuel Digital Lab. I build fast, modern websites for businesses, schools, churches and publishers across Nigeria, using Laravel, PHP and MySQL.\n\nMy focus is simple: a website that opens quickly on mobile data, shows up on Google and turns visitors into calls and WhatsApp messages. Every site I build is tested before launch and comes with an admin panel you can run yourself.\n\nWhen you work with me, you deal directly with the person building your website, with no middlemen. I explain everything in plain language, keep you updated on a preview link while I build, and stay with you after launch.",

        // Home page: search engines
        'seo_title' => 'Web design & development in Nigeria',
        'seo_description' => 'We build fast, modern and responsive websites that help Nigerian businesses grow online: business, e-commerce, blog and news websites and landing pages.',

        // Home page: hero
        'hero_badge' => 'Web design & development in Nigeria',
        'hero_title' => "We don't just build websites. We build online",
        'hero_text' => 'We build fast, modern and responsive websites that help businesses grow online.',
        'hero_primary_button' => 'Get a free quote',
        'hero_secondary_button' => 'See our work',
        'hero_note' => 'Business, e-commerce, portfolio, blog and news websites, and landing pages.',
        'speed_card_url' => 'news-site.example.ng',
        'speed_card_footer' => '218 tests passed',

        // Home page: sections
        'services_eyebrow' => 'What we do',
        'services_title' => 'Websites for every kind of business',
        'services_text' => 'Every site comes with hosting setup, SSL, Google Search Console and a training call for your team.',
        'features_title' => 'Every website we build is',
        'work_eyebrow' => 'Selected work',
        'work_title' => 'Websites we are proud of',
        'work_text' => 'Real projects for real businesses. Tap a project to see screenshots, what we built and the results.',
        'process_eyebrow' => 'How we work',
        'process_title' => 'From first chat to launch',
        'process_text' => "You see progress on a private link while we build, and you only pay the balance when you're happy.",
        'testimonials_eyebrow' => 'Client stories',
        'testimonials_title' => 'What our clients say',
        'why_eyebrow' => 'Why choose us',
        'why_title' => 'Built to help your business grow',
        'technologies_title' => 'Technologies we use',
        'blog_eyebrow' => 'From the blog',
        'blog_title' => 'Tips to grow your business online',
        'faq_eyebrow' => 'Questions',
        'faq_title' => 'Things people often ask',
        'contact_eyebrow' => 'Contact',
        'contact_title' => "Let's build your website",
        'contact_text' => 'Tell us about your business. We reply on WhatsApp or by email within one working day with a fixed price.',
        'form_title' => 'Get a quote',
        'form_note' => 'We reply on WhatsApp or by email within one working day. Your details are only used to answer your request.',
        'cta_title' => 'Want a website like this?',
        'cta_text' => "Tell us about your business and we'll send a fixed price within a day.",
    ];

    /**
     * List values used until the owner saves their own.
     *
     * @var array<string, list<mixed>>
     */
    public const array LIST_DEFAULTS = [
        'hero_words' => ['success', 'sales', 'customers', 'growth'],
        'speed_scores' => [
            ['label' => 'Performance', 'value' => '92'],
            ['label' => 'Accessibility', 'value' => '97'],
            ['label' => 'Best practices', 'value' => '100'],
            ['label' => 'SEO', 'value' => '100'],
        ],
        'stats' => [
            ['value' => '0.7 s', 'label' => 'page opens in Nigeria'],
            ['value' => '31 KB', 'label' => 'main photo on phones'],
            ['value' => '366', 'label' => "pages in Google's sitemap"],
        ],
        'features' => [
            ['title' => 'Mobile responsive', 'text' => 'Looks right on every phone, tablet and laptop'],
            ['title' => 'Fast loading', 'text' => 'Opens quickly, even on mobile data'],
            ['title' => 'SEO optimised', 'text' => 'Set up so Google can find and rank it'],
            ['title' => 'Secure & reliable', 'text' => 'SSL, backups and safe forms as standard'],
            ['title' => 'Modern UI/UX', 'text' => 'Clean design your customers find easy to use'],
        ],
        'process_steps' => [
            ['title' => 'Chat', 'text' => 'Tell us about your business and what the site must do.', 'note' => '15-minute WhatsApp call'],
            ['title' => 'Quote', 'text' => 'A fixed price, a timeline and a list of pages. No surprises.', 'note' => '50% deposit to start'],
            ['title' => 'Build', 'text' => 'We build on a preview link you can open on your phone any time.', 'note' => '7 to 14 days'],
            ['title' => 'Launch', 'text' => 'Your domain goes live, Google is told, and we train your team.', 'note' => 'Balance on launch day'],
        ],
        'why_points' => [
            ['title' => 'Clean and modern design', 'text' => 'A look that builds trust with your customers from the first visit.'],
            ['title' => 'Affordable pricing', 'text' => 'A fixed price agreed before we start. No hidden costs.'],
            ['title' => 'Fast delivery', 'text' => 'From 3 days for a landing page, 7 to 21 days for full websites.'],
            ['title' => 'Ongoing support', 'text' => 'We stay with you after launch for updates, backups and fixes.'],
            ['title' => 'Custom solutions', 'text' => 'Booking, payments, portals: built around how your business works.'],
        ],
        'consult_days' => [1, 2, 3, 4, 5],
        'calc_site_types' => [
            ['name' => 'Landing pages', 'price' => '80000', 'pages' => '1'],
            ['name' => 'Business & portfolio websites', 'price' => '150000', 'pages' => '6'],
            ['name' => 'Blog & news websites', 'price' => '250000', 'pages' => '8'],
            ['name' => 'E-commerce websites', 'price' => '350000', 'pages' => '10'],
        ],
        'calc_extras' => [
            ['name' => 'Online payments (Paystack or Flutterwave)', 'price' => '40000'],
            ['name' => 'Logo design', 'price' => '25000'],
            ['name' => 'Booking or appointment system', 'price' => '50000'],
            ['name' => 'We write the text for your pages', 'price' => '30000'],
            ['name' => 'Google Business Profile setup', 'price' => '15000'],
            ['name' => 'Ongoing support for 3 months', 'price' => '45000'],
        ],
        'about_facts' => [],
        'about_skills' => ['Laravel', 'PHP', 'MySQL', 'Livewire', 'Tailwind CSS', 'JavaScript', 'SEO', 'Website speed'],
        'technologies' => ['HTML', 'CSS', 'JavaScript', 'PHP', 'Laravel', 'MySQL', 'Livewire', 'Tailwind CSS', 'Bootstrap', 'Paystack', 'Flutterwave', 'Google Search Console'],
    ];

    /**
     * The keys edited on the Business details page; the rest are website content.
     *
     * @var list<string>
     */
    public const array BUSINESS_KEYS = [
        'business_name', 'tagline', 'whatsapp_primary', 'whatsapp_secondary', 'email', 'city',
        'facebook_url', 'instagram_url', 'x_url', 'linkedin_url', 'tiktok_url', 'logo_path', 'logo_show_name',
        'google_analytics_id', 'privacy_policy', 'google_rating', 'google_review_count', 'google_reviews_url',
        'calc_eyebrow', 'calc_title', 'calc_text', 'calc_extra_page_price',
        'consult_enabled', 'consult_start', 'consult_end', 'consult_minutes', 'consult_interval', 'consult_days_ahead', 'consult_notice_hours', 'bank_details',
        'about_eyebrow', 'about_title', 'about_name', 'about_role', 'about_photo_path', 'about_cv_path', 'github_url', 'about_story',
    ];

    private const string CACHE_KEY = 'site-settings';

    /**
     * Get every stored value (text, or JSON for lists), falling back to the defaults.
     *
     * @return array<string, string>
     */
    public static function values(): array
    {
        // Read once per request. Only the saved values are cached; defaults are added on
        // top so settings introduced by a later update are never missing from an old cache.
        return once(fn (): array => array_merge(self::DEFAULTS, Cache::rememberForever(
            self::CACHE_KEY,
            fn (): array => static::query()->pluck('value', 'key')->map(fn (?string $value): string => (string) $value)->all(),
        )));
    }

    /**
     * Get one text value.
     */
    public static function get(string $key): string
    {
        return self::values()[$key] ?? '';
    }

    /**
     * Get the public URL of the uploaded logo, if there is one.
     */
    public static function logoUrl(): ?string
    {
        $path = self::get('logo_path');

        return $path !== '' ? Storage::disk('public')->url($path) : null;
    }

    /**
     * Get the PNG copy of the uploaded logo for emails (email apps can't show WebP), if there is one.
     */
    public static function emailLogoUrl(): ?string
    {
        $path = self::get('logo_path');

        if ($path === '') {
            return null;
        }

        $pngPath = ImageOptimizer::emailLogoPath($path);

        return Storage::disk('public')->exists($pngPath) ? Storage::disk('public')->url($pngPath) : null;
    }

    /**
     * Get the public URL of a file saved in a setting (e.g. the About photo or CV), if there is one.
     */
    public static function fileUrl(string $key): ?string
    {
        $path = self::get($key);

        return $path !== '' ? Storage::disk('public')->url($path) : null;
    }

    /**
     * Get the About story as paragraphs.
     *
     * @return list<string>
     */
    public static function aboutParagraphs(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', self::get('about_story')) ?: [])));
    }

    /**
     * Get the privacy policy text (Markdown): the owner's own, or a starter
     * version filled in with the business details.
     */
    public static function privacyPolicy(): string
    {
        $saved = trim(self::get('privacy_policy'));

        if ($saved !== '') {
            return $saved;
        }

        $values = self::values();
        $business = $values['business_name'];
        $email = $values['email'];
        $phone = $values['whatsapp_primary'];

        return <<<MARKDOWN
            This page explains what personal information {$business} collects through this website, why, and what you can ask us to do with it. We follow the Nigeria Data Protection Act 2023.

            ## What we collect

            When you send a quote request we collect your **name**, **phone or WhatsApp number**, and, if you give them, your **business name**, **email address** and the **details of your project**. We also record your IP address to protect the form from spam.

            If visitor statistics are switched on, Google Analytics collects anonymous information about how the website is used, such as the pages visited and the type of device.

            ## Why we use it

            - To reply to your request and send you a quote.
            - To keep in touch about a project you have asked us to do.
            - To protect the website from spam and abuse.

            We do **not** sell your information or use it for unrelated marketing.

            ## Who can see it

            Only {$business} can see your request. Our website host stores the data securely. Google processes the anonymous statistics if they are switched on.

            ## How long we keep it

            We keep quote requests for up to two years after our last contact, then delete them, unless we need them for a project or the law requires us to keep them.

            ## Your rights

            You can ask us to show you the information we hold about you, correct it, or delete it. Contact us at {$email} or on WhatsApp at {$phone}. You may also complain to the Nigeria Data Protection Commission.

            ## Changes

            We may update this page. The latest version is always shown here.
            MARKDOWN;
    }

    /**
     * Get one list value.
     *
     * @return list<mixed>
     */
    public static function list(string $key): array
    {
        $stored = self::values()[$key] ?? null;
        $decoded = is_string($stored) ? json_decode($stored, true) : null;

        return is_array($decoded) ? array_values($decoded) : (self::LIST_DEFAULTS[$key] ?? []);
    }

    /**
     * Save several values at once. Arrays are stored as JSON lists.
     *
     * @param  array<string, string|array<mixed>|null>  $values
     */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(
                ['key' => $key],
                ['value' => is_array($value) ? json_encode(array_values($value), JSON_UNESCAPED_UNICODE) : (string) $value],
            );
        }

        Cache::forget(self::CACHE_KEY);
        Once::flush();
    }
}
