<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class MemberController extends Controller
{
    public function dashboard()
    {
        return Inertia::render('Member/Dashboard', [
            'user' => auth()->user(),
        ]);
    }
}
