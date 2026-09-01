<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\NodeSpouse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NodeController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'active');

        $nodes = Node::with(['parent', 'spouses'])
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($request->search, fn($q) => $q->where(function ($sub) use ($request) {
                $sub->where('name', 'like', "%{$request->search}%")
                    ->orWhere('marga', 'like', "%{$request->search}%");
            }))
            ->orderBy('level')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Nodes/Index', [
            'nodes'   => $nodes,
            'filters' => [
                'search' => $request->search,
                'status' => $status,
            ],
        ]);
    }

    public function create()
    {
        $parents = Node::active()
            ->where('gender', 'male')
            ->orderBy('level')
            ->orderBy('name')
            ->get(['id', 'name', 'marga', 'level']);

        return Inertia::render('Admin/Nodes/Form', [
            'parents' => $parents,
            'node'    => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id'   => 'nullable|exists:nodes,id',
            'name'        => 'required|string|max:255',
            'gender'      => 'required|in:male,female',
            'marga'       => 'nullable|string|max:100',
            'asal_daerah' => 'nullable|string|max:255',
            'tahun_lahir' => 'nullable|string|max:10',
            'tahun_wafat' => 'nullable|string|max:10',
            'foto'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'deskripsi'   => 'nullable|string|max:1000',
            'status'      => 'required|in:active,pending',
            'sort_order'  => 'nullable|integer|min:1',
            // Spouses
            'spouses'         => 'nullable|array',
            'spouses.*.name'  => 'required|string|max:255',
            'spouses.*.marga' => 'nullable|string|max:100',
            'spouses.*.deskripsi' => 'nullable|string|max:1000',
        ]);

        // Calculate level & sort order
        $level = 0;
        if (!empty($validated['parent_id'])) {
            $parent = Node::find($validated['parent_id']);
            $level  = $parent ? $parent->level + 1 : 0;
        }
        $validated['level'] = $level;

        if (empty($validated['sort_order'])) {
            $siblingCount = Node::where('parent_id', $validated['parent_id'])->count();
            $validated['sort_order'] = $siblingCount + 1;
        }

        // Handle photo
        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('nodes', 'public');
        }

        $spouses = $validated['spouses'] ?? [];
        unset($validated['spouses']);

        $node = Node::create($validated);

        // Create spouses if male
        if ($node->gender === 'male' && !empty($spouses)) {
            foreach ($spouses as $spouse) {
                $node->spouses()->create($spouse);
            }
        }

        return redirect()->route('admin.nodes.index')->with('success', 'Node berhasil ditambahkan.');
    }

    public function edit(Node $node)
    {
        $parents = Node::active()
            ->where('gender', 'male')
            ->where('id', '!=', $node->id)
            ->orderBy('level')
            ->orderBy('name')
            ->get(['id', 'name', 'marga', 'level']);

        return Inertia::render('Admin/Nodes/Form', [
            'parents' => $parents,
            'node'    => $node->load('spouses'),
        ]);
    }

    public function update(Request $request, Node $node)
    {
        $validated = $request->validate([
            'parent_id'   => 'nullable|exists:nodes,id',
            'name'        => 'required|string|max:255',
            'gender'      => 'required|in:male,female',
            'marga'       => 'nullable|string|max:100',
            'asal_daerah' => 'nullable|string|max:255',
            'tahun_lahir' => 'nullable|string|max:10',
            'tahun_wafat' => 'nullable|string|max:10',
            'foto'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'deskripsi'   => 'nullable|string|max:1000',
            'status'      => 'required|in:active,pending',
            'sort_order'  => 'nullable|integer|min:1',
            'spouses'     => 'nullable|array',
            'spouses.*.name'      => 'required|string|max:255',
            'spouses.*.marga'     => 'nullable|string|max:100',
            'spouses.*.deskripsi' => 'nullable|string|max:1000',
        ]);

        // Parent ID is fixed for existing node
        $validated['parent_id'] = $node->parent_id;

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('nodes', 'public');
        }

        $spouses = $validated['spouses'] ?? null;
        unset($validated['spouses']);

        $node->update($validated);

        // Sync spouses
        if ($node->gender === 'male' && $spouses !== null) {
            $node->spouses()->delete();
            foreach ($spouses as $spouse) {
                $node->spouses()->create($spouse);
            }
        }

        return redirect()->route('admin.nodes.index')->with('success', 'Node berhasil diperbarui.');
    }

    public function destroy(Node $node)
    {
        $node->delete();
        return redirect()->route('admin.nodes.index')->with('success', 'Node berhasil dihapus.');
    }
}
