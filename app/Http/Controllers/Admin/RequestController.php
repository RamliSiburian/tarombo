<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\RequestAccepted;
use App\Mail\RequestRejected;
use App\Models\Node;
use App\Models\NodeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        $requests = NodeRequest::with(['parentNode', 'reviewer'])
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Requests/Index', [
            'requests'       => $requests,
            'filters'        => ['status' => $status],
            'pendingCount'   => NodeRequest::pending()->count(),
        ]);
    }

    public function accept(Request $request, NodeRequest $nodeRequest)
    {
        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:1',
        ]);

        if ($nodeRequest->node_id && ($node = Node::find($nodeRequest->node_id))) {
            $updateData = ['status' => 'active'];
            if (!empty($validated['sort_order'])) {
                $updateData['sort_order'] = (int) $validated['sort_order'];
            }
            $node->update($updateData);
        } else {
            // Fallback: Create the node if not created yet
            $parent = Node::find($nodeRequest->parent_node_id);
            $level  = $parent ? $parent->level + 1 : 0;
            $sortOrder = !empty($validated['sort_order']) ? (int) $validated['sort_order'] : ($nodeRequest->sort_order ?: 1);

            $node = Node::create([
                'parent_id'   => $nodeRequest->parent_node_id,
                'name'        => $nodeRequest->name,
                'gender'      => $nodeRequest->gender,
                'marga'       => $nodeRequest->marga,
                'asal_daerah' => $nodeRequest->asal_daerah,
                'tahun_lahir' => $nodeRequest->tahun_lahir,
                'tahun_wafat' => $nodeRequest->tahun_wafat,
                'foto'        => $nodeRequest->foto,
                'deskripsi'   => $nodeRequest->deskripsi,
                'status'      => 'active',
                'level'       => $level,
                'sort_order'  => $sortOrder,
            ]);

            if ($nodeRequest->gender === 'male' && $nodeRequest->spouse_name) {
                $node->spouses()->create([
                    'name'       => $nodeRequest->spouse_name,
                    'marga'      => $nodeRequest->spouse_marga,
                    'foto'       => $nodeRequest->spouse_foto,
                    'deskripsi'  => $nodeRequest->spouse_deskripsi,
                ]);
            }

            $nodeRequest->update(['node_id' => $node->id]);
        }

        // Update request status
        $nodeRequest->update([
            'status'      => 'accepted',
            'admin_note'  => $request->admin_note,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        // Send acceptance email
        try {
            Mail::to($nodeRequest->requester_email)->send(new RequestAccepted($nodeRequest));
        } catch (\Exception $e) {
            \Log::warning('Failed to send acceptance email: ' . $e->getMessage());
        }

        return back()->with('success', "Request dari {$nodeRequest->requester_name} telah disetujui.");
    }

    public function reject(Request $request, NodeRequest $nodeRequest)
    {
        $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        // Store note before updating
        $requesterEmail = $nodeRequest->requester_email;
        $requesterName  = $nodeRequest->requester_name;

        // If a pending node was created for this request, delete it from the tree
        if ($nodeRequest->node_id && ($node = Node::find($nodeRequest->node_id))) {
            $node->delete();
        }

        $nodeRequest->update([
            'status'      => 'rejected',
            'admin_note'  => $request->admin_note,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        // Send rejection email
        try {
            Mail::to($requesterEmail)->send(new RequestRejected($nodeRequest));
        } catch (\Exception $e) {
            \Log::warning('Failed to send rejection email: ' . $e->getMessage());
        }

        return back()->with('success', "Request dari {$requesterName} telah ditolak.");
    }

    public function history(Request $request)
    {
        $requests = NodeRequest::with(['parentNode', 'reviewer'])
            ->whereIn('status', ['accepted', 'rejected'])
            ->orderBy('reviewed_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Requests/History', [
            'requests' => $requests,
        ]);
    }

    public function destroy(NodeRequest $nodeRequest)
    {
        $nodeRequest->delete();
        return back()->with('success', 'Request berhasil dihapus.');
    }
}
