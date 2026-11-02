<?php

namespace App\Http\Controllers;

use App\Models\Node;
use App\Models\NodeRequest;
use Inertia\Inertia;

class MemberController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        $node = null;
        $ancestors = [];
        $motherName = null;

        if ($user->node_id) {
            $node = Node::with([
                'spouses' => fn($q) => $q->orderBy('id', 'asc'),
                'mother',
                'parent.spouses',
                'activeChildren.spouses',
            ])->find($user->node_id);

            if ($node) {
                $ancestors = $node->getAncestors();

                $formatBoru = function(?string $marga) {
                    if (!$marga) return '';
                    $clean = trim($marga);
                    if (preg_match('/^br[\.\s]/i', $clean)) {
                        return ' ' . $clean;
                    }
                    return ' br. ' . $clean;
                };

                // Check mother relationship first, then deskripsi, then parent's first spouse
                if ($node->mother) {
                    $motherName = $node->mother->name . $formatBoru($node->mother->marga);
                } elseif (!empty($node->deskripsi) && preg_match('/Ibu Kandung:\s*([^\n]+)/i', $node->deskripsi, $matches)) {
                    $motherName = trim($matches[1]);
                } elseif ($node->parent && $node->parent->spouses->isNotEmpty()) {
                    $mother = $node->parent->spouses->first();
                    $motherName = $mother->name . $formatBoru($mother->marga);
                }
            }
        }

        $pendingRequest = NodeRequest::with('parentNode')
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        return Inertia::render('Member/Dashboard', [
            'user'           => $user,
            'node'           => $node,
            'ancestors'      => $ancestors,
            'motherName'     => $motherName,
            'pendingRequest' => $pendingRequest,
        ]);
    }
}
