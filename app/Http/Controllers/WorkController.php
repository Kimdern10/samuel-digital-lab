<?php

namespace App\Http\Controllers;

use App\Models\Work;
use Illuminate\View\View;

class WorkController extends Controller
{
    /**
     * Show every published work.
     */
    public function index(): View
    {
        return view('work.index', [
            'works' => Work::query()->published()->ordered()->get(),
        ]);
    }

    /**
     * Show one published work.
     */
    public function show(Work $work): View
    {
        abort_unless($work->is_published, 404);

        return view('work.show', [
            'work' => $work,
            'moreWorks' => Work::query()->published()->whereKeyNot($work->getKey())->ordered()->take(3)->get(),
        ]);
    }
}
