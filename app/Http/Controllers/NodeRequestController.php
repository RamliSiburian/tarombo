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
            ->orderBy('level')
            ->orderBy('name')
            ->get(['id', 'name', 'marga', 'level']);

        return Inertia::render('Request/Create', [
            'nodes' => $nodes,
        ]);
    }

    /**
     * Store a new public request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_node_id'  => 'required|exists:nodes,id',
            'name'            => 'required|string|max:255',
            'gender'          => 'required|in:male,female',
            'marga'           => 'nullable|string|max:100',
            'asal_daerah'     => 'nullable|string|max:255',
            'tahun_lahir'     => 'nullable|string|max:10',
            'tahun_wafat'     => 'nullable|string|max:10',
            'foto'            => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'deskripsi'       => 'nullable|string|max:1000',
            // Spouse (only if male)
            'spouse_name'     => 'nullable|string|max:255',
            'spouse_marga'    => 'nullable|string|max:100',
            'spouse_deskripsi' => 'nullable|string|max:1000',
            // Requester
            'requester_name'  => 'required|string|max:255',
            'requester_email' => 'required|email|max:255',
        ]);

        // Handle photo upload
        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('requests', 'public');
        }

        $nodeRequest = NodeRequest::create($validated);

        // Send confirmation email
        try {
            Mail::to($validated['requester_email'])->send(new RequestSubmitted($nodeRequest));
        } catch (\Exception $e) {
            // Log but don't fail the request
            \Log::warning('Failed to send request submitted email: ' . $e->getMessage());
        }

        return redirect()->route('tree.index')->with('success', 'Permintaan Anda telah dikirim! Kami akan segera meninjau dan menghubungi Anda via email.');
    }
}
