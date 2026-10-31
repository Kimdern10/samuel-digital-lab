<?php

namespace App\Http\Controllers;

use App\Models\Work;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkController extends Controller
{
    /**
     * Show every published work, optionally filtered by category.
     */
    public function index(Request $request): View
    {
        $categories = Work::query()->published()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category');
        $category = $categories->contains($request->query('category')) ? (string) $request->query('category') : null;

        return view('work.index', [
            'works' => Work::query()->published()->when($category, fn ($query) => $query->where('category', $category))->ordered()->get(),
            'categories' => $categories,
            'category' => $category,
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
