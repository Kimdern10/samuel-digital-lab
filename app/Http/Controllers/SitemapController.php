<?php

namespace App\Http\Controllers;

use App\Models\Work;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * List the public pages for search engines.
     */
    public function __invoke(): Response
    {
        $works = Work::query()->published()->ordered()->get(['slug', 'updated_at']);

        return response()
            ->view('sitemap', [
                'works' => $works,
                'lastUpdated' => $works->max('updated_at'),
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * Tell search engines where the sitemap is and keep them out of the admin.
     */
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /settings',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
