<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->get('category');
        $search = $request->get('q');

        $articles = Article::with('author')
            ->when($category, fn($q) => $q->where('category', $category))
            ->when($search, fn($q) => $q->where('title', 'like', "%{$search}%"))
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Admin/Articles/Index', [
            'articles' => $articles,
            'filters'  => [
                'category' => $category,
                'q'        => $search,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Articles/Form', [
            'article' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'category'     => 'required|in:kegiatan,berita,sejarah,budaya',
            'excerpt'      => 'nullable|string|max:500',
            'content'      => 'required|string',
            'event_date'   => 'nullable|date',
            'is_published' => 'boolean',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('articles', 'public');
        }

        $validated['author_id'] = auth()->id();
        $validated['slug'] = Str::slug($validated['title']) . '-' . Str::random(5);

        Article::create($validated);

        return redirect()->route('admin.articles.index')->with('success', 'Data kegiatan/berita berhasil ditambahkan.');
    }

    public function edit(Article $article)
    {
        return Inertia::render('Admin/Articles/Form', [
            'article' => $article,
        ]);
    }

    public function update(Request $request, Article $article)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'category'     => 'required|in:kegiatan,berita,sejarah,budaya',
            'excerpt'      => 'nullable|string|max:500',
            'content'      => 'required|string',
            'event_date'   => 'nullable|date',
            'is_published' => 'boolean',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($article->image && Storage::disk('public')->exists($article->image)) {
                Storage::disk('public')->delete($article->image);
            }
            $validated['image'] = $request->file('image')->store('articles', 'public');
        }

        $article->update($validated);

        return redirect()->route('admin.articles.index')->with('success', 'Data kegiatan/berita berhasil diperbarui.');
    }

    public function destroy(Article $article)
    {
        if ($article->image && Storage::disk('public')->exists($article->image)) {
            Storage::disk('public')->delete($article->image);
        }

        $article->delete();

        return redirect()->route('admin.articles.index')->with('success', 'Kegiatan/berita berhasil dihapus.');
    }
}
