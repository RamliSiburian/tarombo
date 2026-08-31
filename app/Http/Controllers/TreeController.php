<?php

namespace App\Http\Controllers;

use App\Models\Node;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TreeController extends Controller
{
    /**
     * Public tree page - load full active tree.
     */
    public function index()
    {
        $root = Node::active()
            ->root()
            ->with(['childrenRecursive', 'spouses'])
            ->first();

        $totalNodes = Node::active()->count();
        $totalMarga = Node::active()->whereNotNull('marga')->distinct('marga')->count('marga');

        return Inertia::render('Tree/Index', [
            'tree' => $root,
            'stats' => [
                'total_nodes' => $totalNodes,
                'total_marga' => $totalMarga,
            ],
        ]);
    }

    /**
     * Get ancestry path IDs for a given node (for highlight).
     */
    public function ancestors(Node $node)
    {
        $ids = $node->getAncestorIds();
        $ids[] = $node->id; // include self

        return response()->json([
            'node' => $node->load('spouses'),
            'ancestor_ids' => $ids,
        ]);
    }

    /**
     * Search nodes by name.
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $results = Node::active()
            ->where('name', 'like', "%{$query}%")
            ->orWhere('marga', 'like', "%{$query}%")
            ->limit(20)
            ->get(['id', 'name', 'marga', 'gender', 'level']);

        return response()->json($results);
    }
}
