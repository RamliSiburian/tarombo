<?php

namespace App\Http\Controllers;

use App\Mail\RequestSubmitted;
use App\Models\Node;
use App\Models\NodeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class NodeRequestController extends Controller
{
    /**
     * Show public request form.
     */
    public function create()
    {
        $nodes = Node::active()
            ->where('gender', 'male')
            ->with(['spouses' => fn($q) => $q->orderBy('id', 'asc')])
            ->orderBy('level')
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name', 'marga', 'level']);

        return Inertia::render('Request/Create', [
            'initialNodes' => $nodes,
        ]);
    }

    /**
     * Store a new public request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_node_id'    => 'required|exists:nodes,id',
            'mother_spouse_id'  => 'required|exists:node_spouses,id',
            'name'              => 'required|string|max:255',
            'gender'            => 'required|in:male,female',
            'marga'             => 'nullable|string|max:100',
            'asal_daerah'       => 'nullable|string|max:255',
            'tahun_lahir'       => 'nullable|string|max:10',
            'tahun_wafat'       => 'nullable|string|max:10',
            'foto'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'deskripsi'         => 'nullable|string|max:1000',
            'anak_ke'           => 'nullable|integer|min:1',
            // Spouses array (only if male)
            'spouses'           => 'nullable|array',
            'spouses.*.name'    => 'required|string|max:255',
            'spouses.*.marga'   => 'nullable|string|max:100',
            'spouses.*.deskripsi' => 'nullable|string|max:1000',
            // Requester
            'requester_name'    => 'required|string|max:255',
            'requester_email'   => 'required|email|max:255',
        ]);

        // Verify that mother_spouse_id belongs to parent_node_id
        $mother = \App\Models\NodeSpouse::where('id', $validated['mother_spouse_id'])
            ->where('node_id', $validated['parent_node_id'])
            ->firstOrFail();

        // Handle photo upload
        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('requests', 'public');
        }

        // Determine sort order (birth order position)
        $parent = Node::find($validated['parent_node_id']);
        $level  = $parent ? $parent->level + 1 : 0;

        if (!empty($validated['anak_ke'])) {
            $sortOrder = (int) $validated['anak_ke'];
        } else {
            $existingSiblings = Node::where('parent_id', $validated['parent_node_id'])->count();
            $sortOrder = $existingSiblings + 1;
        }

        $motherNote = "Ibu Kandung: {$mother->name}" . ($mother->marga ? " (Marga {$mother->marga})" : "");
        $fullDeskripsi = !empty($validated['deskripsi']) 
            ? "{$motherNote}\n{$validated['deskripsi']}" 
            : $motherNote;

        $pendingNode = Node::create([
            'parent_id'   => $validated['parent_node_id'],
            'name'        => $validated['name'],
            'gender'      => $validated['gender'],
            'marga'       => $validated['marga'],
            'asal_daerah' => $validated['asal_daerah'] ?? null,
            'tahun_lahir' => $validated['tahun_lahir'] ?? null,
            'tahun_wafat' => $validated['tahun_wafat'] ?? null,
            'foto'        => $validated['foto'] ?? null,
            'deskripsi'   => $fullDeskripsi,
            'status'      => 'pending',
            'level'       => $level,
            'sort_order'  => $sortOrder,
        ]);

        $spouses = $validated['spouses'] ?? [];

        // Save multiple spouses if male
        if ($validated['gender'] === 'male' && !empty($spouses)) {
            foreach ($spouses as $spouse) {
                if (!empty($spouse['name'])) {
                    $pendingNode->spouses()->create([
                        'name'      => $spouse['name'],
                        'marga'     => $spouse['marga'] ?? null,
                        'deskripsi' => $spouse['deskripsi'] ?? null,
                    ]);
                }
            }
        }

        $firstSpouse = $spouses[0] ?? null;
        $requestData = [
            'parent_node_id'   => $validated['parent_node_id'],
            'node_id'          => $pendingNode->id,
            'name'             => $validated['name'],
            'gender'           => $validated['gender'],
            'marga'            => $validated['marga'],
            'asal_daerah'      => $validated['asal_daerah'] ?? null,
            'tahun_lahir'      => $validated['tahun_lahir'] ?? null,
            'tahun_wafat'      => $validated['tahun_wafat'] ?? null,
            'foto'             => $validated['foto'] ?? null,
            'deskripsi'        => $fullDeskripsi,
            'anak_ke'          => $validated['anak_ke'] ?? null,
            'sort_order'       => $sortOrder,
            'spouse_name'      => $firstSpouse['name'] ?? null,
            'spouse_marga'     => $firstSpouse['marga'] ?? null,
            'spouse_deskripsi' => $firstSpouse['deskripsi'] ?? null,
            'requester_name'   => $validated['requester_name'],
            'requester_email'  => $validated['requester_email'],
            'status'           => 'pending',
        ];

        $nodeRequest = NodeRequest::create($requestData);

        // Send confirmation email
        try {
            Mail::to($validated['requester_email'])->send(new RequestSubmitted($nodeRequest));
        } catch (\Exception $e) {
            \Log::warning('Failed to send request submitted email: ' . $e->getMessage());
        }

        return redirect()->route('tree.index')->with('success', 'Permintaan Anda telah dikirim! Kami akan segera meninjau dan menghubungi Anda via email.');
    }
}
