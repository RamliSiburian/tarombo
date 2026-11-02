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
            ->with([
                'children' => fn($q) => $q->select('id', 'name', 'marga', 'parent_id', 'level'),
                'spouses'  => fn($q) => $q->orderBy('id', 'asc'),
            ])
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
            'parent_id'        => 'nullable|exists:nodes,id',
            'mother_spouse_id' => 'nullable|exists:node_spouses,id',
            'name'             => 'required|string|max:255',
            'gender'           => 'required|in:male,female',
            'marga'            => 'nullable|string|max:100',
            'asal_daerah'      => 'nullable|string|max:255',
            'tahun_lahir'      => 'nullable|string|max:10',
            'tahun_wafat'      => 'nullable|string|max:10',
            'foto'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'deskripsi'        => 'nullable|string|max:1000',
            'status'           => 'required|in:active,pending',
            'sort_order'       => 'nullable|integer|min:1',
            'reparent_children'   => 'nullable|array',
            'reparent_children.*' => 'exists:nodes,id',
            // Spouses
            'spouses'             => 'nullable|array',
            'spouses.*.name'      => 'required|string|max:255',
            'spouses.*.marga'     => 'nullable|string|max:100',
            'spouses.*.tahun_lahir' => 'nullable|string|max:10',
            'spouses.*.tahun_wafat' => 'nullable|string|max:10',
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

        // Handle mother_spouse_id and sync with deskripsi
        if (!empty($validated['parent_id']) && !empty($validated['mother_spouse_id'])) {
            $mother = NodeSpouse::where('id', $validated['mother_spouse_id'])
                ->where('node_id', $validated['parent_id'])
                ->first();
            if ($mother) {
                $validated['mother_spouse_id'] = $mother->id;
                $motherLine = "Ibu Kandung: {$mother->name}" . ($mother->marga ? " (Marga {$mother->marga})" : "");
                $cleanDeskripsi = preg_replace('/Ibu Kandung:\s*[^\n\r]+[\r\n]*/i', '', $validated['deskripsi'] ?? '');
                $cleanDeskripsi = trim($cleanDeskripsi);
                $validated['deskripsi'] = !empty($cleanDeskripsi) ? "{$motherLine}\n{$cleanDeskripsi}" : $motherLine;
            } else {
                $validated['mother_spouse_id'] = null;
            }
        } else {
            $validated['mother_spouse_id'] = null;
            if (isset($validated['deskripsi'])) {
                $cleanDeskripsi = preg_replace('/Ibu Kandung:\s*[^\n\r]+[\r\n]*/i', '', $validated['deskripsi'] ?? '');
                $validated['deskripsi'] = trim($cleanDeskripsi) ?: null;
            }
        }

        // Handle photo
        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('nodes', 'public');
        }

        $spouses = $validated['spouses'] ?? [];
        $reparentChildren = $validated['reparent_children'] ?? [];
        unset($validated['spouses'], $validated['reparent_children']);

        $node = Node::create($validated);

        // Reparent selected children to this new node
        if (!empty($reparentChildren)) {
            Node::whereIn('id', $reparentChildren)->update([
                'parent_id' => $node->id,
                'level' => $node->level + 1,
            ]);

            // Update all descendants levels recursively for reparented nodes
            foreach ($reparentChildren as $childId) {
                $child = Node::find($childId);
                $child?->updateDescendantLevels();
            }
        }

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
            ->with([
                'children' => fn($q) => $q->select('id', 'name', 'marga', 'parent_id', 'level'),
                'spouses'  => fn($q) => $q->orderBy('id', 'asc'),
            ])
            ->orderBy('level')
            ->orderBy('name')
            ->get(['id', 'name', 'marga', 'level']);

        return Inertia::render('Admin/Nodes/Form', [
            'parents' => $parents,
            'node'    => $node->load(['spouses', 'mother']),
        ]);
    }

    public function update(Request $request, Node $node)
    {
        $validated = $request->validate([
            'parent_id'        => 'nullable|exists:nodes,id',
            'mother_spouse_id' => 'nullable|exists:node_spouses,id',
            'name'             => 'required|string|max:255',
            'gender'           => 'required|in:male,female',
            'marga'            => 'nullable|string|max:100',
            'asal_daerah'      => 'nullable|string|max:255',
            'tahun_lahir'      => 'nullable|string|max:10',
            'tahun_wafat'      => 'nullable|string|max:10',
            'foto'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'deskripsi'        => 'nullable|string|max:1000',
            'status'           => 'required|in:active,pending',
            'sort_order'       => 'nullable|integer|min:1',
            'spouses'          => 'nullable|array',
            'spouses.*.name'      => 'required|string|max:255',
            'spouses.*.marga'     => 'nullable|string|max:100',
            'spouses.*.tahun_lahir' => 'nullable|string|max:10',
            'spouses.*.tahun_wafat' => 'nullable|string|max:10',
            'spouses.*.deskripsi' => 'nullable|string|max:1000',
        ]);

        if (!empty($validated['parent_id']) && $validated['parent_id'] != $node->parent_id) {
            $parent = Node::find($validated['parent_id']);
            $validated['level'] = $parent ? $parent->level + 1 : 0;
        }

        // Handle mother_spouse_id and sync with deskripsi
        if (!empty($validated['parent_id']) && !empty($validated['mother_spouse_id'])) {
            $mother = NodeSpouse::where('id', $validated['mother_spouse_id'])
                ->where('node_id', $validated['parent_id'])
                ->first();
            if ($mother) {
                $validated['mother_spouse_id'] = $mother->id;
                $motherLine = "Ibu Kandung: {$mother->name}" . ($mother->marga ? " (Marga {$mother->marga})" : "");
                $cleanDeskripsi = preg_replace('/Ibu Kandung:\s*[^\n\r]+[\r\n]*/i', '', $validated['deskripsi'] ?? '');
                $cleanDeskripsi = trim($cleanDeskripsi);
                $validated['deskripsi'] = !empty($cleanDeskripsi) ? "{$motherLine}\n{$cleanDeskripsi}" : $motherLine;
            } else {
                $validated['mother_spouse_id'] = null;
            }
        } else {
            $validated['mother_spouse_id'] = null;
            if (isset($validated['deskripsi'])) {
                $cleanDeskripsi = preg_replace('/Ibu Kandung:\s*[^\n\r]+[\r\n]*/i', '', $validated['deskripsi'] ?? '');
                $validated['deskripsi'] = trim($cleanDeskripsi) ?: null;
            }
        }

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('nodes', 'public');
        }

        $spouses = $validated['spouses'] ?? null;
        unset($validated['spouses']);

        $node->update($validated);

        // Recalculate descendant levels if level changed
        $node->updateDescendantLevels();

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
