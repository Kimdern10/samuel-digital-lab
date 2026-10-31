<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    /**
     * Show published articles, optionally filtered by category or a search.
     */
    public function index(Request $request): View
    {
        $categories = Post::query()->published()->reorder()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category');
        $category = $categories->contains($request->query('category')) ? (string) $request->query('category') : null;
        $search = trim(mb_substr((string) $request->query('q'), 0, 80));

        $posts = Post::query()
            ->published()
            ->when($category, fn ($query) => $query->where('category', $category))
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';

                $query->where(fn ($query) => $query
                    ->where('title', 'like', $like)
                    ->orWhere('excerpt', 'like', $like)
                    ->orWhere('body', 'like', $like));
            })
            ->paginate(12)
            ->withQueryString();

        return view('blog.index', [
            'posts' => $posts,
            'categories' => $categories,
            'category' => $category,
            'search' => $search,
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
            'morePosts' => Post::query()->published()->whereKeyNot($post->getKey())
                ->when($post->category, fn ($query) => $query->reorder()->orderByRaw('category = ? desc', [$post->category])->orderByDesc('published_at'))
                ->take(3)->get(),
        ]);
    }
}
