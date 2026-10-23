<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\View\View;

class BlogController extends Controller
{
    /**
     * Show every published article.
     */
    public function index(): View
    {
        return view('blog.index', [
            'posts' => Post::query()->published()->paginate(12),
        ]);
    }

    /**
     * Show one published article.
     */
    public function show(Post $post): View
    {
        abort_unless($post->is_published && ($post->published_at === null || $post->published_at->isPast()), 404);

        return view('blog.show', [
            'post' => $post,
            'morePosts' => Post::query()->published()->whereKeyNot($post->getKey())->take(3)->get(),
        ]);
    }
}
