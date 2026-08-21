<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Settings/Profile', [
            'user' => $request->user()->only([
                'id', 'name', 'email', 'phone', 'city', 'country_code',
                'locale', 'timezone', 'website', 'social_links',
                'show_email', 'show_phone', 'show_website', 'show_socials', 'show_address',
                'email_verified_at',
            ]),
        ]);
    }

    /**
     * Deleting the account is a sensitive operation — the frontend routes
     * it through ConfirmPasswordModal first (spec §89), and this action
     * still re-validates the password itself rather than trusting that
     * the modal ran (spec §100 — frontend never makes authorization
     * decisions on the backend's behalf).
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password:web'],
        ]);

        $user = $request->user();

        auth()->guard('web')->logout();

        $user->delete(); // soft delete — spec keeps an audit trail (§49)

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
