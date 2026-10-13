<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\NodeRequest;
use App\Models\User;
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
            'rejected_requests' => NodeRequest::where('status', 'rejected')->count(),
            'total_members'     => User::where('role', 'member')->count(),
            'pending_members'   => User::where('role', 'member')->where('membership_status', 'pending')->count(),
            'approved_members'  => User::where('role', 'member')->where('membership_status', 'approved')->count(),
        ];

        $recentRequests = NodeRequest::with('parentNode')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentMembers = User::where('role', 'member')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats'          => $stats,
            'recentRequests' => $recentRequests,
            'recentMembers'  => $recentMembers,
        ]);
    }
}
