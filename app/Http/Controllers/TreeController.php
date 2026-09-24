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
            ->with(['childrenRecursive', 'spouses', 'parent.spouses'])
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
            'node' => $node->load(['spouses', 'parent.spouses']),
            'ancestor_ids' => $ids,
        ]);
    }

    /**
     * Search nodes by name or marga.
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $limit = (int) $request->get('limit', 12);
        $gender = $request->get('gender', null);

        $results = Node::active()
            ->when($gender, fn($q) => $q->where('gender', $gender))
            ->when($query, function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'like', "%{$query}%")
                        ->orWhere('marga', 'like', "%{$query}%");
                });
            })
            ->with(['spouses' => fn($q) => $q->orderBy('id', 'asc')])
            ->orderBy('level')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'marga', 'gender', 'level']);

        return response()->json($results);
    }
}
