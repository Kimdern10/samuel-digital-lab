<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\View\View;

class BlogController extends Controller
{
    /**
     * Show the blog. The list, live search and topic filters are the blog-search component.
     */
    public function index(): View
    {
        return view('blog.index');
    }

    /**
     * Show one published article.
     */
    public function show(Post $post): View
    {
        abort_unless($post->is_published && ($post->published_at === null || $post->published_at->isPast()), 404);

        return view('blog.show', [
            'post' => $post,
            'morePosts' => Post::query()->published()->whereKeyNot($post->getKey())
                ->when($post->category, fn ($query) => $query->reorder()->orderByRaw('category = ? desc', [$post->category])->orderByDesc('published_at'))
                ->take(3)->get(),
        ]);
    }
}
