<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Node;
use App\Models\NodeRequest;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_nodes'       => Node::active()->count(),
            'total_marga'       => Node::active()->whereNotNull('marga')->distinct('marga')->count('marga'),
            'pending_requests'  => NodeRequest::pending()->count(),
            'total_requests'    => NodeRequest::count(),
            'accepted_requests' => NodeRequest::where('status', 'accepted')->count(),
            'total_articles'    => Article::count(),
            'conflict_nodes'    => $this->countConflictNodes(),
        ];

        $recentRequests = NodeRequest::with('parentNode')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentArticles = Article::with('author')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Nodes with sibling order conflicts (unconfirmed duplicate sort_order)
        $conflictNodes = Node::active()
            ->whereNotNull('parent_id')
            ->where('order_confirmed', false)
            ->with('parent:id,name')
            ->get(['id', 'name', 'marga', 'parent_id', 'sort_order'])
            ->filter(function ($node) {
                return Node::where('parent_id', $node->parent_id)
                    ->where('id', '!=', $node->id)
                    ->where('sort_order', $node->sort_order)
                    ->where('status', 'active')
                    ->exists();
            })
            ->values();

        return Inertia::render('Admin/Dashboard', [
            'stats'          => $stats,
            'recentRequests' => $recentRequests,
            'recentArticles' => $recentArticles,
            'conflictNodes'  => $conflictNodes,
        ]);
    }

    private function countConflictNodes(): int
    {
        return Node::active()
            ->whereNotNull('parent_id')
            ->where('order_confirmed', false)
            ->get(['id', 'parent_id', 'sort_order'])
            ->filter(function ($node) {
                return Node::where('parent_id', $node->parent_id)
                    ->where('id', '!=', $node->id)
                    ->where('sort_order', $node->sort_order)
                    ->where('status', 'active')
                    ->exists();
            })
            ->count();
    }
}
