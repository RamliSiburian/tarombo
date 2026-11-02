<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ArticleController extends Controller
{
    /**
     * Display the public article/news portal page.
     */
    public function index(Request $request)
    {
        $category = $request->query('category');
        $search = $request->query('q');

        $query = Article::published()->with('author:id,name')->latest();

        if ($category && in_array($category, ['kegiatan', 'berita', 'sejarah', 'budaya'])) {
            $query->where('category', $category);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $articles = $query->paginate(9)->withQueryString();

        // Category counts for tab badges
        $counts = [
            'all'      => Article::published()->count(),
            'kegiatan' => Article::published()->where('category', 'kegiatan')->count(),
            'berita'   => Article::published()->where('category', 'berita')->count(),
            'sejarah'  => Article::published()->where('category', 'sejarah')->count(),
            'budaya'   => Article::published()->where('category', 'budaya')->count(),
        ];

        return Inertia::render('Articles/Index', [
            'articles' => $articles,
            'filters'  => [
                'category' => $category,
                'q'        => $search,
            ],
            'counts'   => $counts,
        ]);
    }

    /**
     * Display a single article detail.
     */
    public function show(string $slug)
    {
        $article = Article::published()
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)->orWhere('id', $slug);
            })
            ->with('author:id,name')
            ->firstOrFail();

        // Related articles: same category if available, otherwise latest published
        $related = Article::published()
            ->where('id', '!=', $article->id)
            ->where('category', $article->category)
            ->latest()
            ->take(3)
            ->get();

        if ($related->count() < 3) {
            $more = Article::published()
                ->where('id', '!=', $article->id)
                ->whereNotIn('id', $related->pluck('id'))
                ->latest()
                ->take(3 - $related->count())
                ->get();
            $related = $related->merge($more);
        }

        return Inertia::render('Articles/Show', [
            'article' => $article,
            'relatedArticles' => $related,
        ]);
    }
}
