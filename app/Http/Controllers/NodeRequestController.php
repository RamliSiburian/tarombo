<?php

namespace App\Http\Controllers;

use App\Mail\RequestSubmitted;
use App\Models\Node;
use App\Models\NodeRequest;
use App\Models\NodeSpouse;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
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
        $isSelf = filter_var($request->input('is_self'), FILTER_VALIDATE_BOOLEAN);

        // Pre-process spouses: remove empty entries where name is not filled
        if ($request->has('spouses') && is_array($request->input('spouses'))) {
            $filteredSpouses = array_values(array_filter($request->input('spouses'), function ($item) {
                return is_array($item) && !empty(trim($item['name'] ?? ''));
            }));
            $request->merge(['spouses' => $filteredSpouses]);
        }

        // Merge normalized boolean is_self
        $request->merge(['is_self' => $isSelf]);

        $rules = [
            'parent_node_id'      => 'required|exists:nodes,id',
            'mother_spouse_id'    => 'required|exists:node_spouses,id',
            'name'                => 'required|string|max:255',
            'gender'              => 'required|in:male,female',
            'marga'               => 'nullable|string|max:100',
            'asal_daerah'         => 'nullable|string|max:255',
            'tahun_lahir'         => 'nullable|string|max:10',
            'tahun_wafat'         => 'nullable|string|max:10',
            'foto'                => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'deskripsi'           => 'nullable|string|max:1000',
            'anak_ke'             => 'nullable|integer|min:1',
            'is_self'             => 'nullable',
            // Spouses array (only if male and has items)
            'spouses'             => 'nullable|array',
            'spouses.*.name'      => 'required|string|max:255',
            'spouses.*.marga'     => 'nullable|string|max:100',
            'spouses.*.tahun_lahir' => 'nullable|string|max:10',
            'spouses.*.tahun_wafat' => 'nullable|string|max:10',
            'spouses.*.deskripsi' => 'nullable|string|max:1000',
        ];

        if ($isSelf) {
            $rules['email']    = 'required|string|lowercase|email|max:255|unique:' . User::class;
            $rules['phone']    = 'nullable|string|max:25';
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        } else {
            $rules['requester_name']  = 'required|string|max:255';
            $rules['requester_email'] = 'required|email|max:255';
        }

        $validated = $request->validate($rules);

        // Verify that mother_spouse_id belongs to parent_node_id
        $mother = NodeSpouse::where('id', $validated['mother_spouse_id'])
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
            'parent_id'        => $validated['parent_node_id'],
            'mother_spouse_id' => $validated['mother_spouse_id'],
            'name'             => $validated['name'],
            'gender'           => $validated['gender'],
            'marga'            => $validated['marga'] ?? null,
            'asal_daerah'      => $validated['asal_daerah'] ?? null,
            'tahun_lahir'      => $validated['tahun_lahir'] ?? null,
            'tahun_wafat'      => $validated['tahun_wafat'] ?? null,
            'foto'             => $validated['foto'] ?? null,
            'deskripsi'        => $fullDeskripsi,
            'status'           => 'pending',
            'level'            => $level,
            'sort_order'       => $sortOrder,
        ]);

        $spouses = $validated['spouses'] ?? [];

        // Save multiple spouses if male
        if ($validated['gender'] === 'male' && !empty($spouses)) {
            foreach ($spouses as $spouse) {
                if (!empty($spouse['name'])) {
                    $pendingNode->spouses()->create([
                        'name'        => $spouse['name'],
                        'marga'       => $spouse['marga'] ?? null,
                        'tahun_lahir' => $spouse['tahun_lahir'] ?? null,
                        'tahun_wafat' => $spouse['tahun_wafat'] ?? null,
                        'deskripsi'   => $spouse['deskripsi'] ?? null,
                    ]);
                }
            }
        }

        // Create User account if self-registration
        $userId = null;
        $user = null;
        if ($isSelf) {
            $user = User::create([
                'name'              => $validated['name'],
                'email'             => $validated['email'],
                'phone'             => $validated['phone'] ?? null,
                'marga'             => $validated['marga'] ?? null,
                'password'          => Hash::make($validated['password']),
                'role'              => 'member',
                'membership_status' => 'pending',
                'node_id'           => null,
            ]);
            $userId = $user->id;
            $requesterName = $validated['name'];
            $requesterEmail = $validated['email'];
            $requesterPhone = $validated['phone'] ?? null;
        } else {
            $requesterName = $validated['requester_name'];
            $requesterEmail = $validated['requester_email'];
            $requesterPhone = null;
        }

        $firstSpouse = $spouses[0] ?? null;
        $requestData = [
            'parent_node_id'     => $validated['parent_node_id'],
            'mother_spouse_id'   => $validated['mother_spouse_id'],
            'node_id'            => $pendingNode->id,
            'name'               => $validated['name'],
            'gender'             => $validated['gender'],
            'marga'              => $validated['marga'] ?? null,
            'asal_daerah'        => $validated['asal_daerah'] ?? null,
            'tahun_lahir'        => $validated['tahun_lahir'] ?? null,
            'tahun_wafat'        => $validated['tahun_wafat'] ?? null,
            'foto'               => $validated['foto'] ?? null,
            'deskripsi'          => $fullDeskripsi,
            'anak_ke'            => $validated['anak_ke'] ?? null,
            'sort_order'         => $sortOrder,
            'spouse_name'        => $firstSpouse['name'] ?? null,
            'spouse_marga'       => $firstSpouse['marga'] ?? null,
            'spouse_tahun_lahir' => $firstSpouse['tahun_lahir'] ?? null,
            'spouse_tahun_wafat' => $firstSpouse['tahun_wafat'] ?? null,
            'spouse_deskripsi'   => $firstSpouse['deskripsi'] ?? null,
            'requester_name'     => $requesterName,
            'requester_email'    => $requesterEmail,
            'requester_phone'    => $requesterPhone,
            'is_self'            => $isSelf,
            'user_id'            => $userId,
            'status'             => 'pending',
        ];

        $nodeRequest = NodeRequest::create($requestData);

        // If self registration, trigger verification email and login
        if ($isSelf && $user) {
            try {
                event(new Registered($user));
            } catch (\Throwable $e) {
                \Log::warning('Failed to dispatch Registered event email: ' . $e->getMessage());
            }
            Auth::login($user);
            return redirect()->route('verification.notice');
        }

        // Send confirmation email for other's request
        try {
            Mail::to($requesterEmail)->send(new RequestSubmitted($nodeRequest));
        } catch (\Exception $e) {
            \Log::warning('Failed to send request submitted email: ' . $e->getMessage());
        }

        return redirect()->route('tree.index')->with('success', 'Permintaan Anda telah dikirim! Kami akan segera meninjau dan menghubungi Anda via email.');
    }
}
