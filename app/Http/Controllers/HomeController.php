<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Node;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Landing page — shown at /.
     * Displays site description, stats, and news/events section.
     */
    public function index()
    {
        $totalNodes = Node::active()->count();
        $totalMarga = Node::active()->whereNotNull('marga')->distinct('marga')->count('marga');

        $articles = Article::published()
            ->latest()
            ->take(6)
            ->get();

        return Inertia::render('Home/Index', [
            'stats' => [
                'total_nodes' => $totalNodes,
                'total_marga' => $totalMarga,
            ],
            'articles' => $articles,
        ]);
    }
}
