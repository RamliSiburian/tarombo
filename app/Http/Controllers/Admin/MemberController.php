<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    /**
     * Display a listing of members.
     */
    public function index(Request $request): Response
    {
        $query = User::where('role', 'member');

        // Filter status
        if ($request->filled('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
            $query->where('membership_status', $request->status);
        }

        // Search query
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('marga', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $members = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total'    => User::where('role', 'member')->count(),
            'pending'  => User::where('role', 'member')->where('membership_status', 'pending')->count(),
            'approved' => User::where('role', 'member')->where('membership_status', 'approved')->count(),
            'rejected' => User::where('role', 'member')->where('membership_status', 'rejected')->count(),
            'verified' => User::where('role', 'member')->whereNotNull('email_verified_at')->count(),
        ];

        return Inertia::render('Admin/Members/Index', [
            'members' => $members,
            'filters' => $request->only(['search', 'status']),
            'stats'   => $stats,
        ]);
    }

    /**
     * Approve a member's registration.
     */
    public function approve(User $user): RedirectResponse
    {
        if ($user->role === 'admin') {
            return back()->with('error', 'User admin tidak dapat dimodifikasi statusnya.');
        }

        $user->update(['membership_status' => 'approved']);

        return back()->with('success', "Keanggotaan {$user->name} berhasil disetujui.");
    }

    /**
     * Reject a member's registration.
     */
    public function reject(User $user): RedirectResponse
    {
        if ($user->role === 'admin') {
            return back()->with('error', 'User admin tidak dapat dimodifikasi statusnya.');
        }

        $user->update(['membership_status' => 'rejected']);

        return back()->with('success', "Pendaftaran keanggotaan {$user->name} telah ditolak.");
    }

    /**
     * Remove the specified member.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->role === 'admin') {
            return back()->with('error', 'Akun admin tidak dapat dihapus.');
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "Anggota {$name} berhasil dihapus.");
    }
}
