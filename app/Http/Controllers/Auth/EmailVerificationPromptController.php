<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        if ($request->user()->hasVerifiedEmail()) {
            $targetRoute = $request->user()->isAdmin() ? 'admin.dashboard' : 'member.dashboard';
            return redirect()->intended(route($targetRoute, absolute: false));
        }

        return Inertia::render('Auth/VerifyEmail', ['status' => session('status')]);
    }
}
