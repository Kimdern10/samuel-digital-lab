<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Work;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show the home page.
     */
    public function __invoke(): View
    {
        return view('home', [
            'services' => Service::query()->visible()->ordered()->get(),
            'featuredWorks' => Work::query()->published()->where('is_featured', true)->ordered()->take(4)->get(),
        ]);
    }
}
